@extends('layouts.app')
@section('title', 'New Deposit')

@php
  $collectionsJson = $collections->map(fn ($c) => [
      'id' => $c->id,
      'or_number' => $c->or_number,
      'date' => $c->collection_date->format('M j, Y'),
      'invoice' => $c->invoice->invoice_number,
      'customer' => $c->invoice->customer->company_name,
      'amount' => (float) $c->amount,
      'method' => $c->payment_method,
      'method_label' => match ($c->payment_method) {
          'cash' => 'Cash',
          'bank_transfer' => 'Bank',
          'check' => 'Check',
          'card' => 'Card',
          default => $c->payment_method,
      },
      'reference' => $c->reference_number,
  ]);
@endphp

@section('content')
@include('partials.header', [
  'eyebrow' => 'Sales · Deposits',
  'title' => 'New Bank Deposit',
  'accent' => '#fb923c',
  'subtitle' => 'Deposit # ' . $nextDepositNumber . ' · Select un-deposited collections',
])

@if ($collections->isEmpty())
  <div class="alert-info">No un-deposited collections available. Record some collections first, then create a deposit.</div>
  <div class="mt-3"><a href="{{ route('deposits.index') }}" class="btn btn-secondary btn-sm">← Back to Deposits</a></div>
@else
<form method="POST" action="{{ route('deposits.store') }}"
      x-data="depForm()"
      x-init="init({{ $collectionsJson->toJson() }})"
      class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf

  <div>
    <div class="card mb-3">
      <div class="card-head">Deposit Header</div>
      <div class="card-body grid grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Bank Account <span style="color:#f87171">*</span></label>
          <select class="input" name="bank_account_id" required>
            <option value="">— Select —</option>
            @foreach ($accounts as $a)
              <option value="{{ $a->id }}" @selected($a->is_default)>
                {{ $a->bank_name }} {{ $a->maskedNumber() }} @if ($a->is_default) (Default) @endif
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">Deposit Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="deposit_date" value="{{ old('deposit_date', now()->toDateString()) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Slip # (from bank)</label>
          <input class="input" name="slip_number" placeholder="Issued by teller after deposit">
        </div>
        <div class="col-span-3">
          <label class="label block mb-1.5">Notes</label>
          <input class="input" name="notes">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head flex items-center justify-between">
        <span>Un-Deposited Collections · <span x-text="`${selectedCount()} of ${collections.length} selected`"></span></span>
        <div class="flex gap-2 items-center">
          <button type="button" @click="selectAll()" class="btn btn-secondary btn-sm">Select All</button>
          <button type="button" @click="selectByMethod('cash')" class="btn btn-secondary btn-sm">All Cash</button>
          <button type="button" @click="selectByMethod('check')" class="btn btn-secondary btn-sm">All Check</button>
          <button type="button" @click="clearSelection()" class="btn btn-secondary btn-sm">Clear</button>
        </div>
      </div>
      <div class="card-body !p-0">
        <table class="dt">
          <thead><tr>
            <th style="width:36px"></th>
            <th>OR #</th><th>Date</th><th>Invoice / Customer</th>
            <th>Method</th><th>Reference</th>
            <th class="text-right">Amount</th>
          </tr></thead>
          <tbody>
            <template x-for="c in collections" :key="c.id">
              <tr :style="selected[c.id] ? 'background:rgba(56,189,248,.05)' : ''">
                <td>
                  <input type="checkbox" name="collection_ids[]" :value="c.id" :checked="selected[c.id]" @change="toggle(c.id)">
                </td>
                <td class="ref" x-text="c.or_number"></td>
                <td class="dim" x-text="c.date"></td>
                <td>
                  <div class="ref text-[11px]" x-text="c.invoice"></div>
                  <div x-text="c.customer"></div>
                </td>
                <td>
                  <span class="badge"
                        :style="c.method === 'cash' ? 'color:#94a3b8;border:1px solid rgba(148,163,184,.3)' :
                                c.method === 'check' ? 'color:#a78bfa;border:1px solid rgba(167,139,250,.3)' :
                                c.method === 'bank_transfer' ? 'color:#38bdf8;border:1px solid rgba(56,189,248,.3)' :
                                'color:#fbbf24;border:1px solid rgba(251,191,36,.3)'"
                        x-text="c.method_label"></span>
                </td>
                <td class="dim font-mono text-[11px]" x-text="c.reference || '—'"></td>
                <td class="num text-right" x-text="`₱${formatMoney(c.amount)}`"></td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr><td colspan="6" class="text-right dim">Cash total</td><td class="num text-right" x-text="`₱${formatMoney(byMethod('cash'))}`"></td></tr>
            <tr><td colspan="6" class="text-right dim">Check total</td><td class="num text-right" x-text="`₱${formatMoney(byMethod('check'))}`"></td></tr>
            <tr style="background:#181c24"><td colspan="6" class="text-right" style="color:#34d399;font-weight:700">DEPOSIT TOTAL</td><td class="num text-right" style="color:#34d399;font-weight:700;font-size:14px" x-text="`₱${formatMoney(grandTotal())}`"></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head">Actions</div>
      <div class="card-body flex flex-col gap-2">
        @php $canPost = auth()->user()->hasRole('Admin', 'Manager'); @endphp
        @if ($canPost)
          <button type="submit" name="action" value="post" class="btn btn-ok" style="justify-content:center" :disabled="selectedCount() === 0">Save and Post</button>
          <button type="submit" name="action" value="draft" class="btn btn-secondary" style="justify-content:center" :disabled="selectedCount() === 0">Save as Draft</button>
        @else
          <button type="submit" name="action" value="draft" class="btn btn-sell" style="justify-content:center" :disabled="selectedCount() === 0">Save as Draft</button>
        @endif
        <a href="{{ route('deposits.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-head">Workflow</div>
      <div class="card-body text-[11px]" style="color:#8892a4;line-height:1.7">
        <div>1. Select collections to deposit (cash + check most common).</div>
        <div>2. Save as draft. Selected collections are locked from other deposits.</div>
        <div>3. Bring cash & checks to the bank.</div>
        <div>4. Get the deposit slip back from the teller.</div>
        <div>5. Enter the slip # and Post the deposit.</div>
      </div>
    </div>
  </div>
</form>
@endif

@push('scripts')
<script>
function depForm() {
  return {
    collections: [],
    selected: {},
    init(collections) { this.collections = collections; },
    toggle(id) {
      if (this.selected[id]) delete this.selected[id];
      else this.selected[id] = true;
    },
    selectAll() { this.collections.forEach(c => this.selected[c.id] = true); },
    selectByMethod(method) {
      this.collections.forEach(c => { if (c.method === method) this.selected[c.id] = true; });
    },
    clearSelection() { this.selected = {}; },
    selectedCount() { return Object.keys(this.selected).length; },
    selectedCollections() { return this.collections.filter(c => this.selected[c.id]); },
    byMethod(method) {
      return this.selectedCollections().filter(c => c.method === method).reduce((s, c) => s + c.amount, 0);
    },
    byOtherMethods() {
      return this.selectedCollections().filter(c => !['cash','check'].includes(c.method)).reduce((s, c) => s + c.amount, 0);
    },
    grandTotal() {
      return this.selectedCollections().reduce((s, c) => s + c.amount, 0);
    },
    formatMoney(n) { return (n || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); },
  };
}
</script>
@endpush
@endsection
