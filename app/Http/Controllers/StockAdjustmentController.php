<?php

namespace App\Http\Controllers;

use App\Models\AdjustmentType;
use App\Models\DocumentSequence;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockAdjustment::with(['type', 'creator', 'poster'])->withCount('items');

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($type = $request->integer('adjustment_type_id')) {
            $query->where('adjustment_type_id', $type);
        }
        if ($s = $request->string('search')->trim()->toString()) {
            $query->where('adj_number', 'like', "%{$s}%");
        }
        if ($request->filled('from_date')) {
            $query->whereDate('adjustment_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('adjustment_date', '<=', $request->input('to_date'));
        }

        $adjustments = $query->latest('adjustment_date')->latest('id')->paginate(15)->withQueryString();
        $types = AdjustmentType::where('status', 'active')->orderBy('type_name')->get();

        return view('stock-adjustments.index', compact('adjustments', 'types'));
    }

    public function create(): View
    {
        return view('stock-adjustments.form', [
            'adjustment' => new StockAdjustment([
                'adjustment_date' => now()->toDateString(),
            ]),
            'lines' => [],
            'types' => AdjustmentType::where('status', 'active')->orderBy('type_name')->get(),
            'items' => Item::with('uom')->where('status', 'active')->orderBy('item_code')->get(),
            'nextAdjNumber' => $this->peekSequence('adj'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $postNow = $request->input('action') === 'post';

        if ($postNow && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can post a stock adjustment.');
        }

        $adjustment = DB::transaction(function () use ($data, $request, $postNow) {
            $adjustment = StockAdjustment::create([
                'adj_number'         => DocumentSequence::next('adj'),
                'adjustment_type_id' => $data['adjustment_type_id'],
                'adjustment_date'    => $data['adjustment_date'],
                'notes'              => $data['notes'] ?? null,
                'status'             => 'draft',
                'created_by'         => $request->user()->id,
            ]);

            $this->saveLines($adjustment, $data['lines']);

            if ($postNow) {
                $this->applyPosting($adjustment->fresh(), $request->user()->id);
            }

            return $adjustment->fresh();
        });

        if ($postNow) {
            return redirect()->route('stock-adjustments.show', $adjustment)
                ->with('flash', "Adjustment {$adjustment->adj_number} posted. Inventory updated.");
        }

        return redirect()->route('stock-adjustments.index')
            ->with('flash', 'Adjustment saved as draft. Post to apply inventory changes.');
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        $stockAdjustment->load(['type', 'items.item.uom', 'creator', 'poster', 'voider']);

        return view('stock-adjustments.show', ['adjustment' => $stockAdjustment]);
    }

    public function edit(StockAdjustment $stockAdjustment): View
    {
        if (! $stockAdjustment->isEditable()) {
            abort(403, 'Only draft adjustments can be edited.');
        }

        return view('stock-adjustments.form', [
            'adjustment' => $stockAdjustment,
            'lines' => $stockAdjustment->items()->with('item.uom')->get(),
            'types' => AdjustmentType::where('status', 'active')->orderBy('type_name')->get(),
            'items' => Item::with('uom')->where('status', 'active')->orderBy('item_code')->get(),
            'nextAdjNumber' => null,
        ]);
    }

    public function update(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        if (! $stockAdjustment->isEditable()) {
            abort(403, 'Only draft adjustments can be edited.');
        }

        $data = $this->validateData($request);
        $postNow = $request->input('action') === 'post';

        if ($postNow && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can post a stock adjustment.');
        }

        DB::transaction(function () use ($data, $stockAdjustment, $request, $postNow) {
            $stockAdjustment->update([
                'adjustment_type_id' => $data['adjustment_type_id'],
                'adjustment_date'    => $data['adjustment_date'],
                'notes'              => $data['notes'] ?? null,
            ]);
            $stockAdjustment->items()->delete();
            $this->saveLines($stockAdjustment, $data['lines']);

            if ($postNow) {
                $this->applyPosting($stockAdjustment->fresh(), $request->user()->id);
            }
        });

        $msg = $postNow
            ? "Adjustment {$stockAdjustment->adj_number} updated and posted. Inventory updated."
            : 'Adjustment updated.';

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('flash', $msg);
    }

    public function post(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        if (! $stockAdjustment->isPostable()) {
            abort(422, 'Only draft adjustments can be posted.');
        }

        DB::transaction(function () use ($stockAdjustment, $request) {
            $this->applyPosting($stockAdjustment, $request->user()->id);
        });

        return redirect()->route('stock-adjustments.show', $stockAdjustment)
            ->with('flash', 'Adjustment posted. Inventory updated.');
    }

    public function void(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        if (! $stockAdjustment->isVoidable()) {
            abort(422, 'Only posted adjustments can be voided.');
        }

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'void_reason.required' => 'A reason is required to void a posted adjustment.',
            'void_reason.min'      => 'Reason must be at least 5 characters.',
        ]);

        DB::transaction(function () use ($stockAdjustment, $data, $request) {
            // Reverse the inventory effect
            $stockAdjustment->load('items.item');
            foreach ($stockAdjustment->items as $line) {
                $item = Item::where('id', $line->item_id)->lockForUpdate()->firstOrFail();
                // Revert: set qty back to qty_before
                $item->qty_on_hand = (float) $line->qty_before;
                $item->save();
            }

            $stockAdjustment->update([
                'status'      => 'void',
                'void_reason' => $data['void_reason'],
                'voided_by'   => $request->user()->id,
                'voided_at'   => now(),
            ]);
        });

        return redirect()->route('stock-adjustments.show', $stockAdjustment)
            ->with('flash', "Adjustment {$stockAdjustment->adj_number} voided. Inventory reverted.");
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'adjustment_type_id'   => ['required', 'exists:adjustment_types,id'],
            'adjustment_date'      => ['required', 'date'],
            'notes'                => ['nullable', 'string'],
            'lines'                => ['required', 'array', 'min:1'],
            'lines.*.item_id'      => ['required', 'exists:items,id'],
            'lines.*.qty_adjusted' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes'        => ['nullable', 'string', 'max:255'],
        ]);

        // Reject duplicate items in the same adjustment
        $itemIds = array_column($data['lines'], 'item_id');
        if (count($itemIds) !== count(array_unique($itemIds))) {
            throw ValidationException::withMessages([
                'lines' => 'An item cannot appear more than once in the same adjustment.',
            ]);
        }

        return $data;
    }

    private function saveLines(StockAdjustment $adjustment, array $lines): void
    {
        $type = AdjustmentType::findOrFail($adjustment->adjustment_type_id);

        foreach ($lines as $line) {
            $item = Item::findOrFail($line['item_id']);
            $qtyBefore = (float) $item->qty_on_hand;
            $qtyAdj = (float) $line['qty_adjusted'];

            $qtyAfter = $this->computeQtyAfter($type, $qtyBefore, $qtyAdj);

            StockAdjustmentItem::create([
                'adj_id'       => $adjustment->id,
                'item_id'      => $item->id,
                'qty_before'   => $type->code === 'BBAL' ? 0 : $qtyBefore,
                'qty_adjusted' => $qtyAdj,
                'qty_after'    => $qtyAfter,
                'unit_cost'    => (float) ($line['unit_cost'] ?? $item->unit_cost),
                'notes'        => $line['notes'] ?? null,
            ]);
        }
    }

    /**
     * THE STOCK MUTATION OPERATION.
     * Caller wraps in a DB transaction.
     * Atomically:
     *   - re-reads each item's current qty under a row lock
     *   - recomputes qty_after based on the type direction (race-safe vs concurrent sales/receipts)
     *   - validates non-negative result
     *   - sets items.qty_on_hand = qty_after
     *   - persists qty_before / qty_after on the line for the audit trail
     *   - flips the adjustment to status=posted
     */
    private function applyPosting(StockAdjustment $adjustment, int $userId): void
    {
        $adjustment->load(['type', 'items.item']);
        $type = $adjustment->type;

        foreach ($adjustment->items as $line) {
            $item = Item::where('id', $line->item_id)->lockForUpdate()->firstOrFail();

            $currentQty = (float) $item->qty_on_hand;
            $qtyAdj = (float) $line->qty_adjusted;
            $qtyAfter = $this->computeQtyAfter($type, $currentQty, $qtyAdj);

            if ($qtyAfter < -0.001) {
                throw ValidationException::withMessages([
                    'stock' => "Cannot post {$adjustment->adj_number}: {$item->item_code} would go below zero (current: {$currentQty}, change: -{$qtyAdj}).",
                ]);
            }

            $item->qty_on_hand = round($qtyAfter, 2);
            // Beginning Balance: also seed unit_cost from the line if it differs
            if ($type->code === 'BBAL' && (float) $line->unit_cost > 0) {
                $item->unit_cost = (float) $line->unit_cost;
            }
            $item->save();

            $line->qty_before = $type->code === 'BBAL' ? 0 : $currentQty;
            $line->qty_after = round($qtyAfter, 2);
            $line->save();
        }

        $adjustment->update([
            'status'    => 'posted',
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);
    }

    /**
     * Compute qty_after based on adjustment type direction.
     * - BBAL (Beginning Balance): qty_after = qty_adjusted (absolute set, ignores current)
     * - IN: qty_after = current + qty_adjusted
     * - OUT: qty_after = current - qty_adjusted
     * - BOTH (other than BBAL): qty_after = current + qty_adjusted (treat as IN by default)
     */
    private function computeQtyAfter(AdjustmentType $type, float $qtyBefore, float $qtyAdjusted): float
    {
        if ($type->code === 'BBAL') {
            return $qtyAdjusted;
        }
        if ($type->direction === 'OUT') {
            return $qtyBefore - $qtyAdjusted;
        }

        return $qtyBefore + $qtyAdjusted;
    }

    private function peekSequence(string $key): string
    {
        $seq = DocumentSequence::where('seq_key', $key)->first();

        return $seq->prefix . str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
    }
}
