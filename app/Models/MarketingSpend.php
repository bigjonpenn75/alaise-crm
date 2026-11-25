<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingSpend extends Model
{
    public const CHANNELS = ['instagram', 'google', 'facebook', 'tiktok', 'staff'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['spend_date' => 'date'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalAttribute(): float
    {
        return (float) collect(self::CHANNELS)->sum(fn ($c) => (float) $this->{$c});
    }
}
