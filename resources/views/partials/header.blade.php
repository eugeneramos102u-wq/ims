@props(['eyebrow' => '', 'title' => '', 'accent' => '#38bdf8', 'subtitle' => null])
<div class="mb-5">
  @if ($eyebrow)
    <div class="font-mono text-[10px] tracking-[0.12em] uppercase mb-1" style="color:#8892a4">{{ $eyebrow }}</div>
  @endif
  <div class="flex items-center gap-2.5 text-[21px] font-bold tracking-tight">
    <span class="rounded inline-block" style="width:4px;height:21px;background:{{ $accent }}"></span>{{ $title }}
  </div>
  @if ($subtitle)
    <div class="text-xs mt-1 pl-3.5" style="color:#8892a4">{{ $subtitle }}</div>
  @endif
</div>
