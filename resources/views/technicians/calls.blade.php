<x-layouts.app title="Calls to make">
    <x-page-header title="Calls to make" :subtitle="$business->name" />
    @foreach ($lists as $heading => $appointments)
        <x-card :title="$heading.' ('.$appointments->count().')'" class="mb-4" :padded="false">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Client name</th><th>Technician</th><th>Status</th><th>Date status changed</th><th>Tel</th><th>Latest note</th></tr></thead>
                    <tbody>
                        @forelse ($appointments as $a)
                            <tr>
                                <td>@if ($a->client)<a class="link font-medium" href="{{ route('clients.show', $a->client) }}">{{ $a->client->name ?: $a->client_name }}</a>@else{{ $a->client_name ?? '—' }}@endif</td>
                                <td>{{ $a->technician?->name ?? 'Unassigned' }}</td>
                                <td>{{ $a->disposition?->name }}</td>
                                <td class="whitespace-nowrap">{{ $a->status_updated_at?->format('j M Y H:i') }}</td>
                                <td class="whitespace-nowrap">{{ $a->tel ?? $a->client?->tel ?? '—' }}</td>
                                <td class="max-w-md text-slate-600">{{ Str::limit($latestNotes[$a->client_id] ?? '', 160) ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-slate-400">Nobody on this list.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endforeach
</x-layouts.app>
