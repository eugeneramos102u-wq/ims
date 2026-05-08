@extends('layouts.app')
@section('title', $grn->grn_number)

@section('content')
@include('partials.header', [
  'eyebrow' => 'Purchasing · Stock Receiving',
  'title' => $grn->grn_number,
  'accent' => '#60a5fa',
  'subtitle' => 'PO ' . $grn->purchaseOrder->po_number . ' · ' . $grn->supplier->company_name . ' · ' . $grn->received_date->format('M j, Y'),
])

<div class="flex items-center gap-2 mb-3.5">
  <span class="badge b-{{ $grn->status }}" style="font-size:11px;padding:4px 10px">{{ $grn->status }}</span>
  <div class="flex-1"></div>

  @if ($grn->isConfirmable() && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('stock-receiving.confirm', $grn) }}" class="inline" onsubmit="return confirm('Confirm stock receipt? This will increase qty_on_hand by qty_accepted and update unit_cost. Confirmed receipts cannot be edited.');">
      @csrf
      <button type="submit" class="btn btn-ok btn-sm">Confirm (Stock IN)</button>
    </form>
  @endif
  @if ($grn->isCancellable() && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('stock-receiving.cancel', $grn) }}" class="inline" onsubmit="return confirm('Cancel this draft stock receipt?');">
      @csrf
      <button type="submit" class="btn btn-danger btn-sm">Cancel Draft</button>
    </form>
  @endif
</div>

<div class="grid gap-3" style="grid-template-columns:1fr 280px">
  <div class="card">
    <div class="card-head">Receipt Lines</div>
    <div class="card-body !p-0">
      <table class="dt">
        <thead><tr>
          <th>Item</th><th>UoM</th>
          <th class="text-right">Received</th>
          <th class="text-right" style="color:#34d399">Accepted</th>
          <th class="text-right" style="color:#f87171">Rejected</th>
          <th>Rejection Reason</th>
          <th class="text-right">Unit Cost</th>
        </tr></thead>
        <tbody>
          @foreach ($grn->items as $line)
            <tr>
              <td>
                <span class="ref">{{ $line->item->item_code }}</span>
                <span class="ml-2">{{ $line->item->item_name }}</span>
              </td>
              <td class="dim">{{ $line->item->uom->uom_code }}</td>
              <td class="num text-right">{{ number_format($line->qty_received, 2) }}</td>
              <td class="num text-right" style="color:#34d399;font-weight:600">{{ number_format($line->qty_accepted, 2) }}</td>
              <td class="num text-right" style="color:{{ $line->qty_rejected > 0 ? '#f87171' : '#8892a4' }};font-weight:{{ $line->qty_rejected > 0 ? 600 : 400 }}">{{ number_format($line->qty_rejected, 2) }}</td>
              <td class="dim text-[11.5px]">{{ $line->rejection_reason ?: '—' }}</td>
              <td class="num text-right">₱{{ number_format($line->unit_cost, 4) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr style="background:#181c24">
            <td colspan="2" class="text-right dim">Totals</td>
            <td class="num text-right">{{ number_format($grn->items->sum('qty_received'), 2) }}</td>
            <td class="num text-right" style="color:#34d399;font-weight:700">{{ number_format($grn->totalAccepted(), 2) }}</td>
            <td class="num text-right" style="color:#f87171;font-weight:700">{{ number_format($grn->totalRejected(), 2) }}</td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div>
    <div class="card mb-3">
      <div class="card-head">PO Reference</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div><a class="ref" href="{{ route('purchase-orders.show', $grn->purchaseOrder) }}">{{ $grn->purchaseOrder->po_number }}</a></div>
        <div class="dim">{{ $grn->supplier->company_name }}</div>
        <div class="dim">PO status: <span class="badge b-{{ $grn->purchaseOrder->status }}">{{ $grn->purchaseOrder->status }}</span></div>
        @if ($grn->delivery_note_no)
          <div class="mt-2"><span class="dim">DN #:</span> <span class="font-mono">{{ $grn->delivery_note_no }}</span></div>
        @endif
        @if ($grn->carrier)
          <div class="dim">Carrier: {{ $grn->carrier }}</div>
        @endif
      </div>
    </div>

    @if ($grn->status === 'confirmed')
      <div class="alert-ok mb-3" style="font-size:11.5px;line-height:1.6">
        ✓ Stock IN posted on {{ $grn->confirmed_at?->format('M j, Y g:i a') }}.<br>
        Items received: <strong>{{ number_format($grn->totalAccepted(), 2) }}</strong> units accepted into inventory.
      </div>
    @endif

    <div class="card">
      <div class="card-head">Audit</div>
      <div class="card-body text-[11px]" style="line-height:1.7;color:#8892a4">
        <div>Received by <span style="color:#e2e8f4">{{ $grn->receiver?->full_name }}</span></div>
        <div>{{ $grn->created_at->format('M j, Y g:i a') }}</div>
        @if ($grn->confirmed_at)
          <div class="mt-2">Confirmed by <span style="color:#e2e8f4">{{ $grn->confirmer?->full_name }}</span></div>
          <div>{{ $grn->confirmed_at->format('M j, Y g:i a') }}</div>
        @endif
        @if ($grn->cancelled_at)
          <div class="mt-2" style="color:#f87171">Cancelled {{ $grn->cancelled_at->format('M j, Y g:i a') }}</div>
          @if ($grn->cancel_reason)
            <div style="color:#f87171">{{ $grn->cancel_reason }}</div>
          @endif
        @endif
        @if ($grn->remarks)
          <div class="mt-2"><span class="dim">Remarks:</span><br>{{ $grn->remarks }}</div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
