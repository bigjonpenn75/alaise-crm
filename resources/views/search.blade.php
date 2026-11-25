<x-layouts.app title="Search">
    <x-page-header title="Search results" :subtitle="$term !== '' ? 'For “'.$term.'”' : 'Type a name, phone number or email in the box above.'" />
    @php($canOpen = auth()->user()->can('manage'))
    <x-card :title="'Bookings ('.$leads->count().')'" class="mb-4" :padded="false">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Business</th><th>Tel</th><th>Email</th><th>Date added</th></tr></thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td class="font-medium">@if ($canOpen)<a class="link" href="{{ route('leads.show', $lead) }}">{{ $lead->name }}</a>@else{{ $lead->name }}@endif</td>
                        <td>{{ $lead->business->short_name }}</td><td>{{ $lead->tel ?? '—' }}</td><td>{{ $lead->email ?? '—' }}</td>
                        <td class="whitespace-nowrap">{{ $lead->date_added?->format('j M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-slate-400">No bookings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
    <x-card :title="'Clinic customers ('.$clients->count().')'" :padded="false">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Business</th><th>Tel</th><th>Email</th><th class="text-right">Appointments</th><th>Date added</th></tr></thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td class="font-medium">@if ($canOpen)<a class="link" href="{{ route('clients.show', $client) }}">{{ $client->name ?: '(no name)' }}</a>@else{{ $client->name }}@endif</td>
                        <td>{{ $client->business->short_name }}</td><td>{{ $client->tel ?? '—' }}</td><td>{{ $client->email ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $client->appointments_count }}</td>
                        <td class="whitespace-nowrap">{{ $client->created_date?->format('j M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-slate-400">No clinic customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
    <p class="mt-3 text-xs text-slate-500">Showing up to 100 of each, newest first.</p>
</x-layouts.app>
