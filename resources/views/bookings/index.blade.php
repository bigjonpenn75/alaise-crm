<x-layouts.app :title="$business->short_name.' new bookings'">
    <x-page-header :title="$business->short_name.' new bookings'" subtitle="Bookings received from Timely that have not been turned into an appointment yet." />
    <x-card :padded="false">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Tel</th><th>Location</th><th>Customer type</th><th>Appointment date</th><th>Time</th><th>Date added</th><th></th></tr></thead>
                <tbody>
                    @forelse ($bookings as $b)
                        <tr>
                            <td class="font-medium">{{ $b->name ?: '(no name)' }}</td>
                            <td>{{ $b->email ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ $b->tel ?? '—' }}</td>
                            <td>{{ $b->location ?? '—' }}</td>
                            <td>
                                @if ($b->match)
                                    <a class="link" href="{{ route('clients.show', $b->match) }}">Existing</a>
                                @else
                                    New
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $b->hasAppointmentDate() ? $b->next_appointment->format('D j M Y') : 'No date' }}</td>
                            <td>{{ $b->hasAppointmentDate() ? $b->next_appointment->format('H:i') : '—' }}</td>
                            <td class="whitespace-nowrap text-slate-500">{{ $b->created_at?->format('j M Y') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('bookings.show', $b) }}" class="btn btn-primary btn-sm">Process</a>
                                <x-confirm-delete :action="route('bookings.destroy', $b)" label="Dismiss" message="Remove this booking from the list without creating an appointment?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-slate-400">No new bookings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bookings->hasPages())<div class="border-t border-slate-200 p-3">{{ $bookings->links() }}</div>@endif
    </x-card>
</x-layouts.app>
