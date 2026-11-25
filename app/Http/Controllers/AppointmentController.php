<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\Disposition;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $this->authorize('access-business', $client->business);
        $data = $this->validated($request);
        $client->appointments()->create($data + [
            'client_name' => $client->name, 'email' => $client->email, 'tel' => $client->tel,
            'disposition_id' => $data['disposition_id'] ?? Disposition::idFor(Disposition::UPCOMING),
            'status_updated_at' => now(), 'created_date' => today(), 'added_by' => $request->user()->id,
        ]);
        $client->logHistory('Appointment added');

        return back()->with('status', 'Appointment added.');
    }

    public function edit(Appointment $appointment)
    {
        $business = $this->businessFor($appointment);

        return view('appointments.edit', ['appointment' => $appointment, 'business' => $business]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->businessFor($appointment);
        $data = $this->validated($request);
        if (($data['disposition_id'] ?? null) != $appointment->disposition_id) {
            $data['status_updated_at'] = now();
        }
        $appointment->update($data);
        $appointment->client?->logHistory('Appointment updated');

        return redirect()->to($appointment->client ? route('clients.show', $appointment->client) : route('dashboard'))
            ->with('status', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->businessFor($appointment);
        $appointment->delete();
        $appointment->client?->logHistory('Appointment deleted');

        return back()->with('status', 'Appointment deleted.');
    }

    /** Appointments that have no technician yet, grouped by customer. */
    public function unassigned(Request $request)
    {
        $business = $this->clinic($request);
        $appointments = Appointment::whereNull('technician_id')
            ->whereIn('client_id', Client::select('id')->where('business_id', $business->id))
            ->with(['client', 'location', 'disposition'])->orderByDesc('appointment_date')->paginate(50);

        return view('appointments.unassigned', [
            'business' => $business,
            'appointments' => $appointments,
            'technicians' => $business->technicians()->active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /** Give a technician to all of one customer's unassigned appointments. */
    public function assign(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'technician_id' => ['required', 'exists:technicians,id'],
        ]);
        $client = Client::findOrFail($data['client_id']);
        $this->authorize('access-business', $client->business);
        $count = $client->appointments()->whereNull('technician_id')->update(['technician_id' => $data['technician_id']]);

        return back()->with('status', "$count appointment(s) assigned.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'booking_type' => ['nullable', 'in:1,2'],
            'disposition_id' => ['nullable', 'exists:dispositions,id'],
            'marketing_source_id' => ['nullable', 'exists:marketing_sources,id'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ]);
    }

    private function businessFor(Appointment $appointment): Business
    {
        $business = $appointment->client?->business ?? Business::where('is_clinic', true)->firstOrFail();
        $this->authorize('access-business', $business);

        return $business;
    }

    private function clinic(Request $request): Business
    {
        $business = $request->user()->accessibleBusinesses()->firstWhere('is_clinic', true);
        abort_unless($business, 403);

        return $business;
    }
}
