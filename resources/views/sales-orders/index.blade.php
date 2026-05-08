@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
@include('partials.header', ['eyebrow' => 'Sales', 'title' => 'Sales Orders', 'accent' => '#fb923c'])

<div class="flex items-center gap-2 mb-3.5">
  @if (auth()->user()->hasRole('Admin', 'Manager', 'Cashier'))
    <a href="{{ route('sales-orders.create') }}" class="btn btn-sell">+ New SO</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $orders->total() }} records</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    @foreach (['draft','confirmed','partial','fulfilled','cancelled'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
  </select>
  <select name="customer_id" class="input" style="width:auto">
    <option value="">All Customers</option>
    @foreach ($customers as $c)
      <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->company_name }}</option>
    @endforeach
  </select>
  <input type="text" name="search" placeholder="SO # or DR #…" value="{{ request('search') }}" class="input" style="width:200px">
  <input type="date" name="from_date" value="{{ request('from_date') }}" class="input" style="width:140px" title="From date">
  <input type="date" name="to_date" value="{{ request('to_date') }}" class="input" style="width:140px" title="To date">
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['status','customer_id','search','from_date','to_date']))
    <a href="{{ route('sales-orders.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>SO #</th><th>DR #</th><th>Customer</th><th>Date</th>
      <th class="text-right">Total</th>
      <th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($orders as $so)
        <tr>
          <td><a href="{{ route('sales-orders.show', $so) }}" class="ref">{{ $so->so_number }}</a></td>
          <td class="dim">{{ $so->dr_number ?: '—' }}</td>
          <td>{{ $so->customer->company_name }}</td>
          <td class="dim">{{ $so->order_date->format('M j, Y') }}</td>
          <td class="num text-right">₱{{ number_format($so->total_amount, 2) }}</td>
          <td><span class="badge b-{{ $so->status }}">{{ $so->status }}</span></td>
          <td>
            <a href="{{ route('sales-orders.show', $so) }}" class="btn btn-secondary btn-sm">View</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="dim text-center py-6">No sales orders yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $orders->links() }}
  </div>
</div>
@endsection
