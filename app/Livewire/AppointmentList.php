<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Client;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** A client's appointments, with the quick "update record" form the technicians use. */
class AppointmentList extends Component
{
    #[Locked]
    public int $clientId;

    public ?int $editing = null;
    public string $disposition_id = '';
    public string $technician_id = '';
    public string $marketing_source_id = '';
    public string $fee = '';
    public string $note = '';

    public function mount(Client $client): void
    {
        $this->clientId = $client->id;
    }

    private function client(): Client
    {
        $client = Client::with('business')->findOrFail($this->clientId);
        abort_unless(auth()->user()->can('manage-business', $client->business), 403);

        return $client;
    }

    public function edit(int $id): void
    {
        $appointment = $this->client()->appointments()->findOrFail($id);
        $this->editing = $id;
        $this->disposition_id = (string) $appointment->disposition_id;
        $this->technician_id = (string) $appointment->technician_id;
        $this->marketing_source_id = (string) $appointment->marketing_source_id;
        $this->fee = (string) $appointment->fee;
        $this->note = '';
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->editing = null;
    }

    public function save(): void
    {
        $client = $this->client();
        $appointment = $client->appointments()->findOrFail($this->editing);
        $data = $this->validate([
            'disposition_id' => ['required', 'exists:dispositions,id'],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'marketing_source_id' => ['nullable', 'exists:marketing_sources,id'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'note' => ['nullable', 'string', 'max:5000'],
        ], attributes: ['disposition_id' => 'disposition']);

        $changes = [
            'disposition_id' => $data['disposition_id'],
            'technician_id' => $data['technician_id'] ?: null,
            'marketing_source_id' => $data['marketing_source_id'] ?: null,
            'fee' => $data['fee'] === '' ? null : $data['fee'],
        ];
        if ((int) $data['disposition_id'] !== (int) $appointment->disposition_id) {
            $changes['status_updated_at'] = now();
        }
        $appointment->update($changes);

        if (trim((string) $data['note']) !== '') {
            $client->notes()->create(['body' => trim($data['note']), 'user_id' => auth()->id()]);
        }
        $client->logHistory('Appointment updated');

        $this->editing = null;
        $this->dispatch('record-changed');
    }

    public function render()
    {
        $client = $this->client();
        $business = $client->business;

        return view('livewire.appointment-list', [
            'business' => $business,
            'clientName' => $client->name,
            'appointments' => $client->appointments()->with(['technician', 'location', 'service', 'disposition', 'marketingSource'])
                ->orderByDesc('appointment_date')->orderByDesc('id')->get(),
            'dispositions' => $business->dispositions()->active()->orderBy('name')->pluck('name', 'id'),
            'technicians' => $business->technicians()->active()->orderBy('name')->pluck('name', 'id'),
            'marketingSources' => $business->marketingSources()->active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
