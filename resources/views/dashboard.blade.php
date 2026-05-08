@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="mb-5">
  <div class="font-mono text-[10px] tracking-[0.12em] uppercase mb-1" style="color:#8892a4">Overview</div>
  <div class="flex items-center gap-2.5 text-[21px] font-bold tracking-tight">
    <span class="rounded inline-block" style="width:4px;height:21px;background:#38bdf8"></span>Dashboard
  </div>
  <div class="text-xs mt-1 pl-3.5" style="color:#8892a4">
    Month-to-date · <span class="font-mono" style="color:#38bdf8">{{ now()->format('M j, Y') }}</span>
  </div>
</div>

<div class="grid gap-3 mb-6" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
  <div class="card" style="border-top:2px solid #fb923c">
    <div class="card-body !py-3.5">
      <div class="font-mono text-[9px] tracking-[0.1em] uppercase mb-1.5" style="color:#8892a4">Sales Today</div>
      <div class="text-[21px] font-bold tracking-tight" style="color:#fb923c">₱{{ number_format($salesToday, 2) }}</div>
      <div class="text-[11px] mt-1" style="color:#8892a4">
        @if ($salesDoDPct === null)
          vs ₱{{ number_format($salesYesterday, 2) }} yesterday
        @elseif ($salesDoDPct > 0)
          <span style="color:#34d399">↑ {{ $salesDoDPct }}%</span> vs yesterday
        @elseif ($salesDoDPct < 0)
          <span style="color:#f87171">↓ {{ abs($salesDoDPct) }}%</span> vs yesterday
        @else
          flat vs yesterday
        @endif
      </div>
    </div>
  </div>
  <div class="card" style="border-top:2px solid #fb923c">
    <div class="card-body !py-3.5">
      <div class="font-mono text-[9px] tracking-[0.1em] uppercase mb-1.5" style="color:#8892a4">Sales MTD</div>
      <div class="text-[21px] font-bold tracking-tight" style="color:#fb923c">₱{{ number_format($salesMTD, 2) }}</div>
    </div>
  </div>

  <div class="card" style="border-top:2px solid #94a3b8">
    <div class="card-body !py-3.5">
      <div class="font-mono text-[9px] tracking-[0.1em] uppercase mb-1.5" style="color:#8892a4">Cash on Hand</div>
      <div class="text-[21px] font-bold tracking-tight" style="color:#94a3b8">₱{{ number_format($cashOnHand, 2) }}</div>
      <div class="text-[11px] mt-1" style="color:#8892a4">
        {{ $cashOnHandCount }} un-deposited collection{{ $cashOnHandCount === 1 ? '' : 's' }}
        @if ($cashOnHand > 0)
          · <a href="{{ route('deposits.create') }}" style="color:#38bdf8">Deposit →</a>
        @endif
      </div>
    </div>
  </div>

  <div class="card" style="border-top:2px solid #a78bfa">
    <div class="card-body !py-3.5">
      <div class="font-mono text-[9px] tracking-[0.1em] uppercase mb-1.5" style="color:#8892a4">Checks on Hand</div>
      <div class="text-[21px] font-bold tracking-tight" style="color:#a78bfa">₱{{ number_format($checksOnHand, 2) }}</div>
      <div class="text-[11px] mt-1" style="color:#8892a4">{{ $checksOnHandCount }} pending deposit</div>
    </div>
  </div>

  <div class="card" style="border-top:2px solid #f87171">
    <div class="card-body !py-3.5">
      <div class="font-mono text-[9px] tracking-[0.1em] uppercase mb-1.5" style="color:#8892a4">Outstanding AR</div>
      <div class="text-[21px] font-bold tracking-tight" style="color:#f87171">₱{{ number_format($arOutstanding, 2) }}</div>
      <div class="text-[11px] mt-1" style="color:#8892a4">{{ $unpaidCount }} unpaid · {{ $overdueCount }} overdue</div>
    </div>
  </div>

  <div class="card" style="border-top:2px solid #34d399">
    <div class="card-body !py-3.5">
      <div class="font-mono text-[9px] tracking-[0.1em] uppercase mb-1.5" style="color:#8892a4">Low Stock Items</div>
      <div class="text-[21px] font-bold tracking-tight" style="color:#34d399">{{ $lowStockCount + $zeroStockCount }}</div>
      <div class="text-[11px] mt-1" style="color:#8892a4">
        @if ($zeroStockCount) <span style="color:#f87171">{{ $zeroStockCount }} at zero</span> · @endif
        {{ $lowStockCount }} low
      </div>
    </div>
  </div>
</div>

<div class="card mb-6">
  <div class="card-head flex items-center justify-between flex-wrap gap-2">
    <span>Sales — Last 30 Days</span>
    <div class="flex gap-4 text-[11px]" style="color:#8892a4">
      <span>30-day total: <span class="num" style="color:#fb923c">₱{{ number_format($salesChart30dTotal, 2) }}</span></span>
      <span>Daily avg: <span class="num" style="color:#34d399">₱{{ number_format($salesChart30dAvg, 2) }}</span></span>
    </div>
  </div>
  <div class="card-body">
    <div style="position:relative;height:240px"><canvas id="chart-sales-30d"></canvas></div>
  </div>
