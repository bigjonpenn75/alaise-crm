<?php

namespace App\Models;

use App\Enums\BookingType;
use App\Models\Concerns\HasRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasRecords, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_added' => 'date',
            'deposit' => 'decimal:2',
            'full_cost' => 'decimal:2',
            'booking_type' => BookingType::class,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('date_added', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_added', '<=', $to));
    }

    /**
     * Bookings credited to reception: logged under a reception staff member,
     * or a walk-in whoever logged it.
     */
    public function scopeReception(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereIn('staff_member_id', StaffMember::select('id')->where('is_reception', true))
            ->orWhereIn('lead_source_id', LeadSource::select('id')->where('counts_as_reception', true)));
    }

    /** Everything else: bookings the artists brought in themselves. */
    public function scopeNonReception(Builder $query): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('staff_member_id')
                ->orWhereNotIn('staff_member_id', StaffMember::select('id')->where('is_reception', true)))
            ->where(fn ($q) => $q->whereNull('lead_source_id')
                ->orWhereNotIn('lead_source_id', LeadSource::select('id')->where('counts_as_reception', true)));
    }
}
