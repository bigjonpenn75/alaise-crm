<?php

namespace Tests\Feature;

use App\Enums\UserLevel;
use App\Livewire\AppointmentList;
use App\Livewire\LeadForm;
use App\Livewire\LookupManager;
use App\Livewire\RecordPanel;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Location;
use App\Models\StaffMember;
use App\Models\Technician;
use App\Models\TimelyBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_booking_can_be_added(): void
    {
        $user = $this->user(UserLevel::Standard, ['flesh']);
        $artist = Technician::create(['business_id' => 2, 'name' => 'Sam']);

        Livewire::actingAs($user)->test(LeadForm::class)
            ->set('first_name', 'Perry')->set('last_name', 'Morris')->set('tel', '07700900123')
            ->set('business_id', '2')->set('technician_id', (string) $artist->id)->set('lead_source_id', '9')
            ->set('deposit', '50')->set('full_cost', '250')
            ->call('save')->assertHasNoErrors()->assertRedirect(route('leads.create'));

        $lead = Lead::sole();
        $this->assertSame('Perry', $lead->first_name);
        $this->assertSame(2, $lead->business_id);
        $this->assertSame($user->id, $lead->added_by);
        $this->assertSame('250.00', $lead->full_cost);
    }

    public function test_a_booking_cannot_be_added_to_a_business_the_user_cannot_access_or_with_another_business_artist(): void
    {
        $user = $this->user(UserLevel::Standard, ['flesh']);
        $mtsArtist = Technician::create(['business_id' => 4, 'name' => 'Other']);

        Livewire::actingAs($user)->test(LeadForm::class)
            ->set('first_name', 'A')->set('last_name', 'B')->set('business_id', '4')
            ->call('save')->assertHasErrors('business_id');

        Livewire::actingAs($user)->test(LeadForm::class)
            ->set('first_name', 'A')->set('last_name', 'B')->set('business_id', '2')->set('technician_id', (string) $mtsArtist->id)
            ->call('save')->assertHasErrors('technician_id');

        $this->assertSame(0, Lead::count());
    }

    public function test_notes_documents_and_history_on_a_booking(): void
    {
        Storage::fake('local');
        $user = $this->user();
        $lead = Lead::create(['business_id' => 2, 'first_name' => 'A', 'last_name' => 'B', 'date_added' => today()]);

        $panel = Livewire::actingAs($user)->test(RecordPanel::class, ['record' => $lead])
            ->set('note', 'Called back, wants a sleeve')->call('addNote')->assertHasNoErrors()
            ->set('documentTitle', 'Consent form')->set('upload', UploadedFile::fake()->create('consent.pdf', 20, 'application/pdf'))
            ->call('addDocument')->assertHasNoErrors();

        $this->assertSame('Called back, wants a sleeve', $lead->notes()->sole()->body);
        $document = $lead->documents()->sole();
        Storage::disk('local')->assertExists($document->path);
        $this->assertSame(2, $lead->histories()->count());

        $this->actingAs($user)->get(route('documents.show', $document))->assertOk()->assertDownload('Consent form.pdf');
        $this->actingAs($this->user(UserLevel::Management, ['mts']))->get(route('documents.show', $document))->assertForbidden();

        $this->actingAs($user);
        $panel->call('deleteDocument', $document->id);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_updating_an_appointment_records_the_status_change_note_and_history(): void
    {
        $user = $this->user(UserLevel::Management, ['truk']);
        $client = Client::create(['business_id' => 1, 'first_name' => 'Elle', 'tel' => '07700900111']);
        $appointment = $client->appointments()->create(['appointment_date' => today(), 'disposition_id' => 1, 'status_updated_at' => now()->subWeek()]);

        Livewire::actingAs($user)->test(AppointmentList::class, ['client' => $client])
            ->call('edit', $appointment->id)->set('disposition_id', '8')->set('fee', '70')->set('note', 'Rebooked for June')
            ->call('save')->assertHasNoErrors();

        $appointment->refresh();
        $this->assertSame(8, $appointment->disposition_id);
        $this->assertSame('70.00', $appointment->fee);
        $this->assertTrue($appointment->status_updated_at->isToday());
        $this->assertSame('Rebooked for June', $client->notes()->sole()->body);
        $this->assertSame('Appointment updated', $client->histories()->sole()->action);
    }

    public function test_a_manager_without_clinic_access_cannot_update_clinic_appointments(): void
    {
        $client = Client::create(['business_id' => 1, 'first_name' => 'Elle']);

        Livewire::actingAs($this->user(UserLevel::Management, ['flesh']))
            ->test(AppointmentList::class, ['client' => $client])->assertForbidden();
    }

    public function test_settings_lists_can_be_added_to_renamed_and_toggled(): void
    {
        $user = $this->user(UserLevel::Management, ['flesh']);
        $flesh = \App\Models\Business::find(2);

        $component = Livewire::actingAs($user)->test(LookupManager::class, ['type' => 'staff', 'business' => $flesh])
            ->set('newName', 'Front desk')->call('add')->assertHasNoErrors();
        $staff = StaffMember::sole();
        $this->assertSame(2, $staff->business_id);

        $component->call('toggle', $staff->id, 'is_reception')->call('toggle', $staff->id)
            ->call('edit', $staff->id)->set('editName', 'Reception desk')->call('rename');
        $staff->refresh();
        $this->assertTrue($staff->is_reception);
        $this->assertFalse($staff->active);
        $this->assertSame('Reception desk', $staff->name);

        // Not allowed to flip arbitrary columns, or manage another business's lists.
        $component->call('toggle', $staff->id, 'business_id')->assertNotFound();
        Livewire::actingAs($user)->test(LookupManager::class, ['type' => 'staff', 'business' => \App\Models\Business::find(4)])
            ->assertForbidden();
    }

    public function test_a_timely_booking_for_a_new_customer_creates_the_customer_and_appointment(): void
    {
        $user = $this->user(UserLevel::Management, ['truk']);
        $technician = Technician::create(['business_id' => 1, 'name' => 'Nicola']);
        $location = Location::create(['business_id' => 1, 'name' => 'Manchester']);
        $booking = TimelyBooking::create(['first_name' => 'Tanisha', 'last_name' => 'Rigby', 'tel' => '7821605224', 'next_appointment' => '2026-12-01 18:30:00']);

        $this->actingAs($user)->post(route('bookings.store', $booking), [
            'first_name' => 'Tanisha', 'last_name' => 'Rigby', 'tel' => '07821605224',
            'technician_id' => $technician->id, 'location_id' => $location->id, 'booking_type' => 2,
            'appointment_date' => '2026-12-01', 'appointment_time' => '18:30',
        ])->assertRedirect(route('bookings.index', 'truk'));

        $client = Client::sole();
        $this->assertSame('07821605224', $client->tel);
        $appointment = Appointment::sole();
        $this->assertSame($client->id, $appointment->client_id);
        $this->assertSame(1, $appointment->disposition_id);
        $this->assertFalse($booking->fresh()->is_new);
    }
}
