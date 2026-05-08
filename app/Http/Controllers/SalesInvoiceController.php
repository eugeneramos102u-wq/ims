<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DocumentSequence;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = SalesInvoice::with('customer');

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($request->boolean('overdue_only')) {
            $query->where('status', 'overdue');
        }
        if ($cust = $request->integer('customer_id')) {
            $query->where('customer_id', $cust);
        }
        if ($s = $request->string('search')->trim()->toString()) {
            $query->where('invoice_number', 'like', "%{$s}%");
        }
        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->input('to_date'));
        }

        $invoices = $query->latest('invoice_date')->latest('id')->paginate(15)->withQueryString();
        $customers = Customer::where('status', 'active')->orderBy('company_name')->get();
        $overdueCount = SalesInvoice::where('status', 'overdue')->count();

        return view('invoices.index', compact('invoices', 'customers', 'overdueCount'));
    }

    public function createFromSO(SalesOrder $salesOrder): View
    {
        if (! $salesOrder->isInvoiceable()) {
            abort(422, 'Only confirmed/partial sales orders can be invoiced.');
        }

        $salesOrder->load(['items.item.uom', 'customer.paymentTerm']);

        $dueDays = (int) ($salesOrder->customer->paymentTerm?->due_days ?? 0);

        return view('invoices.form', [
            'order' => $salesOrder,
            'invoice' => new SalesInvoice([
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($dueDays)->toDateString(),
            ]),
            'nextInvoiceNumber' => $this->peekSequence('invoice'),
        ]);
    }

    public function storeFromSO(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if (! $salesOrder->isInvoiceable()) {
            abort(422, 'Only confirmed/partial sales orders can be invoiced.');
        }

        $data = $request->validate([
            'invoice_date'         => ['required', 'date'],
            'due_date'             => ['required', 'date', 'after_or_equal:invoice_date'],
            'notes'                => ['nullable', 'string'],
            'lines'                => ['required', 'array', 'min:1'],
            'lines.*.so_item_id'   => ['required', 'exists:sales_order_items,id'],
            'lines.*.qty_invoiced' => ['required', 'numeric', 'min:0'],
        ]);

        $issueNow = $request->input('action') === 'issue';

        if ($issueNow && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can issue an invoice.');
        }

        $invoice = DB::transaction(function () use ($data, $salesOrder, $request, $issueNow) {
            $invoice = SalesInvoice::create([
                'invoice_number' => DocumentSequence::next('invoice'),
                'customer_id'    => $salesOrder->customer_id,
                'so_id'          => $salesOrder->id,
                'invoice_date'   => $data['invoice_date'],
                'due_date'       => $data['due_date'],
                'notes'          => $data['notes'] ?? null,
                'status'         => 'draft',
                'created_by'     => $request->user()->id,
            ]);

            $subtotal = 0;
            $discount = 0;
            $tax = 0;

            foreach ($data['lines'] as $line) {
                $qty = (float) $line['qty_invoiced'];
                if ($qty <= 0) {
                    continue;
                }

                $soItem = $salesOrder->items->firstWhere('id', (int) $line['so_item_id']);
                if (! $soItem) {
                    continue;
                }

                $remaining = $soItem->qtyRemaining();
                if ($qty > $remaining) {
                    throw ValidationException::withMessages([
                        'lines' => "Line for {$soItem->item->item_code}: invoice qty {$qty} exceeds remaining uninvoiced qty {$remaining}.",
                    ]);
                }

                $gross = $qty * (float) $soItem->unit_price;
                $lineDisc = $gross * ((float) $soItem->discount_pct / 100);
                $afterDisc = $gross - $lineDisc;
                $lineTax = $afterDisc * ((float) $soItem->tax_rate_pct / 100);
                $lineTotal = $afterDisc + $lineTax;

                SalesInvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'so_item_id'  => $soItem->id,
                    'item_id'     => $soItem->item_id,
                    'qty_invoiced'=> $qty,
                    'unit_price'  => $soItem->unit_price,
                    'discount_pct'=> $soItem->discount_pct,
                    'tax_rate_pct'=> $soItem->tax_rate_pct,
                    'line_total'  => round($lineTotal, 2),
                ]);

                // Update qty_invoiced on the SO line
                $soItem->increment('qty_invoiced', $qty);

                $subtotal += $gross;
                $discount += $lineDisc;
                $tax += $lineTax;
            }

            $invoice->update([
                'subtotal'        => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'tax_amount'      => round($tax, 2),
                'total_amount'    => round($subtotal - $discount + $tax, 2),
            ]);

            // Refresh the SO status
            $salesOrder->refresh();
            $salesOrder->load('items');
            $allFullyInvoiced = $salesOrder->items->every(fn ($l) => $l->qtyRemaining() <= 0);
            $someInvoiced = $salesOrder->items->contains(fn ($l) => (float) $l->qty_invoiced > 0);

            $salesOrder->update([
                'status' => $allFullyInvoiced ? 'fulfilled' : ($someInvoiced ? 'partial' : 'confirmed'),
            ]);

            if ($issueNow) {
                $invoice->update([
                    'status'    => 'issued',
                    'issued_by' => $request->user()->id,
                    'issued_at' => now(),
                ]);
            }

            return $invoice->fresh();
        });

        if ($issueNow) {
            return redirect()->route('invoices.show', $invoice)
                ->with('flash', "Invoice {$invoice->invoice_number} created and issued.");
        }

        return redirect()->route('invoices.index')->with('flash', 'Invoice created (draft).');
    }

    public function show(SalesInvoice $invoice): View
    {
        $invoice->load(['customer', 'items.item.uom', 'collections.collector', 'salesOrder']);

        return view('invoices.show', compact('invoice'));
    }

    public function issue(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            abort(422, 'Only draft invoices can be issued.');
        }
        $invoice->update([
            'status'    => 'issued',
            'issued_by' => $request->user()->id,
            'issued_at' => now(),
        ]);

        return redirect()->route('invoices.show', $invoice)->with('flash', 'Invoice issued.');
    }

    public function void(SalesInvoice $invoice): RedirectResponse
    {
        if ($invoice->amount_paid > 0) {
            abort(422, 'Cannot void an invoice that has payments recorded.');
        }
        if (! in_array($invoice->status, ['draft', 'issued'], true)) {
            abort(422, 'Cannot void this invoice.');
        }
        $invoice->update(['status' => 'void']);

        return redirect()->route('invoices.show', $invoice)->with('flash', 'Invoice voided.');
    }

    private function peekSequence(string $key): string
    {
        $seq = DocumentSequence::where('seq_key', $key)->first();

        return $seq->prefix . str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
    }
}
