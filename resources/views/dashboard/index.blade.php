<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" :subtitle="$range->label()">
        @include('partials.range-filter')
    </x-page-header>

    @forelse ($businesses as $business)
        @php($d = $data[$business->id])
        <section class="mb-8">
            <h2 class="mb-3 text-base font-semibold text-slate-900">{{ $business->name }}</h2>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @if ($business->is_clinic)
                    <x-stat label="New customers" :value="number_format($d['clients']['period'])"
                        :sub="'This month '.number_format($d['clients']['thisMonth']).' · same point last month '.number_format($d['clients']['lastMonth'])" />
                @endif
                <x-stat :label="$business->uses_reception_split ? 'Reception bookings' : 'Bookings'" :value="number_format($d['period']['bookings'])"
                    :sub="'This month '.number_format($d['thisMonth']['bookings']).' · same point last month '.number_format($d['lastMonth']['bookings'])" />
                <x-stat label="Deposits" :value="money($d['period']['deposits'])"
                    :sub="'This month '.money($d['thisMonth']['deposits'], 0).' · last '.money($d['lastMonth']['deposits'], 0)" />
                <x-stat label="Revenue" :value="money($d['period']['revenue'])"
                    :sub="'This month '.money($d['thisMonth']['revenue'], 0).' · last '.money($d['lastMonth']['revenue'], 0)" />
            </div>

            <div class="mt-3 grid gap-3 xl:grid-cols-2">
                @foreach ([
                    'bySource' => ['By source (sales only)', false],
                    'byTechnician' => ['By '.strtolower($business->technicianLabel()), true],
                    'byLocation' => ['By location', false],
                    'byStaff' => ['By staff member', false],
                ] as $key => [$heading, $showMoney])
                    <x-card :title="$heading" :padded="false">
                        <div class="max-h-72 overflow-auto">
                            <table class="data-table">
                                <thead><tr><th>Name</th><th class="text-right">Bookings</th>@if ($showMoney)<th class="text-right">Deposits</th><th class="text-right">Revenue</th>@endif</tr></thead>
                                <tbody>
                                    @forelse ($d[$key] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-right tabular-nums">{{ number_format($row['bookings']) }}</td>
                                            @if ($showMoney)
                                                <td class="text-right tabular-nums">{{ money($row['deposits']) }}</td>
                                                <td class="text-right tabular-nums">{{ money($row['revenue']) }}</td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-slate-400">No bookings in this period.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                @endforeach
            </div>
        </section>
    @empty
        <x-card><p class="text-sm text-slate-500">Your account is not linked to any business yet. Ask a manager to tick the businesses you work with.</p></x-card>
    @endforelse
</x-layouts.app>
