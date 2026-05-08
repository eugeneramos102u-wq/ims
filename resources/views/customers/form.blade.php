@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'New Customer')

@section('content')
@include('partials.header', [
  'eyebrow' => 'Master Data · Customers',
  'title' => $customer->exists ? 'Edit Customer' : 'New Customer',
  'accent' => '#94a3b8',
  'subtitle' => $customer->exists ? $customer->customer_code : 'Code auto-assigned on save',
])

<form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf
  @if ($customer->exists) @method('PUT') @endif

  <div class="card">
    <div class="card-head">Customer Details</div>
    <div class="card-body grid grid-cols-2 gap-3">
      <div class="col-span-2">
        <label class="label block mb-1.5">Company Name <span style="color:#f87171">*</span></label>
        <input class="input" name="company_name" value="{{ old('company_name', $customer->company_name) }}" required>
      </div>
      <div>
        <label class="label block mb-1.5">Contact Person</label>
        <input class="input" name="contact_person" value="{{ old('contact_person', $customer->contact_person) }}">
      </div>
      <div>
        <label class="label block mb-1.5">Phone</label>
        <input class="input" name="phone" value="{{ old('phone', $customer->phone) }}">
      </div>
      <div>
        <label class="label block mb-1.5">Email</label>
        <input class="input" type="email" name="email" value="{{ old('email', $customer->email) }}">
      </div>
      <div>
        <label class="label block mb-1.5">Tax ID</label>
        <input class="input" name="tax_id" value="{{ old('tax_id', $customer->tax_id) }}">
      </div>
      <div class="col-span-2">
        <label class="label block mb-1.5">Billing Address</label>
        <textarea class="input" name="billing_address" rows="2">{{ old('billing_address', $customer->billing_address) }}</textarea>
      </div>
      <div class="col-span-2">
        <label class="label block mb-1.5">Shipping Address</label>
        <textarea class="input" name="shipping_address" rows="2">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
      </div>
      <div>
        <label class="label block mb-1.5">Credit Limit ₱ <span style="color:#f87171">*</span></label>
        <input class="input" type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" required>
      </div>
      <div>
        <label class="label block mb-1.5">Payment Term</label>
        <select class="input" name="payment_term_id">
          <option value="">— None —</option>
          @foreach ($paymentTerms as $t)
            <option value="{{ $t->id }}" @selected(old('payment_term_id', $customer->payment_term_id) == $t->id)>{{ $t->term_code }} — {{ $t->term_name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="label block mb-1.5">Status <span style="color:#f87171">*</span></label>
        <select class="input" name="status" required>
          <option value="active" @selected(old('status', $customer->status ?? 'active') === 'active')>Active</option>
          <option value="inactive" @selected(old('status', $customer->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
      </div>
      <div class="col-span-2">
        <label class="label block mb-1.5">Notes</label>
        <textarea class="input" name="notes" rows="2">{{ old('notes', $customer->notes) }}</textarea>
      </div>
    </div>
  </div>

  <div class="card" style="height:fit-content">
    <div class="card-head">Actions</div>
    <div class="card-body flex flex-col gap-2">
      <button type="submit" class="btn btn-master" style="justify-content:center">{{ $customer->exists ? 'Save Changes' : 'Create Customer' }}</button>
      <a href="{{ route('customers.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
    </div>
  </div>
</form>
@endsection
