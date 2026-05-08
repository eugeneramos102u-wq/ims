<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — ISMS</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { background:#0a0b0e; color:#e2e8f4; font-family:'IBM Plex Sans',sans-serif; }
  .input { background:#181c24; border:1px solid rgba(255,255,255,.12); color:#e2e8f4; border-radius:5px; padding:9px 12px; font-size:13px; width:100%; }
  .input:focus { outline:none; border-color:#38bdf8; box-shadow:0 0 0 2px rgba(56,189,248,.1); }
  .label { font-family:'IBM Plex Mono',monospace; font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:#8892a4; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
  <div style="background:#111318; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:28px;">
    <div class="font-mono text-[10px] tracking-[0.14em] uppercase mb-1" style="color:#38bdf8">ISMS · v1.0</div>
    <div class="text-[22px] font-bold mb-1">InventoryPro</div>
    <div class="text-xs mb-6" style="color:#4a5568;font-family:'IBM Plex Mono',monospace">Inventory & Sales Management</div>

    @if ($errors->any())
      <div class="mb-4" style="background:rgba(248,113,113,.08); border:1px solid rgba(248,113,113,.25); color:#f87171; padding:9px 12px; border-radius:6px; font-size:12.5px;">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
      @csrf
      <div>
        <label class="label block mb-1.5">Username</label>
        <input class="input" name="username" autofocus value="{{ old('username') }}" required>
      </div>
      <div>
        <label class="label block mb-1.5">Password</label>
        <input class="input" type="password" name="password" required>
      </div>
      <label class="flex items-center gap-2 text-xs" style="color:#8892a4">
        <input type="checkbox" name="remember"> Remember me
      </label>
      <button type="submit" class="w-full" style="background:#38bdf8;color:#000;padding:10px;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;border:none;">
        Sign In
      </button>
    </form>

    <div class="mt-6 pt-4 border-t border-white/[0.07] text-[11px]" style="color:#4a5568">
      <div class="font-mono mb-1">Default seed accounts:</div>
      <div>admin / admin123 · manager / manager123 · cashier / cashier123</div>
    </div>
  </div>
</div>

</body>
</html>
