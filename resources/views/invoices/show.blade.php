@extends('layouts.app')
@section('title', $invoice->invoice_number)

@section('content')
@include('partials.header', [
  'eyebrow' => 'Sales · Invoices',
  'title' => $invoice->invoice_number,
  'accent' => '#fb923c',
  'subtitle' => $invoice->customer->company_name . ' · Due ' . $invoice->due_date?->format('M j, Y'),
])

<div class="flex items-center gap-2 mb-3.5">
  <span class="badge b-{{ $invoice->status }}" style="font-size:11px;padding:4px 10px">{{ $invoice->status }}</span>
  <div class="flex-1"></div>

  @if ($invoice->status === 'draft' && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('invoices.issue', $invoice) }}" class="inline">
      @csrf
      <button type="submit" class="btn btn-warn btn-sm">Issue Invoice</button>
    </form>
  @endif
  @if (in_array($invoice->status, ['issued','partial','overdue']) && auth()->user()->hasRole('Admin','Manager','Cashier'))
    <a href="{{ route('collections.create', $invoice) }}" class="btn btn-ok btn-sm">+ Record Payment</a>
  @endif
  @if (in_array($invoice->status, ['draft','issued']) && $invoice->amount_paid == 0 && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('invoices.void', $invoice) }}" class="inline" onsubmit="return confirm('Void invoice?');">
      @csrf
      <button type="submit" class="btn btn-danger btn-sm">Void</button>
    </form>
  @endif
</div>

<div class="grid gap-3" style="grid-template-columns:1fr 280px">
  <div class="card">
    <div class="card-head">Line Items</div>
    <div class="card-body !p-0">
      <table class="dt">
        <thead><tr>
          <th>Item</th><th>UoM</th>
          <th class="text-right">Qty</th><th class="text-right">Unit Price</th>
          <th class="text-right">Disc %</th><th class="text-right">Tax %</th><th class="text-right">Line Total</th>
        </tr></thead>
        <tbody>
          @foreach ($invoice->items as $line)
            <tr>
              <td><span class="ref">{{ $line->item->item_code }}</span> {{ $line->item->item_name }}</td>
              <td class="dim">{{ $line->item->uom->uom_code }}</td>
              <td class="num text-right">{{ number_format($line->qty_invoiced, 2) }}</td>
              <td class="num text-right">₱{{ number_format($line->unit_price, 4) }}</td>
              <td class="num text-right">{{ number_format($line->discount_pct, 2) }}%</td>
              <td class="num text-right">{{ number_format($line->tax_rate_pct, 2) }}%</td>
              <td class="num text-right">₱{{ number_format($line->line_total, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr><td colspan="6" class="text-right dim">Subtotal</td><td class="num text-right">₱{{ number_format($invoice->subtotal, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right dim">Discount</td><td class="num text-right">₱{{ number_format($invoice->discount_amount, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right dim">Tax</td><td class="num text-right">₱{{ number_format($invoice->tax_amount, 2) }}</td></tr>
          <tr style="background:#181c24"><td colspan="6" class="text-right" style="color:#fb923c;font-weight:700">TOTAL</td><td class="num text-right" style="color:#fb923c;font-weight:700">₱{{ number_format($invoice->total_amount, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right dim">Paid</td><td class="num text-right" style="color:#34d399">₱{{ number_format($invoice->amount_paid, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right" style="color:#f87171;font-weight:700">BALANCE DUE</td><td class="num text-right" style="color:#f87171;font-weight:700">₱{{ number_format($invoice->balance_due, 2) }}</td></tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div>
    <div class="card mb-3">
      <div class="card-head">Customer</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div class="font-semibold">{{ $invoice->customer->company_name }}</div>
        <div class="dim">{{ $invoice->customer->contact_person ?: '—' }}</div>
        <div class="dim">{{ $invoice->customer->email ?: '—' }}</div>
        @if ($invoice->salesOrder)
          <div class="mt-2">
            <span class="dim">SO:</span> <a class="ref" href="{{ route('sales-orders.show', $invoice->salesOrder) }}">{{ $invoice->salesOrder->so_number }}</a>
          </div>
        @endif
      </div>
    </div>

    @if ($invoice->collections->count())
      <div class="card">
        <div class="card-head">Payments ({{ $invoice->collections->count() }})</div>
        <div class="card-body !p-0">
          <table class="dt" style="font-size:11.5px">
            <thead><tr><th>OR #</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
              @foreach ($invoice->collections as $col)
                <tr>
                  <td class="ref">{{ $col->or_number }}</td>
                  <td class="dim">{{ $col->collection_date->format('M j') }}</td>
                  <td class="num text-right" style="color:#34d399">₱{{ number_format($col->amount, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
