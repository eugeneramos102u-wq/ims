@extends('layouts.app')
@section('title', 'Invoice from ' . $order->so_number)

@section('content')
@include('partials.header', [
  'eyebrow' => 'Sales · Invoices',
  'title' => 'New Invoice from ' . $order->so_number,
  'accent' => '#fb923c',
  'subtitle' => 'Invoice # ' . $nextInvoiceNumber . ' · ' . $order->customer->company_name,
])

<form method="POST" action="{{ route('invoices.store-from-so', $order) }}" class="grid gap-3" style="grid-template-columns:1fr 280px">
  @csrf

  <div>
    <div class="card mb-3">
      <div class="card-head">Invoice Header</div>
      <div class="card-body grid grid-cols-3 gap-3">
        <div>
          <label class="label block mb-1.5">Invoice Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Due Date <span style="color:#f87171">*</span></label>
          <input class="input" type="date" name="due_date" value="{{ old('due_date', $invoice->due_date) }}" required>
        </div>
        <div>
          <label class="label block mb-1.5">Currency</label>
          <input class="input" value="PHP" readonly>
        </div>
        <div class="col-span-3">
          <label class="label block mb-1.5">Notes</label>
          <input class="input" name="notes" value="{{ old('notes') }}">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head">Lines (uninvoiced from SO)</div>
      <div class="card-body !p-0">
        <table class="dt">
          <thead><tr>
            <th>Item</th><th>UoM</th>
            <th class="text-right">Ordered</th><th class="text-right">Already Inv.</th>
            <th class="text-right">Remaining</th>
            <th class="text-right">Invoice Now</th>
            <th class="text-right">Unit Price</th>
          </tr></thead>
          <tbody>
            @foreach ($order->items as $line)
              @php $remaining = $line->qtyRemaining(); @endphp
              <tr>
                <td><span class="ref">{{ $line->item->item_code }}</span> · {{ $line->item->item_name }}</td>
                <td class="dim">{{ $line->item->uom->uom_code }}</td>
                <td class="num text-right">{{ number_format($line->qty_ordered, 2) }}</td>
                <td class="num text-right" style="color:#34d399">{{ number_format($line->qty_invoiced, 2) }}</td>
                <td class="num text-right" style="color:{{ $remaining > 0 ? '#fbbf24' : '#8892a4' }}">{{ number_format($remaining, 2) }}</td>
                <td>
                  <input type="hidden" name="lines[{{ $loop->index }}][so_item_id]" value="{{ $line->id }}">
                  <input class="input text-right" type="number" step="0.01" min="0" max="{{ $remaining }}"
                         name="lines[{{ $loop->index }}][qty_invoiced]"
                         value="{{ $remaining }}" @disabled($remaining <= 0)>
                </td>
                <td class="num text-right">₱{{ number_format($line->unit_price, 4) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head">Actions</div>
      <div class="card-body flex flex-col gap-2">
        @php $canIssue = auth()->user()->hasRole('Admin', 'Manager'); @endphp
        @if ($canIssue)
          <button type="submit" name="action" value="issue" class="btn btn-ok" style="justify-content:center">Save and Issue</button>
          <button type="submit" name="action" value="draft" class="btn btn-secondary" style="justify-content:center">Save as Draft</button>
        @else
          <button type="submit" name="action" value="draft" class="btn btn-sell" style="justify-content:center">Save as Draft</button>
        @endif
        <a href="{{ route('sales-orders.show', $order) }}" class="btn btn-secondary" style="justify-content:center">Cancel</a>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-head">Customer</div>
      <div class="card-body text-[12.5px]" style="line-height:1.7">
        <div class="font-semibold">{{ $order->customer->company_name }}</div>
        <div class="dim">Term: {{ $order->customer->paymentTerm?->term_name ?? 'COD' }}</div>
        <div class="dim">Credit limit: ₱{{ number_format($order->customer->credit_limit, 2) }}</div>
      </div>
    </div>
  </div>
</form>
@endsection
