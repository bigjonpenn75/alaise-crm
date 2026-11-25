<?php

namespace App\Enums;

enum Channel: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Google = 'google';
    case TikTok = 'tiktok';

    public function label(): string
    {
        return $this->name;
    }
}
