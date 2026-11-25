<?php

namespace App\Livewire;

use App\Enums\BookingType;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Add or edit a booking. The dropdowns follow whichever business is chosen. */
class LeadForm extends Component
{
    public ?Lead $lead = null;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $tel = '';
    public string $date_added = '';
    public string $business_id = '';
    public string $staff_member_id = '';
    public string $technician_id = '';
    public string $lead_source_id = '';
    public string $location_id = '';
    public string $service_id = '';
    public string $booking_type = '1';
    public string $deposit = '';
    public string $full_cost = '';

    public function mount(?Lead $lead = null): void
    {
        $this->lead = $lead?->exists ? $lead : null;
        if ($this->lead) {
            foreach (['first_name', 'last_name', 'email', 'tel', 'business_id', 'staff_member_id', 'technician_id', 'lead_source_id', 'location_id', 'service_id', 'deposit', 'full_cost'] as $field) {
                $this->{$field} = (string) $this->lead->{$field};
            }
            $this->booking_type = (string) $this->lead->booking_type->value;
            $this->date_added = $this->lead->date_added?->toDateString() ?? '';
        } else {
            $this->date_added = today()->toDateString();
            $businesses = auth()->user()->accessibleBusinesses();
            if ($businesses->count() === 1) {
                $this->business_id = (string) $businesses->first()->id;
            }
        }
    }

    public function updatedBusinessId(): void
    {
        $this->reset('staff_member_id', 'technician_id', 'location_id', 'service_id');
    }

    public function save()
    {
        $business = auth()->user()->accessibleBusinesses()->firstWhere('id', (int) $this->business_id);
        $owned = fn (string $table) => ['nullable', Rule::exists($table, 'id')->where('business_id', $business?->id)];

        $data = $this->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'tel' => ['nullable', 'string', 'max:20'],
            'date_added' => ['required', 'date'],
            'business_id' => ['required', Rule::in(auth()->user()->accessibleBusinesses()->pluck('id'))],
            'staff_member_id' => $owned('staff_members'),
            'technician_id' => $owned('technicians'),
            'location_id' => $owned('locations'),
            'service_id' => $owned('services'),
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'booking_type' => ['required', Rule::enum(BookingType::class)],
            'deposit' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'full_cost' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ], attributes: ['business_id' => 'business', 'staff_member_id' => 'added by', 'lead_source_id' => 'source']);

        foreach ($data as $key => $value) {
            $data[$key] = $value === '' ? null : $value;
        }
        $data['full_cost'] ??= 0;

        if ($this->lead) {
            $this->lead->update($data);
            $this->lead->logHistory('Booking details updated');
            session()->flash('status', 'Booking updated.');

            return $this->redirectRoute('leads.show', $this->lead);
        }

        Lead::create($data + ['added_by' => auth()->id()]);
        session()->flash('status', "Booking added for {$data['first_name']} {$data['last_name']}.");

        return $this->redirectRoute('leads.create');
    }

    public function render()
    {
        $business = $this->business_id ? Business::find($this->business_id) : null;
        $options = fn ($relation) => $business
            ? $business->{$relation}()->where(fn ($q) => $q->where('active', true)->orWhere('id', $this->lead?->{str($relation)->singular()->snake().'_id'}))->orderBy('name')->pluck('name', 'id')
            : collect();

        return view('livewire.lead-form', [
            'business' => $business,
            'businesses' => auth()->user()->accessibleBusinesses(),
            'staff' => $options('staffMembers'),
            'technicians' => $options('technicians'),
            'locations' => $options('locations'),
            'services' => $options('services'),
            'sources' => LeadSource::active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
