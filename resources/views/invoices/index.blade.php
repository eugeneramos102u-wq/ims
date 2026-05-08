@extends('layouts.app')
@section('title', 'Invoices')

@section('content')
@include('partials.header', ['eyebrow' => 'Sales', 'title' => 'Sales Invoices', 'accent' => '#fb923c'])

@if ($overdueCount > 0)
  <div class="alert-danger mb-4">⚠ <strong>{{ $overdueCount }} invoice(s) are overdue.</strong> Collect immediately.</div>
@endif

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    @foreach (['draft','issued','partial','paid','overdue','void'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
  </select>
  <select name="customer_id" class="input" style="width:auto">
    <option value="">All Customers</option>
    @foreach ($customers as $c)
      <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->company_name }}</option>
    @endforeach
  </select>
  <input type="text" name="search" placeholder="Invoice #…" value="{{ request('search') }}" class="input" style="width:160px">
  <input type="date" name="from_date" value="{{ request('from_date') }}" class="input" style="width:140px" title="From date">
  <input type="date" name="to_date" value="{{ request('to_date') }}" class="input" style="width:140px" title="To date">
  <label class="flex items-center gap-1.5 text-xs" style="color:#8892a4">
    <input type="checkbox" name="overdue_only" value="1" @checked(request('overdue_only')) onchange="this.form.submit()"> Overdue only
  </label>
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['status','customer_id','search','from_date','to_date','overdue_only']))
    <a href="{{ route('invoices.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>Invoice #</th><th>Customer</th><th>Date</th><th>Due Date</th>
      <th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th>
      <th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($invoices as $inv)
        @php $isOverdue = $inv->status === 'overdue' || ($inv->due_date && $inv->due_date->isPast() && $inv->balance_due > 0 && ! in_array($inv->status, ['paid','void'])); @endphp
        <tr style="{{ $isOverdue ? 'background:rgba(248,113,113,.04)' : '' }}">
          <td><a class="ref" href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
          <td>{{ $inv->customer->company_name }}</td>
          <td class="dim">{{ $inv->invoice_date->format('M j, Y') }}</td>
          <td style="color:{{ $isOverdue ? '#f87171' : '#8892a4' }};font-size:12px">{{ $inv->due_date?->format('M j, Y') }} {{ $isOverdue ? '⚠' : '' }}</td>
          <td class="num text-right">₱{{ number_format($inv->total_amount, 2) }}</td>
          <td class="num text-right" style="color:{{ $inv->amount_paid > 0 ? '#34d399' : '#8892a4' }}">₱{{ number_format($inv->amount_paid, 2) }}</td>
          <td class="num text-right" style="color:{{ $inv->balance_due > 0 ? '#f87171' : '#8892a4' }};font-weight:{{ $inv->balance_due > 0 ? 600 : 400 }}">₱{{ number_format($inv->balance_due, 2) }}</td>
          <td><span class="badge b-{{ $inv->status }}">{{ $inv->status }}</span></td>
          <td>
            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-secondary btn-sm">View</a>
            @if (in_array($inv->status, ['issued','partial','overdue']) && auth()->user()->hasRole('Admin','Manager','Cashier'))
              <a href="{{ route('collections.create', $inv) }}" class="btn btn-ok btn-sm">+ Payment</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="dim text-center py-6">No invoices yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $invoices->links() }}
  </div>
</div>
@endsection
