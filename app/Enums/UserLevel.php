<?php

namespace App\Enums;

/** Values match the IDs in the old user_levels table. */
enum UserLevel: int
{
    case Management = 1;
    case Standard = 2;
    case Superuser = 5;
    case Marketing = 6;
    case Reception = 7;

    public function label(): string
    {
        return $this->name;
    }

    /** Reports, customers, users and settings. */
    public function canManage(): bool
    {
        return in_array($this, [self::Management, self::Marketing, self::Superuser], true);
    }

    public function isSuperuser(): bool
    {
        return $this === self::Superuser;
    }
}