</div>

<div class="card mb-6">
  <div class="card-head flex items-center justify-between flex-wrap gap-2">
    <span>Sales vs Purchases — Last 6 Months</span>
    <div class="flex gap-4 text-[11px] flex-wrap" style="color:#8892a4">
      <span class="flex items-center gap-1.5"><span style="display:inline-block;width:10px;height:10px;background:#fb923c;border-radius:2px"></span>Sales: <span class="num" style="color:#fb923c">₱{{ number_format($vsTotalSales, 2) }}</span></span>
      <span class="flex items-center gap-1.5"><span style="display:inline-block;width:10px;height:10px;background:#60a5fa;border-radius:2px"></span>Purchases: <span class="num" style="color:#60a5fa">₱{{ number_format($vsTotalPurchases, 2) }}</span></span>
      @php $netCashflow = $vsTotalSales - $vsTotalPurchases; @endphp
      <span>Net: <span class="num" style="color:{{ $netCashflow >= 0 ? '#34d399' : '#f87171' }}">{{ $netCashflow >= 0 ? '+' : '' }}₱{{ number_format($netCashflow, 2) }}</span></span>
    </div>
  </div>
  <div class="card-body">
    <div style="position:relative;height:260px"><canvas id="chart-sales-vs-purchases"></canvas></div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
  <div class="card">
    <div class="card-head flex items-center justify-between">
      <span>Pending Approvals</span>
      <span class="font-mono text-[10px]" style="color:#8892a4">{{ $pendingApprovals->count() }} drafts</span>
    </div>
    <div class="card-body !p-0">
      @forelse ($pendingApprovals as $p)
        <a href="{{ $p['url'] }}" class="flex items-center gap-3 px-4 py-2.5 border-b border-white/[0.05] hover:bg-white/[0.02]" style="text-decoration:none">
          <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $p['accent'] }}"></span>
          <span class="text-[10.5px] font-mono tracking-wide uppercase" style="color:{{ $p['accent'] }};min-width:90px">{{ $p['type'] }}</span>
          <span class="ref text-[12px]">{{ $p['number'] }}</span>
          <span class="dim text-[11px] flex-1">{{ \Carbon\Carbon::parse($p['date'])->format('M j') }}</span>
          @if ($p['amount'] !== null)
            <span class="num text-[12px]">₱{{ number_format($p['amount'], 2) }}</span>
          @endif
        </a>
      @empty
        <div class="text-center py-6 dim">No drafts awaiting approval. ✓</div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-head flex items-center justify-between">
      <span>POs Awaiting Receipt</span>
      @if ($posOverdueCount > 0)
        <span class="badge" style="background:rgba(248,113,113,.12);color:#f87171;border:1px solid rgba(248,113,113,.25)">{{ $posOverdueCount }} overdue</span>
      @endif
    </div>
    <div class="card-body !p-0">
      @forelse ($posOverdue as $po)
        @php $daysLate = now()->diffInDays($po->expected_date, false); @endphp
        <a href="{{ route('purchase-orders.show', $po) }}" class="flex items-center gap-3 px-4 py-2.5 border-b border-white/[0.05] hover:bg-white/[0.02]" style="text-decoration:none">
          <span class="ref text-[12px]" style="min-width:95px">{{ $po->po_number }}</span>
          <span class="text-[12px] flex-1 truncate">{{ $po->supplier->company_name }}</span>
          <span class="text-[11px]" style="color:#f87171">{{ abs((int) $daysLate) }}d late</span>
          <span class="num text-[12px]">₱{{ number_format($po->total_amount, 2) }}</span>
        </a>
      @empty
        <div class="text-center py-6 dim">No POs past their expected date. ✓</div>
      @endforelse
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
  <div class="card">
    <div class="card-head flex items-center justify-between">
      <span>AR Aging</span>
      <span class="font-mono text-[10px]" style="color:#8892a4">₱{{ number_format($arOutstanding, 2) }} total</span>
    </div>
    <div class="card-body">
      @php $maxBucket = max(array_map(fn($b) => $b['amount'], $aging)) ?: 1; @endphp
      @foreach ($aging as $bucket)
        <div class="flex items-center gap-2 mb-2">
          <div class="text-[11.5px] w-20" style="color:#8892a4">{{ $bucket['label'] }}</div>
          <div class="flex-1 h-2 rounded-full" style="background:#1f2433">
            <div class="h-2 rounded-full" style="width:{{ $bucket['amount'] > 0 ? max(2, ($bucket['amount'] / $maxBucket) * 100) : 0 }}%; background:{{ $bucket['color'] }}"></div>
          </div>
          <div class="num text-[11.5px] w-24 text-right" style="color:{{ $bucket['amount'] > 0 ? $bucket['color'] : '#4a5568' }}">₱{{ number_format($bucket['amount'], 2) }}</div>
        </div>
      @endforeach
      @if (collect($aging)->sum('amount') == 0)
        <div class="text-center py-2 dim">No outstanding AR. ✓</div>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-head">Top Customers — MTD</div>
    <div class="card-body">
      @forelse ($topCustomers as $c)
        @php $max = $topCustomers->max('total') ?: 1; @endphp
        <div class="flex items-center gap-2 mb-2">
          <div class="text-[11.5px] truncate w-32" style="color:#8892a4">{{ $c->company_name }}</div>
          <div class="flex-1 h-1.5 rounded-full" style="background:#1f2433">
            <div class="h-1.5 rounded-full" style="width:{{ ($c->total / $max) * 100 }}%; background:#60a5fa"></div>
          </div>
          <div class="font-mono text-[11px] w-20 text-right">₱{{ number_format($c->total, 0) }}</div>
        </div>
      @empty
        <div class="text-xs" style="color:#8892a4">No sales this month yet.</div>
      @endforelse
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
  <div class="card">
    <div class="card-head">Recent Sales Orders</div>
    <div class="card-body !p-0 overflow-x-auto">
      <table class="dt">
        <thead><tr><th>SO #</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
          @forelse ($recentSO as $so)
            <tr>
              <td><a class="ref" href="{{ route('sales-orders.show', $so) }}">{{ $so->so_number }}</a></td>
              <td>{{ $so->customer->company_name }}</td>
              <td class="num">₱{{ number_format($so->total_amount, 2) }}</td>
              <td><span class="badge b-{{ $so->status }}">{{ $so->status }}</span></td>
            </tr>
          @empty
            <tr><td colspan="4" class="dim text-center py-4">No orders yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head">Recent Invoices</div>
    <div class="card-body !p-0 overflow-x-auto">
      <table class="dt">
        <thead><tr><th>Invoice #</th><th>Customer</th><th class="text-right">Total</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
        <tbody>
          @forelse ($recentInvoices as $inv)
            <tr>
              <td><a class="ref" href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
              <td>{{ $inv->customer->company_name }}</td>
              <td class="num text-right">₱{{ number_format($inv->total_amount, 2) }}</td>
              <td class="num text-right" style="color:{{ $inv->balance_due > 0 ? '#f87171' : '#8892a4' }}">₱{{ number_format($inv->balance_due, 2) }}</td>
              <td><span class="badge b-{{ $inv->status }}">{{ $inv->status }}</span></td>
            </tr>
          @empty
            <tr><td colspan="5" class="dim text-center py-4">No invoices yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') return;

  // ── Common tooltip/axis options ──
  const moneyTooltip = (ctx) => '₱' + Number(ctx.parsed.y).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const moneyTick = (v) => v >= 1000 ? '₱' + (v / 1000).toFixed(0) + 'k' : '₱' + v;
  const monoFont = "'IBM Plex Mono', monospace";

  // ── 30-day daily sales bar chart ──
  const el = document.getElementById('chart-sales-30d');
  if (! el) return;

  // Build a vertical orange→transparent gradient for the bars
  const ctx = el.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 240);
  gradient.addColorStop(0, 'rgba(251,146,60,0.9)');
  gradient.addColorStop(1, 'rgba(251,146,60,0.2)');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: @json($salesChart['labels']),
      datasets: [{
        label: 'Sales',
        data: @json($salesChart['data']),
        backgroundColor: gradient,
        borderColor: '#fb923c',
        borderWidth: 1,
        borderRadius: 3,
        barPercentage: 0.85,
        categoryPercentage: 0.85,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#181c24',
          borderColor: 'rgba(255,255,255,0.12)',
          borderWidth: 1,
          titleColor: '#e2e8f4',
          bodyColor: '#e2e8f4',
          padding: 10,
          callbacks: { label: moneyTooltip },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#8892a4', font: { family: monoFont, size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 15 },
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#8892a4', font: { family: monoFont, size: 10 }, callback: moneyTick },
        },
      },
    },
  });

  // ── Sales vs Purchases (last 6 months) twin-bar chart ──
  const vsEl = document.getElementById('chart-sales-vs-purchases');
  if (vsEl) {
    new Chart(vsEl.getContext('2d'), {
      type: 'bar',
      data: {
        labels: @json($vsChart['labels']),
        datasets: [
          {
            label: 'Sales',
            data: @json($vsChart['sales']),
            backgroundColor: 'rgba(251,146,60,0.75)',
            borderColor: '#fb923c',
            borderWidth: 1,
            borderRadius: 3,
          },
          {
            label: 'Purchases',
            data: @json($vsChart['purchases']),
            backgroundColor: 'rgba(96,165,250,0.75)',
            borderColor: '#60a5fa',
            borderWidth: 1,
            borderRadius: 3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#181c24',
            borderColor: 'rgba(255,255,255,0.12)',
            borderWidth: 1,
            titleColor: '#e2e8f4',
            bodyColor: '#e2e8f4',
            padding: 10,
            callbacks: {
              label: (ctx) => ctx.dataset.label + ': ' + moneyTooltip(ctx),
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#8892a4', font: { family: monoFont, size: 10 } },
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(255,255,255,0.04)' },
            ticks: { color: '#8892a4', font: { family: monoFont, size: 10 }, callback: moneyTick },
          },
        },
      },
    });
  }
});
</script>
@endpush
@endsection
