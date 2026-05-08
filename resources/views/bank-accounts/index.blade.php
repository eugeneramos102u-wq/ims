@extends('layouts.app')
@section('title', 'Bank Accounts')

@section('content')
@include('partials.header', ['eyebrow' => 'Master Data', 'title' => 'Bank Accounts', 'accent' => '#94a3b8', 'subtitle' => 'Company accounts where deposits are posted.'])

<div class="flex items-center gap-2 mb-3.5">
  @if (auth()->user()->hasRole('Admin', 'Manager'))
    <a href="{{ route('bank-accounts.create') }}" class="btn btn-master">+ New Bank Account</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $accounts->count() }} accounts</span>
</div>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>Bank</th><th>Account #</th><th>Account Name</th><th>Branch</th>
      <th>Default</th><th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($accounts as $a)
        <tr>
          <td class="font-semibold">{{ $a->bank_name }}</td>
          <td class="ref">{{ $a->maskedNumber() }}</td>
          <td>{{ $a->account_name }}</td>
          <td class="dim">{{ $a->branch ?: '—' }}</td>
          <td>
            @if ($a->is_default)
              <span class="badge" style="background:rgba(56,189,248,.15);color:#38bdf8;border:1px solid rgba(56,189,248,.3)">Default</span>
            @else
              <span class="dim">—</span>
            @endif
          </td>
          <td><span class="badge b-{{ $a->status }}">{{ $a->status }}</span></td>
          <td>
            @if (auth()->user()->hasRole('Admin', 'Manager'))
              <a href="{{ route('bank-accounts.edit', $a) }}" class="btn btn-secondary btn-sm">Edit</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="dim text-center py-6">No bank accounts configured yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
