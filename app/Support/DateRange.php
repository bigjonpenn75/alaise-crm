<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** The preset/custom date filter shared by the dashboard and marketing spend pages. */
class DateRange
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'week' => 'This week',
        'month' => 'Month to date',
        'last-month' => 'Last month',
        'all' => 'All time',
        'custom' => 'Custom',
    ];

    public function __construct(public string $key, public ?string $from, public ?string $to) {}

    public static function fromRequest(Request $request, string $default = 'today'): self
    {
        $key = array_key_exists($request->query('range'), self::PRESETS) ? $request->query('range') : $default;
        $today = CarbonImmutable::today();
        $valid = fn (?string $d) => $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;

        [$from, $to] = match ($key) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'week' => [$today->startOfWeek(), $today],
            'month' => [$today->startOfMonth(), $today],
            'last-month' => [$today->startOfMonth()->subMonthNoOverflow(), $today->startOfMonth()->subDay()],
            'custom' => [$valid($request->query('from')), $valid($request->query('to'))],
            default => [null, null],
        };

        return new self($key, is_string($from) ? $from : $from?->toDateString(), is_string($to) ? $to : $to?->toDateString());
    }

    public function label(): string
    {
        if ($this->key !== 'custom') {
            return self::PRESETS[$this->key];
        }

        return trim(($this->from ? 'From '.CarbonImmutable::parse($this->from)->format('j M Y') : '')
            .($this->to ? ' to '.CarbonImmutable::parse($this->to)->format('j M Y') : '')) ?: 'All time';
    }

    public static function month(Request $request): CarbonImmutable
    {
        $value = (string) $request->query('month');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)
            ? CarbonImmutable::createFromFormat('!Y-m', $value)
            : CarbonImmutable::today()->startOfMonth();
    }
}
