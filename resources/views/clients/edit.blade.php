<x-layouts.app title="Edit customer">
    <x-page-header title="Edit customer" :subtitle="$client->name" />
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="First name" name="first_name" :value="$client->first_name" required />
                <x-field label="Last name" name="last_name" :value="$client->last_name" />
                <x-field label="Email address" name="email" type="email" :value="$client->email" />
                <x-field label="Telephone" name="tel" :value="$client->tel" />
                <x-select label="Technician" name="technician_id" :options="$business->technicians()->orderBy('name')->pluck('name', 'id')" :value="$client->technician_id" />
                <x-select label="Location" name="location_id" :options="$business->locations()->orderBy('name')->pluck('name', 'id')" :value="$client->location_id" />
                <x-select label="Service" name="service_id" :options="$business->services()->orderBy('name')->pluck('name', 'id')" :value="$client->service_id" />
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary">Save changes</button>
                <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </x-card>
</x-layouts.app>
