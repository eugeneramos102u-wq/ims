@extends('layouts.app')
@section('title', 'Purchase Orders')

@section('content')
@include('partials.header', ['eyebrow' => 'Purchasing', 'title' => 'Purchase Orders', 'accent' => '#60a5fa'])

<div class="flex items-center gap-2 mb-3.5">
  @if (auth()->user()->hasRole('Admin', 'Manager', 'Cashier'))
    <a href="{{ route('purchase-orders.create') }}" class="btn btn-buy">+ New PO</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $orders->total() }} records</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    @foreach (['draft','issued','partial','received','cancelled'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
  </select>
  <select name="supplier_id" class="input" style="width:auto">
    <option value="">All Suppliers</option>
    @foreach ($suppliers as $sup)
      <option value="{{ $sup->id }}" @selected(request('supplier_id') == $sup->id)>{{ $sup->company_name }}</option>
    @endforeach
  </select>
  <input type="text" name="search" placeholder="PO #…" value="{{ request('search') }}" class="input" style="width:200px">
  <input type="date" name="from_date" value="{{ request('from_date') }}" class="input" style="width:140px" title="From date">
  <input type="date" name="to_date" value="{{ request('to_date') }}" class="input" style="width:140px" title="To date">
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['status','supplier_id','search','from_date','to_date']))
    <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>PO #</th><th>Supplier</th><th>Order Date</th><th>Expected</th>
      <th class="text-right">Items</th><th class="text-right">Total</th>
      <th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($orders as $po)
        <tr>
          <td><a href="{{ route('purchase-orders.show', $po) }}" class="ref">{{ $po->po_number }}</a></td>
          <td>{{ $po->supplier->company_name }}</td>
          <td class="dim">{{ $po->order_date->format('M j, Y') }}</td>
          <td class="dim">{{ $po->expected_date?->format('M j, Y') ?: '—' }}</td>
          <td class="num text-right">{{ $po->items_count ?? $po->items()->count() }}</td>
          <td class="num text-right">₱{{ number_format($po->total_amount, 2) }}</td>
          <td><span class="badge b-{{ $po->status }}">{{ $po->status }}</span></td>
          <td>
            <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-secondary btn-sm">View</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="dim text-center py-6">No purchase orders yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $orders->links() }}
  </div>
</div>
@endsection
