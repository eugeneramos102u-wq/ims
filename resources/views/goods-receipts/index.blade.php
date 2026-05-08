@extends('layouts.app')
@section('title', 'Stock Receiving')

@section('content')
@include('partials.header', ['eyebrow' => 'Purchasing', 'title' => 'Stock Receiving', 'accent' => '#60a5fa', 'subtitle' => 'Stock IN — receive against issued POs.'])

<div class="flex items-center gap-2 mb-3.5">
  <div class="text-xs" style="color:#8892a4">Stock receipts are created from a Purchase Order.</div>
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $grns->total() }} records</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    @foreach (['draft','confirmed','cancelled'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
  </select>
  <select name="supplier_id" class="input" style="width:auto">
    <option value="">All Suppliers</option>
    @foreach ($suppliers as $sup)
      <option value="{{ $sup->id }}" @selected(request('supplier_id') == $sup->id)>{{ $sup->company_name }}</option>
    @endforeach
  </select>
  <input type="text" name="search" placeholder="SR #…" value="{{ request('search') }}" class="input" style="width:160px">
  <input type="date" name="from_date" value="{{ request('from_date') }}" class="input" style="width:140px" title="From date">
  <input type="date" name="to_date" value="{{ request('to_date') }}" class="input" style="width:140px" title="To date">
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['status','supplier_id','search','from_date','to_date']))
    <a href="{{ route('stock-receiving.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>SR #</th><th>PO Reference</th><th>Supplier</th><th>Received Date</th>
      <th>Delivery Note</th><th class="text-right">Lines</th>
      <th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($grns as $grn)
        <tr>
          <td><a href="{{ route('stock-receiving.show', $grn) }}" class="ref">{{ $grn->grn_number }}</a></td>
          <td><a href="{{ route('purchase-orders.show', $grn->po_id) }}" class="ref">{{ $grn->purchaseOrder->po_number }}</a></td>
          <td>{{ $grn->supplier->company_name }}</td>
          <td class="dim">{{ $grn->received_date->format('M j, Y') }}</td>
          <td class="dim font-mono text-[11px]">{{ $grn->delivery_note_no ?: '—' }}</td>
          <td class="num text-right">{{ $grn->items_count }}</td>
          <td><span class="badge b-{{ $grn->status }}">{{ $grn->status }}</span></td>
          <td><a href="{{ route('stock-receiving.show', $grn) }}" class="btn btn-secondary btn-sm">View</a></td>
        </tr>
      @empty
        <tr><td colspan="8" class="dim text-center py-6">No stock receipts yet. Receive against an issued PO from the Purchase Orders page.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $grns->links() }}
  </div>
</div>
@endsection
