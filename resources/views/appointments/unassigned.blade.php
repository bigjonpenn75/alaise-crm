<x-layouts.app title="Unassigned appointments">
    <x-page-header title="Unassigned appointments" subtitle="Appointments with no technician. Assigning one fills in all of that customer's unassigned appointments." />
    <x-card :padded="false">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Customer</th><th>Date</th><th>Location</th><th>Status</th><th>Assign technician</th></tr></thead>
                <tbody>
                    @forelse ($appointments as $a)
                        <tr>
                            <td>@if ($a->client)<a class="link font-medium" href="{{ route('clients.show', $a->client) }}">{{ $a->client->name ?: $a->client_name }}</a>@else{{ $a->client_name ?? '—' }}@endif</td>
                            <td class="whitespace-nowrap">{{ $a->appointment_date?->format('D j M Y') ?? '—' }} {{ $a->time_label }}</td>
                            <td>{{ $a->location?->name ?? '—' }}</td>
                            <td>{{ $a->disposition?->name ?? '—' }}</td>
                            <td>
                                @if ($a->client_id)
                                    <form method="POST" action="{{ route('appointments.assign') }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="client_id" value="{{ $a->client_id }}">
                                        <select name="technician_id" class="input w-48 py-1" required aria-label="Technician">
                                            <option value="">Please select…</option>
                                            @foreach ($technicians as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                                        </select>
                                        <button class="btn btn-primary btn-sm">Assign</button>
                                    </form>
                                @else
                                    <a href="{{ route('appointments.edit', $a) }}" class="btn btn-secondary btn-sm">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-slate-400">Every appointment has a technician.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())<div class="border-t border-slate-200 p-3">{{ $appointments->links() }}</div>@endif
    </x-card>
</x-layouts.app>
