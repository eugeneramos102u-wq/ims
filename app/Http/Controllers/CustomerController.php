<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DocumentSequence;
use App\Models\PaymentTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::with('paymentTerm');

        if ($s = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($s) {
                $q->where('customer_code', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%");
            });
        }
        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($request->boolean('has_outstanding')) {
            $query->whereHas('invoices', function ($q) {
                $q->whereIn('status', ['issued', 'partial', 'overdue'])
                  ->whereColumn('total_amount', '>', 'amount_paid');
            });
        }

        $customers = $query->orderBy('customer_code')->paginate(20)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.form', [
            'customer' => new Customer(),
            'paymentTerms' => PaymentTerm::where('status', 'active')->where('applies_to', '!=', 'purchases')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['customer_code'] = DocumentSequence::next('customer');
        Customer::create($data);

        return redirect()->route('customers.index')->with('flash', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', [
            'customer' => $customer,
            'paymentTerms' => PaymentTerm::where('status', 'active')->where('applies_to', '!=', 'purchases')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validateData($request));

        return redirect()->route('customers.index')->with('flash', 'Customer updated.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'company_name'     => ['required', 'string', 'max:150'],
            'contact_person'   => ['nullable', 'string', 'max:100'],
            'email'            => ['nullable', 'email', 'max:120'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'billing_address'  => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'tax_id'           => ['nullable', 'string', 'max:50'],
            'credit_limit'     => ['required', 'numeric', 'min:0'],
            'payment_term_id'  => ['nullable', 'exists:payment_terms,id'],
            'notes'            => ['nullable', 'string'],
            'status'           => ['required', 'in:active,inactive'],
        ]);
    }
}
