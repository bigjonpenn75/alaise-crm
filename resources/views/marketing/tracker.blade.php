@php
    $cell = fn ($v, $money = false) => ((float) $v == 0.0 ? '<span class="zero">' : '<span>').($money ? money($v) : number_format($v)).'</span>';
    $groups = $business->uses_reception_split ? ['reception' => 'Reception totals', 'artist' => 'Artist totals', 'total' => 'Totals'] : ['total' => 'Totals'];
@endphp
<x-layouts.app :title="$business->short_name.' daily tracker'">
    <x-page-header :title="$business->short_name.' daily tracker'" :subtitle="$month->format('F Y')">
        <x-month-nav :month="$month" />
    </x-page-header>

    <x-card :padded="false">
        <div class="overflow-x-auto">
            <table class="grid-table">
                <thead>
                    <tr>
                        <th colspan="2"></th>
                        @foreach ($groups as $label)<th colspan="3" class="group edge">{{ $label }}</th>@endforeach
                        @foreach ($technicians as $technician)<th colspan="3" class="edge">{{ $technician->name }}</th>@endforeach
                    </tr>
                    <tr>
                        <th>Day</th><th>Date</th>
                        @foreach ($groups as $label)<th class="edge">Bookings</th><th>Deposits</th><th>Revenue</th>@endforeach
                        @foreach ($technicians as $technician)<th class="edge">Bookings</th><th>Deposits</th><th>Tat price</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="{{ $row['type'] === 'day' ? '' : $row['type'] }}">
                            @if ($row['type'] === 'day')
                                <td class="label">{{ $row['date']->format('D') }}</td><td class="label">{{ $row['date']->format('jS') }}</td>
                            @else
                                <td class="label" colspan="2">{{ $row['type'] === 'week' ? 'Week total' : 'Month total' }}</td>
                            @endif
                            @foreach (array_keys($groups) as $group)
                                <td class="edge">{!! $cell($row[$group]['bookings']) !!}</td>
                                <td>{!! $cell($row[$group]['deposits'], true) !!}</td>
                                <td>{!! $cell($row[$group]['revenue'], true) !!}</td>
                            @endforeach
                            @foreach ($technicians as $technician)
                                @php($t = $row['techs'][$technician->id] ?? ['bookings' => 0, 'deposits' => 0, 'revenue' => 0])
                                <td class="edge">{!! $cell($t['bookings']) !!}</td>
                                <td>{!! $cell($t['deposits'], true) !!}</td>
                                <td>{!! $cell($t['revenue'], true) !!}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    @if ($business->uses_reception_split)
        <p class="mt-3 text-xs text-slate-500">Reception totals are bookings logged under a staff member marked as reception, plus all walk-ins. Change who counts as reception in {{ $business->short_name }} settings.</p>
    @endif
</x-layouts.app>
