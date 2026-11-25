<x-layouts.app title="Edit appointment">
    <x-page-header title="Edit appointment" :subtitle="$appointment->client?->name ?? $appointment->client_name" />
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Date" name="appointment_date" type="date" :value="$appointment->appointment_date?->toDateString()" required />
                <x-field label="Time" name="appointment_time" type="time" :value="$appointment->time_label" />
                <x-select label="Technician" name="technician_id" :options="$business->technicians()->orderBy('name')->pluck('name', 'id')" :value="$appointment->technician_id" />
                <x-select label="Location" name="location_id" :options="$business->locations()->orderBy('name')->pluck('name', 'id')" :value="$appointment->location_id" />
                <x-select label="Service" name="service_id" :options="$business->services()->orderBy('name')->pluck('name', 'id')" :value="$appointment->service_id" />
                <x-select label="Type" name="booking_type" :options="[1 => 'Treatment', 2 => 'Consultation']" :value="$appointment->booking_type?->value" />
                <x-select label="Disposition" name="disposition_id" :options="$business->dispositions()->orderBy('name')->pluck('name', 'id')" :value="$appointment->disposition_id" />
                <x-select label="Marketing source" name="marketing_source_id" :options="$business->marketingSources()->orderBy('name')->pluck('name', 'id')" :value="$appointment->marketing_source_id" />
                <x-field label="Fee (£)" name="fee" type="number" step="0.01" min="0" :value="$appointment->fee" />
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary">Save changes</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <div class="mt-6 border-t border-slate-200 pt-4">
            <x-confirm-delete :action="route('appointments.destroy', $appointment)" label="Delete appointment" message="Delete this appointment?" />
        </div>
    </x-card>
</x-layouts.app>
