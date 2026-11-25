@props(['label', 'value', 'sub' => null])
<div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-xs">
    <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900 tabular-nums">{{ $value }}</p>
    @if ($sub)<p class="mt-0.5 text-xs text-slate-500">{{ $sub }}</p>@endif
</div>
