<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name') }} — @yield('title', 'Dashboard')</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        bg: '#0a0b0e', s1:'#111318', s2:'#181c24', s3:'#1f2433',
        sub:'#8892a4', dim:'#4a5568',
        amber:{500:'#fbbf24'},
        c: {
          dash:'#38bdf8', buy:'#60a5fa', sell:'#fb923c',
          inv:'#34d399', exp:'#a78bfa', rep:'#fbbf24', master:'#94a3b8',
          danger:'#f87171', warn:'#fbbf24', ok:'#34d399',
        }
      },
      fontFamily: {
        sans: ['IBM Plex Sans','sans-serif'],
        mono: ['IBM Plex Mono','monospace'],
      },
    }
  }
}
</script>
<style>
  body { background:#0a0b0e; color:#e2e8f4; font-family:'IBM Plex Sans',sans-serif; font-size:14px; }
  [x-cloak] { display: none !important; }
  ::-webkit-scrollbar { width:6px; height:6px; }
  ::-webkit-scrollbar-track { background:#111318; }
  ::-webkit-scrollbar-thumb { background:#1f2433; border-radius:3px; }
  .b-draft     { background:rgba(148,163,184,.1); color:#94a3b8; border:1px solid rgba(148,163,184,.2); }
  .b-confirmed { background:rgba(96,165,250,.12); color:#60a5fa; border:1px solid rgba(96,165,250,.25); }
  .b-issued    { background:rgba(129,140,248,.12); color:#818cf8; border:1px solid rgba(129,140,248,.25); }
  .b-partial   { background:rgba(251,191,36,.12); color:#fbbf24; border:1px solid rgba(251,191,36,.25); }
  .b-paid      { background:rgba(52,211,153,.12); color:#34d399; border:1px solid rgba(52,211,153,.25); }
  .b-fulfilled { background:rgba(52,211,153,.12); color:#34d399; border:1px solid rgba(52,211,153,.25); }
  .b-overdue   { background:rgba(248,113,113,.12); color:#f87171; border:1px solid rgba(248,113,113,.25); }
  .b-void      { background:rgba(71,85,105,.15); color:#475569; border:1px solid rgba(71,85,105,.2); }
  .b-cancelled { background:rgba(71,85,105,.15); color:#475569; border:1px solid rgba(71,85,105,.2); }
  .b-active    { background:rgba(52,211,153,.1); color:#34d399; border:1px solid rgba(52,211,153,.2); }
  .b-inactive  { background:rgba(71,85,105,.12); color:#64748b; border:1px solid rgba(71,85,105,.2); }
  .badge { display:inline-flex; align-items:center; padding:2px 7px; border-radius:3px; font-family:'IBM Plex Mono',monospace; font-size:10px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
  .input { background:#181c24; border:1px solid rgba(255,255,255,.12); color:#e2e8f4; border-radius:5px; padding:7px 10px; font-size:12.5px; width:100%; }
  .input:focus { outline:none; border-color:#38bdf8; box-shadow:0 0 0 2px rgba(56,189,248,.1); }
  .label { font-family:'IBM Plex Mono',monospace; font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:#8892a4; font-weight:500; }
  .btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; border:none; font-family:'IBM Plex Sans',sans-serif; white-space:nowrap; }
  .btn-primary { background:#38bdf8; color:#000; }
  .btn-primary:hover { filter:brightness(1.1); }
  .btn-buy { background:#60a5fa; color:#000; }
  .btn-sell { background:#fb923c; color:#000; }
  .btn-inv { background:#34d399; color:#000; }
  .btn-master { background:#94a3b8; color:#000; }
  .btn-secondary { background:#181c24; color:#e2e8f4; border:1px solid rgba(255,255,255,.12); }
  .btn-secondary:hover { background:#1f2433; }
  .btn-ok { background:rgba(52,211,153,.15); color:#34d399; border:1px solid rgba(52,211,153,.3); }
  .btn-danger { background:rgba(248,113,113,.12); color:#f87171; border:1px solid rgba(248,113,113,.3); }
  .btn-warn { background:rgba(251,191,36,.12); color:#fbbf24; border:1px solid rgba(251,191,36,.25); }
  .btn-sm { padding:4px 10px; font-size:11px; }
  .ref { font-family:'IBM Plex Mono',monospace; font-size:11.5px; color:#38bdf8; }
  .num { font-family:'IBM Plex Mono',monospace; font-size:12px; }
  .dim { color:#8892a4; font-size:12px; }
  .card { background:#111318; border:1px solid rgba(255,255,255,.07); border-radius:8px; }
  .card-head { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,.07); font-family:'IBM Plex Mono',monospace; font-size:10px; letter-spacing:.1em; text-transform:uppercase; color:#8892a4; font-weight:600; }
  .card-body { padding:16px 18px; }
  table.dt { width:100%; border-collapse:collapse; font-size:12.5px; }
  table.dt th { background:#181c24; padding:8px 12px; text-align:left; font-family:'IBM Plex Mono',monospace; font-size:9.5px; letter-spacing:.09em; text-transform:uppercase; color:#8892a4; border-bottom:1px solid rgba(255,255,255,.07); font-weight:500; }
  table.dt td { padding:9px 12px; border-bottom:1px solid rgba(255,255,255,.07); color:#e2e8f4; vertical-align:middle; }
  table.dt tr:last-child td { border-bottom:none; }
  table.dt tr:hover td { background:rgba(255,255,255,.02); }
  .alert-warn { background:rgba(251,191,36,.08); border:1px solid rgba(251,191,36,.2); color:#fbbf24; padding:10px 14px; border-radius:6px; font-size:12.5px; }
  .alert-ok   { background:rgba(52,211,153,.08); border:1px solid rgba(52,211,153,.2); color:#34d399; padding:10px 14px; border-radius:6px; font-size:12.5px; }
  .alert-info { background:rgba(56,189,248,.08); border:1px solid rgba(56,189,248,.2); color:#38bdf8; padding:10px 14px; border-radius:6px; font-size:12.5px; }
  .alert-danger { background:rgba(248,113,113,.08); border:1px solid rgba(248,113,113,.25); color:#f87171; padding:10px 14px; border-radius:6px; font-size:12.5px; }
  .toast { position:fixed; bottom:20px; right:20px; background:#1f2433; border:1px solid rgba(255,255,255,.1); border-left:3px solid #38bdf8; padding:10px 16px; border-radius:8px; z-index:300; font-size:12.5px; box-shadow:0 8px 24px rgba(0,0,0,.4); }
  .toast.ok { border-left-color:#34d399; color:#34d399; }
  .toast.err { border-left-color:#f87171; color:#f87171; }
  a { color:inherit; text-decoration:none; }
  .nav-link { display:flex; align-items:center; gap:9px; padding:7px 14px; margin:0 6px; border-radius:6px; font-size:12.5px; color:#8892a4; cursor:pointer; transition:all .12s; border-left:2px solid transparent; }
  .nav-link:hover { background:#181c24; color:#e2e8f4; }
  .nav-link.active { background:#181c24; color:#e2e8f4; border-left-color:var(--ac, #38bdf8); }
  .nav-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; opacity:.7; }
  .nav-link.active .nav-dot { opacity:1; }
  .nav-section { padding:14px 14px 4px; font-family:'IBM Plex Mono',monospace; font-size:9px; letter-spacing:.12em; text-transform:uppercase; color:#4a5568; font-weight:600; }
</style>
@stack('head')
</head>
<body class="h-full m-0 p-0">

<div class="flex h-screen overflow-hidden" x-data="{ sidebar: false }">

  {{-- ═══ MOBILE TOP BAR (hamburger) ═══ --}}
  <div class="md:hidden fixed top-0 inset-x-0 z-30 flex items-center gap-3 px-4 py-2.5"
       style="background:#111318;border-bottom:1px solid rgba(255,255,255,.07);height:48px">
    <button @click="sidebar = !sidebar" class="text-xl leading-none" style="color:#e2e8f4" aria-label="Toggle menu">☰</button>
    <div class="flex-1">
      <div class="text-[14px] font-bold leading-tight">InventoryPro</div>
      <div class="text-[9px] font-mono" style="color:#4a5568">@yield('title', 'Dashboard')</div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn btn-secondary btn-sm">Out</button>
    </form>
  </div>

  {{-- ═══ MOBILE BACKDROP ═══ --}}
  <div x-show="sidebar" x-cloak @click="sidebar = false"
       class="md:hidden fixed inset-0 bg-black/50 z-40"
       x-transition.opacity></div>

  {{-- ═══ SIDEBAR ═══ --}}
  <nav class="fixed md:static inset-y-0 left-0 z-50 flex flex-col overflow-y-auto transform transition-transform duration-200"
       :class="sidebar ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
       style="width:218px; min-width:218px; background:#111318; border-right:1px solid rgba(255,255,255,.07);">
    <div class="px-4 pt-4 pb-3 border-b border-white/[0.07]">
      <div class="font-mono text-[9px] tracking-[0.14em] uppercase text-c-dash font-medium">ISMS · v1.0</div>
      <div class="text-[17px] font-bold mt-1">InventoryPro</div>
      <div class="text-[10px] font-mono mt-0.5" style="color:#4a5568">{{ request()->getHost() }} · LAN</div>
    </div>

    <div class="nav-section">Overview</div>
    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" style="--ac:#38bdf8">
      <span class="nav-dot" style="background:#38bdf8"></span>Dashboard
    </a>

    <div class="nav-section">Purchasing</div>
    <a href="{{ route('purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" style="--ac:#60a5fa">
      <span class="nav-dot" style="background:#60a5fa"></span>Purchase Orders
    </a>
    <a href="{{ route('stock-receiving.index') }}" class="nav-link {{ request()->routeIs('stock-receiving.*') ? 'active' : '' }}" style="--ac:#60a5fa">
      <span class="nav-dot" style="background:#60a5fa"></span>Stock Receiving
    </a>

    <div class="nav-section">Sales</div>
    <a href="{{ route('sales-orders.index') }}" class="nav-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" style="--ac:#fb923c">
      <span class="nav-dot" style="background:#fb923c"></span>Sales Orders
    </a>
    <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" style="--ac:#fb923c">
      <span class="nav-dot" style="background:#fb923c"></span>Invoices
    </a>
    <a href="{{ route('collections.index') }}" class="nav-link {{ request()->routeIs('collections.*') ? 'active' : '' }}" style="--ac:#fb923c">
      <span class="nav-dot" style="background:#fb923c"></span>Collections
    </a>
    <a href="{{ route('deposits.index') }}" class="nav-link {{ request()->routeIs('deposits.*') ? 'active' : '' }}" style="--ac:#fb923c">
      <span class="nav-dot" style="background:#fb923c"></span>Deposits
    </a>

    <div class="nav-section">Inventory</div>
    <a href="{{ route('items.index') }}" class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}" style="--ac:#34d399">
      <span class="nav-dot" style="background:#34d399"></span>Items
    </a>
    <a href="{{ route('stock-adjustments.index') }}" class="nav-link {{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}" style="--ac:#34d399">
      <span class="nav-dot" style="background:#34d399"></span>Stock Adjustment
    </a>

    <div class="nav-section">Master Data</div>
    <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" style="--ac:#94a3b8">
      <span class="nav-dot" style="background:#94a3b8"></span>Customers
    </a>
    <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" style="--ac:#94a3b8">
      <span class="nav-dot" style="background:#94a3b8"></span>Suppliers
    </a>
    <a href="{{ route('bank-accounts.index') }}" class="nav-link {{ request()->routeIs('bank-accounts.*') ? 'active' : '' }}" style="--ac:#94a3b8">
      <span class="nav-dot" style="background:#94a3b8"></span>Bank Accounts
    </a>

    <div class="mt-auto px-4 py-3 border-t border-white/[0.07] text-[11px]" style="color:#8892a4">
      <div class="flex items-center justify-between">
        <div>
          <div class="font-semibold text-white text-xs">{{ auth()->user()?->full_name }}</div>
          <div class="font-mono text-[10px]" style="color:#4a5568">{{ auth()->user()?->role?->role_name }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-secondary btn-sm" type="submit">Logout</button>
        </form>
      </div>
    </div>
  </nav>

  {{-- ═══ MAIN ═══ --}}
  <main class="flex-1 overflow-y-auto" style="padding-top:10px">
    <div class="px-4 sm:px-6 md:px-8 py-4 md:py-6 pt-[60px] md:pt-4">
      @if (session('flash'))
        <div class="alert-ok mb-4">✓ {{ session('flash') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert-danger mb-4">
          <div class="font-semibold mb-1">Please fix the following:</div>
          <ul class="list-disc ml-5 space-y-0.5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @yield('content')
    </div>
  </main>
</div>

@stack('scripts')
</body>
</html>
