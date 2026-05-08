@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
@include('partials.header', ['eyebrow' => 'Master Data', 'title' => 'Suppliers', 'accent' => '#94a3b8'])

<div class="flex items-center gap-2 mb-3.5">
  @if (auth()->user()->hasRole('Admin', 'Manager'))
    <a href="{{ route('suppliers.create') }}" class="btn btn-master">+ New Supplier</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $suppliers->total() }} suppliers</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    <option value="active" @selected(request('status') === 'active')>Active</option>
    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
  </select>
  <input type="text" name="search" placeholder="Search code or name…" value="{{ request('search') }}" class="input" style="width:220px">
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['status','search']))
    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>Code</th><th>Supplier Name</th><th>Contact</th><th>Phone</th>
      <th>Bank</th><th>Terms</th>
      <th class="text-right">Open Balance</th>
      <th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($suppliers as $sup)
        @php $bal = $sup->openBalance(); @endphp
        <tr>
          <td class="ref">{{ $sup->supplier_code }}</td>
          <td>{{ $sup->company_name }}</td>
          <td class="dim">{{ $sup->contact_person ?: '—' }}</td>
          <td class="dim">{{ $sup->phone ?: '—' }}</td>
          <td class="dim font-mono text-[11px]">{{ $sup->bank_name ? $sup->bank_name . ' ' . substr($sup->bank_account ?? '', -4) : '—' }}</td>
          <td class="dim">{{ $sup->paymentTerm?->term_code ?: '—' }}</td>
          <td class="num text-right" style="color:{{ $bal > 0 ? '#fbbf24' : '#8892a4' }}">₱{{ number_format($bal, 2) }}</td>
          <td><span class="badge b-{{ $sup->status }}">{{ $sup->status }}</span></td>
          <td>
            @if (auth()->user()->hasRole('Admin', 'Manager'))
              <a href="{{ route('suppliers.edit', $sup) }}" class="btn btn-secondary btn-sm">Edit</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="dim text-center py-6">No suppliers yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $suppliers->links() }}
  </div>
</div>
@endsection
