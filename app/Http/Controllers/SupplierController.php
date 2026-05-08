<?php

namespace App\Http\Controllers;

use App\Models\DocumentSequence;
use App\Models\PaymentTerm;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $query = Supplier::with('paymentTerm');

        if ($s = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($s) {
                $q->where('supplier_code', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%");
            });
        }
        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        $suppliers = $query->orderBy('supplier_code')->paginate(20)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.form', [
            'supplier' => new Supplier(),
            'paymentTerms' => PaymentTerm::where('status', 'active')->where('applies_to', '!=', 'sales')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['supplier_code'] = DocumentSequence::next('supplier');
        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('flash', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', [
            'supplier' => $supplier,
            'paymentTerms' => PaymentTerm::where('status', 'active')->where('applies_to', '!=', 'sales')->get(),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validateData($request));

        return redirect()->route('suppliers.index')->with('flash', 'Supplier updated.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'company_name'    => ['required', 'string', 'max:150'],
            'contact_person'  => ['nullable', 'string', 'max:100'],
            'email'           => ['nullable', 'email', 'max:120'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string'],
            'tax_id'          => ['nullable', 'string', 'max:50'],
            'bank_name'       => ['nullable', 'string', 'max:100'],
            'bank_account'    => ['nullable', 'string', 'max:50'],
            'payment_term_id' => ['nullable', 'exists:payment_terms,id'],
            'notes'           => ['nullable', 'string'],
            'status'          => ['required', 'in:active,inactive'],
        ]);
    }
}
