@extends('layouts.app')
@section('title', $order->so_number)

@section('content')
@include('partials.header', [
  'eyebrow' => 'Sales · Sales Orders',
  'title' => $order->so_number,
  'accent' => '#fb923c',
  'subtitle' => $order->customer->company_name . ' · ' . $order->order_date->format('M j, Y'),
])

<div class="flex items-center gap-2 mb-3.5">
  <span class="badge b-{{ $order->status }}" style="font-size:11px;padding:4px 10px">{{ $order->status }}</span>
  <div class="flex-1"></div>

  @if ($order->isEditable() && auth()->user()->hasRole('Admin','Manager','Cashier'))
    <a href="{{ route('sales-orders.edit', $order) }}" class="btn btn-secondary btn-sm">Edit</a>
  @endif
  @if ($order->isConfirmable() && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('sales-orders.confirm', $order) }}" class="inline" onsubmit="return confirm('Confirm SO? This will deduct stock from inventory.');">
      @csrf
      <button type="submit" class="btn btn-warn btn-sm">Confirm (Stock OUT)</button>
    </form>
  @endif
  @if ($order->isInvoiceable() && auth()->user()->hasRole('Admin','Manager','Cashier'))
    <a href="{{ route('invoices.create-from-so', $order) }}" class="btn btn-sell btn-sm">+ Create Invoice</a>
  @endif
  @if (in_array($order->status, ['draft','confirmed']) && auth()->user()->hasRole('Admin','Manager') && ! $order->invoices->count())
    <form method="POST" action="{{ route('sales-orders.cancel', $order) }}" class="inline" onsubmit="return confirm('Cancel SO?');">
      @csrf
      <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
    </form>
  @endif
</div>

<div class="grid gap-3" style="grid-template-columns:1fr 280px">
  <div class="card">
    <div class="card-head">Line Items · {{ ucfirst($order->price_type) }} pricing</div>
    <div class="card-body !p-0">
      <table class="dt">
        <thead><tr>
          <th>Item</th><th>UoM</th>
          <th class="text-right">Qty</th><th class="text-right">Invoiced</th>
          <th class="text-right">Unit Price</th><th class="text-right">Disc %</th>
          <th class="text-right">Line Total</th>
        </tr></thead>
        <tbody>
          @foreach ($order->items as $line)
            <tr>
              <td>
                <span class="ref">{{ $line->item->item_code }}</span>
                <span class="ml-2">{{ $line->item->item_name }}</span>
              </td>
              <td class="dim">{{ $line->item->uom->uom_code }}</td>
              <td class="num text-right">{{ number_format($line->qty_ordered, 2) }}</td>
              <td class="num text-right" style="color:{{ $line->qty_invoiced > 0 ? '#34d399' : '#8892a4' }}">{{ number_format($line->qty_invoiced, 2) }}</td>
              <td class="num text-right">₱{{ number_format($line->unit_price, 4) }}</td>
              <td class="num text-right">{{ number_format($line->discount_pct, 2) }}%</td>
              <td class="num text-right">₱{{ number_format($line->line_total, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr><td colspan="6" class="text-right dim">Subtotal</td><td class="num text-right">₱{{ number_format($order->subtotal, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right dim">Discount</td><td class="num text-right">₱{{ number_format($order->discount_amount, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right dim">Tax</td><td class="num text-right">₱{{ number_format($order->tax_amount, 2) }}</td></tr>
          <tr style="background:#181c24"><td colspan="6" class="text-right" style="color:#fb923c;font-weight:700">TOTAL</td><td class="num text-right" style="color:#fb923c;font-weight:700">₱{{ number_format($order->total_amount, 2) }}</td></tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div>
    <div class="card mb-3">
      <div class="card-head">Customer</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div class="font-semibold">{{ $order->customer->company_name }}</div>
        <div class="dim">{{ $order->customer->contact_person ?: '—' }}</div>
        <div class="dim">{{ $order->customer->phone ?: '—' }}</div>
        <div class="dim">{{ $order->customer->email ?: '—' }}</div>
      </div>
    </div>

    @if ($order->salesperson)
      <div class="card mb-3">
        <div class="card-head">Salesperson</div>
        <div class="card-body text-[12.5px]" style="line-height:1.7">
          <div class="font-semibold">{{ $order->salesperson->full_name }}</div>
          <div class="dim">Rate: {{ number_format($order->commission_rate_pct, 2) }}%</div>
          <div class="dim">Commission: <span class="num" style="color:#a78bfa">₱{{ number_format($order->commission_amount, 2) }}</span></div>
        </div>
      </div>
    @endif

    <div class="card">
      <div class="card-head">Audit</div>
      <div class="card-body text-[11px]" style="line-height:1.7;color:#8892a4">
        <div>Created by <span style="color:#e2e8f4">{{ $order->creator?->full_name }}</span></div>
        <div>Created {{ $order->created_at->format('M j, Y g:i a') }}</div>
        @if ($order->confirmed_at)
          <div class="mt-2">Confirmed by <span style="color:#e2e8f4">{{ $order->confirmer?->full_name }}</span></div>
          <div>{{ $order->confirmed_at->format('M j, Y g:i a') }}</div>
        @endif
      </div>
    </div>
  </div>
</div>

@if ($order->invoices->count())
  <div class="mt-5">
    <div class="card">
      <div class="card-head">Invoices ({{ $order->invoices->count() }})</div>
      <table class="dt">
        <thead><tr><th>Invoice #</th><th>Date</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach ($order->invoices as $inv)
            <tr>
              <td class="ref">{{ $inv->invoice_number }}</td>
              <td class="dim">{{ $inv->invoice_date->format('M j, Y') }}</td>
              <td class="num text-right">₱{{ number_format($inv->total_amount, 2) }}</td>
              <td class="num text-right" style="color:#34d399">₱{{ number_format($inv->amount_paid, 2) }}</td>
              <td class="num text-right" style="color:{{ $inv->balance_due > 0 ? '#f87171' : '#8892a4' }}">₱{{ number_format($inv->balance_due, 2) }}</td>
              <td><span class="badge b-{{ $inv->status }}">{{ $inv->status }}</span></td>
              <td><a href="{{ route('invoices.show', $inv) }}" class="btn btn-secondary btn-sm">View</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
