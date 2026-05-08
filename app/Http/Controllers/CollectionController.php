<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\DocumentSequence;
use App\Models\SalesInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Collection::with(['invoice.customer', 'collector', 'deposit']);

        if ($method = $request->string('payment_method')->trim()->toString()) {
            $query->where('payment_method', $method);
        }
        if ($s = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($s) {
                $q->where('or_number', 'like', "%{$s}%")
                  ->orWhereHas('invoice', fn ($iq) => $iq->where('invoice_number', 'like', "%{$s}%"));
            });
        }
        if ($cust = $request->integer('customer_id')) {
            $query->where('customer_id', $cust);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('collection_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('collection_date', '<=', $request->input('to_date'));
        }
        if ($request->boolean('pending_deposit')) {
            $query->whereNull('deposit_id')->whereIn('payment_method', ['cash', 'check']);
        }

        $collections = $query->latest('collection_date')->latest('id')->paginate(15)->withQueryString();
        $customers = \App\Models\Customer::where('status', 'active')->orderBy('company_name')->get();

        return view('collections.index', compact('collections', 'customers'));
    }

    public function create(SalesInvoice $invoice): View
    {
        if (! in_array($invoice->status, ['issued', 'partial', 'overdue'], true)) {
            abort(422, 'Payments can only be recorded against issued, partial, or overdue invoices.');
        }

        return view('collections.form', [
            'invoice' => $invoice->load('customer'),
            'nextOrNumber' => $this->peekSequence('or'),
            'balance' => $invoice->balance_due,
        ]);
    }

    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if (! in_array($invoice->status, ['issued', 'partial', 'overdue'], true)) {
            abort(422, 'Payments can only be recorded against issued, partial, or overdue invoices.');
        }

        $data = $request->validate([
            'collection_date'  => ['required', 'date'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'in:cash,bank_transfer,check,card'],
            'reference_number' => [
                'nullable', 'string', 'max:80',
                Rule::requiredIf(fn () => $request->input('payment_method') === 'bank_transfer'),
            ],
            'bank_name'        => ['nullable', 'string', 'max:100'],
            'check_number'     => [
                'nullable', 'string', 'max:50',
                Rule::requiredIf(fn () => $request->input('payment_method') === 'check'),
            ],
            'check_date'       => [
                'nullable', 'date',
                Rule::requiredIf(fn () => $request->input('payment_method') === 'check'),
            ],
            'card_last4'       => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'approval_code'    => [
                'nullable', 'string', 'max:50',
                Rule::requiredIf(fn () => $request->input('payment_method') === 'card'),
            ],
            'notes'            => ['nullable', 'string'],
        ], [
            'reference_number.required' => 'Reference / Transaction # is required for bank transfers.',
            'check_number.required'     => 'Check number is required.',
            'check_date.required'       => 'Check date is required.',
            'approval_code.required'    => 'Approval / auth code is required for card payments.',
            'card_last4.regex'          => 'Card last 4 must be exactly 4 digits.',
        ]);

        DB::transaction(function () use ($data, $invoice, $request) {
            $invoice->refresh();
            $balance = (float) $invoice->total_amount - (float) $invoice->amount_paid;

            if ((float) $data['amount'] > $balance + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => "Payment {$data['amount']} exceeds outstanding balance {$balance}.",
                ]);
            }

            Collection::create([
                'or_number'        => DocumentSequence::next('or'),
                'invoice_id'       => $invoice->id,
                'customer_id'      => $invoice->customer_id,
                'collection_date'  => $data['collection_date'],
                'amount'           => $data['amount'],
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'bank_name'        => $data['bank_name'] ?? null,
                'check_number'     => $data['check_number'] ?? null,
                'check_date'       => $data['check_date'] ?? null,
                'card_last4'       => $data['card_last4'] ?? null,
                'approval_code'    => $data['approval_code'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'collected_by'     => $request->user()->id,
            ]);

            $invoice->amount_paid = (float) $invoice->amount_paid + (float) $data['amount'];
            $newBalance = (float) $invoice->total_amount - (float) $invoice->amount_paid;

            if ($newBalance <= 0.001) {
                $invoice->status = 'paid';
            } elseif ((float) $invoice->amount_paid > 0) {
                $invoice->status = 'partial';
            }

            $invoice->save();
        });

        return redirect()->route('invoices.show', $invoice)->with('flash', 'Payment recorded.');
    }

    private function peekSequence(string $key): string
    {
        $seq = DocumentSequence::where('seq_key', $key)->first();

        return $seq->prefix . str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
    }
}
