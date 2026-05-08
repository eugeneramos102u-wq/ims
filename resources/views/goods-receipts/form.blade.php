@extends('layouts.app')
@section('title', 'Receive Stock — ' . $po->po_number)

@php
  $linesJson = $lines->values();
@endphp

@section('content')
@include('partials.header', [
  'eyebrow' => 'Purchasing · Stock Receiving',
  'title' => 'Receive Stock — ' . $po->po_number,
  'accent' => '#60a5fa',
  'subtitle' => 'SR # ' . $nextGrnNumber . ' · ' . $po->supplier->company_name,
])

<form method="POST" action="{{ route('stock-receiving.store-from-po', $po) }}"
      x-data="grnForm()"
      x-init="init({{ $linesJson->toJson() }})"
      class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf

  <div>
    <div class="card mb-3">
      <div class="card-head">Stock Receipt Header</div>
      <div class="card-body grid grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Received Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="received_date" value="{{ old('received_date', now()->toDateString()) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Delivery Note #</label>
          <input class="input" name="delivery_note_no" value="{{ old('delivery_note_no') }}" placeholder="From supplier's delivery slip">
        </div>
        <div>
          <label class="label block mb-1.5">Carrier</label>
          <input class="input" name="carrier" value="{{ old('carrier') }}" placeholder="e.g. LBC, JRS, own truck">
        </div>
        <div class="col-span-3">
          <label class="label block mb-1.5">Remarks</label>
          <textarea class="input" name="remarks" rows="2">{{ old('remarks') }}</textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head flex items-center justify-between">
        <span>Lines to Receive</span>
        <button type="button" @click="fillAllOutstanding()" class="btn btn-secondary btn-sm">Fill all outstanding</button>
      </div>
      <div class="card-body !p-0">
        <table class="dt" style="font-size:12px">
          <thead><tr>
            <th>Item</th><th>UoM</th>
            <th class="text-right">Ordered</th>
            <th class="text-right">Already Recv.</th>
            <th class="text-right">Outstanding</th>
            <th class="text-right">Receive Now</th>
            <th class="text-right" style="color:#34d399">Accept</th>
            <th class="text-right" style="color:#f87171">Reject</th>
            <th class="text-right">Unit Cost</th>
          </tr></thead>
          <tbody>
            <template x-for="(line, idx) in lines" :key="line.po_item_id">
              <tr>
                <td>
                  <input type="hidden" :name="`lines[${idx}][po_item_id]`" :value="line.po_item_id">
                  <span class="ref" x-text="line.item_code"></span>
                  <span class="ml-2" x-text="line.item_name"></span>
                </td>
                <td class="dim" x-text="line.uom"></td>
                <td class="num text-right" x-text="line.qty_ordered.toFixed(2)"></td>
                <td class="num text-right dim" x-text="line.qty_received.toFixed(2)"></td>
                <td class="num text-right" :style="line.outstanding > 0 ? 'color:#fbbf24' : 'color:#8892a4'" x-text="line.outstanding.toFixed(2)"></td>
                <td>
                  <input class="input text-right" type="number" step="0.01" min="0" :max="line.outstanding"
                         :name="`lines[${idx}][qty_received]`" x-model.number="line.qty_received_now" @input="autoSplit(idx)">
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.01" min="0"
                         :name="`lines[${idx}][qty_accepted]`" x-model.number="line.qty_accepted" @input="recalcRej(idx)" style="border-color:rgba(52,211,153,.3)">
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.01" min="0"
                         :name="`lines[${idx}][qty_rejected]`" x-model.number="line.qty_rejected" @input="recalcAcc(idx)" style="border-color:rgba(248,113,113,.3)">
                </td>
                <td>
                  <input class="input text-right" type="number" step="0.0001" min="0"
                         :name="`lines[${idx}][unit_cost]`" x-model.number="line.unit_cost">
                </td>
              </tr>
            </template>
            <template x-for="(line, idx) in lines" :key="`reason-${line.po_item_id}`">
              <tr x-show="line.qty_rejected > 0" style="background:rgba(248,113,113,.04)">
                <td colspan="9">
                  <label class="label block mb-1" style="color:#f87171">Rejection reason for <span x-text="line.item_code"></span> <span style="color:#f87171">*</span></label>
                  <input class="input" type="text" :name="`lines[${idx}][rejection_reason]`" x-model="line.rejection_reason"
                         placeholder="e.g. damaged in transit, wrong specification, expired"
                         :required="line.qty_rejected > 0">
                </td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr style="background:#181c24">
              <td colspan="5" class="text-right dim">Total this GRN</td>
              <td class="num text-right" x-text="totalRecv().toFixed(2)"></td>
              <td class="num text-right" style="color:#34d399;font-weight:600" x-text="totalAcc().toFixed(2)"></td>
              <td class="num text-right" style="color:#f87171;font-weight:600" x-text="totalRej().toFixed(2)"></td>
              <td></td>
            </tr>
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
          <button type="submit" name="action" value="confirm" class="btn btn-ok" style="justify-content:center">Save and Confirm (Stock IN)</button>
          <button type="submit" name="action" value="draft" class="btn btn-secondary" style="justify-content:center">Save as Draft</button>
        @else
          <button type="submit" name="action" value="draft" class="btn btn-buy" style="justify-content:center">Save as Draft</button>
        @endif
        <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-head">PO Reference</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div class="font-semibold">{{ $po->po_number }}</div>
        <div class="dim">{{ $po->supplier->company_name }}</div>
        <div class="dim">Ordered {{ $po->order_date->format('M j, Y') }}</div>
        @if ($po->expected_date)
          <div class="dim">Expected {{ $po->expected_date->format('M j, Y') }}</div>
        @endif
        <div class="mt-2 font-mono" style="color:#60a5fa">PO total: ₱{{ number_format($po->total_amount, 2) }}</div>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-head">Workflow</div>
      <div class="card-body text-[11px]" style="color:#8892a4;line-height:1.7">
        <div>1. Enter qty received per line.</div>
        <div>2. Split into accepted / rejected (must sum to received).</div>
        <div>3. Reason required if any qty rejected.</div>
        <div>4. Save as draft → review.</div>
        <div>5. Confirm → <strong style="color:#34d399">stock IN</strong> + unit_cost updated.</div>
        <div>6. Confirmed stock receipts are immutable.</div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
function grnForm() {
  return {
    lines: [],
    init(rawLines) {
      this.lines = rawLines.map(l => ({
        ...l,
        qty_received_now: 0,
        qty_accepted: 0,
        qty_rejected: 0,
        rejection_reason: '',
      }));
    },
    fillAllOutstanding() {
      this.lines.forEach(l => {
        l.qty_received_now = l.outstanding;
        l.qty_accepted = l.outstanding;
        l.qty_rejected = 0;
      });
    },
    autoSplit(idx) {
      // Default: when user types qty_received, set accepted = received, rejected = 0
      const l = this.lines[idx];
      l.qty_accepted = l.qty_received_now || 0;
      l.qty_rejected = 0;
    },
    recalcRej(idx) {
      const l = this.lines[idx];
      l.qty_rejected = Math.max(0, (l.qty_received_now || 0) - (l.qty_accepted || 0));
    },
    recalcAcc(idx) {
      const l = this.lines[idx];
      l.qty_accepted = Math.max(0, (l.qty_received_now || 0) - (l.qty_rejected || 0));
    },
    totalRecv() { return this.lines.reduce((s, l) => s + (+l.qty_received_now || 0), 0); },
    totalAcc()  { return this.lines.reduce((s, l) => s + (+l.qty_accepted || 0), 0); },
    totalRej()  { return this.lines.reduce((s, l) => s + (+l.qty_rejected || 0), 0); },
  };
}
</script>
@endpush
@endsection
