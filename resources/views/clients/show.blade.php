<x-layouts.app :title="$client->name">
    <x-page-header :title="$client->name ?: '(no name)'" :subtitle="$business->name.' customer'">
        <a href="{{ route('customers.index', $business) }}" class="btn btn-secondary btn-sm">Back to customers</a>
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary btn-sm">Edit</a>
        <x-confirm-delete :action="route('clients.destroy', $client)" message="Delete this customer and all of their appointments?" />
    </x-page-header>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4">
            <x-card title="Overview">
                <dl class="grid grid-cols-3 gap-x-3 gap-y-2 text-sm">
                    @foreach ([
                        'Email' => $client->email, 'Telephone' => $client->tel,
                        'Added' => $client->created_date?->format('j M Y'),
                        'Added by' => $client->staffMember?->name,
                        'Technician' => $client->technician?->name,
                        'Location' => $client->location?->name,
                        'Service' => $client->service?->name,
                        'Total fees' => money($feesTotal),
                    ] as $label => $value)
                        <dt class="text-slate-500">{{ $label }}</dt><dd class="col-span-2 break-words">{{ $value ?: '—' }}</dd>
                    @endforeach
                </dl>
            </x-card>

            <x-card title="Add appointment">
                <form method="POST" action="{{ route('appointments.store', $client) }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <x-field label="Date" name="appointment_date" type="date" required />
                    <x-field label="Time" name="appointment_time" type="time" />
                    <x-select label="Technician" name="technician_id" :options="$business->technicians()->active()->orderBy('name')->pluck('name', 'id')" :value="$client->technician_id" />
                    <x-select label="Location" name="location_id" :options="$business->locations()->active()->orderBy('name')->pluck('name', 'id')" :value="$client->location_id" />
                    <x-select label="Service" name="service_id" :options="$business->services()->active()->orderBy('name')->pluck('name', 'id')" :value="$client->service_id" />
                    <x-select label="Type" name="booking_type" :options="[1 => 'Treatment', 2 => 'Consultation']" value="1" :placeholder="false" />
                    <div class="col-span-2"><button class="btn btn-primary btn-sm">Add appointment</button></div>
                </form>
            </x-card>
        </div>

        <div class="space-y-4 xl:col-span-2">
            <livewire:appointment-list :client="$client" />
            <livewire:record-panel :record="$client" />
        </div>
    </div>
</x-layouts.app>
