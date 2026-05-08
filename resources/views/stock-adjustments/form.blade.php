@extends('layouts.app')
@section('title', $adjustment->exists ? 'Edit Adjustment' : 'New Stock Adjustment')

@php
  $items = collect($items ?? []);
  $existingLines = collect($lines ?? []);
  $itemsJson = $items->map(fn ($i) => [
      'id' => $i->id,
      'code' => $i->item_code,
      'name' => $i->item_name,
      'uom' => $i->uom?->uom_code,
      'qty' => (float) $i->qty_on_hand,
      'cost' => (float) $i->unit_cost,
  ])->values();
  $typesJson = collect($types)->map(fn ($t) => [
      'id' => $t->id,
      'code' => $t->code,
      'name' => $t->type_name,
      'direction' => $t->direction,
  ])->values();
  $linesJson = $existingLines->map(fn ($l) => [
      'item_id' => $l->item_id,
      'qty_adjusted' => (float) $l->qty_adjusted,
      'unit_cost' => (float) $l->unit_cost,
      'notes' => $l->notes,
  ])->values();
@endphp

@section('content')
@include('partials.header', [
  'eyebrow' => 'Inventory · Stock Adjustment',
  'title' => $adjustment->exists ? 'Edit Adjustment' : 'New Stock Adjustment',
  'accent' => '#34d399',
  'subtitle' => $adjustment->exists ? $adjustment->adj_number . ' · ' . ucfirst($adjustment->status) : ($nextAdjNumber ? 'New · ' . $nextAdjNumber : null),
])

