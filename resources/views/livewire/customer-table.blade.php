<div>
    <x-card :padded="false">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 p-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, phone or email…" class="input max-w-xs py-1.5" aria-label="Search">
            <input type="month" wire:model.live="month" class="input w-44 py-1.5" aria-label="Month added">
            @if ($month !== '' || $search !== '')
                <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('month', ''); $set('search', '')">Clear</button>
            @endif
            <span class="ml-auto text-xs text-slate-500">{{ number_format($rows->total()) }} {{ Str::plural('record', $rows->total()) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th><th>Added by</th><th>Email</th><th>Tel</th><th>{{ $business->technicianLabel() }}</th><th>Location</th>
                        @if ($business->is_clinic)
                            <th>Service</th><th class="text-right">Appointments</th>
                        @else
                            <th>Source</th><th class="text-right">Deposit</th><th class="text-right">{{ $business->uses_reception_split ? 'Tattoo cost' : 'Cost' }}</th>
                        @endif
                        <th>Date added</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="row-{{ $row->id }}">
                            <td><a class="link font-medium" href="{{ $business->is_clinic ? route('clients.show', $row) : route('leads.show', $row) }}">{{ $row->name ?: '(no name)' }}</a></td>
                            <td>{{ $row->staffMember?->name ?? '—' }}</td>
                            <td>{{ $row->email ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ $row->tel ?? '—' }}</td>
                            <td>{{ $row->technician?->name ?? '—' }}</td>
                            <td>{{ $row->location?->name ?? '—' }}</td>
                            @if ($business->is_clinic)
                                <td>{{ $row->service?->name ?? '—' }}</td>
                                <td class="text-right tabular-nums">{{ $row->appointments_count }}</td>
                                <td class="whitespace-nowrap">{{ $row->created_date?->format('j M Y') ?? '—' }}</td>
                            @else
                                <td>{{ $row->source?->name ?? 'No source added' }}</td>
                                <td class="text-right tabular-nums">{{ money($row->deposit) }}</td>
                                <td class="text-right tabular-nums">{{ money($row->full_cost) }}</td>
                                <td class="whitespace-nowrap">{{ $row->date_added?->format('j M Y') ?? '—' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-slate-400">No customers match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())<div class="border-t border-slate-200 p-3">{{ $rows->links() }}</div>@endif
    </x-card>
</div>
