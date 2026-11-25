<?php

namespace App\Models;

use App\Enums\DispositionCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposition extends Model
{
    // Stable handles for the dispositions that automated rules depend on.
    public const UPCOMING = 'upcoming';
    public const DID_NOT_SHOW = 'did-not-show';
    public const NEW_BOOKING = 'new-booking';
    public const CANCELLED = 'cancelled';
    public const CALLED_ONCE = 'called-once';
    public const TO_BE_CONTACTED = 'dnrb-to-be-contacted';
    public const NOT_DISPOSITIONED = 'not-dispositioned';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'category' => DispositionCategory::class];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** IDs of the dispositions with the given slugs (empty array if none are set up). */
    public static function idsFor(string ...$slugs): array
    {
        return static::whereIn('slug', $slugs)->pluck('id')->all();
    }

    public static function idFor(string $slug): ?int
    {
        return static::where('slug', $slug)->value('id');
    }
}
