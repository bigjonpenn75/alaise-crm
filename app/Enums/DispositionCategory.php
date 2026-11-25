<?php

namespace App\Enums;

/** Buckets used by the technician performance report. */
enum DispositionCategory: string
{
    case Upcoming = 'upcoming';
    case Converted = 'converted';
    case Lost = 'lost';
    case Chase = 'chase';
    case Undispositioned = 'undispositioned';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::Converted => 'Converts',
            self::Lost => 'Lost',
            self::Chase => 'To be contacted',
            self::Undispositioned => 'Un-dispositioned',
            self::Other => 'Other',
        };
    }
}
