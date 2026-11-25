<?php

namespace App\Models;

use App\Enums\BookingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'created_date' => 'date',
            'status_updated_at' => 'datetime',
            'fee' => 'decimal:2',
            'booking_type' => BookingType::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function marketingSource(): BelongsTo
    {
        return $this->belongsTo(MarketingSource::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('appointment_date', '>=', $from)->whereDate('appointment_date', '<=', $to);
    }

    public function getTimeLabelAttribute(): ?string
    {
        return $this->appointment_time ? substr($this->appointment_time, 0, 5) : null;
    }
}
