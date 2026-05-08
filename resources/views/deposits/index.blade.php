@extends('layouts.app')
@section('title', 'Deposits')

@section('content')
@include('partials.header', ['eyebrow' => 'Sales', 'title' => 'Bank Deposits', 'accent' => '#fb923c', 'subtitle' => 'Group collections into bank deposit slips.'])

@if ($undepositedCount > 0)
  <div class="alert-info mb-4">
    💡 <strong>{{ $undepositedCount }} un-deposited collection{{ $undepositedCount === 1 ? '' : 's' }}</strong> totaling
    <span class="font-mono">₱{{ number_format($undepositedTotal, 2) }}</span>.
    @if (auth()->user()->hasRole('Admin', 'Manager', 'Cashier'))
      <a href="{{ route('deposits.create') }}" class="ml-2 underline" style="color:#fb923c">Create deposit →</a>
    @endif
  </div>
@endif

<div class="flex items-center gap-2 mb-3.5">
  @if (auth()->user()->hasRole('Admin', 'Manager', 'Cashier'))
    <a href="{{ route('deposits.create') }}" class="btn btn-sell">+ New Deposit</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $deposits->total() }} records</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    @foreach (['draft','posted','cancelled'] as $s)
      <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
  </select>
  <select name="bank_account_id" class="input" style="width:auto">
    <option value="">All Bank Accounts</option>
    @foreach ($accounts as $a)
      <option value="{{ $a->id }}" @selected(request('bank_account_id') == $a->id)>{{ $a->bank_name }} {{ $a->maskedNumber() }}</option>
    @endforeach
  </select>
  <input type="date" name="from_date" value="{{ request('from_date') }}" class="input" style="width:140px" title="From date">
  <input type="date" name="to_date" value="{{ request('to_date') }}" class="input" style="width:140px" title="To date">
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['status','bank_account_id','from_date','to_date']))
    <a href="{{ route('deposits.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>Deposit #</th><th>Date</th><th>Bank</th><th>Slip #</th>
      <th class="text-right">Cash</th><th class="text-right">Check</th><th class="text-right">Total</th>
      <th class="text-right">Items</th><th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($deposits as $d)
        <tr>
          <td><a href="{{ route('deposits.show', $d) }}" class="ref">{{ $d->deposit_number }}</a></td>
          <td class="dim">{{ $d->deposit_date->format('M j, Y') }}</td>
          <td class="font-mono text-[11px]" style="color:#38bdf8">{{ $d->bankAccount->bank_name }} {{ $d->bankAccount->maskedNumber() }}</td>
          <td class="dim font-mono text-[11px]">{{ $d->slip_number ?: '—' }}</td>
          <td class="num text-right" style="color:{{ $d->cash_amount > 0 ? '#94a3b8' : '#4a5568' }}">₱{{ number_format($d->cash_amount, 2) }}</td>
          <td class="num text-right" style="color:{{ $d->check_amount > 0 ? '#a78bfa' : '#4a5568' }}">₱{{ number_format($d->check_amount, 2) }}</td>
          <td class="num text-right" style="font-weight:600;color:#34d399">₱{{ number_format($d->total_amount, 2) }}</td>
          <td class="num text-right">{{ $d->collections_count }}</td>
          <td><span class="badge b-{{ $d->status }}">{{ $d->status }}</span></td>
          <td><a href="{{ route('deposits.show', $d) }}" class="btn btn-secondary btn-sm">View</a></td>
        </tr>
      @empty
        <tr><td colspan="10" class="dim text-center py-6">No deposits yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $deposits->links() }}
  </div>
</div>
@endsection
