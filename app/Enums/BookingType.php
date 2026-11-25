<?php

namespace App\Enums;

enum BookingType: int
{
    case Sale = 1;
    case Consultation = 2;

    /** Leads call type 1 a sale; clinic appointments call it a treatment. */
    public function label(bool $clinic = false): string
    {
        return match ($this) {
            self::Sale => $clinic ? 'Treatment' : 'Sale',
            self::Consultation => 'Consultation',
        };
    }
}
