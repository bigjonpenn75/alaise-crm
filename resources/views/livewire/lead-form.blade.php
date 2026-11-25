<div>
    <form wire:submit="save" class="max-w-3xl space-y-4">
        <x-card title="Customer">
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (['first_name' => ['First name', 'text'], 'last_name' => ['Last name', 'text'], 'email' => ['Email address', 'email'], 'tel' => ['Telephone', 'text'], 'date_added' => ['Date', 'date']] as $name => [$label, $type])
                    <div>
                        <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
                        <input id="{{ $name }}" type="{{ $type }}" wire:model="{{ $name }}" class="input">
                        @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div>
                    <label for="business_id" class="mb-1 block text-sm font-medium text-slate-700">Business</label>
                    <select id="business_id" wire:model.live="business_id" class="input" @disabled($lead)>
                        <option value="">Please select…</option>
                        @foreach ($businesses as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                    @error('business_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        @if ($business)
            <x-card :title="$business->name" wire:key="business-{{ $business->id }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'staff_member_id' => ['Added by', $staff],
                        'technician_id' => [$business->technicianLabel(), $technicians],
                        'lead_source_id' => ['Source', $sources],
                        'location_id' => ['Location', $locations],
                    ] as $name => [$label, $options])
                        <div>
                            <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <select id="{{ $name }}" wire:model="{{ $name }}" class="input">
                                <option value="">Please select…</option>
                                @foreach ($options as $id => $optionLabel)<option value="{{ $id }}">{{ $optionLabel }}</option>@endforeach
                            </select>
                            @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach

                    @if ($business->is_clinic)
                        <div>
                            <label for="service_id" class="mb-1 block text-sm font-medium text-slate-700">Service</label>
                            <select id="service_id" wire:model="service_id" class="input">
                                <option value="">Please select…</option>
                                @foreach ($services as $id => $optionLabel)<option value="{{ $id }}">{{ $optionLabel }}</option>@endforeach
                            </select>
                            @error('service_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="booking_type" class="mb-1 block text-sm font-medium text-slate-700">Booking type</label>
                            <select id="booking_type" wire:model="booking_type" class="input">
                                <option value="1">Sale</option>
                                <option value="2">Consultation</option>
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="deposit" class="mb-1 block text-sm font-medium text-slate-700">Deposit (£)</label>
                        <input id="deposit" type="number" step="0.01" min="0" wire:model="deposit" class="input" placeholder="99.99">
                        @error('deposit')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="full_cost" class="mb-1 block text-sm font-medium text-slate-700">Total cost (£)</label>
                        <input id="full_cost" type="number" step="0.01" min="0" wire:model="full_cost" class="input" placeholder="399.99">
                        @error('full_cost')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-card>
        @endif

        <div class="flex gap-2">
            <button class="btn btn-primary" wire:loading.attr="disabled">{{ $lead ? 'Save changes' : 'Add booking' }}</button>
            @if ($lead)<a href="{{ route('leads.show', $lead) }}" class="btn btn-secondary">Cancel</a>@endif
        </div>
    </form>
</div>
