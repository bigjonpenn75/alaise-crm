<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Disposition;
use App\Models\TimelyBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimelyBookingController extends Controller
{
    /** Bookings received from Timely that still need turning into an appointment. */
    public function index(Business $business)
    {
        abort_unless($business->is_clinic, 404);
        $bookings = TimelyBooking::pending()->orderBy('id')->paginate(50);
        $bookings->getCollection()->each(fn ($b) => $b->setRelation('match', $b->matchingClient()));

        return view('bookings.index', ['business' => $business, 'bookings' => $bookings]);
    }

    public function show(TimelyBooking $booking)
    {
        $business = $this->clinic();
        $client = $booking->matchingClient();

        return view('bookings.show', [
            'business' => $business,
            'booking' => $booking,
            'client' => $client,
            'last' => $client?->appointments()->latest('id')->first(),
        ]);
    }

    /** Create the appointment (and the customer, if they are new) and clear the booking from the queue. */
    public function store(Request $request, TimelyBooking $booking)
    {
        $business = $this->clinic();
        $client = $booking->matchingClient();
        $data = $request->validate([
            'first_name' => [$client ? 'nullable' : 'required', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'tel' => ['nullable', 'string', 'max:20'],
            'location_id' => ['required', 'exists:locations,id'],
            'technician_id' => ['required', 'exists:technicians,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'booking_type' => ['required', 'in:1,2'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
        ]);

        DB::transaction(function () use ($booking, $business, $client, $data, $request) {
            $client ??= Client::create([
                'business_id' => $business->id,
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null, 'tel' => $data['tel'] ?? null,
                'location_id' => $data['location_id'], 'technician_id' => $data['technician_id'], 'service_id' => $data['service_id'] ?? null,
                'added_by' => $request->user()->id, 'created_date' => $booking->created_at?->toDateString() ?? today(),
            ]);
            $client->appointments()->create([
                'client_name' => $client->name, 'email' => $client->email, 'tel' => $client->tel,
                'location_id' => $data['location_id'], 'technician_id' => $data['technician_id'], 'service_id' => $data['service_id'] ?? null,
                'booking_type' => $data['booking_type'],
                'appointment_date' => $data['appointment_date'], 'appointment_time' => $data['appointment_time'] ?? null,
                'disposition_id' => Disposition::idFor(Disposition::UPCOMING),
                'status_updated_at' => now(), 'created_date' => today(), 'added_by' => $request->user()->id,
            ]);
            $client->logHistory('Appointment created from Timely booking');
            $booking->update(['is_new' => false]);
        });

        return redirect()->route('bookings.index', $business)->with('status', 'Booking processed.');
    }

    /** Dismiss a booking without creating anything. */
    public function destroy(TimelyBooking $booking)
    {
        $business = $this->clinic();
        $booking->update(['is_new' => false]);

        return redirect()->route('bookings.index', $business)->with('status', 'Booking dismissed.');
    }

    private function clinic(): Business
    {
        $business = Business::where('is_clinic', true)->firstOrFail();
        $this->authorize('access-business', $business);

        return $business;
    }
}
