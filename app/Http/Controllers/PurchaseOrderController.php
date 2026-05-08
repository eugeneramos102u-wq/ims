<?php

namespace App\Http\Controllers;

use App\Models\DocumentSequence;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['supplier']);

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($sup = $request->integer('supplier_id')) {
            $query->where('supplier_id', $sup);
        }
        if ($s = $request->string('search')->trim()->toString()) {
            $query->where('po_number', 'like', "%{$s}%");
        }
        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->input('to_date'));
        }

        $orders = $query->latest('order_date')->latest('id')->paginate(15)->withQueryString();
        $suppliers = Supplier::where('status', 'active')->orderBy('company_name')->get();

        return view('purchase-orders.index', compact('orders', 'suppliers'));
    }

    public function create(): View
    {
        return view('purchase-orders.form', [
            'order' => new PurchaseOrder([
                'order_date' => now()->toDateString(),
                'currency_code' => 'PHP',
            ]),
            'orderItems' => [],
            'suppliers' => Supplier::with('paymentTerm')->where('status', 'active')->orderBy('company_name')->get(),
            'paymentTerms' => PaymentTerm::where('status', 'active')->where('applies_to', '!=', 'sales')->orderBy('term_code')->get(),
            'items' => Item::with('uom')->where('status', 'active')->orderBy('item_code')->get(),
            'nextPoNumber' => $this->peekSequence('po'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $issue = $request->input('action') === 'issue';

        if ($issue && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can issue a PO.');
        }

        $order = DB::transaction(function () use ($data, $request, $issue) {
            $order = PurchaseOrder::create([
                'po_number'        => DocumentSequence::next('po'),
                'supplier_id'      => $data['supplier_id'],
                'payment_term_id'  => $data['payment_term_id'] ?? null,
                'order_date'       => $data['order_date'],
                'expected_date'    => $data['expected_date'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'currency_code'    => 'PHP',
                'notes'            => $data['notes'] ?? null,
                'status'           => 'draft',
                'created_by'       => $request->user()->id,
            ]);

            $this->saveLines($order, $data['lines']);
            $this->recalcTotals($order);

            if ($issue) {
                $order->update([
                    'status'    => 'issued',
                    'issued_by' => $request->user()->id,
                    'issued_at' => now(),
                ]);
            }

            return $order->fresh();
        });

        if ($issue) {
            return redirect()->route('purchase-orders.show', $order)
                ->with('flash', "Purchase Order {$order->po_number} created and issued to supplier.");
        }

        return redirect()->route('purchase-orders.index')->with('flash', 'Purchase Order created.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier.paymentTerm', 'paymentTerm', 'items.item.uom', 'creator', 'issuer', 'goodsReceipts.receiver']);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        if (! $purchaseOrder->isEditable()) {
            abort(403, 'Only draft POs can be edited.');
        }

        return view('purchase-orders.form', [
            'order' => $purchaseOrder,
            'orderItems' => $purchaseOrder->items()->with('item.uom')->get(),
            'suppliers' => Supplier::with('paymentTerm')->where('status', 'active')->orderBy('company_name')->get(),
            'paymentTerms' => PaymentTerm::where('status', 'active')->where('applies_to', '!=', 'sales')->orderBy('term_code')->get(),
            'items' => Item::with('uom')->where('status', 'active')->orderBy('item_code')->get(),
            'nextPoNumber' => null,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->isEditable()) {
            abort(403, 'Only draft POs can be edited.');
        }

        $data = $this->validateData($request);
        $issue = $request->input('action') === 'issue';

        if ($issue && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can issue a PO.');
        }

        DB::transaction(function () use ($data, $purchaseOrder, $request, $issue) {
            $purchaseOrder->update([
                'supplier_id'      => $data['supplier_id'],
                'payment_term_id'  => $data['payment_term_id'] ?? null,
                'order_date'       => $data['order_date'],
                'expected_date'    => $data['expected_date'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);
            $purchaseOrder->items()->delete();
            $this->saveLines($purchaseOrder, $data['lines']);
            $this->recalcTotals($purchaseOrder->fresh());

            if ($issue) {
                $purchaseOrder->update([
                    'status'    => 'issued',
                    'issued_by' => $request->user()->id,
                    'issued_at' => now(),
                ]);
            }
        });

        $message = $issue
            ? "Purchase Order {$purchaseOrder->po_number} updated and issued to supplier."
            : 'Purchase Order updated.';

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('flash', $message);
    }

    public function issue(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->isIssuable()) {
            abort(422, 'Only draft POs can be issued.');
        }

        $purchaseOrder->update([
            'status'    => 'issued',
            'issued_by' => $request->user()->id,
            'issued_at' => now(),
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('flash', 'PO issued to supplier.');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->isCancellable()) {
            abort(422, 'Cannot cancel this PO in its current status.');
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancel_reason.required' => 'A cancel reason is required.',
            'cancel_reason.min'      => 'Cancel reason must be at least 5 characters.',
        ]);

        $purchaseOrder->update([
            'status'        => 'cancelled',
            'cancel_reason' => $data['cancel_reason'],
            'cancelled_by'  => $request->user()->id,
            'cancelled_at'  => now(),
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('flash', 'PO cancelled.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'supplier_id'        => ['required', 'exists:suppliers,id'],
            'payment_term_id'    => ['nullable', 'exists:payment_terms,id'],
            'order_date'         => ['required', 'date'],
            'expected_date'      => ['nullable', 'date', 'after_or_equal:order_date'],
            'delivery_address'   => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.item_id'    => ['required', 'exists:items,id'],
            'lines.*.qty'        => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ], [
            'expected_date.after_or_equal' => 'Expected date cannot be before order date.',
        ]);
    }

    private function saveLines(PurchaseOrder $order, array $lines): void
    {
        foreach ($lines as $line) {
            $item = Item::findOrFail($line['item_id']);
            $qty = (float) $line['qty'];
            $cost = (float) $line['unit_cost'];
            $taxPct = (float) $item->tax_rate_pct;
            $gross = $qty * $cost;
            $lineTotal = $gross + ($gross * $taxPct / 100);

            PurchaseOrderItem::create([
                'po_id'       => $order->id,
                'item_id'     => $item->id,
                'qty_ordered' => $qty,
                'unit_cost'   => $cost,
                'tax_rate_pct'=> $taxPct,
                'line_total'  => round($lineTotal, 2),
            ]);
        }
    }

    private function recalcTotals(PurchaseOrder $order): void
    {
        $order->load('items');
        $subtotal = 0;
        $tax = 0;
        foreach ($order->items as $line) {
            $gross = (float) $line->qty_ordered * (float) $line->unit_cost;
            $subtotal += $gross;
            $tax += $gross * ((float) $line->tax_rate_pct / 100);
        }
        $order->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($tax, 2),
            'total_amount' => round($subtotal + $tax, 2),
        ]);
    }

    private function peekSequence(string $key): string
    {
        $seq = DocumentSequence::where('seq_key', $key)->first();

        return $seq->prefix . str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
    }
}
