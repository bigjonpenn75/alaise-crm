<?php

if (! function_exists('money')) {
    /** £1,234.50, or a dash for null (used where a ratio has no denominator). */
    function money(float|int|string|null $value, int $decimals = 2): string
    {
        return $value === null ? '—' : '£'.number_format((float) $value, $decimals);
    }
}

if (! function_exists('percent')) {
    function percent(?float $value): string
    {
        return $value === null ? '—' : number_format($value * 100, 1).'%';
    }
}
