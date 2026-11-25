<x-layouts.app :title="$technician->name.'’s appointments'">
    <x-page-header :title="$technician->name.'’s appointments'" subtitle="Upcoming appointments only." />
    <div x-data="{ tab: 'Today' }">
        <div class="mb-3 flex flex-wrap gap-1.5">
            @foreach ($tabs as $label => $appointments)
                <button type="button" class="btn btn-sm" :class="tab === @js($label) ? 'btn-primary' : 'btn-secondary'" @click="tab = @js($label)">{{ $label }} ({{ $appointments->count() }})</button>
            @endforeach
        </div>
        @foreach ($tabs as $label => $appointments)
            <div x-show="tab === @js($label)" @if (! $loop->first) x-cloak @endif>
            <x-card :padded="false">
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th>Client name</th><th>Location</th><th>Appointment date</th><th>Time</th><th>Tel</th><th>Email</th></tr></thead>
                        <tbody>
                            @forelse ($appointments as $a)
                                <tr>
                                    <td class="font-medium">
                                        @if ($a->client && auth()->user()->can('manage'))<a class="link" href="{{ route('clients.show', $a->client) }}">{{ $a->client->name ?: $a->client_name }}</a>@else{{ $a->client?->name ?: $a->client_name ?: '—' }}@endif
                                    </td>
                                    <td>{{ $a->location?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap">{{ $a->appointment_date?->format('D j M Y') }}</td>
                                    <td>{{ $a->time_label ?? '—' }}</td>
                                    <td class="whitespace-nowrap">{{ $a->tel ?? $a->client?->tel ?? '—' }}</td>
                                    <td>{{ $a->email ?? $a->client?->email ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-slate-400">Nothing booked.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
            </div>
        @endforeach
    </div>
</x-layouts.app>
