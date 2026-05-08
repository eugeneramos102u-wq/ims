<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DocumentSequence;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = SalesOrder::with(['customer', 'salesperson']);

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($cust = $request->integer('customer_id')) {
            $query->where('customer_id', $cust);
        }
        if ($s = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($s) {
                $q->where('so_number', 'like', "%{$s}%")
                  ->orWhere('dr_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->input('to_date'));
        }

        $orders = $query->latest('order_date')->latest('id')->paginate(15)->withQueryString();
        $customers = Customer::where('status', 'active')->orderBy('company_name')->get();

        return view('sales-orders.index', compact('orders', 'customers'));
    }

    public function create(): View
    {
        return view('sales-orders.form', [
            'order' => new SalesOrder([
                'order_date' => now()->toDateString(),
                'price_type' => 'retail',
                'currency_code' => 'PHP',
            ]),
            'orderItems' => [],
            'customers' => Customer::where('status', 'active')->orderBy('company_name')->get(),
            'items' => Item::with('uom')->where('status', 'active')->orderBy('item_code')->get(),
            'nextSoNumber' => $this->peekSequence('so'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $confirmNow = $request->input('action') === 'confirm';

        if ($confirmNow && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can confirm a Sales Order.');
        }

        $order = DB::transaction(function () use ($data, $request, $confirmNow) {
            $order = SalesOrder::create([
                'so_number'       => DocumentSequence::next('so'),
                'dr_number'       => $data['dr_number'] ?? null,
                'customer_id'     => $data['customer_id'],
                'order_date'      => $data['order_date'],
                'required_date'   => $data['required_date'] ?? null,
                'price_type'      => $data['price_type'],
                'shipping_address'=> $data['shipping_address'] ?? null,
                'currency_code'   => 'PHP',
                'notes'           => $data['notes'] ?? null,
                'status'          => 'draft',
                'created_by'      => $request->user()->id,
            ]);

            $this->saveLines($order, $data['lines']);
            $this->recalcTotals($order);

            if ($confirmNow) {
                $this->applyConfirmation($order->fresh(), $request->user()->id);
            }

            return $order->fresh();
        });

        if ($confirmNow) {
            return redirect()->route('sales-orders.show', $order)
                ->with('flash', "Sales Order {$order->so_number} created and confirmed. Stock OUT posted.");
        }

        return redirect()->route('sales-orders.index')->with('flash', 'Sales Order created.');
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['customer', 'salesperson', 'items.item.uom', 'invoices', 'creator', 'confirmer']);

        return view('sales-orders.show', ['order' => $salesOrder]);
    }

    public function edit(SalesOrder $salesOrder): View
    {
        if (! $salesOrder->isEditable()) {
            abort(403, 'Only draft orders can be edited.');
        }

        return view('sales-orders.form', [
            'order' => $salesOrder,
            'orderItems' => $salesOrder->items()->with('item.uom')->get(),
            'customers' => Customer::where('status', 'active')->orderBy('company_name')->get(),
            'items' => Item::with('uom')->where('status', 'active')->orderBy('item_code')->get(),
            'nextSoNumber' => null,
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if (! $salesOrder->isEditable()) {
            abort(403, 'Only draft orders can be edited.');
        }

        $data = $this->validateData($request);
        $confirmNow = $request->input('action') === 'confirm';

        if ($confirmNow && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can confirm a Sales Order.');
        }

        DB::transaction(function () use ($data, $salesOrder, $request, $confirmNow) {
            $salesOrder->update([
                'dr_number'       => $data['dr_number'] ?? null,
                'customer_id'     => $data['customer_id'],
                'order_date'      => $data['order_date'],
                'required_date'   => $data['required_date'] ?? null,
                'price_type'      => $data['price_type'],
                'shipping_address'=> $data['shipping_address'] ?? null,
                'notes'           => $data['notes'] ?? null,
            ]);
            $salesOrder->items()->delete();
            $this->saveLines($salesOrder, $data['lines']);
            $this->recalcTotals($salesOrder->fresh());

            if ($confirmNow) {
                $this->applyConfirmation($salesOrder->fresh(), $request->user()->id);
            }
        });

        $message = $confirmNow
            ? "Sales Order {$salesOrder->so_number} updated and confirmed. Stock OUT posted."
            : 'Sales Order updated.';

        return redirect()->route('sales-orders.show', $salesOrder)->with('flash', $message);
    }

    public function confirm(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if (! $salesOrder->isConfirmable()) {
            abort(422, 'Only draft orders can be confirmed.');
        }

        DB::transaction(function () use ($salesOrder, $request) {
            $this->applyConfirmation($salesOrder, $request->user()->id);
        });

        return redirect()->route('sales-orders.show', $salesOrder)->with('flash', 'Sales Order confirmed. Stock deducted.');
    }

    /**
     * Stock OUT — caller wraps in DB::transaction.
     * Atomically validates available stock per line, decrements qty_on_hand,
     * and flips the SO status to 'confirmed'.
     */
    private function applyConfirmation(SalesOrder $order, int $userId): void
    {
        $order->load('items.item');

        foreach ($order->items as $line) {
            $item = Item::where('id', $line->item_id)->lockForUpdate()->firstOrFail();
            if ((float) $item->qty_on_hand < (float) $line->qty_ordered) {
                throw ValidationException::withMessages([
                    'stock' => "Insufficient stock for {$item->item_code} ({$item->item_name}). Available: {$item->qty_on_hand}, requested: {$line->qty_ordered}.",
                ]);
            }
            $item->decrement('qty_on_hand', (float) $line->qty_ordered);
        }

        $order->update([
            'status'       => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(SalesOrder $salesOrder): RedirectResponse
    {
        if (! in_array($salesOrder->status, ['draft', 'confirmed'], true)) {
            abort(422, 'Cannot cancel this order in its current status.');
        }
        if ($salesOrder->invoices()->exists()) {
            abort(422, 'Cannot cancel an order with existing invoices.');
        }

        DB::transaction(function () use ($salesOrder) {
            // Restore stock if it had been deducted
            if ($salesOrder->status === 'confirmed') {
                $salesOrder->load('items.item');
                foreach ($salesOrder->items as $line) {
                    $line->item->increment('qty_on_hand', $line->qty_ordered);
                }
            }
            $salesOrder->update(['status' => 'cancelled']);
        });

        return redirect()->route('sales-orders.show', $salesOrder)->with('flash', 'Sales Order cancelled.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'customer_id'         => ['required', 'exists:customers,id'],
            'order_date'          => ['required', 'date'],
            'required_date'       => ['nullable', 'date', 'after_or_equal:order_date'],
            'price_type'          => ['required', 'in:wholesale,retail'],
            'dr_number'           => ['nullable', 'string', 'max:30'],
            'shipping_address'    => ['nullable', 'string'],
            'notes'               => ['nullable', 'string'],
            'lines'               => ['required', 'array', 'min:1'],
            'lines.*.item_id'     => ['required', 'exists:items,id'],
            'lines.*.qty'         => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'lines.*.discount_pct'=> ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function saveLines(SalesOrder $order, array $lines): void
    {
        foreach ($lines as $line) {
            $item = Item::findOrFail($line['item_id']);
            $qty = (float) $line['qty'];
            $price = (float) $line['unit_price'];
            $disc = (float) ($line['discount_pct'] ?? 0);
            $taxPct = (float) $item->tax_rate_pct;
            $gross = $qty * $price;
            $discAmt = $gross * ($disc / 100);
            $afterDisc = $gross - $discAmt;
            $lineTotal = $afterDisc + ($afterDisc * $taxPct / 100);

            SalesOrderItem::create([
                'so_id'       => $order->id,
                'item_id'     => $item->id,
                'qty_ordered' => $qty,
                'unit_price'  => $price,
                'discount_pct'=> $disc,
                'tax_rate_pct'=> $taxPct,
                'line_total'  => round($lineTotal, 2),
            ]);
        }
    }

    private function recalcTotals(SalesOrder $order): void
    {
        $order->load('items');
        $subtotal = 0;
        $tax = 0;
        $disc = 0;
        foreach ($order->items as $line) {
            $gross = (float) $line->qty_ordered * (float) $line->unit_price;
            $lineDisc = $gross * ((float) $line->discount_pct / 100);
            $afterDisc = $gross - $lineDisc;
            $lineTax = $afterDisc * ((float) $line->tax_rate_pct / 100);
            $subtotal += $gross;
            $disc += $lineDisc;
            $tax += $lineTax;
        }
        $total = $subtotal - $disc + $tax;

        $order->update([
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($disc, 2),
            'tax_amount' => round($tax, 2),
            'total_amount' => round($total, 2),
        ]);
    }

    private function peekSequence(string $key): string
    {
        $seq = DocumentSequence::where('seq_key', $key)->first();

        return $seq->prefix . str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
    }
}
