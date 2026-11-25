<?php

namespace App\Console\Commands;

use App\Models\Disposition;
use App\Models\TimelyBooking;
use Illuminate\Console\Command;

class ProcessTimelyBookings extends Command
{
    protected $signature = 'alaise:process-timely-bookings';

    protected $description = 'Book returning customers from Timely straight in with their usual technician';

    /**
     * A new Timely booking whose phone number matches an existing customer becomes a
     * "New Booking" appointment copied from that customer's last one, provided the
     * technician is still active. Everything else stays in the New bookings queue.
     * Bookings for dates already gone are left alone, so a backlog is never booked in bulk.
     */
    public function handle(): int
    {
        $disposition = Disposition::idFor(Disposition::NEW_BOOKING);
        $created = 0;

        TimelyBooking::pending()->where('next_appointment', '>=', today())->chunkById(200, function ($bookings) use (&$created, $disposition) {
            foreach ($bookings as $booking) {
                $client = $booking->matchingClient();
                $last = $client?->appointments()->with('technician')->latest('id')->first();
                if (! $last || ! $last->technician?->active || ! $booking->hasAppointmentDate()) {
                    continue;
                }

                $client->appointments()->create([
                    'client_name' => $last->client_name, 'email' => $last->email, 'tel' => $last->tel,
                    'location_id' => $last->location_id, 'technician_id' => $last->technician_id,
                    'service_id' => $last->service_id, 'staff_member_id' => $last->staff_member_id, 'added_by' => $last->added_by,
                    'appointment_date' => $booking->next_appointment->toDateString(),
                    'appointment_time' => $booking->next_appointment->format('H:i:s'),
                    'booking_type' => 2, 'disposition_id' => $disposition,
                    'status_updated_at' => now(), 'created_date' => today(),
                ]);
                $booking->update(['is_new' => false]);
                $created++;
            }
        });

        $this->info("$created appointment(s) created from Timely bookings.");

        return self::SUCCESS;
    }
}
