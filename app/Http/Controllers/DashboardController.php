<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $monthStart = Carbon::now()->startOfMonth();

        // Today's sales + day-over-day comparison
        $salesToday = (float) SalesOrder::where('status', '!=', 'cancelled')
            ->whereDate('order_date', $today)
            ->sum('total_amount');
        $salesYesterday = (float) SalesOrder::where('status', '!=', 'cancelled')
            ->whereDate('order_date', $yesterday)
            ->sum('total_amount');
        $salesDoDPct = $salesYesterday > 0
            ? round((($salesToday - $salesYesterday) / $salesYesterday) * 100, 1)
            : null;

        $salesMTD = SalesOrder::where('status', '!=', 'cancelled')
            ->where('order_date', '>=', $monthStart)
            ->sum('total_amount');

        $arOutstanding = SalesInvoice::whereIn('status', ['issued', 'partial', 'overdue'])
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as bal')
            ->value('bal');

        $unpaidCount = SalesInvoice::whereIn('status', ['issued', 'partial', 'overdue'])->count();
        $overdueCount = SalesInvoice::where('status', 'overdue')->count();

        $lowStockCount = Item::where('status', 'active')
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->where('qty_on_hand', '>', 0)->count();
        $zeroStockCount = Item::where('status', 'active')->where('qty_on_hand', '<=', 0)->count();

        $topCustomers = Customer::query()
            ->leftJoin('sales_orders', 'sales_orders.customer_id', '=', 'customers.id')
            ->where('sales_orders.status', '!=', 'cancelled')
            ->where('sales_orders.order_date', '>=', $monthStart)
            ->groupBy('customers.id', 'customers.company_name')
            ->selectRaw('customers.id, customers.company_name, COALESCE(SUM(sales_orders.total_amount),0) as total')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $recentSO = SalesOrder::with('customer')->latest()->limit(5)->get();
        $recentInvoices = SalesInvoice::with('customer')->latest()->limit(5)->get();

        // Cash on Hand = cash collections not yet posted to a bank deposit.
        // (deposit_id NULL = un-deposited, or attached to a draft deposit = slip prepared but cash still in drawer)
        $undepositedQuery = fn ($method) => Collection::where('payment_method', $method)
            ->where(function ($q) {
                $q->whereNull('deposit_id')
                  ->orWhereHas('deposit', fn ($d) => $d->where('status', 'draft'));
            });

        $cashOnHand = (float) $undepositedQuery('cash')->sum('amount');
        $cashOnHandCount = $undepositedQuery('cash')->count();
        $checksOnHand = (float) $undepositedQuery('check')->sum('amount');
        $checksOnHandCount = $undepositedQuery('check')->count();

        // ── Pending Approvals (unified list of drafts awaiting confirmation) ──
        $pendingApprovals = collect()
            ->merge(SalesOrder::where('status', 'draft')->latest('id')->limit(5)->get()->map(fn ($r) => [
                'type' => 'Sales Order', 'accent' => '#fb923c',
                'number' => $r->so_number, 'amount' => (float) $r->total_amount,
                'date' => $r->order_date, 'url' => route('sales-orders.show', $r),
            ]))
            ->merge(PurchaseOrder::where('status', 'draft')->latest('id')->limit(5)->get()->map(fn ($r) => [
                'type' => 'Purchase Order', 'accent' => '#60a5fa',
                'number' => $r->po_number, 'amount' => (float) $r->total_amount,
                'date' => $r->order_date, 'url' => route('purchase-orders.show', $r),
            ]))
            ->merge(GoodsReceipt::where('status', 'draft')->latest('id')->limit(5)->get()->map(fn ($r) => [
                'type' => 'Stock Receipt', 'accent' => '#60a5fa',
                'number' => $r->grn_number, 'amount' => null,
                'date' => $r->received_date, 'url' => route('stock-receiving.show', $r),
            ]))
            ->merge(SalesInvoice::where('status', 'draft')->latest('id')->limit(5)->get()->map(fn ($r) => [
                'type' => 'Invoice', 'accent' => '#fb923c',
                'number' => $r->invoice_number, 'amount' => (float) $r->total_amount,
                'date' => $r->invoice_date, 'url' => route('invoices.show', $r),
            ]))
            ->merge(Deposit::where('status', 'draft')->latest('id')->limit(5)->get()->map(fn ($r) => [
                'type' => 'Deposit', 'accent' => '#fb923c',
                'number' => $r->deposit_number, 'amount' => (float) $r->total_amount,
                'date' => $r->deposit_date, 'url' => route('deposits.show', $r),
            ]))
            ->sortByDesc('date')
            ->take(8)
            ->values();

        // ── POs awaiting receipt — issued/partial with expected_date in the past ──
        $posOverdue = PurchaseOrder::with('supplier')
            ->whereIn('status', ['issued', 'partial'])
            ->whereNotNull('expected_date')
            ->where('expected_date', '<', $today)
            ->orderBy('expected_date')
            ->limit(5)
            ->get();
        $posOverdueCount = PurchaseOrder::whereIn('status', ['issued', 'partial'])
            ->whereNotNull('expected_date')
            ->where('expected_date', '<', $today)
            ->count();

        // ── AR Aging (buckets in days since due_date) ──
        $arAging = SalesInvoice::query()
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) <= 0 THEN total_amount - amount_paid ELSE 0 END), 0) AS b_current,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 1 AND 30 THEN total_amount - amount_paid ELSE 0 END), 0) AS b_30,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN total_amount - amount_paid ELSE 0 END), 0) AS b_60,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN total_amount - amount_paid ELSE 0 END), 0) AS b_90,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN total_amount - amount_paid ELSE 0 END), 0) AS b_91
            ")
            ->first();

        $aging = [
            ['label' => 'Current',  'amount' => (float) $arAging->b_current, 'color' => '#34d399'],
            ['label' => '1-30 days','amount' => (float) $arAging->b_30,      'color' => '#fbbf24'],
            ['label' => '31-60',    'amount' => (float) $arAging->b_60,      'color' => '#fb923c'],
            ['label' => '61-90',    'amount' => (float) $arAging->b_90,      'color' => '#f87171'],
            ['label' => '90+',      'amount' => (float) $arAging->b_91,      'color' => '#ef4444'],
        ];

        // ── 30-day daily sales chart ──
        $chartStart = $today->copy()->subDays(29);
        $rawDailySales = SalesOrder::where('status', '!=', 'cancelled')
            ->whereDate('order_date', '>=', $chartStart)
            ->selectRaw('DATE(order_date) AS d, COALESCE(SUM(total_amount), 0) AS total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $salesChart = ['labels' => [], 'data' => []];
        for ($i = 0; $i < 30; $i++) {
            $date = $chartStart->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $salesChart['labels'][] = $date->format('M j');
            $salesChart['data'][] = (float) ($rawDailySales[$key] ?? 0);
        }
        $salesChart30dTotal = array_sum($salesChart['data']);
        $salesChart30dAvg = $salesChart30dTotal / 30;

        // ── 6-month Sales vs Purchases chart ──
        $monthsBack = 6;
        $vsStart = Carbon::today()->subMonthsNoOverflow($monthsBack - 1)->startOfMonth();

        $salesByMonth = SalesOrder::where('status', '!=', 'cancelled')
            ->where('order_date', '>=', $vsStart)
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') AS ym, COALESCE(SUM(total_amount), 0) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $purchasesByMonth = PurchaseOrder::where('status', '!=', 'cancelled')
            ->where('order_date', '>=', $vsStart)
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') AS ym, COALESCE(SUM(total_amount), 0) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $vsChart = ['labels' => [], 'sales' => [], 'purchases' => []];
        for ($i = 0; $i < $monthsBack; $i++) {
            $month = $vsStart->copy()->addMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $vsChart['labels'][] = $month->format('M Y');
            $vsChart['sales'][] = (float) ($salesByMonth[$key] ?? 0);
            $vsChart['purchases'][] = (float) ($purchasesByMonth[$key] ?? 0);
        }
        $vsTotalSales = array_sum($vsChart['sales']);
        $vsTotalPurchases = array_sum($vsChart['purchases']);

        return view('dashboard', compact(
            'salesMTD', 'salesToday', 'salesYesterday', 'salesDoDPct',
            'arOutstanding', 'unpaidCount', 'overdueCount',
            'lowStockCount', 'zeroStockCount', 'topCustomers', 'recentSO', 'recentInvoices',
            'cashOnHand', 'cashOnHandCount', 'checksOnHand', 'checksOnHandCount',
            'pendingApprovals', 'posOverdue', 'posOverdueCount', 'aging',
            'salesChart', 'salesChart30dTotal', 'salesChart30dAvg',
            'vsChart', 'vsTotalSales', 'vsTotalPurchases'
        ));
    }
}
