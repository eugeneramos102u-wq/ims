@extends('layouts.app')
@section('title', $order->exists ? 'Edit PO' : 'New Purchase Order')

@php
  $items = collect($items ?? []);
  $existingLines = collect($orderItems ?? []);
  $itemsJson = $items->map(fn ($i) => [
      'id' => $i->id,
      'code' => $i->item_code,
      'name' => $i->item_name,
      'uom' => $i->uom?->uom_code,
      'cost' => (float) $i->unit_cost,
      'tax' => (float) $i->tax_rate_pct,
  ])->values();
  $linesJson = $existingLines->map(fn ($l) => [
      'item_id' => $l->item_id,
      'qty' => (float) $l->qty_ordered,
      'unit_cost' => (float) $l->unit_cost,
  ])->values();
@endphp

@section('content')
@include('partials.header', [
  'eyebrow' => 'Purchasing · Purchase Orders',
  'title' => $order->exists ? 'Edit Purchase Order' : 'New Purchase Order',
  'accent' => '#60a5fa',
  'subtitle' => $order->exists ? $order->po_number . ' · ' . ucfirst($order->status) : ($nextPoNumber ? 'New · PO # ' . $nextPoNumber : null),
])

<form method="POST" action="{{ $order->exists ? route('purchase-orders.update', $order) : route('purchase-orders.store') }}"
      x-data="poForm()"
      x-init="init({{ $itemsJson->toJson() }}, {{ $linesJson->toJson() }})"
      class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf
  @if ($order->exists) @method('PUT') @endif

  <div>
    <div class="card mb-3">
      <div class="card-head">PO Header</div>
      <div class="card-body grid grid-cols-3 gap-3">
        <div class="col-span-2">
          <label class="label block mb-1.5">Supplier <span style="color:#f87171">*</span></label>
          <select class="input" name="supplier_id" required>
            <option value="">— Select —</option>
            @foreach ($suppliers as $sup)
              <option value="{{ $sup->id }}"
                      data-term="{{ $sup->payment_term_id }}"
                      @selected(old('supplier_id', $order->supplier_id) == $sup->id)>
                {{ $sup->company_name }}@if ($sup->paymentTerm) · {{ $sup->paymentTerm->term_code }} @endif
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">Payment Term</label>
          <select class="input" name="payment_term_id">
            <option value="">— Default —</option>
            @foreach ($paymentTerms as $t)
              <option value="{{ $t->id }}" @selected(old('payment_term_id', $order->payment_term_id) == $t->id)>{{ $t->term_code }} — {{ $t->term_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">Order Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="order_date" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Expected Date</label>
          <input class="input" type="date" name="expected_date" value="{{ old('expected_date', optional($order->expected_date)->format('Y-m-d')) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Currency</label>
          <input class="input" value="PHP" readonly>
        </div>
        <div class="col-span-3">
          <label class="label block mb-1.5">Delivery Address</label>
          <textarea class="input" name="delivery_address" rows="2">{{ old('delivery_address', $order->delivery_address) }}</textarea>
        </div>
        <div class="col-span-3">
          <label class="label block mb-1.5">Notes</label>
          <input class="input" name="notes" value="{{ old('notes', $order->notes) }}">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head flex items-center justify-between">
        <span>Line Items</span>
        <button type="button" @click="addLine()" class="btn btn-buy btn-sm">+ Add Line</button>
      </div>
      <div class="card-body !p-0">
        <table class="dt" style="font-size:12px">
          <thead><tr>
            <th style="width:40px">#</th>
            <th>Item</th>
            <th class="text-right" style="width:100px">Qty</th>
            <th class="text-right" style="width:130px">Unit Cost</th>
            <th class="text-right" style="width:130px">Line Total</th>
            <th style="width:50px"></th>
          </tr></thead>
          <tbody>
            <template x-for="(line, idx) in lines" :key="idx">
              <tr>
                <td class="dim" x-text="idx + 1"></td>
                <td>
                  <input type="hidden" :name="`lines[${idx}][item_id]`" :value="line.item_id">
                  <select class="input" :value="line.item_id" @change="setItem(idx, $event.target.value)">
                    <option value="">— Select item —</option>
                    <template x-for="i in items" :key="i.id">
                      <option :value="i.id" :selected="line.item_id == i.id" x-text="`${i.code} · ${i.name} (${i.uom})`"></option>
                    </template>
                  </select>
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.01" min="0.01" :name="`lines[${idx}][qty]`" x-model.number="line.qty" @input="recalc()">
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.0001" min="0" :name="`lines[${idx}][unit_cost]`" x-model.number="line.unit_cost" @input="recalc()">
                </td>
                <td class="num text-right" x-text="`₱${formatMoney(lineTotal(line))}`"></td>
                <td><button type="button" @click="lines.splice(idx,1); recalc()" class="btn btn-danger btn-sm">✕</button></td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr><td colspan="4" class="text-right dim">Subtotal</td><td class="num text-right" x-text="`₱${formatMoney(subtotal())}`"></td><td></td></tr>
            <tr><td colspan="4" class="text-right dim">Tax</td><td class="num text-right" x-text="`₱${formatMoney(tax())}`"></td><td></td></tr>
            <tr style="background:#181c24"><td colspan="4" class="text-right" style="color:#60a5fa;font-weight:700">TOTAL</td><td class="num text-right" style="color:#60a5fa;font-weight:700;font-size:14px" x-text="`₱${formatMoney(total())}`"></td><td></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head">Actions</div>
      <div class="card-body flex flex-col gap-2">
        @php $canIssue = auth()->user()->hasRole('Admin', 'Manager'); @endphp
        @if ($canIssue)
          <button type="submit" name="action" value="issue" class="btn btn-buy" style="justify-content:center">{{ $order->exists ? 'Save and Issue' : 'Save and Issue' }}</button>
          <button type="submit" name="action" value="draft" class="btn btn-secondary" style="justify-content:center">{{ $order->exists ? 'Save Changes (keep draft)' : 'Save as Draft' }}</button>
        @else
          <button type="submit" name="action" value="draft" class="btn btn-buy" style="justify-content:center">{{ $order->exists ? 'Save Changes' : 'Save as Draft' }}</button>
        @endif
        <a href="{{ $order->exists ? route('purchase-orders.show', $order) : route('purchase-orders.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-head">Notes</div>
      <div class="card-body text-[11px]" style="color:#8892a4;line-height:1.7">
        <div>• Drafts can be edited freely.</div>
        <div>• Issuing a PO sends it to the supplier and locks it from edits.</div>
        <div>• Stock is updated only when goods are received (GRN).</div>
        <div>• Cancelling an issued PO requires a written reason.</div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
function poForm() {
  return {
    items: [], lines: [],
    init(items, lines) {
      this.items = items;
      this.lines = lines.length ? lines.map(l => ({...l})) : [];
      if (! this.lines.length) this.addLine();
    },
    addLine() {
      this.lines.push({ item_id: '', qty: 1, unit_cost: 0 });
    },
    setItem(idx, itemId) {
      const it = this.items.find(i => i.id == itemId);
      this.lines[idx].item_id = itemId;
      if (it) this.lines[idx].unit_cost = it.cost;
      this.recalc();
    },
    lineTotal(line) {
      const it = this.items.find(i => i.id == line.item_id);
      const tax = it ? it.tax : 0;
      const gross = (line.qty || 0) * (line.unit_cost || 0);
      return gross + (gross * tax / 100);
    },
    subtotal() { return this.lines.reduce((s, l) => s + ((l.qty||0) * (l.unit_cost||0)), 0); },
    tax() { return this.lines.reduce((s, l) => {
      const it = this.items.find(i => i.id == l.item_id);
      const taxRate = it ? it.tax : 0;
      return s + ((l.qty||0)*(l.unit_cost||0) * taxRate / 100);
    }, 0); },
    total() { return this.subtotal() + this.tax(); },
    recalc() {},
    formatMoney(n) { return (n || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); },
  };
}
</script>
@endpush
@endsection
