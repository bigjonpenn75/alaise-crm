<?php

namespace App\Models;

use App\Enums\UserLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'level' => UserLevel::class,
            'active' => 'boolean',
            'is_reception' => 'boolean',
        ];
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function canManage(): bool
    {
        return $this->level->canManage();
    }

    public function isSuperuser(): bool
    {
        return $this->level->isSuperuser();
    }

    /** Superusers see every business; everyone else only the ones ticked on their account. */
    public function canAccessBusiness(Business|int $business): bool
    {
        if ($this->isSuperuser()) {
            return true;
        }
        $id = $business instanceof Business ? $business->id : $business;

        return $this->businesses->contains('id', $id);
    }

    /** @return \Illuminate\Support\Collection<int, Business> */
    public function accessibleBusinesses()
    {
        $all = Business::active()->orderBy('id')->get();

        return $this->isSuperuser() ? $all : $all->whereIn('id', $this->businesses->pluck('id'))->values();
    }
}
