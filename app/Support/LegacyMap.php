<?php

namespace App\Support;

/**
 * Rules that were hard-coded as ID lists in the old PHP pages. They are now
 * data (editable under Settings); these are only the starting values.
 */
class LegacyMap
{
    public const BUSINESSES = [
        1 => ['code' => 'truk', 'short_name' => 'TRUK', 'is_clinic' => true, 'uses_reception_split' => false],
        2 => ['code' => 'flesh', 'short_name' => 'Flesh', 'is_clinic' => false, 'uses_reception_split' => true],
        3 => ['code' => 'salford', 'short_name' => 'Salford', 'is_clinic' => false, 'uses_reception_split' => true],
        4 => ['code' => 'mts', 'short_name' => 'MTS', 'is_clinic' => false, 'uses_reception_split' => true],
    ];

    /** lead_users IDs the old trackers treated as reception ($flesh_reception, latest version). */
    public const RECEPTION_STAFF_IDS = [1, 2, 20, 26, 27, 32, 44, 45, 46, 47];

    /** "Walk In": counted as a reception booking whoever logged it. */
    public const RECEPTION_SOURCE_IDS = [4];

    /** channel => lead source IDs. The studios shared one list; TRUK had its own Google list. */
    public const CHANNEL_SOURCES = [
        'default' => ['instagram' => [9], 'facebook' => [5], 'tiktok' => [14], 'google' => [1, 3, 4, 6, 12]],
        1 => ['instagram' => [9], 'facebook' => [5], 'tiktok' => [14], 'google' => [1, 3, 4, 7, 11, 12]],
    ];

    /** truk_dispositions ID => [report bucket, stable slug]. Anything not listed is "other". */
    public const DISPOSITIONS = [
        1 => ['upcoming', 'upcoming'],
        3 => ['chase', 'did-not-show'],
        5 => ['chase', null],
        6 => ['other', 'new-booking'],
        7 => ['lost', 'cancelled'],
        8 => ['converted', null],
        9 => ['lost', null],
        10 => ['chase', null],
        11 => ['converted', null],
        12 => ['chase', 'called-once'],
        13 => ['chase', null],
        14 => ['chase', null],
        15 => ['lost', null],
        17 => ['chase', 'dnrb-to-be-contacted'],
        18 => ['undispositioned', 'not-dispositioned'],
    ];
}
