@extends('layouts.app')
@section('title', $adjustment->adj_number)

@section('content')
@include('partials.header', [
  'eyebrow' => 'Inventory · Stock Adjustment',
  'title' => $adjustment->adj_number,
  'accent' => '#34d399',
  'subtitle' => $adjustment->type->type_name . ' · ' . $adjustment->adjustment_date->format('M j, Y'),
])

<div class="flex items-center gap-2 mb-3.5 flex-wrap">
  <span class="badge b-{{ $adjustment->status }}" style="font-size:11px;padding:4px 10px">{{ $adjustment->status }}</span>
  <div class="flex-1"></div>

  @if ($adjustment->isEditable() && auth()->user()->hasRole('Admin','Manager','Cashier'))
    <a href="{{ route('stock-adjustments.edit', $adjustment) }}" class="btn btn-secondary btn-sm">Edit</a>
  @endif
  @if ($adjustment->isPostable() && auth()->user()->hasRole('Admin','Manager'))
    <form method="POST" action="{{ route('stock-adjustments.post', $adjustment) }}" class="inline" onsubmit="return confirm('Post this adjustment? Inventory quantities will be updated immediately.');">
      @csrf
      <button type="submit" class="btn btn-ok btn-sm">Post Adjustment</button>
    </form>
  @endif
  @if ($adjustment->isVoidable() && auth()->user()->hasRole('Admin','Manager'))
    <button type="button" onclick="document.getElementById('void-modal').style.display='flex'" class="btn btn-danger btn-sm">Void</button>
  @endif
</div>

<div class="grid gap-3 grid-cols-1 md:grid-cols-[1fr_280px]">
  <div class="card overflow-hidden">
    <div class="card-head">Lines ({{ $adjustment->items->count() }})</div>
    <div class="overflow-x-auto">
      <table class="dt">
        <thead><tr>
          <th>Item</th><th>UoM</th>
          <th class="text-right">Qty Before</th>
          <th class="text-right">Qty Adjusted</th>
          <th class="text-right">Qty After</th>
          <th class="text-right">Unit Cost</th>
        </tr></thead>
        <tbody>
          @foreach ($adjustment->items as $line)
            @php
              $isOut = $adjustment->type->direction === 'OUT';
              $isBBAL = $adjustment->type->code === 'BBAL';
              $delta = (float) $line->qty_after - (float) $line->qty_before;
            @endphp
            <tr>
              <td>
                <span class="ref">{{ $line->item->item_code }}</span>
                <span class="ml-2">{{ $line->item->item_name }}</span>
              </td>
              <td class="dim">{{ $line->item->uom->uom_code }}</td>
              <td class="num text-right" style="color:{{ $isBBAL ? '#4a5568' : '#8892a4' }}">{{ number_format($line->qty_before, 2) }}</td>
              <td class="num text-right" style="color:{{ $isOut ? '#f87171' : ($isBBAL ? '#38bdf8' : '#34d399') }};font-weight:600">
                {{ $isOut ? '−' : ($isBBAL ? '=' : '+') }}{{ number_format($line->qty_adjusted, 2) }}
              </td>
              <td class="num text-right" style="color:#34d399;font-weight:600">{{ number_format($line->qty_after, 2) }}</td>
              <td class="num text-right">₱{{ number_format($line->unit_cost, 4) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <div class="card mb-3">
      <div class="card-head">Summary</div>
      <div class="card-body text-[12.5px]" style="line-height:1.8">
        <div><span class="dim">Type:</span> {{ $adjustment->type->type_name }}
          <span class="font-mono ml-1" style="color:{{ $adjustment->type->direction === 'OUT' ? '#f87171' : ($adjustment->type->code === 'BBAL' ? '#38bdf8' : '#34d399') }}">[{{ $adjustment->type->direction }}]</span>
        </div>
        <div><span class="dim">Lines:</span> <span class="num">{{ $adjustment->items->count() }}</span></div>
        <div><span class="dim">Total qty change:</span> <span class="num">{{ number_format($adjustment->items->sum('qty_adjusted'), 2) }}</span></div>
        @if ($adjustment->notes)
          <div class="mt-2"><span class="dim">Notes:</span><br>{{ $adjustment->notes }}</div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-head">Audit</div>
      <div class="card-body text-[11px]" style="line-height:1.7;color:#8892a4">
        <div>Created by <span style="color:#e2e8f4">{{ $adjustment->creator?->full_name }}</span></div>
        <div>{{ $adjustment->created_at->format('M j, Y g:i a') }}</div>
        @if ($adjustment->posted_at)
          <div class="mt-2">Posted by <span style="color:#34d399">{{ $adjustment->poster?->full_name }}</span></div>
          <div>{{ $adjustment->posted_at->format('M j, Y g:i a') }}</div>
        @endif
        @if ($adjustment->voided_at)
          <div class="mt-2" style="color:#f87171">Voided by {{ $adjustment->voider?->full_name }}</div>
          <div style="color:#f87171">{{ $adjustment->voided_at->format('M j, Y g:i a') }}</div>
          @if ($adjustment->void_reason)
            <div class="mt-1" style="color:#f87171">Reason: {{ $adjustment->void_reason }}</div>
          @endif
        @endif
      </div>
    </div>
  </div>
</div>

@if ($adjustment->isVoidable() && auth()->user()->hasRole('Admin','Manager'))
  <div id="void-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#181c24; border:1px solid rgba(255,255,255,.12); border-radius:10px; max-width:500px; width:100%;">
      <div style="padding:16px 20px; border-bottom:1px solid rgba(255,255,255,.07); font-size:14px; font-weight:700;">Void Posted Adjustment</div>
      <form method="POST" action="{{ route('stock-adjustments.void', $adjustment) }}">
        @csrf
        <div style="padding:20px;">
          <div class="text-xs mb-3" style="color:#8892a4">Voiding will reverse the inventory changes on every line, restoring qty_on_hand to the pre-post value. A reason is required.</div>
          <label class="label block mb-1.5">Void Reason <span style="color:#f87171">*</span></label>
          <textarea class="input" name="void_reason" rows="3" required minlength="5" placeholder="e.g. wrong type selected, bad data entry"></textarea>
        </div>
        <div style="padding:12px 20px; border-top:1px solid rgba(255,255,255,.07); display:flex; gap:8px; justify-content:flex-end;">
          <button type="button" onclick="document.getElementById('void-modal').style.display='none'" class="btn btn-secondary btn-sm">Back</button>
          <button type="submit" class="btn btn-danger btn-sm">Void Adjustment</button>
        </div>
      </form>
    </div>
  </div>
@endif
@endsection
