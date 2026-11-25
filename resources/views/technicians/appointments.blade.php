<x-layouts.app :title="$technician->name.' appointments'">
    <x-page-header :title="$technician->name" :subtitle="$month->format('F Y').' · '.['all' => 'all appointments', 'consultations' => 'consultations', 'repeat' => 'repeat appointments'][$segment].($filter ? ' · '.$filter : '')">
        <a href="{{ route('technicians.performance', ['month' => $month->format('Y-m')]) }}" class="btn btn-secondary btn-sm">Back to performance</a>
    </x-page-header>
    <x-card :padded="false">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Customer</th><th>Location</th><th>Date</th><th>Time</th><th>Tel</th><th>Status</th><th class="text-right">Fee</th></tr></thead>
                <tbody>
                    @forelse ($appointments as $a)
                        <tr>
                            <td>@if ($a->client)<a class="link font-medium" href="{{ route('clients.show', $a->client) }}">{{ $a->client->name ?: $a->client_name }}</a>@else{{ $a->client_name ?? '—' }}@endif</td>
                            <td>{{ $a->location?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ $a->appointment_date?->format('D j M Y') }}</td>
                            <td>{{ $a->time_label ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ $a->tel ?? $a->client?->tel ?? '—' }}</td>
                            <td>{{ $a->disposition?->name ?? '—' }}</td>
                            <td class="text-right tabular-nums">{{ money($a->fee) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-slate-400">No appointments match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())<div class="border-t border-slate-200 p-3">{{ $appointments->links() }}</div>@endif
    </x-card>
</x-layouts.app>
