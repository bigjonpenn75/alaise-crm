<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TimelyBooking extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['next_appointment' => 'datetime', 'is_new' => 'boolean'];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Timely sends numbers without the leading zero; clients are stored with it. */
    public function matchingClient(): ?Client
    {
        if (! $this->tel) {
            return null;
        }

        return Client::whereIn('tel', [$this->tel, '0'.ltrim($this->tel, '0')])->latest('id')->first();
    }

    /** Timely sends an empty date when no appointment is attached. */
    public function hasAppointmentDate(): bool
    {
        return $this->next_appointment !== null && $this->next_appointment->year > 2020;
    }
}
