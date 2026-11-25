@php
    use App\Enums\Channel;
    use App\Services\ReportService as R;
    $dim = fn ($v, string $text) => ((float) $v == 0.0 ? '<span class="zero">' : '<span>').$text.'</span>';
@endphp
<x-layouts.app :title="$business->short_name.' marketing performance'">
    <x-page-header :title="$business->short_name.' marketing performance'" :subtitle="$month->format('F Y')">
        <x-month-nav :month="$month" />
    </x-page-header>

    <x-card :padded="false">
        <div class="overflow-x-auto">
            <table class="grid-table">
                <thead>
                    <tr>
                        <th colspan="2"></th>
                        <th colspan="{{ 2 + $locations->count() }}" class="group edge">{{ $business->is_clinic ? 'New customers' : 'Sales' }}</th>
                        <th colspan="4" class="group edge">Performance</th>
                        @foreach (Channel::cases() as $channel)<th colspan="3" class="edge">{{ $channel->label() }}</th>@endforeach
                        <th class="edge">Staff</th>
                    </tr>
                    <tr>
                        <th>Day</th><th>Date</th>
                        <th class="edge">{{ $business->is_clinic ? 'Total' : 'Leads' }}</th>
                        @foreach ($locations as $location)<th>{{ $location->name }}</th>@endforeach
                        <th>Revenue</th>
                        <th class="edge">Total spend</th><th>Ave CPL</th><th>Ave lead val</th><th>Cost %</th>
                        @foreach (Channel::cases() as $channel)<th class="edge">Spend</th><th>Leads</th><th>CPL</th>@endforeach
                        <th class="edge">Spend</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php($spend = array_sum($row['spend']) + $row['staff'])
                        <tr class="{{ $row['type'] === 'day' ? '' : $row['type'] }}">
                            @if ($row['type'] === 'day')
                                <td class="label">{{ $row['date']->format('D') }}</td><td class="label">{{ $row['date']->format('jS') }}</td>
                            @else
                                <td class="label" colspan="2">{{ $row['type'] === 'week' ? 'Week total' : 'Month total' }}</td>
                            @endif
                            <td class="edge">{!! $dim($row['leads'], number_format($row['leads'])) !!}</td>
                            @foreach ($locations as $location)
                                @php($n = $row['locations'][$location->id] ?? 0)
                                <td>{!! $dim($n, number_format($n)) !!}</td>
                            @endforeach
                            <td>{!! $dim($row['revenue'], money($row['revenue'])) !!}</td>
                            <td class="edge">{!! $dim($spend, money($spend)) !!}</td>
                            <td>{{ money(R::ratio($spend, $row['leads'])) }}</td>
                            <td>{{ money(R::ratio($row['revenue'], $row['leads'])) }}</td>
                            <td>{{ percent(R::ratio($spend, $row['revenue'])) }}</td>
                            @foreach (Channel::cases() as $channel)
                                @php([$s, $l] = [$row['spend'][$channel->value], $row['channelLeads'][$channel->value]])
                                <td class="edge">{!! $dim($s, money($s)) !!}</td>
                                <td>{!! $dim($l, number_format($l)) !!}</td>
                                <td>{{ money(R::ratio($s, $l)) }}</td>
                            @endforeach
                            <td class="edge">{!! $dim($row['staff'], money($row['staff'])) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    <p class="mt-3 text-xs text-slate-500">
        @if ($business->is_clinic)
            New customers are counted on the day they were added. Revenue is appointment fees for that day, leaving out no-shows and cancellations. Channel leads come from bookings logged against {{ $business->short_name }}.
        @elseif ($business->uses_reception_split)
            Leads and revenue count reception bookings only (reception staff plus walk-ins), as in the daily tracker.
        @endif
        Which lead sources count towards each channel is set in {{ $business->short_name }} settings.
    </p>
</x-layouts.app>
