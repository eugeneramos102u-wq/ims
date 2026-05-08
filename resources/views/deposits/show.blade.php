@extends('layouts.app')
@section('title', $deposit->deposit_number)

@section('content')
@include('partials.header', [
  'eyebrow' => 'Sales · Deposits',
  'title' => $deposit->deposit_number,
  'accent' => '#fb923c',
  'subtitle' => $deposit->bankAccount->bank_name . ' ' . $deposit->bankAccount->maskedNumber() . ' · ' . $deposit->deposit_date->format('M j, Y'),
])

<div class="flex items-center gap-2 mb-3.5">
  <span class="badge b-{{ $deposit->status }}" style="font-size:11px;padding:4px 10px">{{ $deposit->status }}</span>
  <div class="flex-1"></div>

  @if ($deposit->isPostable() && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('deposits.post', $deposit) }}" class="inline" onsubmit="return confirm('Post this deposit? Once posted, the included collections cannot be removed.');">
      @csrf
      <button type="submit" class="btn btn-ok btn-sm">Post Deposit</button>
    </form>
  @endif
  @if ($deposit->isCancellable() && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('deposits.cancel', $deposit) }}" class="inline" onsubmit="return confirm('Cancel deposit? Selected collections will be released back to the un-deposited pool.');">
      @csrf
      <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
    </form>
  @endif
</div>

<div class="grid gap-3" style="grid-template-columns:1fr 280px">
  <div class="card">
    <div class="card-head">Collections in this Deposit ({{ $deposit->collections->count() }})</div>
    <div class="card-body !p-0">
      <table class="dt">
        <thead><tr>
          <th>OR #</th><th>Date</th><th>Invoice</th><th>Customer</th>
          <th>Method</th><th>Reference</th><th class="text-right">Amount</th>
        </tr></thead>
        <tbody>
          @php
            $methodColors = ['cash'=>'#94a3b8','bank_transfer'=>'#38bdf8','check'=>'#a78bfa','card'=>'#fbbf24'];
            $methodLabels = ['cash'=>'Cash','bank_transfer'=>'Bank','check'=>'Check','card'=>'Card'];
          @endphp
          @foreach ($deposit->collections as $col)
            <tr>
              <td class="ref">{{ $col->or_number }}</td>
              <td class="dim">{{ $col->collection_date->format('M j, Y') }}</td>
              <td><a class="ref text-[11px]" href="{{ route('invoices.show', $col->invoice) }}">{{ $col->invoice->invoice_number }}</a></td>
              <td>{{ $col->invoice->customer->company_name }}</td>
              <td><span class="badge" style="color:{{ $methodColors[$col->payment_method] ?? '#8892a4' }};border:1px solid {{ $methodColors[$col->payment_method] ?? '#8892a4' }}40">{{ $methodLabels[$col->payment_method] ?? $col->payment_method }}</span></td>
              <td class="dim font-mono text-[11px]">{{ $col->reference_number ?: '—' }}</td>
              <td class="num text-right" style="color:#34d399">₱{{ number_format($col->amount, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr><td colspan="6" class="text-right dim">Cash</td><td class="num text-right" style="color:#94a3b8">₱{{ number_format($deposit->cash_amount, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right dim">Check</td><td class="num text-right" style="color:#a78bfa">₱{{ number_format($deposit->check_amount, 2) }}</td></tr>
          <tr><td colspan="6" class="text-right dim">Other</td><td class="num text-right" style="color:#fbbf24">₱{{ number_format($deposit->other_amount, 2) }}</td></tr>
          <tr style="background:#181c24"><td colspan="6" class="text-right" style="color:#34d399;font-weight:700">TOTAL DEPOSITED</td><td class="num text-right" style="color:#34d399;font-weight:700">₱{{ number_format($deposit->total_amount, 2) }}</td></tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div>
    <div class="card mb-3">
      <div class="card-head">Bank Account</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div class="font-semibold">{{ $deposit->bankAccount->bank_name }}</div>
        <div class="font-mono" style="color:#38bdf8">{{ $deposit->bankAccount->maskedNumber() }}</div>
        <div class="dim">{{ $deposit->bankAccount->account_name }}</div>
        @if ($deposit->bankAccount->branch)
          <div class="dim">{{ $deposit->bankAccount->branch }}</div>
        @endif
        @if ($deposit->slip_number)
          <div class="mt-2">
            <span class="dim">Slip #:</span>
            <span class="font-mono text-[11px]" style="color:#fbbf24">{{ $deposit->slip_number }}</span>
          </div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-head">Audit</div>
      <div class="card-body text-[11px]" style="line-height:1.7;color:#8892a4">
        <div>Created by <span style="color:#e2e8f4">{{ $deposit->creator?->full_name }}</span></div>
        <div>{{ $deposit->created_at->format('M j, Y g:i a') }}</div>
        @if ($deposit->posted_at)
          <div class="mt-2">Posted by <span style="color:#e2e8f4">{{ $deposit->poster?->full_name }}</span></div>
          <div>{{ $deposit->posted_at->format('M j, Y g:i a') }}</div>
        @endif
        @if ($deposit->cancelled_at)
          <div class="mt-2" style="color:#f87171">Cancelled {{ $deposit->cancelled_at->format('M j, Y g:i a') }}</div>
          @if ($deposit->cancel_reason)
            <div class="text-[11px] mt-1" style="color:#f87171">Reason: {{ $deposit->cancel_reason }}</div>
          @endif
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
