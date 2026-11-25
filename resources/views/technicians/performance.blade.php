@php
    use App\Enums\DispositionCategory as C;
    $buckets = [C::Upcoming, C::Converted, C::Lost, C::Chase, C::Undispositioned];
    $query = ['month' => $month->format('Y-m')];
@endphp
<x-layouts.app title="Technician performance">
    <x-page-header title="Technician performance" :subtitle="$business->name.' · '.$month->format('F Y')">
        <x-month-nav :month="$month" />
    </x-page-header>

    @foreach (['consultations' => 'Consultations', 'repeat' => 'Repeat appointments', 'all' => 'All appointments'] as $segment => $heading)
        <x-card :title="$heading" class="mb-4" :padded="false">
            <div class="overflow-x-auto">
                <table class="grid-table">
                    <thead>
                        <tr>
                            <th class="text-left">Technician</th><th>Appointments</th>
                            @foreach ($buckets as $bucket)<th>{{ $bucket->label() }}</th>@endforeach
                            <th>Rev</th>
                            @foreach ($dispositions as $d)<th class="edge font-normal">{{ $d->name }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($technicians as $technician)
                            @continue(! isset($segments[$segment][$technician->id]))
                            @php($row = $segments[$segment][$technician->id])
                            @php($link = fn (array $extra) => route('technicians.appointments', [$technician] + $query + ['segment' => $segment] + $extra))
                            <tr>
                                <td class="label"><a class="link" href="{{ route('technicians.diary', $technician) }}">{{ $technician->name }}</a></td>
                                <td><a class="link" href="{{ $link([]) }}">{{ $row['appointments'] }}</a></td>
                                @foreach ($buckets as $bucket)
                                    <td>@if ($row[$bucket->value])<a class="link" href="{{ $link(['category' => $bucket->value]) }}">{{ $row[$bucket->value] }}</a>@else<span class="zero">0</span>@endif</td>
                                @endforeach
                                <td>{{ money($row['fees']) }}</td>
                                @foreach ($dispositions as $d)
                                    @php($n = $row['dispositions'][$d->id] ?? 0)
                                    <td class="edge">@if ($n)<a class="link" href="{{ $link(['disposition' => $d->id]) }}">{{ $n }}</a>@else<span class="zero">0</span>@endif</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td class="label" colspan="{{ 8 + $dispositions->count() }}">No appointments this month.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endforeach
    <p class="text-xs text-slate-500">A consultation is the first appointment of a customer added this month or in the two months before. The Converts, Lost and To be contacted columns follow the bucket set against each disposition in {{ $business->short_name }} settings.</p>
</x-layouts.app>
