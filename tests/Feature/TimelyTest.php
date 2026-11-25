<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Technician;
use App\Models\TimelyBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelyTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = ['firstName' => 'Alice', 'lastName' => 'Allen', 'tel' => '7526382125', 'emailAddress' => 'alice@example.com',
        'location' => 'Tattoo Removal - Manchester', 'nextAppointment' => '2026-12-03 14:00:00'];

    public function test_the_webhook_stores_a_booking_on_both_the_new_and_the_old_url(): void
    {
        $this->post('/api/timely/leads', $this->payload)->assertOk()->assertJsonPath('response.status', 'OK');
        $this->post('/api/timely_leads.php', ['tel' => '7000000000'] + $this->payload)->assertOk();

        $this->assertSame(2, TimelyBooking::count());
        $booking = TimelyBooking::first();
        $this->assertSame('Alice', $booking->first_name);
        $this->assertSame('2026-12-03 14:00:00', $booking->next_appointment->toDateTimeString());
        $this->assertTrue($booking->is_new);
    }

    public function test_the_same_booking_sent_twice_is_stored_once(): void
    {
        $this->post('/api/timely/leads', $this->payload)->assertOk();
        $this->post('/api/timely/leads', $this->payload)->assertOk()->assertJsonPath('response.info', 'Duplicate ignored');

        $this->assertSame(1, TimelyBooking::count());
    }

    public function test_the_webhook_requires_the_token_when_one_is_configured(): void
    {
        config(['alaise.timely_token' => 'secret-token']);

        $this->post('/api/timely/leads', $this->payload)->assertUnauthorized();
        $this->post('/api/timely/leads?token=wrong', $this->payload)->assertUnauthorized();
        $this->post('/api/timely/leads?token=secret-token', $this->payload)->assertOk();
        $this->post('/api/timely/leads', ['tel' => '7111111111'] + $this->payload, ['X-Webhook-Token' => 'secret-token'])->assertOk();

        $this->assertSame(2, TimelyBooking::count());
    }

    public function test_a_returning_customer_is_booked_in_with_their_usual_technician(): void
    {
        $this->travelTo('2026-11-24 10:00:00');
        $technician = Technician::create(['business_id' => 1, 'name' => 'Nicola']);
        $client = Client::create(['business_id' => 1, 'first_name' => 'Alice', 'tel' => '07526382125']);
        $client->appointments()->create(['client_name' => 'Alice Allen', 'technician_id' => $technician->id, 'appointment_date' => '2026-10-01', 'disposition_id' => 8]);

        $returning = TimelyBooking::create(['tel' => '7526382125', 'next_appointment' => '2026-12-03 14:00:00']);
        $unknown = TimelyBooking::create(['tel' => '7999999999', 'next_appointment' => '2026-12-04 09:00:00']);
        $stale = TimelyBooking::create(['tel' => '7526382125', 'next_appointment' => '2025-01-10 09:00:00']);

        $this->artisan('alaise:process-timely-bookings')->assertSuccessful();

        $new = Appointment::latest('id')->first();
        $this->assertSame(2, Appointment::count());
        $this->assertSame('2026-12-03', $new->appointment_date->toDateString());
        $this->assertSame('14:00:00', $new->appointment_time);
        $this->assertSame($technician->id, $new->technician_id);
        $this->assertSame(6, $new->disposition_id); // New Booking
        $this->assertFalse($returning->fresh()->is_new);
        $this->assertTrue($unknown->fresh()->is_new, 'unmatched bookings stay in the queue');
        $this->assertTrue($stale->fresh()->is_new, 'bookings for past dates are never auto-booked');
    }

    public function test_a_returning_customer_whose_technician_has_left_stays_in_the_queue(): void
    {
        $this->travelTo('2026-11-24 10:00:00');
        $technician = Technician::create(['business_id' => 1, 'name' => 'Left', 'active' => false]);
        $client = Client::create(['business_id' => 1, 'first_name' => 'Alice', 'tel' => '07526382125']);
        $client->appointments()->create(['technician_id' => $technician->id, 'appointment_date' => '2026-10-01']);
        $booking = TimelyBooking::create(['tel' => '7526382125', 'next_appointment' => '2026-12-03 14:00:00']);

        $this->artisan('alaise:process-timely-bookings');

        $this->assertSame(1, Appointment::count());
        $this->assertTrue($booking->fresh()->is_new);
    }

    public function test_past_upcoming_appointments_become_not_dispositioned(): void
    {
        $this->travelTo('2026-11-24 22:00:00');
        $client = Client::create(['business_id' => 1, 'first_name' => 'A']);
        $past = $client->appointments()->create(['appointment_date' => '2026-11-23', 'disposition_id' => 1]);
        $today = $client->appointments()->create(['appointment_date' => '2026-11-24', 'disposition_id' => 1]);
        $rebooked = $client->appointments()->create(['appointment_date' => '2026-11-01', 'disposition_id' => 8]);

        $this->artisan('alaise:mark-undispositioned')->assertSuccessful();

        $this->assertSame(18, $past->fresh()->disposition_id);
        $this->assertSame(1, $today->fresh()->disposition_id);
        $this->assertSame(8, $rebooked->fresh()->disposition_id);
    }
}
