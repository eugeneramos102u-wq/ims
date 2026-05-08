@extends('layouts.app')
@section('title', $order->exists ? 'Edit SO' : 'New Sales Order')

@php
  $items = collect($items ?? []);
  $existingLines = collect($orderItems ?? []);
  $itemsJson = $items->map(fn ($i) => [
      'id' => $i->id,
      'code' => $i->item_code,
      'name' => $i->item_name,
      'uom' => $i->uom?->uom_code,
      'qty' => (float) $i->qty_on_hand,
      'wholesale' => (float) $i->wholesale_price,
      'retail' => (float) $i->retail_price,
      'tax' => (float) $i->tax_rate_pct,
  ])->values();
  $linesJson = $existingLines->map(fn ($l) => [
      'item_id' => $l->item_id,
      'qty' => (float) $l->qty_ordered,
      'unit_price' => (float) $l->unit_price,
      'discount_pct' => (float) $l->discount_pct,
  ])->values();
@endphp

@section('content')
@include('partials.header', [
  'eyebrow' => 'Sales · Sales Orders',
  'title' => $order->exists ? 'Edit Sales Order' : 'New Sales Order',
  'accent' => '#fb923c',
  'subtitle' => $order->exists ? $order->so_number . ' · ' . ucfirst($order->status) : ($nextSoNumber ? 'New · SO # ' . $nextSoNumber : null),
])