<form method="POST" action="{{ $adjustment->exists ? route('stock-adjustments.update', $adjustment) : route('stock-adjustments.store') }}"
      x-data="adjForm()"
      x-init="init({{ $itemsJson->toJson() }}, {{ $typesJson->toJson() }}, {{ $linesJson->toJson() }}, {{ (int) ($adjustment->adjustment_type_id ?? 0) }})"
      class="grid gap-3 grid-cols-1 md:grid-cols-[1fr_280px]">
  @csrf
  @if ($adjustment->exists) @method('PUT') @endif

  <div>
    <div class="card mb-3">
      <div class="card-head">Adjustment Header</div>
      <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Type <span style="color:#f87171">*</span></label>
          <select class="input" name="adjustment_type_id" x-model="typeId" @change="onTypeChange()" required>
            <option value="">— Select —</option>
            @foreach ($types as $t)
              <option value="{{ $t->id }}" @selected(old('adjustment_type_id', $adjustment->adjustment_type_id) == $t->id)>
                {{ $t->type_name }} ({{ $t->direction }})
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">Adjustment Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="adjustment_date" value="{{ old('adjustment_date', optional($adjustment->adjustment_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">ADJ #</label>
          <input class="input" value="{{ $adjustment->adj_number ?? $nextAdjNumber }}" readonly style="font-family:'IBM Plex Mono',monospace;color:#34d399">
        </div>
        <div class="sm:col-span-3">
          <label class="label block mb-1.5">Notes</label>
          <textarea class="input" name="notes" rows="2" placeholder="e.g. Annual physical count — May 2026 cutover">{{ old('notes', $adjustment->notes) }}</textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head flex items-center justify-between flex-wrap gap-2">
        <span>Line Items</span>
        <div class="flex items-center gap-3 text-[11px]" style="color:#8892a4">
          <span x-show="selectedType" x-cloak>
            Direction: <span class="font-mono" :style="`color:${dirColor()}`" x-text="selectedType?.direction"></span>
            <span x-show="selectedType?.code === 'BBAL'" class="ml-1" style="color:#38bdf8">· qty_before treated as 0</span>
          </span>
          <button type="button" @click="addLine()" class="btn btn-inv btn-sm">+ Add Item</button>
        </div>
      </div>
      <div class="card-body !p-0 overflow-x-auto">
        <table class="dt" style="font-size:12px">
          <thead><tr>
            <th>Item</th>
            <th>UoM</th>
            <th class="text-right" style="width:100px">Qty Before</th>
            <th class="text-right" style="width:110px">Qty Adjusted <span style="color:#f87171">*</span></th>
            <th class="text-right" style="width:100px">Qty After</th>
            <th class="text-right" style="width:110px">Unit Cost</th>
            <th style="width:50px"></th>
          </tr></thead>
          <tbody>
            <template x-for="(line, idx) in lines" :key="idx">
              <tr>
                <td style="min-width:200px">
                  <input type="hidden" :name="`lines[${idx}][item_id]`" :value="line.item_id">
                  <select class="input" :value="line.item_id" @change="setItem(idx, $event.target.value)">
                    <option value="">— Select item —</option>
                    <template x-for="i in items" :key="i.id">
                      <option :value="i.id" :selected="line.item_id == i.id" x-text="`${i.code} · ${i.name} · stock: ${i.qty}`"></option>
                    </template>
                  </select>
                </td>
                <td class="dim text-[11px]" x-text="itemOf(line)?.uom || '—'"></td>
                <td class="num text-right" x-text="qtyBefore(line).toFixed(2)" :style="`color:${selectedType?.code === 'BBAL' ? '#4a5568' : '#8892a4'}`"></td>
                <td>
                  <input class="input text-right" type="number" step="0.01" min="0.01"
                         :name="`lines[${idx}][qty_adjusted]`"
                         x-model.number="line.qty_adjusted">
                </td>
                <td class="num text-right" x-text="qtyAfter(line).toFixed(2)"
                    :style="`color:${qtyAfter(line) < 0 ? '#f87171' : (selectedType?.direction === 'OUT' ? '#fbbf24' : '#34d399')};font-weight:600`"></td>
                <td>
                  <input class="input text-right" type="number" step="0.0001" min="0"
                         :name="`lines[${idx}][unit_cost]`"
                         x-model.number="line.unit_cost">
                </td>
                <td><button type="button" @click="lines.splice(idx,1)" class="btn btn-danger btn-sm">✕</button></td>
              </tr>
            </template>
            <tr x-show="lines.length === 0">
              <td colspan="7" class="dim text-center py-4">Click <strong>+ Add Item</strong> to start adding lines.</td>
            </tr>
          </tbody>
          <tfoot x-show="lines.length > 0">
            <tr style="background:#181c24">
              <td colspan="2" class="text-right dim">Total qty change</td>
              <td></td>
              <td class="num text-right" x-text="totalAdjusted().toFixed(2)" :style="`color:${dirColor()};font-weight:700`"></td>
              <td class="num text-right" x-text="totalAfter().toFixed(2)" style="color:#34d399;font-weight:700"></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <template x-if="hasNegative()">
        <div class="px-4 py-2 text-[11.5px]" style="background:rgba(248,113,113,.08);color:#f87171;border-top:1px solid rgba(248,113,113,.2)">
          ⚠ One or more lines would result in negative stock. Posting will be blocked.
        </div>
      </template>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head">Actions</div>
      <div class="card-body flex flex-col gap-2">
        @php $canPost = auth()->user()->hasRole('Admin', 'Manager'); @endphp
        @if ($canPost)
          <button type="submit" name="action" value="post" class="btn btn-ok" style="justify-content:center" :disabled="hasNegative() || lines.length === 0">Save and Post</button>
          <button type="submit" name="action" value="draft" class="btn btn-secondary" style="justify-content:center">{{ $adjustment->exists ? 'Save Changes (keep draft)' : 'Save as Draft' }}</button>
        @else
          <button type="submit" name="action" value="draft" class="btn btn-inv" style="justify-content:center">{{ $adjustment->exists ? 'Save Changes' : 'Save as Draft' }}</button>
        @endif
        <a href="{{ $adjustment->exists ? route('stock-adjustments.show', $adjustment) : route('stock-adjustments.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-head">How it works</div>
      <div class="card-body text-[11px]" style="color:#8892a4;line-height:1.7">
        <div>• <strong style="color:#38bdf8">Beginning Balance</strong> — sets absolute starting qty. Use only for cutover / first-time setup.</div>
        <div>• <strong style="color:#34d399">Adjustment In</strong> — adds to current stock (found stock, internal returns).</div>
        <div>• <strong style="color:#f87171">Adjustment Out</strong> — removes from current stock (samples, internal use).</div>
        <div>• <strong style="color:#fbbf24">Write-off</strong> — removes damaged/expired stock as a loss.</div>
        <div class="mt-2">Posting requires Admin or Manager and is logged with timestamp + user. Posted adjustments can be voided (reverses qty changes).</div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
function adjForm() {
  return {
    items: [], types: [], lines: [], typeId: '',
    init(items, types, lines, currentTypeId) {
      this.items = items;
      this.types = types;
      this.typeId = currentTypeId || '';
      this.lines = lines.length ? lines.map(l => ({...l})) : [];
    },
    get selectedType() {
      return this.types.find(t => t.id == this.typeId) || null;
    },
    onTypeChange() {
      // Recompute happens reactively via qtyBefore/qtyAfter functions
    },
    addLine() {
      this.lines.push({ item_id: '', qty_adjusted: 0, unit_cost: 0, notes: '' });
    },
    setItem(idx, itemId) {
      const it = this.items.find(i => i.id == itemId);
      this.lines[idx].item_id = itemId;
      if (it) this.lines[idx].unit_cost = it.cost;
    },
    itemOf(line) {
      return this.items.find(i => i.id == line.item_id) || null;
    },
    qtyBefore(line) {
      const t = this.selectedType;
      if (t && t.code === 'BBAL') return 0;
      const it = this.itemOf(line);
      return it ? Number(it.qty) : 0;
    },
    qtyAfter(line) {
      const t = this.selectedType;
      const before = this.qtyBefore(line);
      const adj = Number(line.qty_adjusted || 0);
      if (! t) return before;
      if (t.code === 'BBAL') return adj;
      if (t.direction === 'OUT') return before - adj;
      return before + adj;
    },
    totalAdjusted() {
      return this.lines.reduce((s, l) => s + Number(l.qty_adjusted || 0), 0);
    },
    totalAfter() {
      return this.lines.reduce((s, l) => s + this.qtyAfter(l), 0);
    },
    hasNegative() {
      return this.lines.some(l => l.item_id && this.qtyAfter(l) < 0);
    },
    dirColor() {
      const t = this.selectedType;
      if (! t) return '#8892a4';
      if (t.code === 'BBAL') return '#38bdf8';
      return t.direction === 'OUT' ? '#f87171' : '#34d399';
    },
  };
}
</script>
@endpush
@endsection
