@extends('layouts.app')
@section('title', $item->exists ? 'Edit Item' : 'New Item')

@section('content')
@include('partials.header', [
  'eyebrow' => 'Inventory · Items',
  'title' => $item->exists ? 'Edit Item' : 'New Item',
  'accent' => '#34d399',
  'subtitle' => $item->exists ? $item->item_code : ($nextCode ? 'New · code: ' . $nextCode : null),
])

<form method="POST" action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}" class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf
  @if ($item->exists) @method('PUT') @endif

  <div>
    <div class="card mb-3">
      <div class="card-head">Basic Info</div>
      <div class="card-body grid grid-cols-2 gap-3">
        @if ($item->exists)
          <div>
            <label class="label block mb-1.5">Item Code</label>
            <input class="input" value="{{ $item->item_code }}" readonly>
          </div>
        @endif
        <div class="{{ $item->exists ? '' : 'col-span-2' }}">
          <label class="label block mb-1.5">Item Name <span style="color:#f87171">*</span></label>
          <input class="input" name="item_name" value="{{ old('item_name', $item->item_name) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Category <span style="color:#f87171">*</span></label>
          <select class="input" name="category_id" required>
            <option value="">— Select —</option>
            @foreach ($categories as $c)
              <option value="{{ $c->id }}" @selected(old('category_id', $item->category_id) == $c->id)>{{ $c->category_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">UoM <span style="color:#f87171">*</span></label>
          <select class="input" name="uom_id" required>
            <option value="">— Select —</option>
            @foreach ($uoms as $u)
              <option value="{{ $u->id }}" @selected(old('uom_id', $item->uom_id) == $u->id)>{{ $u->uom_code }} · {{ $u->uom_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label block mb-1.5">Barcode</label>
          <input class="input" name="barcode" value="{{ old('barcode', $item->barcode) }}">
        </div>
        <div>
          <label class="label block mb-1.5">Reorder Level <span style="color:#f87171">*</span></label>
          <input class="input" type="number" step="0.01" min="0" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level ?? 0) }}" required>
        </div>
        <div class="col-span-2">
          <label class="label block mb-1.5">Description</label>
          <textarea class="input" name="description" rows="2">{{ old('description', $item->description) }}</textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head">Pricing</div>
      <div class="card-body grid grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Unit Cost ₱ <span style="color:#f87171">*</span></label>
          <input class="input" type="number" step="0.0001" min="0" name="unit_cost" value="{{ old('unit_cost', $item->unit_cost ?? 0) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Wholesale ₱ <span style="color:#f87171">*</span></label>
          <input class="input" type="number" step="0.0001" min="0" name="wholesale_price" value="{{ old('wholesale_price', $item->wholesale_price ?? 0) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Retail ₱ <span style="color:#f87171">*</span></label>
          <input class="input" type="number" step="0.0001" min="0" name="retail_price" value="{{ old('retail_price', $item->retail_price ?? 0) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Tax % <span style="color:#f87171">*</span></label>
          <input class="input" type="number" step="0.01" min="0" max="100" name="tax_rate_pct" value="{{ old('tax_rate_pct', $item->tax_rate_pct ?? 12) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Status <span style="color:#f87171">*</span></label>
          <select class="input" name="status" required>
            <option value="active" @selected(old('status', $item->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $item->status ?? 'active') === 'inactive')>Inactive</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head">Actions</div>
      <div class="card-body flex flex-col gap-2">
        <button type="submit" class="btn btn-inv" style="justify-content:center">{{ $item->exists ? 'Save Changes' : 'Create Item' }}</button>
        <a href="{{ route('items.index') }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>
    @if ($item->exists)
      <div class="card mt-3">
        <div class="card-head">Stock</div>
        <div class="card-body">
          <div class="flex items-center justify-between">
            <span class="dim">On Hand</span>
            <span class="num text-lg" style="color:{{ $item->isZeroStock() ? '#f87171' : ($item->isLowStock() ? '#fbbf24' : '#34d399') }}">{{ number_format($item->qty_on_hand, 2) }}</span>
          </div>
          <div class="text-[10.5px] mt-2" style="color:#4a5568">qty_on_hand is updated by Sales Order confirms (out) and Stock Receiving confirms (in). To set initial balances, use Stock Adjustment.</div>
        </div>
      </div>
    @endif
  </div>
</form>
@endsection
