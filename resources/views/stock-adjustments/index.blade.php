@extends('layouts.app')
@section('title', 'Stock Adjustment')

@section('content')
@include('partials.header', ['eyebrow' => 'Inventory', 'title' => 'Stock Adjustment', 'accent' => '#34d399', 'subtitle' => 'Beginning balances, count corrections, write-offs.'])

<div class="alert-info mb-4">
  💡 Use <strong>Beginning Balance</strong> to enter opening quantities during system setup or annual cutover. Only Admin / Manager can post.
</div>

<div class="flex items-center gap-2 mb-3.5 flex-wrap">
  @if (auth()->user()->hasRole('Admin', 'Manager', 'Cashier'))
    <a href="{{ route('stock-adjustments.create') }}" class="btn btn-inv">+ New Adjustment</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $adjustments->total() }} records</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="adjustment_type_id" class="input" style="width:auto">
    <option value="">All Types</option>
    @foreach ($types as $t)
      <option value="{{ $t->id }}" @selected(request('adjustment_type_id') == $t->id)>{{ $t->type_name }}</option>
    @endforeach
  </select>
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    @foreach (['draft','posted','void'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
  </select>
  <input type="text" name="search" placeholder="ADJ #…" value="{{ request('search') }}" class="input" style="width:160px">
  <input type="date" name="from_date" value="{{ request('from_date') }}" class="input" style="width:140px" title="From date">
  <input type="date" name="to_date" value="{{ request('to_date') }}" class="input" style="width:140px" title="To date">
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['adjustment_type_id','status','search','from_date','to_date']))
    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
  <table class="dt">
    <thead><tr>
      <th>ADJ #</th><th>Date</th><th>Type</th><th>Lines</th><th>Notes</th><th>Posted By</th><th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($adjustments as $adj)
        @php
          $typeColor = match ($adj->type->code) {
              'BBAL' => '#38bdf8',
              'ADJIN' => '#34d399',
              'ADJOUT' => '#f87171',
              'WOFF' => '#fbbf24',
              default => '#94a3b8',
          };
        @endphp
        <tr>
          <td><a href="{{ route('stock-adjustments.show', $adj) }}" class="ref">{{ $adj->adj_number }}</a></td>
          <td class="dim">{{ $adj->adjustment_date->format('M j, Y') }}</td>
          <td>
            <span class="badge" style="color:{{ $typeColor }};border:1px solid {{ $typeColor }}40;background:{{ $typeColor }}1a">{{ $adj->type->type_name }}</span>
          </td>
          <td class="num">{{ $adj->items_count }}</td>
          <td class="dim text-[11.5px]" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $adj->notes ?: '—' }}</td>
          <td class="dim">{{ $adj->poster?->username ?: '—' }}</td>
          <td><span class="badge b-{{ $adj->status }}">{{ $adj->status }}</span></td>
          <td><a href="{{ route('stock-adjustments.show', $adj) }}" class="btn btn-secondary btn-sm">View</a></td>
        </tr>
      @empty
        <tr><td colspan="8" class="dim text-center py-6">No stock adjustments yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  </div>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $adjustments->links() }}
  </div>
</div>
@endsection
