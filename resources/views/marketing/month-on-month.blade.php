<x-layouts.app :title="$business->short_name.' month on month'">
    <x-page-header :title="$business->short_name.' month on month'" subtitle="Last 12 months" />
    <x-card :padded="false">
        <div class="overflow-x-auto">
            <table class="grid-table">
                <thead>
                    <tr>
                        <th>Month</th><th class="edge">Total leads</th>
                        @foreach ($locations as $location)<th>{{ $location->name }}</th>@endforeach
                        <th class="edge">Total rev</th><th>Total spend</th><th>Ave CPL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="label">{{ $row['month']->format('M Y') }}</td>
                            <td class="edge">{{ number_format($row['leads']) }}</td>
                            @foreach ($locations as $location)<td>{{ number_format($row['locations'][$location->id] ?? 0) }}</td>@endforeach
                            <td class="edge">{{ money($row['revenue']) }}</td>
                            <td>{{ money($row['spend']) }}</td>
                            <td>{{ money($row['cpl']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    <p class="mt-3 text-xs text-slate-500">Leads are new customers added in the month. Revenue is appointment fees, leaving out no-shows and cancellations.</p>
</x-layouts.app>
