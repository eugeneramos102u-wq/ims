@extends('layouts.app')
@section('title', $account->exists ? 'Edit Bank Account' : 'New Bank Account')

@section('content')
@include('partials.header', [
  'eyebrow' => 'Master Data · Bank Accounts',
  'title' => $account->exists ? 'Edit Bank Account' : 'New Bank Account',
  'accent' => '#94a3b8',
])

<form method="POST" action="{{ $account->exists ? route('bank-accounts.update', $account) : route('bank-accounts.store') }}" class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf
  @if ($account->exists) @method('PUT') @endif

  <div class="card">
    <div class="card-head">Account Details</div>
    <div class="card-body grid grid-cols-2 gap-3">
      <div>
        <label class="label block mb-1.5">Bank Name <span style="color:#f87171">*</span></label>
        <input class="input" name="bank_name" value="{{ old('bank_name', $account->bank_name) }}" placeholder="BDO, BPI, MetroBank…" required>
      </div>
      <div>
        <label class="label block mb-1.5">Account Number <span style="color:#f87171">*</span></label>
        <input class="input" name="account_number" value="{{ old('account_number', $account->account_number) }}" required>
      </div>
      <div class="col-span-2">
        <label class="label block mb-1.5">Account Name <span style="color:#f87171">*</span></label>
        <input class="input" name="account_name" value="{{ old('account_name', $account->account_name) }}" placeholder="Your registered company name" required>
      </div>
      <div>
        <label class="label block mb-1.5">Branch</label>
        <input class="input" name="branch" value="{{ old('branch', $account->branch) }}">
      </div>
      <div>
        <label class="label block mb-1.5">Status <span style="color:#f87171">*</span></label>
        <select class="input" name="status" required>
          <option value="active" @selected(old('status', $account->status ?? 'active') === 'active')>Active</option>
          <option value="inactive" @selected(old('status', $account->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
      </div>
      <div class="col-span-2">
        <label class="flex items-center gap-2 text-xs" style="color:#e2e8f4">
          <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $account->is_default))>
          Set as default deposit account
        </label>
        <div class="text-[11px] mt-1" style="color:#8892a4">Only one account can be default. Setting this clears the default flag from other accounts.</div>
      </div>
      <div class="col-span-2">
        <label class="label block mb-1.5">Notes</label>
        <textarea class="input" name="notes" rows="2">{{ old('notes', $account->notes) }}</textarea>
      </div>
    </div>
  </div>

  <div class="card" style="height:fit-content">
    <div class="card-head">Actions</div>
    <div class="card-body flex flex-col gap-2">
      <button type="submit" class="btn btn-master" style="justify-content:center">{{ $account->exists ? 'Save Changes' : 'Create Account' }}</button>
      <a href="{{ route('bank-accounts.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
    </div>
  </div>
</form>
@endsection
