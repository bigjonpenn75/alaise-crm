<x-layouts.app title="Process booking">
    <x-page-header :title="'Process booking: '.($booking->name ?: '(no name)')"
        :subtitle="$client ? 'Matches existing customer '.$client->name.' by phone number. This adds an appointment to their record.' : 'No existing customer has this phone number. This creates the customer and their first appointment.'" />
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('bookings.store', $booking) }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                @unless ($client)
                    <x-field label="First name" name="first_name" :value="$booking->first_name" required />
                    <x-field label="Last name" name="last_name" :value="$booking->last_name" />
                    <x-field label="Email address" name="email" type="email" :value="$booking->email" />
                    <x-field label="Telephone" name="tel" :value="$booking->tel ? '0'.ltrim($booking->tel, '0') : null" />
                @endunless
                <x-field label="Appointment date" name="appointment_date" type="date" :value="$booking->hasAppointmentDate() ? $booking->next_appointment->toDateString() : null" required />
                <x-field label="Appointment time" name="appointment_time" type="time" :value="$booking->hasAppointmentDate() ? $booking->next_appointment->format('H:i') : null" />
                <x-select label="Technician" name="technician_id" :options="$business->technicians()->active()->orderBy('name')->pluck('name', 'id')" :value="$last?->technician_id ?? $client?->technician_id" required />
                <x-select label="Location" name="location_id" :options="$business->locations()->active()->orderBy('name')->pluck('name', 'id')" :value="$last?->location_id ?? $client?->location_id"
                    :hint="$booking->location ? 'Timely says: '.$booking->location : null" required />
                <x-select label="Service" name="service_id" :options="$business->services()->active()->orderBy('name')->pluck('name', 'id')" :value="$last?->service_id ?? $business->services()->active()->value('id')" />
                <x-select label="Booking type" name="booking_type" :options="[1 => 'Treatment', 2 => 'Consultation']" :value="$client ? 1 : 2" :placeholder="false" />
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary">{{ $client ? 'Add appointment' : 'Create customer and appointment' }}</button>
                <a href="{{ route('bookings.index', $business) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </x-card>
</x-layouts.app>
