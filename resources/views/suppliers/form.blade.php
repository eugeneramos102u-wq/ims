@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit Supplier' : 'New Supplier')

@section('content')
@include('partials.header', [
  'eyebrow' => 'Master Data · Suppliers',
  'title' => $supplier->exists ? 'Edit Supplier' : 'New Supplier',
  'accent' => '#94a3b8',
  'subtitle' => $supplier->exists ? $supplier->supplier_code : 'Code auto-assigned on save',
])

<form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf
  @if ($supplier->exists) @method('PUT') @endif

  <div>
    <div class="card mb-3">
      <div class="card-head">Supplier Details</div>
      <div class="card-body grid grid-cols-2 gap-3">
        <div class="col-span-2">
          <label class="label block mb-1.5">Company Name <span style="color:#f87171">*</span></label>
          <input class="input" name="company_name" value="{{ old('company_name', $supplier->company_name) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Contact Person</label>
          <input class="input" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Phone</label>
          <input class="input" name="phone" value="{{ old('phone', $supplier->phone) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Email</label>
          <input class="input" type="email" name="email" value="{{ old('email', $supplier->email) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Tax ID / TIN</label>
          <input class="input" name="tax_id" value="{{ old('tax_id', $supplier->tax_id) }}">
        </div>
        <div class="col-span-2">
          <label class="label block mb-1.5">Address</label>
          <textarea class="input" name="address" rows="2">{{ old('address', $supplier->address) }}</textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head">Banking & Terms</div>
      <div class="card-body grid grid-cols-2 gap-3">
        <div>
          <label class="label block mb-1.5">Bank Name</label>
          <input class="input" name="bank_name" value="{{ old('bank_name', $supplier->bank_name) }}" placeholder="BDO, BPI, MetroBank…">
        </div>
        <div>
          <label class="label block mb-1.5">Bank Account #</label>
          <input class="input" name="bank_account" value="{{ old('bank_account', $supplier->bank_account) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Default Payment Term</label>
          <select class="input" name="payment_term_id">
            <option value="">— None —</option>
            @foreach ($paymentTerms as $t)
              <option value="{{ $t->id }}" @selected(old('payment_term_id', $supplier->payment_term_id) == $t->id)>{{ $t->term_code }} — {{ $t->term_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">Status <span style="color:#f87171">*</span></label>
          <select class="input" name="status" required>
            <option value="active" @selected(old('status', $supplier->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $supplier->status ?? 'active') === 'inactive')>Inactive</option>
          </select>
        </div>
        <div class="col-span-2">
          <label class="label block mb-1.5">Notes</label>
          <textarea class="input" name="notes" rows="2">{{ old('notes', $supplier->notes) }}</textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="height:fit-content">
    <div class="card-head">Actions</div>
    <div class="card-body flex flex-col gap-2">
      <button type="submit" class="btn btn-master" style="justify-content:center">{{ $supplier->exists ? 'Save Changes' : 'Create Supplier' }}</button>
      <a href="{{ route('suppliers.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
    </div>
  </div>
</form>
@endsection
