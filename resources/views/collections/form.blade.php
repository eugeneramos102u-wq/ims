@extends('layouts.app')
@section('title', 'Record Payment')

@section('content')
@include('partials.header', [
  'eyebrow' => 'Sales · Collections',
  'title' => 'Record Payment for ' . $invoice->invoice_number,
  'accent' => '#fb923c',
  'subtitle' => 'OR # ' . $nextOrNumber . ' · Balance due: ₱' . number_format($balance, 2),
])

<form method="POST" action="{{ route('collections.store', $invoice) }}"
      x-data="{ method: '{{ old('payment_method', 'cash') }}' }"
      class="grid gap-3 grid-cols-1 md:grid-cols-[1fr_280px]">
  @csrf

  <div>
    <div class="card mb-3">
      <div class="card-head">Payment Basics</div>
      <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Collection Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="collection_date" value="{{ old('collection_date', now()->toDateString()) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Amount ₱ <span style="color:#f87171">*</span></label>
          <input class="input" type="number" step="0.01" min="0.01" max="{{ $balance }}" name="amount" value="{{ old('amount', $balance) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Payment Method <span style="color:#f87171">*</span></label>
          <select class="input" name="payment_method" x-model="method" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="check">Check</option>
            <option value="card">Card</option>
          </select>
        </div>
      </div>
    </div>

    {{-- ── Cash hint ── --}}
    <div class="card mb-3" x-show="method === 'cash'" x-cloak>
      <div class="card-body text-[12px]" style="color:#94a3b8">
        💵 No additional details required for cash. The OR # and amount above are sufficient.
      </div>
    </div>

    {{-- ── Bank Transfer details ── --}}
    <div class="card mb-3" x-show="method === 'bank_transfer'" x-cloak>
      <div class="card-head" style="color:#38bdf8">Bank Transfer Details</div>
      <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="label block mb-1.5">Bank Name</label>
          <input class="input" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. BDO, BPI, MetroBank" :disabled="method !== 'bank_transfer'">
        </div>
        <div>
          <label class="label block mb-1.5">Reference / Transaction # <span style="color:#f87171">*</span></label>
          <input class="input" name="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. BDO-TXN-202604-1234" :required="method === 'bank_transfer'" :disabled="method !== 'bank_transfer'">
        </div>
      </div>
    </div>

    {{-- ── Check details ── --}}
    <div class="card mb-3" x-show="method === 'check'" x-cloak>
      <div class="card-head" style="color:#a78bfa">Check Details</div>
      <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Check # <span style="color:#f87171">*</span></label>
          <input class="input" name="check_number" value="{{ old('check_number') }}" placeholder="e.g. 004821" :required="method === 'check'" :disabled="method !== 'check'">
        </div>
        <div>
          <label class="label block mb-1.5">Bank</label>
          <input class="input" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. BDO, BPI" :disabled="method !== 'check'">
        </div>
        <div>
          <label class="label block mb-1.5">Check Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="check_date" value="{{ old('check_date', now()->toDateString()) }}" :required="method === 'check'" :disabled="method !== 'check'">
        </div>
        <div class="sm:col-span-3 text-[11px]" style="color:#8892a4">
          💡 If this is a <strong>post-dated check</strong>, enter the date written on the check. You won't be able to deposit it until that date.
        </div>
      </div>
    </div>

    {{-- ── Card details ── --}}
    <div class="card mb-3" x-show="method === 'card'" x-cloak>
      <div class="card-head" style="color:#fbbf24">Card Details</div>
      <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Last 4 Digits</label>
          <input class="input" name="card_last4" value="{{ old('card_last4') }}" maxlength="4" placeholder="1234" pattern="[0-9]{4}" :disabled="method !== 'card'">
        </div>
        <div>
          <label class="label block mb-1.5">Approval / Auth Code <span style="color:#f87171">*</span></label>
          <input class="input" name="approval_code" value="{{ old('approval_code') }}" placeholder="From the terminal receipt" :required="method === 'card'" :disabled="method !== 'card'">
        </div>
        <div>
          <label class="label block mb-1.5">Reference / Batch #</label>
          <input class="input" name="reference_number" value="{{ old('reference_number') }}" placeholder="Optional batch / merchant reference" :disabled="method !== 'card'">
        </div>
      </div>
    </div>

    {{-- ── Notes (always shown) ── --}}
    <div class="card">
      <div class="card-head">Notes</div>
      <div class="card-body">
        <textarea class="input" name="notes" rows="2" placeholder="Any additional info: drawer name, remarks, etc.">{{ old('notes') }}</textarea>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head">Actions</div>
      <div class="card-body flex flex-col gap-2">
        <button type="submit" class="btn btn-ok" style="justify-content:center">Record Payment</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-head">Invoice</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div class="font-semibold">{{ $invoice->invoice_number }}</div>
        <div class="dim">{{ $invoice->customer->company_name }}</div>
        <div class="mt-2">
          <span class="dim">Total:</span> <span class="num">₱{{ number_format($invoice->total_amount, 2) }}</span>
        </div>
        <div>
          <span class="dim">Paid:</span> <span class="num" style="color:#34d399">₱{{ number_format($invoice->amount_paid, 2) }}</span>
        </div>
        <div class="mt-1.5">
          <span class="dim">Balance:</span> <span class="num" style="color:#f87171;font-weight:700">₱{{ number_format($balance, 2) }}</span>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection
