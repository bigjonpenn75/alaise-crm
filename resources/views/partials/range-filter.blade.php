{{-- Preset buttons plus a custom from/to form. Expects $range (App\Support\DateRange). --}}
<div class="flex flex-wrap items-center gap-1.5">
    @foreach (\App\Support\DateRange::PRESETS as $key => $label)
        @continue($key === 'custom')
        <a href="{{ request()->fullUrlWithQuery(['range' => $key, 'from' => null, 'to' => null, 'page' => null]) }}"
            @class(['btn btn-sm', 'btn-primary' => $range->key === $key, 'btn-secondary' => $range->key !== $key])>{{ $label }}</a>
    @endforeach
    <form method="GET" class="flex items-center gap-1.5">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="from" value="{{ $range->key === 'custom' ? $range->from : '' }}" class="input w-36 py-1" aria-label="From date">
        <span class="text-xs text-slate-500">to</span>
        <input type="date" name="to" value="{{ $range->key === 'custom' ? $range->to : '' }}" class="input w-36 py-1" aria-label="To date">
        <button @class(['btn btn-sm', 'btn-primary' => $range->key === 'custom', 'btn-secondary' => $range->key !== 'custom'])>Apply</button>
    </form>
</div>
