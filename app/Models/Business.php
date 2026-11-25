<?php

namespace App\Models;

use App\Enums\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'is_clinic' => 'boolean', 'uses_reception_split' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(Technician::class);
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(Disposition::class);
    }

    public function marketingSources(): HasMany
    {
        return $this->hasMany(MarketingSource::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function channelSources(): HasMany
    {
        return $this->hasMany(ChannelSource::class);
    }

    /** @return array<string, int[]> lead source IDs keyed by channel value */
    public function channelSourceIds(): array
    {
        $map = array_fill_keys(array_column(Channel::cases(), 'value'), []);
        foreach ($this->channelSources as $row) {
            $map[$row->channel->value][] = $row->lead_source_id;
        }

        return $map;
    }

    /** The term this business uses for the person doing the work. */
    public function technicianLabel(): string
    {
        return $this->is_clinic ? 'Technician' : 'Artist';
    }
}
