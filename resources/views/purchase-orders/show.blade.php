@extends('layouts.app')
@section('title', $order->po_number)

@section('content')
@include('partials.header', [
  'eyebrow' => 'Purchasing · Purchase Orders',
  'title' => $order->po_number,
  'accent' => '#60a5fa',
  'subtitle' => $order->supplier->company_name . ' · ' . $order->order_date->format('M j, Y'),
])

<div class="flex items-center gap-2 mb-3.5">
  <span class="badge b-{{ $order->status }}" style="font-size:11px;padding:4px 10px">{{ $order->status }}</span>
  <div class="flex-1"></div>

  @if ($order->isEditable() && auth()->user()->hasRole('Admin','Manager','Cashier'))
    <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-secondary btn-sm">Edit</a>
  @endif
  @if ($order->isIssuable() && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('purchase-orders.issue', $order) }}" class="inline" onsubmit="return confirm('Issue this PO to the supplier? It cannot be edited after this.');">
      @csrf
      <button type="submit" class="btn btn-buy btn-sm">Issue to Supplier</button>
    </form>
  @endif
  @if ($order->isReceivable() && auth()->user()->hasRole('Admin','Manager','Cashier'))
    <a href="{{ route('stock-receiving.create-from-po', $order) }}" class="btn btn-ok btn-sm">+ Receive Stock</a>
  @endif
  @if ($order->isCancellable() && auth()->user()->hasRole('Admin','Manager'))
    <button type="button" onclick="document.getElementById('cancel-modal').style.display='flex'" class="btn btn-danger btn-sm">Cancel PO</button>
  @endif
</div>

