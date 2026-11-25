<div>
    <x-card title="Appointments" :padded="false">
        <div class="max-h-[32rem] overflow-auto">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Time</th><th>Technician</th><th>Location</th><th>Type</th><th>Status</th><th class="text-right">Fee</th><th></th></tr></thead>
                <tbody>
                    @forelse ($appointments as $a)
                        <tr wire:key="appointment-{{ $a->id }}">
                            <td class="whitespace-nowrap">{{ $a->appointment_date?->format('D j M Y') ?? '—' }}
                                @if ($a->client_name && strcasecmp(trim($a->client_name), $clientName) !== 0)<span class="block text-xs text-amber-700">booked as {{ $a->client_name }}</span>@endif</td>
                            <td>{{ $a->time_label ?? '—' }}</td>
                            <td>{{ $a->technician?->name ?? 'Unassigned' }}</td>
                            <td>{{ $a->location?->name ?? '—' }}</td>
                            <td>{{ $a->booking_type?->label(true) ?? '—' }}</td>
                            <td>{{ $a->disposition?->name ?? '—' }}</td>
                            <td class="text-right tabular-nums">{{ money($a->fee) }}</td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button" class="btn btn-primary btn-sm" wire:click="edit({{ $a->id }})">Update</button>
                                <a href="{{ route('appointments.edit', $a) }}" class="btn btn-secondary btn-sm">Edit</a>
                            </td>
                        </tr>
                        @if ($editing === $a->id)
                            <tr wire:key="editing-{{ $a->id }}">
                                <td colspan="8" class="bg-brand-50">
                                    <form wire:submit="save" class="grid gap-3 p-2 sm:grid-cols-4">
                                        @foreach (['disposition_id' => ['Disposition', $dispositions], 'technician_id' => ['Technician', $technicians], 'marketing_source_id' => ['Marketing source', $marketingSources]] as $name => [$label, $options])
                                            <div>
                                                <label for="{{ $name }}" class="mb-1 block text-xs font-medium text-slate-700">{{ $label }}</label>
                                                <select id="{{ $name }}" wire:model="{{ $name }}" class="input py-1.5">
                                                    <option value="">Please select…</option>
                                                    @foreach ($options as $id => $optionLabel)<option value="{{ $id }}">{{ $optionLabel }}</option>@endforeach
                                                </select>
                                                @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                            </div>
                                        @endforeach
                                        <div>
                                            <label for="fee" class="mb-1 block text-xs font-medium text-slate-700">Appointment fee (£)</label>
                                            <input id="fee" type="number" step="0.01" min="0" wire:model="fee" class="input py-1.5" placeholder="70.00">
                                            @error('fee')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="sm:col-span-4">
                                            <label for="note" class="mb-1 block text-xs font-medium text-slate-700">Add a note</label>
                                            <textarea id="note" wire:model="note" rows="2" class="input"></textarea>
                                        </div>
                                        <div class="flex gap-2 sm:col-span-4">
                                            <button class="btn btn-primary btn-sm">Update record</button>
                                            <button type="button" class="btn btn-secondary btn-sm" wire:click="cancel">Cancel</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="8" class="text-slate-400">No appointments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
