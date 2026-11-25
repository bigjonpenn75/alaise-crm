<?php

namespace App\Services;

use App\Enums\Channel;
use App\Enums\DispositionCategory;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\Disposition;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MarketingSpend;
use App\Models\StaffMember;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The numbers behind the trackers, marketing reports, dashboard and technician tables.
 *
 * Each report loads the period's rows once and totals them in PHP, where the old
 * pages ran a separate query for every cell.
 */
class ReportService
{
    private const MONEY = ['bookings' => 0, 'deposits' => 0.0, 'revenue' => 0.0];

    // ---------------------------------------------------------------- daily tracker

    /**
     * Day-by-day bookings, deposits and revenue for a studio, split into reception
     * and artist bookings, with a column group for each artist active that month.
     */
    public function tracker(Business $business, CarbonImmutable $month): array
    {
        [$from, $to] = [$month->startOfMonth(), $month->endOfMonth()];
        $leads = Lead::where('business_id', $business->id)->between($from->toDateString(), $to->toDateString())
            ->get(['date_added', 'technician_id', 'staff_member_id', 'lead_source_id', 'deposit', 'full_cost']);

        $isReception = $this->receptionTest();
        $blank = fn () => ['reception' => self::MONEY, 'artist' => self::MONEY, 'total' => self::MONEY, 'techs' => []];
        $days = [];
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $days[$d->toDateString()] = $blank();
        }

        foreach ($leads as $lead) {
            $day = &$days[$lead->date_added->toDateString()];
            $groups = ['total', $isReception($lead) ? 'reception' : 'artist'];
            foreach ($groups as $group) {
                $this->addMoney($day[$group], $lead);
            }
            if ($lead->technician_id) {
                $day['techs'][$lead->technician_id] ??= self::MONEY;
                $this->addMoney($day['techs'][$lead->technician_id], $lead);
            }
            unset($day);
        }

        $techIds = $leads->pluck('technician_id')->filter()->unique();