<div class="grid gap-3" style="grid-template-columns:1fr 280px">
  <div class="card">
    <div class="card-head">Line Items</div>
    <div class="card-body !p-0">
      <table class="dt">
        <thead><tr>
          <th>Item</th><th>UoM</th>
          <th class="text-right">Qty Ordered</th><th class="text-right">Received</th><th class="text-right">Outstanding</th>
          <th class="text-right">Unit Cost</th><th class="text-right">Tax %</th>
          <th class="text-right">Line Total</th>
        </tr></thead>
        <tbody>
          @foreach ($order->items as $line)
            @php $out = $line->qtyOutstanding(); @endphp
            <tr>
              <td>
                <span class="ref">{{ $line->item->item_code }}</span>
                <span class="ml-2">{{ $line->item->item_name }}</span>
              </td>
              <td class="dim">{{ $line->item->uom->uom_code }}</td>
              <td class="num text-right">{{ number_format($line->qty_ordered, 2) }}</td>
              <td class="num text-right" style="color:{{ $line->qty_received > 0 ? '#34d399' : '#8892a4' }}">{{ number_format($line->qty_received, 2) }}</td>
              <td class="num text-right" style="color:{{ $out > 0 ? '#fbbf24' : '#8892a4' }}">{{ number_format($out, 2) }}</td>
              <td class="num text-right">₱{{ number_format($line->unit_cost, 4) }}</td>
              <td class="num text-right">{{ number_format($line->tax_rate_pct, 2) }}%</td>
              <td class="num text-right">₱{{ number_format($line->line_total, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr><td colspan="7" class="text-right dim">Subtotal</td><td class="num text-right">₱{{ number_format($order->subtotal, 2) }}</td></tr>
          <tr><td colspan="7" class="text-right dim">Tax</td><td class="num text-right">₱{{ number_format($order->tax_amount, 2) }}</td></tr>
          <tr style="background:#181c24"><td colspan="7" class="text-right" style="color:#60a5fa;font-weight:700">TOTAL</td><td class="num text-right" style="color:#60a5fa;font-weight:700">₱{{ number_format($order->total_amount, 2) }}</td></tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div>
    <div class="card mb-3">
      <div class="card-head">Supplier</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div class="font-semibold">{{ $order->supplier->company_name }}</div>
        <div class="dim">{{ $order->supplier->contact_person ?: '—' }}</div>
        <div class="dim">{{ $order->supplier->phone ?: '—' }}</div>
        <div class="dim">{{ $order->supplier->email ?: '—' }}</div>
        @if ($order->supplier->bank_name)
          <div class="mt-2">
            <span class="dim">Bank:</span>
            <span class="font-mono text-[11px]" style="color:#38bdf8">{{ $order->supplier->bank_name }} ••••{{ substr($order->supplier->bank_account ?? '', -4) }}</span>
          </div>
        @endif
        <div class="mt-1">
          <span class="dim">Term:</span> {{ $order->paymentTerm?->term_name ?? $order->supplier->paymentTerm?->term_name ?? 'Default' }}
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head">Audit</div>
      <div class="card-body text-[11px]" style="line-height:1.7;color:#8892a4">
        <div>Created by <span style="color:#e2e8f4">{{ $order->creator?->full_name }}</span></div>
        <div>Created {{ $order->created_at->format('M j, Y g:i a') }}</div>
        @if ($order->issued_at)
          <div class="mt-2">Issued by <span style="color:#e2e8f4">{{ $order->issuer?->full_name }}</span></div>
          <div>{{ $order->issued_at->format('M j, Y g:i a') }}</div>
        @endif
        @if ($order->cancelled_at)
          <div class="mt-2" style="color:#f87171">Cancelled {{ $order->cancelled_at->format('M j, Y g:i a') }}</div>
          @if ($order->cancel_reason)
            <div class="text-[11px] mt-1" style="color:#f87171">Reason: {{ $order->cancel_reason }}</div>
          @endif
        @endif
        @if ($order->expected_date)
          <div class="mt-2"><span class="dim">Expected:</span> {{ $order->expected_date->format('M j, Y') }}</div>
        @endif
      </div>
    </div>
  </div>
</div>

@if ($order->goodsReceipts->count())
  <div class="mt-5">
    <div class="card">
      <div class="card-head">Stock Receipts ({{ $order->goodsReceipts->count() }})</div>
      <table class="dt">
        <thead><tr><th>SR #</th><th>Date</th><th>DN #</th><th>Received By</th><th class="text-right">Accepted</th><th class="text-right">Rejected</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach ($order->goodsReceipts->load('items') as $g)
            <tr>
              <td class="ref">{{ $g->grn_number }}</td>
              <td class="dim">{{ $g->received_date->format('M j, Y') }}</td>
              <td class="dim font-mono text-[11px]">{{ $g->delivery_note_no ?: '—' }}</td>
              <td class="dim">{{ $g->receiver?->full_name }}</td>
              <td class="num text-right" style="color:#34d399">{{ number_format($g->items->sum('qty_accepted'), 2) }}</td>
              <td class="num text-right" style="color:{{ $g->items->sum('qty_rejected') > 0 ? '#f87171' : '#8892a4' }}">{{ number_format($g->items->sum('qty_rejected'), 2) }}</td>
              <td><span class="badge b-{{ $g->status }}">{{ $g->status }}</span></td>
              <td><a href="{{ route('stock-receiving.show', $g) }}" class="btn btn-secondary btn-sm">View</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

@if ($order->isCancellable() && auth()->user()->hasRole('Admin','Manager'))
  <div id="cancel-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#181c24; border:1px solid rgba(255,255,255,.12); border-radius:10px; max-width:500px; width:100%;">
      <div style="padding:16px 20px; border-bottom:1px solid rgba(255,255,255,.07); font-size:14px; font-weight:700;">
        Cancel Purchase Order
      </div>
      <form method="POST" action="{{ route('purchase-orders.cancel', $order) }}">
        @csrf
        <div style="padding:20px;">
          <div class="text-xs mb-3" style="color:#8892a4">A reason is required when cancelling. This action cannot be undone.</div>
          <label class="label block mb-1.5">Cancel Reason <span style="color:#f87171">*</span></label>
          <textarea class="input" name="cancel_reason" rows="3" required minlength="5" placeholder="e.g. Supplier discontinued the item; ordered in error; etc."></textarea>
        </div>
        <div style="padding:12px 20px; border-top:1px solid rgba(255,255,255,.07); display:flex; gap:8px; justify-content:flex-end;">
          <button type="button" onclick="document.getElementById('cancel-modal').style.display='none'" class="btn btn-secondary btn-sm">Back</button>
          <button type="submit" class="btn btn-danger btn-sm">Cancel PO</button>
        </div>
      </form>
    </div>
  </div>
@endif
@endsection
