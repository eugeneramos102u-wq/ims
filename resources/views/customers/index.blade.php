@extends('layouts.app')
@section('title', 'Customers')

@section('content')
@include('partials.header', ['eyebrow' => 'Master Data', 'title' => 'Customers', 'accent' => '#94a3b8'])

<div class="flex items-center gap-2 mb-3.5">
  @if (auth()->user()->hasRole('Admin', 'Manager', 'Cashier'))
    <a href="{{ route('customers.create') }}" class="btn btn-master">+ New Customer</a>
  @endif
  <div class="flex-1"></div>
  <span class="font-mono text-[11px]" style="color:#8892a4">{{ $customers->total() }} customers</span>
</div>

<form method="GET" class="flex gap-2 mb-3.5 flex-wrap">
  <select name="status" class="input" style="width:auto">
    <option value="">All Status</option>
    <option value="active" @selected(request('status') === 'active')>Active</option>
    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
  </select>
  <input type="text" name="search" placeholder="Search code or name…" value="{{ request('search') }}" class="input" style="width:220px">
  <label class="flex items-center gap-1.5 text-xs" style="color:#8892a4">
    <input type="checkbox" name="has_outstanding" value="1" @checked(request('has_outstanding')) onchange="this.form.submit()"> Has outstanding balance
  </label>
  <button class="btn btn-secondary">Filter</button>
  @if (request()->hasAny(['status','search','has_outstanding']))
    <a href="{{ route('customers.index') }}" class="btn btn-secondary" style="text-decoration:none">Clear</a>
  @endif
</form>

<div class="card overflow-hidden">
  <table class="dt">
    <thead><tr>
      <th>Code</th><th>Customer Name</th><th>Contact</th><th>Email</th>
      <th class="text-right">Credit Limit</th><th class="text-right">Outstanding</th>
      <th>Status</th><th></th>
    </tr></thead>
    <tbody>
      @forelse ($customers as $c)
        @php $bal = $c->outstandingBalance(); @endphp
        <tr>
          <td class="ref">{{ $c->customer_code }}</td>
          <td>{{ $c->company_name }}</td>
          <td class="dim">{{ $c->phone ?: '—' }}</td>
          <td class="dim">{{ $c->email ?: '—' }}</td>
          <td class="num text-right">₱{{ number_format($c->credit_limit, 2) }}</td>
          <td class="num text-right" style="color:{{ $bal > 0 ? '#fbbf24' : '#8892a4' }}">₱{{ number_format($bal, 2) }}</td>
          <td><span class="badge b-{{ $c->status }}">{{ $c->status }}</span></td>
          <td>
            @if (auth()->user()->hasRole('Admin', 'Manager', 'Cashier'))
              <a href="{{ route('customers.edit', $c) }}" class="btn btn-secondary btn-sm">Edit</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="dim text-center py-6">No customers yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-3 py-2.5 border-t border-white/[0.07] text-[11.5px]" style="color:#8892a4">
    {{ $customers->links() }}
  </div>
</div>
@endsection
