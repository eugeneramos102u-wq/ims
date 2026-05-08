@extends('layouts.app')
@section('title', 'Items')

@section('content')
@include('partials.header', ['eyebrow' => 'Inventory', 'title' => 'Items', 'accent' => '#34d399'])

<div class="flex items-center gap-2 mb-3.5">
  @if (auth()->user()->hasRole('Admin', 'Manager'))
    <a href="{{ route('items.create') }}" class="btn btn-inv">+ New Item</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $items->total() }} items</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="category_id" class="input" style="width:auto">
    <option value="">All Categories</option>
    @foreach ($categories as $c)
      <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->category_name }}</option>
    @endforeach
  </select>
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    <option value="active" @selected(request('status') === 'active')>Active</option>
    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
  </select>
  <select name="stock_level" class="input" style="width:auto" onchange="this.form.submit()">
    <option value="">All Stock</option>
    <option value="zero" @selected(request('stock_level') === 'zero')>Zero stock</option>
    <option value="low" @selected(request('stock_level') === 'low')>Low (≤ reorder)</option>
    <option value="in_stock" @selected(request('stock_level') === 'in_stock')>In stock</option>
  </select>
  <input type="text" name="search" placeholder="Search code or name…" value="{{ request('search') }}" class="input" style="width:200px">
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['category_id','status','stock_level','search','low_stock']))
    <a href="{{ route('items.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>Code</th><th>Name</th><th>Category</th><th>UoM</th>
      <th class="text-right">Cost</th><th class="text-right">Wholesale</th><th class="text-right">Retail</th>
      <th class="text-right">On Hand</th><th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($items as $item)
        @php
          $rowStyle = $item->isZeroStock() ? 'background:rgba(248,113,113,.04)' : ($item->isLowStock() ? 'background:rgba(251,191,36,.03)' : '');
          $qtyColor = $item->isZeroStock() ? '#f87171' : ($item->isLowStock() ? '#fbbf24' : '#34d399');
        @endphp
        <tr style="{{ $rowStyle }}">
          <td class="ref">{{ $item->item_code }}</td>
          <td>{{ $item->item_name }}</td>
          <td class="dim">{{ $item->category->category_name }}</td>
          <td class="dim">{{ $item->uom->uom_code }}</td>
          <td class="num text-right">₱{{ number_format($item->unit_cost, 2) }}</td>
          <td class="num text-right">₱{{ number_format($item->wholesale_price, 2) }}</td>
          <td class="num text-right">₱{{ number_format($item->retail_price, 2) }}</td>
          <td class="num text-right" style="color:{{ $qtyColor }};font-weight:{{ $item->isZeroStock() ? 700 : 400 }}">{{ number_format($item->qty_on_hand, 2) }}</td>
          <td><span class="badge b-{{ $item->status }}">{{ $item->status }}</span></td>
          <td>
            @if (auth()->user()->hasRole('Admin', 'Manager'))
              <a href="{{ route('items.edit', $item) }}" class="btn btn-secondary btn-sm">Edit</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="10" class="dim text-center py-6">No items found.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $items->links() }}
  </div>
</div>
@endsection
