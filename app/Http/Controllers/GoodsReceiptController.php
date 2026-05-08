<?php

namespace App\Http\Controllers;

use App\Models\DocumentSequence;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $query = GoodsReceipt::with(['supplier', 'purchaseOrder'])->withCount('items');

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($po = $request->integer('po_id')) {
            $query->where('po_id', $po);
        }
        if ($s = $request->string('search')->trim()->toString()) {
            $query->where('grn_number', 'like', "%{$s}%");
        }
        if ($sup = $request->integer('supplier_id')) {
            $query->where('supplier_id', $sup);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('received_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('received_date', '<=', $request->input('to_date'));
        }

        $grns = $query->latest('received_date')->latest('id')->paginate(15)->withQueryString();
        $suppliers = \App\Models\Supplier::where('status', 'active')->orderBy('company_name')->get();

        return view('goods-receipts.index', compact('grns', 'suppliers'));
    }

    /**
     * Create-from-PO form. The PO must be issued or partial.
     */
    public function createFromPO(PurchaseOrder $purchaseOrder): View
    {
        if (! $purchaseOrder->isReceivable()) {
            abort(422, 'Only issued or partial POs can be received against.');
        }

        $purchaseOrder->load(['supplier', 'items.item.uom']);

        // Pre-compute outstanding qty per line for the form
        $lines = $purchaseOrder->items->map(function ($poLine) {
            return [
                'po_item_id'  => $poLine->id,
                'item_id'     => $poLine->item_id,
                'item_code'   => $poLine->item->item_code,
                'item_name'   => $poLine->item->item_name,
                'uom'         => $poLine->item->uom?->uom_code,
                'qty_ordered' => (float) $poLine->qty_ordered,
                'qty_received'=> (float) $poLine->qty_received,
                'outstanding' => $poLine->qtyOutstanding(),
                'unit_cost'   => (float) $poLine->unit_cost,
            ];
        });

        return view('goods-receipts.form', [
            'po' => $purchaseOrder,
            'grn' => new GoodsReceipt(['received_date' => now()->toDateString()]),
            'lines' => $lines,
            'nextGrnNumber' => $this->peekSequence('grn'),
        ]);
    }

    public function storeFromPO(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->isReceivable()) {
            abort(422, 'Only issued or partial POs can be received against.');
        }

        $data = $this->validateData($request);
        $confirmNow = $request->input('action') === 'confirm';

        if ($confirmNow && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can confirm a stock receipt.');
        }

        $grn = DB::transaction(function () use ($data, $purchaseOrder, $request, $confirmNow) {
            $grn = GoodsReceipt::create([
                'grn_number'       => DocumentSequence::next('grn'),
                'po_id'            => $purchaseOrder->id,
                'supplier_id'      => $purchaseOrder->supplier_id,
                'received_date'    => $data['received_date'],
                'delivery_note_no' => $data['delivery_note_no'] ?? null,
                'carrier'          => $data['carrier'] ?? null,
                'remarks'          => $data['remarks'] ?? null,
                'status'           => 'draft',
                'received_by'      => $request->user()->id,
            ]);

            $hasReceipt = false;
            foreach ($data['lines'] as $line) {
                $qtyRecv = (float) ($line['qty_received'] ?? 0);
                if ($qtyRecv <= 0) {
                    continue;
                }
                $hasReceipt = true;

                $poItem = PurchaseOrderItem::where('id', $line['po_item_id'])
                    ->where('po_id', $purchaseOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $outstanding = $poItem->qtyOutstanding();
                if ($qtyRecv > $outstanding + 0.001) {
                    throw ValidationException::withMessages([
                        'lines' => "Line {$poItem->item->item_code}: received qty {$qtyRecv} exceeds outstanding {$outstanding}.",
                    ]);
                }

                $qtyAcc = (float) ($line['qty_accepted'] ?? 0);
                $qtyRej = (float) ($line['qty_rejected'] ?? 0);

                if (abs(($qtyAcc + $qtyRej) - $qtyRecv) > 0.001) {
                    throw ValidationException::withMessages([
                        'lines' => "Line {$poItem->item->item_code}: accepted ({$qtyAcc}) + rejected ({$qtyRej}) must equal received ({$qtyRecv}).",
                    ]);
                }

                if ($qtyRej > 0 && empty($line['rejection_reason'])) {
                    throw ValidationException::withMessages([
                        'lines' => "Line {$poItem->item->item_code}: rejection reason required when qty_rejected > 0.",
                    ]);
                }

                GoodsReceiptItem::create([
                    'grn_id'           => $grn->id,
                    'po_item_id'       => $poItem->id,
                    'item_id'          => $poItem->item_id,
                    'qty_received'     => $qtyRecv,
                    'qty_accepted'     => $qtyAcc,
                    'qty_rejected'     => $qtyRej,
                    'rejection_reason' => $qtyRej > 0 ? ($line['rejection_reason'] ?? null) : null,
                    'unit_cost'        => (float) ($line['unit_cost'] ?? $poItem->unit_cost),
                ]);
            }

            if (! $hasReceipt) {
                throw ValidationException::withMessages([
                    'lines' => 'Enter received qty for at least one line item.',
                ]);
            }

            if ($confirmNow) {
                $this->applyConfirmation($grn, $request->user()->id);
            }

            return $grn;
        });

        if ($confirmNow) {
            return redirect()->route('stock-receiving.show', $grn)
                ->with('flash', "Stock receipt {$grn->grn_number} created and confirmed. Stock IN posted.");
        }

        return redirect()->route('stock-receiving.index')->with('flash', 'Stock receipt drafted. Confirm to update inventory.');
    }

    public function show(GoodsReceipt $goodsReceipt): View
    {
        $goodsReceipt->load(['supplier', 'purchaseOrder', 'items.item.uom', 'items.poItem', 'receiver', 'confirmer']);

        return view('goods-receipts.show', ['grn' => $goodsReceipt]);
    }

    /**
     * Explicit confirm route. Delegates to applyConfirmation().
     */
    public function confirm(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        if (! $goodsReceipt->isConfirmable()) {
            abort(422, 'Only draft stock receipts can be confirmed.');
        }

        DB::transaction(function () use ($goodsReceipt, $request) {
            $this->applyConfirmation($goodsReceipt, $request->user()->id);
        });

        return redirect()->route('stock-receiving.show', $goodsReceipt)
            ->with('flash', 'Stock receipt confirmed. Stock IN posted.');
    }

    /**
     * THE STOCK IN OPERATION.
     * Caller is responsible for wrapping in a DB transaction.
     * Atomically:
     *   - bumps items.qty_on_hand by qty_accepted per line
     *   - updates items.unit_cost to the GRN line unit_cost (latest cost)
     *   - bumps purchase_order_items.qty_received by qty_accepted (rejected goods don't fulfill the PO)
     *   - flips PO status to partial / received based on outstanding totals
     *   - locks the GRN (status=confirmed, immutable)
     */
    private function applyConfirmation(GoodsReceipt $grn, int $userId): void
    {
        $grn->load(['items.item', 'items.poItem', 'purchaseOrder']);
        $po = $grn->purchaseOrder;

        if (! $po->isReceivable()) {
            throw ValidationException::withMessages([
                'status' => "PO {$po->po_number} is no longer receivable (status: {$po->status}).",
            ]);
        }

        foreach ($grn->items as $line) {
            $item = Item::where('id', $line->item_id)->lockForUpdate()->firstOrFail();
            $poItem = PurchaseOrderItem::where('id', $line->po_item_id)->lockForUpdate()->firstOrFail();

            $outstanding = $poItem->qtyOutstanding();
            if ($line->qty_received > $outstanding + 0.001) {
                throw ValidationException::withMessages([
                    'stock' => "PO line for {$item->item_code}: outstanding changed during confirmation. Refresh and try again.",
                ]);
            }

            // ── STOCK IN — only qty_accepted enters inventory ──
            if ($line->qty_accepted > 0) {
                $item->qty_on_hand = (float) $item->qty_on_hand + (float) $line->qty_accepted;
                $item->unit_cost = (float) $line->unit_cost;
                $item->save();
            }

            // PO line fulfillment uses qty_accepted (rejected goods need to be re-shipped)
            $poItem->qty_received = (float) $poItem->qty_received + (float) $line->qty_accepted;
            $poItem->save();
        }

        $po->refresh();
        $po->load('items');
        $allFullyReceived = $po->items->every(fn ($l) => $l->qtyOutstanding() <= 0.001);
        $anyReceived = $po->items->contains(fn ($l) => (float) $l->qty_received > 0);

        $po->update([
            'status' => $allFullyReceived ? 'received' : ($anyReceived ? 'partial' : 'issued'),
            'amount_received_value' => $po->items->sum(function ($l) {
                return (float) $l->qty_received * (float) $l->unit_cost;
            }),
        ]);

        $grn->update([
            'status' => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        if (! $goodsReceipt->isCancellable()) {
            abort(422, 'Only draft stock receipts can be cancelled. Confirmed receipts are immutable.');
        }

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $goodsReceipt->update([
            'status'        => 'cancelled',
            'cancel_reason' => $data['cancel_reason'] ?? null,
            'cancelled_by'  => $request->user()->id,
            'cancelled_at'  => now(),
        ]);

        return redirect()->route('stock-receiving.show', $goodsReceipt)
            ->with('flash', 'Stock receipt cancelled. No inventory changes.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'received_date'              => ['required', 'date'],
            'delivery_note_no'           => ['nullable', 'string', 'max:60'],
            'carrier'                    => ['nullable', 'string', 'max:100'],
            'remarks'                    => ['nullable', 'string'],
            'lines'                      => ['required', 'array', 'min:1'],
            'lines.*.po_item_id'         => ['required', 'exists:purchase_order_items,id'],
            'lines.*.qty_received'       => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_accepted'       => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_rejected'       => ['nullable', 'numeric', 'min:0'],
            'lines.*.rejection_reason'   => ['nullable', 'string', 'max:500'],
            'lines.*.unit_cost'          => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function peekSequence(string $key): string
    {
        $seq = DocumentSequence::where('seq_key', $key)->first();

        return $seq->prefix . str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
    }
}
