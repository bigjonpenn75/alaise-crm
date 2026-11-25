@props(['month', 'months' => 36])
{{-- Month switcher for the monthly reports. $month is a Carbon date in the month being shown. --}}
<form method="GET" class="flex items-center gap-2">
    @foreach (request()->except('month') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <a class="btn btn-secondary btn-sm" href="{{ request()->fullUrlWithQuery(['month' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}">&larr;</a>
    <select name="month" class="input w-40 py-1.5" onchange="this.form.submit()" aria-label="Month">
        @for ($i = 0; $i < $months; $i++)
            @php($m = now()->startOfMonth()->subMonthsNoOverflow($i))
            <option value="{{ $m->format('Y-m') }}" @selected($m->isSameMonth($month))>{{ $m->format('F Y') }}</option>
        @endfor
        @if ($month->lt(now()->startOfMonth()->subMonthsNoOverflow($months - 1)))
            <option value="{{ $month->format('Y-m') }}" selected>{{ $month->format('F Y') }}</option>
        @endif
    </select>
    @if (! $month->isSameMonth(now()))
        <a class="btn btn-secondary btn-sm" href="{{ request()->fullUrlWithQuery(['month' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}">&rarr;</a>
    @endif
</form>
