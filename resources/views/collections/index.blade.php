@extends('layouts.app')
@section('title', 'Collections')

@section('content')
@include('partials.header', ['eyebrow' => 'Sales', 'title' => 'Collections', 'accent' => '#fb923c'])

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="payment_method" class="input" style="width:auto">
    <option value="">All Methods</option>
    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'check' => 'Check', 'card' => 'Card'] as $val => $label)
      <option value="{{ $val }}" @selected(request('payment_method') === $val)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="customer_id" class="input" style="width:auto">
    <option value="">All Customers</option>
    @foreach ($customers as $c)
      <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->company_name }}</option>
    @endforeach
  </select>
  <input type="text" name="search" placeholder="OR # or Invoice #…" value="{{ request('search') }}" class="input" style="width:180px">
  <input type="date" name="from_date" value="{{ request('from_date') }}" class="input" style="width:140px" title="From date">
  <input type="date" name="to_date" value="{{ request('to_date') }}" class="input" style="width:140px" title="To date">
  <label class="flex items-center gap-1.5 text-xs" style="color:#8892a4">
    <input type="checkbox" name="pending_deposit" value="1" @checked(request('pending_deposit')) onchange="this.form.submit()"> Pending deposit only
  </label>
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['payment_method','customer_id','search','from_date','to_date','pending_deposit']))
    <a href="{{ route('collections.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>OR #</th><th>Date</th><th>Invoice #</th><th>Customer</th>
      <th class="text-right">Amount</th><th>Method</th><th>Payment Details</th><th>Deposited</th><th>Collected By</th>
    </tr></thead>
    <tbody>
      @forelse ($collections as $col)
        <tr>
          <td class="ref">{{ $col->or_number }}</td>
          <td class="dim">{{ $col->collection_date->format('M j, Y') }}</td>
          <td><a class="ref" href="{{ route('invoices.show', $col->invoice) }}">{{ $col->invoice->invoice_number }}</a></td>
          <td>{{ $col->invoice->customer->company_name }}</td>
          <td class="num text-right" style="color:#34d399">₱{{ number_format($col->amount, 2) }}</td>
          <td>
            @php
              $methodLabels = ['cash'=>'Cash','bank_transfer'=>'Bank Transfer','check'=>'Check','card'=>'Card'];
              $methodColors = ['cash'=>'#94a3b8','bank_transfer'=>'#38bdf8','check'=>'#a78bfa','card'=>'#fbbf24'];
            @endphp
            <span class="badge" style="color:{{ $methodColors[$col->payment_method] ?? '#8892a4' }};border-color:{{ $methodColors[$col->payment_method] ?? '#8892a4' }}40">{{ $methodLabels[$col->payment_method] ?? $col->payment_method }}</span>
          </td>
          <td class="dim font-mono text-[11px]" style="line-height:1.5">
            @switch($col->payment_method)
              @case('cash')
                <span class="dim">—</span>
                @break
              @case('bank_transfer')
                @if ($col->bank_name) <span style="color:#38bdf8">{{ $col->bank_name }}</span><br>@endif
                {{ $col->reference_number ?: '—' }}
                @break
              @case('check')
                @if ($col->check_number) <span style="color:#a78bfa">#{{ $col->check_number }}</span>@endif
                @if ($col->bank_name) · {{ $col->bank_name }}@endif
                @if ($col->check_date)
                  @php $isPostdated = $col->check_date->isFuture(); @endphp
                  <br><span style="color:{{ $isPostdated ? '#fbbf24' : '#8892a4' }}">{{ $col->check_date->format('M j, Y') }}@if ($isPostdated) ⏳ PDC @endif</span>
                @endif
                @break
              @case('card')
                @if ($col->card_last4) ••••<span style="color:#fbbf24">{{ $col->card_last4 }}</span>@endif
                @if ($col->approval_code) <br>auth: {{ $col->approval_code }}@endif
                @break
              @default
                {{ $col->reference_number ?: '—' }}
            @endswitch
          </td>
          <td>
            @if ($col->deposit_id)
              <a href="{{ route('deposits.show', $col->deposit_id) }}" class="ref text-[11px]">{{ $col->deposit?->deposit_number }}</a>
            @else
              <span class="dim text-[11px]">— pending —</span>
            @endif
          </td>
          <td class="dim">{{ $col->collector?->username }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="dim text-center py-6">No collections recorded yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $collections->links() }}
  </div>
</div>
@endsection