        return [
            'rows' => $this->withSubtotals($days),
            'technicians' => $business->technicians()->whereIn('id', $techIds)->orderBy('name')->get(),
        ];
    }

    private function addMoney(array &$bucket, Lead $lead): void
    {
        $bucket['bookings']++;
        $bucket['deposits'] += (float) $lead->deposit;
        $bucket['revenue'] += (float) $lead->full_cost;
    }

    /** @return \Closure(Lead): bool whether a lead counts towards reception totals */
    private function receptionTest(): \Closure
    {
        $staff = StaffMember::where('is_reception', true)->pluck('id')->flip();
        $sources = LeadSource::where('counts_as_reception', true)->pluck('id')->flip();

        return fn (Lead $lead) => $staff->has($lead->staff_member_id) || $sources->has($lead->lead_source_id);
    }

    // ---------------------------------------------------------------- marketing performance

    /**
     * Day-by-day leads, revenue and spend with cost per lead, plus spend and leads per channel.
     *
     * Studios: leads and revenue come from bookings (reception-credited ones only where the
     * studio splits reception and artist bookings). Clinic: leads are new clients, broken
     * down by location, and revenue is appointment fees excluding no-shows and cancellations.
     */
    public function performance(Business $business, CarbonImmutable $month): array
    {
        [$from, $to] = [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()];
        $channels = array_column(Channel::cases(), 'value');
        $blank = [
            'leads' => 0, 'revenue' => 0.0, 'staff' => 0.0,
            'spend' => array_fill_keys($channels, 0.0),
            'channelLeads' => array_fill_keys($channels, 0),
            'locations' => [],
        ];
        $days = [];
        for ($d = $month->startOfMonth(); $d->lte($month->endOfMonth()); $d = $d->addDay()) {
            $days[$d->toDateString()] = $blank;
        }

        foreach (MarketingSpend::where('business_id', $business->id)->whereBetween('spend_date', [$from, $to])->get() as $spend) {
            $day = &$days[$spend->spend_date->toDateString()];
            foreach ($channels as $channel) {
                $day['spend'][$channel] += (float) $spend->{$channel};
            }
            $day['staff'] += (float) $spend->staff;
            unset($day);
        }

        $sourceChannel = [];
        foreach ($business->channelSourceIds() as $channel => $ids) {
            foreach ($ids as $id) {
                $sourceChannel[$id] = $channel;
            }
        }

        $leads = Lead::where('business_id', $business->id)->between($from, $to)
            ->when($business->uses_reception_split, fn ($q) => $q->reception())
            ->get(['date_added', 'lead_source_id', 'full_cost']);
        foreach ($leads as $lead) {
            $day = &$days[$lead->date_added->toDateString()];
            if (! $business->is_clinic) {
                $day['leads']++;
                $day['revenue'] += (float) $lead->full_cost;
            }
            if (isset($sourceChannel[$lead->lead_source_id])) {
                $day['channelLeads'][$sourceChannel[$lead->lead_source_id]]++;
            }
            unset($day);
        }

        $locations = collect();
        if ($business->is_clinic) {
            $clients = Client::where('business_id', $business->id)->whereBetween('created_date', [$from, $to])->get(['created_date', 'location_id']);
            foreach ($clients as $client) {
                $day = &$days[$client->created_date->toDateString()];
                $day['leads']++;
                if ($client->location_id) {
                    $day['locations'][$client->location_id] = ($day['locations'][$client->location_id] ?? 0) + 1;
                }
                unset($day);
            }
            foreach ($this->clinicFees($business, $from, $to) as $date => $fees) {
                $days[$date]['revenue'] = $fees;
            }
            $locations = $business->locations()->where(fn ($q) => $q->where('active', true)
                ->orWhereIn('id', $clients->pluck('location_id')->filter()->unique()))->orderBy('name')->get();
        }

        return ['rows' => $this->withSubtotals($days), 'locations' => $locations];
    }

    /** @return array<string, float> fees per appointment date, ignoring no-shows and cancellations */
    private function clinicFees(Business $business, string $from, string $to): array
    {
        $excluded = Disposition::idsFor(Disposition::DID_NOT_SHOW, Disposition::CANCELLED);

        return Appointment::between($from, $to)
            ->whereIn('client_id', Client::select('id')->where('business_id', $business->id))
            ->where(fn ($q) => $q->whereNull('disposition_id')->orWhereNotIn('disposition_id', $excluded))
            ->selectRaw('appointment_date as day, sum(fee) as fees')->groupBy('appointment_date')->toBase()->get()
            ->mapWithKeys(fn ($r) => [substr($r->day, 0, 10) => (float) $r->fees])->all();
    }

    // ---------------------------------------------------------------- marketing spend summary

    /** Spend per channel for a date range, set against the leads and lead value each channel produced. */
    public function spendSummary(Business $business, ?string $from, ?string $to): array
    {
        $spend = MarketingSpend::where('business_id', $business->id)
            ->when($from, fn ($q) => $q->whereDate('spend_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('spend_date', '<=', $to));

        $totals = (clone $spend)->selectRaw('sum(instagram) instagram, sum(google) google, sum(facebook) facebook, sum(tiktok) tiktok, sum(staff) staff')->toBase()->first();

        $channels = [];
        foreach ($business->channelSourceIds() as $channel => $sourceIds) {
            $row = Lead::where('business_id', $business->id)->between($from, $to)
                ->when($business->uses_reception_split, fn ($q) => $q->reception())
                ->whereIn('lead_source_id', $sourceIds)
                ->selectRaw('count(*) as leads, sum(full_cost) as value')->toBase()->first();
            $spent = (float) ($totals->{$channel} ?? 0);
            $channels[$channel] = [
                'label' => Channel::from($channel)->label(),
                'spend' => $spent,
                'leads' => (int) $row->leads,
                'value' => (float) $row->value,
                'cpa' => self::ratio($spent, $row->leads),
                'average' => self::ratio($row->value, $row->leads),
            ];
        }

        return [
            'channels' => $channels,
            'staff' => (float) ($totals->staff ?? 0),
            'total' => array_sum(array_column($channels, 'spend')) + (float) ($totals->staff ?? 0),
            'entries' => $spend->with('user')->orderByDesc('spend_date')->orderByDesc('id')->paginate(50)->withQueryString(),
        ];
    }

    // ---------------------------------------------------------------- month on month (clinic)

    public function monthOnMonth(Business $business, int $months = 12): array
    {
        $locations = $business->locations()->active()->orderBy('name')->get();
        $rows = [];
        for ($i = 0; $i < $months; $i++) {
            $month = CarbonImmutable::now()->startOfMonth()->subMonthsNoOverflow($i);
            [$from, $to] = [$month->toDateString(), $month->endOfMonth()->toDateString()];
            $byLocation = Client::where('business_id', $business->id)->whereBetween('created_date', [$from, $to])
                ->selectRaw('location_id, count(*) as c')->groupBy('location_id')->toBase()->pluck('c', 'location_id');
            $spend = (float) MarketingSpend::where('business_id', $business->id)->whereBetween('spend_date', [$from, $to])
                ->selectRaw('sum(instagram + google + facebook + tiktok + staff) as s')->toBase()->value('s');
            $leads = (int) $byLocation->sum();
            $rows[] = [
                'month' => $month,
                'leads' => $leads,
                'locations' => $byLocation,
                'revenue' => array_sum($this->clinicFees($business, $from, $to)),
                'spend' => $spend,
                'cpl' => self::ratio($spend, $leads),
            ];
        }

        return ['rows' => $rows, 'locations' => $locations];
    }

    // ---------------------------------------------------------------- dashboard

    public function dashboard(Business $business, ?string $from, ?string $to): array
    {
        $today = CarbonImmutable::today();
        $thisMonth = [$today->startOfMonth()->toDateString(), $today->toDateString()];
        // Same number of days into last month, so the two figures are comparable.
        $lastStart = $today->startOfMonth()->subMonthNoOverflow();
        $lastMonth = [$lastStart->toDateString(), $lastStart->addDays($today->day - 1)->min($lastStart->endOfMonth())->toDateString()];

        $leads = fn (?string $a, ?string $b) => Lead::where('business_id', $business->id)->between($a, $b)
            ->when($business->uses_reception_split, fn ($q) => $q->reception());
        $money = function (?string $a, ?string $b) use ($leads) {
            $r = $leads($a, $b)->selectRaw('count(*) as bookings, sum(deposit) as deposits, sum(full_cost) as revenue')->toBase()->first();

            return ['bookings' => (int) $r->bookings, 'deposits' => (float) $r->deposits, 'revenue' => (float) $r->revenue];
        };
        $breakdown = fn (string $column, $names, bool $salesOnly = false) => $leads($from, $to)
            ->when($salesOnly, fn ($q) => $q->where('booking_type', '!=', 2))
            ->selectRaw("$column as k, count(*) as bookings, sum(deposit) as deposits, sum(full_cost) as revenue")
            ->groupBy($column)->toBase()->get()
            ->map(fn ($r) => ['name' => $names[$r->k] ?? '—', 'bookings' => (int) $r->bookings, 'deposits' => (float) $r->deposits, 'revenue' => (float) $r->revenue])
            ->sortByDesc('bookings')->values();

        $data = [
            'period' => $money($from, $to),
            'thisMonth' => $money(...$thisMonth),
            'lastMonth' => $money(...$lastMonth),
            'bySource' => $breakdown('lead_source_id', LeadSource::pluck('name', 'id'), salesOnly: true),
            'byTechnician' => $breakdown('technician_id', $business->technicians()->pluck('name', 'id')),
            'byLocation' => $breakdown('location_id', $business->locations()->pluck('name', 'id')),
            'byStaff' => $breakdown('staff_member_id', $business->staffMembers()->pluck('name', 'id')),
        ];

        if ($business->is_clinic) {
            $clients = fn (?string $a, ?string $b) => Client::where('business_id', $business->id)
                ->when($a, fn ($q) => $q->whereDate('created_date', '>=', $a))
                ->when($b, fn ($q) => $q->whereDate('created_date', '<=', $b))->count();
            $data['clients'] = ['period' => $clients($from, $to), 'thisMonth' => $clients(...$thisMonth), 'lastMonth' => $clients(...$lastMonth)];
        }

        return $data;
    }

    // ---------------------------------------------------------------- technician performance

    /**
     * Appointment outcomes per technician for a month, in three views:
     * consultations (a recent client's first appointment), repeat visits, and everything.
     */
    public function technicianPerformance(Business $business, CarbonImmutable $month): array
    {
        [$from, $to] = [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()];
        $consultIds = $this->consultationIds($business, $month)->flip();

        $appointments = Appointment::between($from, $to)
            ->whereIn('client_id', Client::select('id')->where('business_id', $business->id))
            ->whereNotNull('technician_id')
            ->get(['id', 'technician_id', 'disposition_id', 'fee']);

        $dispositions = $business->dispositions()->get()->keyBy('id');
        $blank = ['appointments' => 0, 'fees' => 0.0, 'dispositions' => []]
            + array_fill_keys(array_column(DispositionCategory::cases(), 'value'), 0);
        $segments = ['consultations' => [], 'repeat' => [], 'all' => []];

        foreach ($appointments as $appointment) {
            $category = $dispositions[$appointment->disposition_id]->category->value ?? DispositionCategory::Other->value;
            foreach (['all', $consultIds->has($appointment->id) ? 'consultations' : 'repeat'] as $segment) {
                $row = &$segments[$segment][$appointment->technician_id];
                $row ??= $blank;
                $row['appointments']++;
                $row['fees'] += (float) $appointment->fee;
                $row[$category]++;
                if ($appointment->disposition_id) {
                    $row['dispositions'][$appointment->disposition_id] = ($row['dispositions'][$appointment->disposition_id] ?? 0) + 1;
                }
                unset($row);
            }
        }

        return [
            'segments' => $segments,
            'technicians' => $business->technicians()->whereIn('id', array_keys($segments['all']))->orderBy('name')->get(),
            'dispositions' => $dispositions->where('active', true)->sortBy('name')->values(),
        ];
    }

    /**
     * Appointments that count as consultations in a month: the first appointment of a
     * client created in that month or the two before it, when it falls inside the month.
     */
    public function consultationIds(Business $business, CarbonImmutable $month): Collection
    {
        $recentClients = Client::select('id')->where('business_id', $business->id)
            ->whereBetween('created_date', [$month->startOfMonth()->subMonthsNoOverflow(2)->toDateString(), $month->endOfMonth()->toDateString()]);
        $firstIds = Appointment::whereIn('client_id', $recentClients)->selectRaw('min(id) as id')->groupBy('client_id')->toBase()->pluck('id');

        return Appointment::whereIn('id', $firstIds)
            ->between($month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString())->pluck('id');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Turn a date => metrics map into display rows, adding a subtotal after each
     * Sunday (and the last day of the month) and a month total at the end.
     */
    private function withSubtotals(array $days): array
    {
        $rows = [];
        $week = $total = null;
        $lastDate = array_key_last($days);
        foreach ($days as $date => $metrics) {
            $day = CarbonImmutable::parse($date);
            $rows[] = ['type' => 'day', 'date' => $day] + $metrics;
            $week = $week === null ? $metrics : self::sum($week, $metrics);
            $total = $total === null ? $metrics : self::sum($total, $metrics);
            if ($day->isSunday() || $date === $lastDate) {
                $rows[] = ['type' => 'week', 'date' => $day] + $week;
                $week = null;
            }
        }
        if ($total !== null) {
            $rows[] = ['type' => 'total', 'date' => null] + $total;
        }

        return $rows;
    }

    /** Add two metric arrays together, however deeply nested. */
    private static function sum(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            $a[$key] = is_array($value) ? self::sum($a[$key] ?? [], $value) : ($a[$key] ?? 0) + $value;
        }

        return $a;
    }

    public static function ratio(float|int|string|null $top, float|int|string|null $bottom): ?float
    {
        return (float) $bottom > 0 ? (float) $top / (float) $bottom : null;
    }
}