<form method="POST" action="{{ $order->exists ? route('sales-orders.update', $order) : route('sales-orders.store') }}"
      x-data="soForm()"
      x-init="init({{ $itemsJson->toJson() }}, {{ $linesJson->toJson() }})"
      class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf
  @if ($order->exists) @method('PUT') @endif

  <div>
    <div class="card mb-3">
      <div class="card-head">SO Header</div>
      <div class="card-body grid grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Customer <span style="color:#f87171">*</span></label>
          <select class="input" name="customer_id" required>
            <option value="">— Select —</option>
            @foreach ($customers as $c)
              <option value="{{ $c->id }}" @selected(old('customer_id', $order->customer_id) == $c->id)>{{ $c->company_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">Price Type <span style="color:#f87171">*</span></label>
          <select class="input" name="price_type" x-model="priceType" @change="repriceLines()">
            <option value="retail">Retail</option>
            <option value="wholesale">Wholesale</option>
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">DR Number</label>
          <input class="input" name="dr_number" value="{{ old('dr_number', $order->dr_number) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Order Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="order_date" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Required Date</label>
          <input class="input" type="date" name="required_date" value="{{ old('required_date', optional($order->required_date)->format('Y-m-d')) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Notes</label>
          <input class="input" name="notes" value="{{ old('notes', $order->notes) }}">
        </div>
        <div class="col-span-3">
          <label class="label block mb-1.5">Shipping Address</label>
          <textarea class="input" name="shipping_address" rows="2">{{ old('shipping_address', $order->shipping_address) }}</textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head flex items-center justify-between">
        <span>Line Items</span>
        <button type="button" @click="addLine()" class="btn btn-sell btn-sm">+ Add Line</button>
      </div>
      <div class="card-body !p-0">
        <table class="dt" style="font-size:12px">
          <thead><tr>
            <th style="width:40px">#</th>
            <th>Item</th>
            <th class="text-right" style="width:100px">Qty</th>
            <th class="text-right" style="width:120px">Unit Price</th>
            <th class="text-right" style="width:90px">Disc %</th>
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
                      <option :value="i.id" :selected="line.item_id == i.id" x-text="`${i.code} · ${i.name} (${i.uom}) · stock: ${i.qty}`"></option>
                    </template>
                  </select>
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.01" min="0.01" :name="`lines[${idx}][qty]`" x-model.number="line.qty" @input="recalc()">
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.0001" min="0" :name="`lines[${idx}][unit_price]`" x-model.number="line.unit_price" @input="recalc()">
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.01" min="0" max="100" :name="`lines[${idx}][discount_pct]`" x-model.number="line.discount_pct" @input="recalc()">
                </td>
                <td class="num text-right" x-text="`₱${formatMoney(lineTotal(line))}`"></td>
                <td><button type="button" @click="lines.splice(idx,1); recalc()" class="btn btn-danger btn-sm">✕</button></td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr><td colspan="5" class="text-right dim">Subtotal</td><td class="num text-right" x-text="`₱${formatMoney(subtotal())}`"></td><td></td></tr>
            <tr><td colspan="5" class="text-right dim">Discount</td><td class="num text-right" x-text="`₱${formatMoney(discount())}`"></td><td></td></tr>
            <tr><td colspan="5" class="text-right dim">Tax</td><td class="num text-right" x-text="`₱${formatMoney(tax())}`"></td><td></td></tr>
            <tr style="background:#181c24"><td colspan="5" class="text-right" style="color:#fb923c;font-weight:700">TOTAL</td><td class="num text-right" style="color:#fb923c;font-weight:700;font-size:14px" x-text="`₱${formatMoney(total())}`"></td><td></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head">Actions</div>
      <div class="card-body flex flex-col gap-2">
        @php $canConfirm = auth()->user()->hasRole('Admin', 'Manager'); @endphp
        @if ($canConfirm)
          <button type="submit" name="action" value="confirm" class="btn btn-ok" style="justify-content:center">Save and Confirm (Stock OUT)</button>
          <button type="submit" name="action" value="draft" class="btn btn-secondary" style="justify-content:center">{{ $order->exists ? 'Save Changes (keep draft)' : 'Save as Draft' }}</button>
        @else
          <button type="submit" name="action" value="draft" class="btn btn-sell" style="justify-content:center">{{ $order->exists ? 'Save Changes' : 'Save as Draft' }}</button>
        @endif
        <a href="{{ $order->exists ? route('sales-orders.show', $order) : route('sales-orders.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-head">Notes</div>
      <div class="card-body text-[11px]" style="color:#8892a4;line-height:1.7">
        <div>• Confirming the SO deducts stock immediately (Stock OUT).</div>
        <div>• Drafts can be edited freely.</div>
        <div>• A confirmed SO cannot be edited — cancel & re-create.</div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
function soForm() {
  return {
    items: [], lines: [], priceType: '{{ old('price_type', $order->price_type ?? 'retail') }}',
    init(items, lines) {
      this.items = items;
      this.lines = lines.length ? lines.map(l => ({...l})) : [];
      if (! this.lines.length) this.addLine();
    },
    addLine() {
      this.lines.push({ item_id: '', qty: 1, unit_price: 0, discount_pct: 0 });
    },
    setItem(idx, itemId) {
      const it = this.items.find(i => i.id == itemId);
      this.lines[idx].item_id = itemId;
      if (it) this.lines[idx].unit_price = this.priceType === 'wholesale' ? it.wholesale : it.retail;
      this.recalc();
    },
    repriceLines() {
      this.lines.forEach((l, i) => {
        const it = this.items.find(x => x.id == l.item_id);
        if (it) l.unit_price = this.priceType === 'wholesale' ? it.wholesale : it.retail;
      });
    },
    lineTotal(line) {
      const it = this.items.find(i => i.id == line.item_id);
      const tax = it ? it.tax : 0;
      const gross = (line.qty || 0) * (line.unit_price || 0);
      const disc = gross * ((line.discount_pct || 0) / 100);
      const after = gross - disc;
      return after + (after * tax / 100);
    },
    subtotal() { return this.lines.reduce((s, l) => s + ((l.qty||0) * (l.unit_price||0)), 0); },
    discount() { return this.lines.reduce((s, l) => s + ((l.qty||0)*(l.unit_price||0)*((l.discount_pct||0)/100)), 0); },
    tax() { return this.lines.reduce((s, l) => {
      const it = this.items.find(i => i.id == l.item_id);
      const taxRate = it ? it.tax : 0;
      const after = (l.qty||0)*(l.unit_price||0) - (l.qty||0)*(l.unit_price||0)*((l.discount_pct||0)/100);
      return s + after * taxRate / 100;
    }, 0); },
    total() { return this.subtotal() - this.discount() + this.tax(); },
    recalc() {},
    formatMoney(n) { return (n || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}); },
  };
}
</script>
@endpush
@endsection
