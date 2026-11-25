<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Lead;
use App\Models\MarketingSpend;
use App\Models\StaffMember;
use App\Models\Technician;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private Business $flesh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flesh = Business::find(2);
        $reception = StaffMember::create(['business_id' => 2, 'name' => 'Front desk', 'is_reception' => true]);
        $artistStaff = StaffMember::create(['business_id' => 2, 'name' => 'Sam (artist)']);
        $sam = Technician::create(['business_id' => 2, 'name' => 'Sam']);

        $lead = fn (string $date, $staff, int $source, float $deposit, float $cost) => Lead::create([
            'business_id' => 2, 'first_name' => 'A', 'last_name' => 'B', 'date_added' => $date,
            'staff_member_id' => $staff->id, 'technician_id' => $sam->id, 'lead_source_id' => $source, 'deposit' => $deposit, 'full_cost' => $cost,
        ]);
        $lead('2026-10-01', $reception, 9, 50, 300);     // reception, Instagram
        $lead('2026-10-01', $artistStaff, 4, 20, 100);   // artist logged it, but a walk-in => reception
        $lead('2026-10-02', $artistStaff, 9, 40, 200);   // artist booking
        $lead('2026-10-05', $reception, 12, 60, 400);    // reception, Google (second week)
        $lead('2026-11-01', $reception, 9, 99, 999);     // different month

        MarketingSpend::create(['business_id' => 2, 'spend_date' => '2026-10-01', 'instagram' => 70, 'google' => 30]);
        MarketingSpend::create(['business_id' => 4, 'spend_date' => '2026-10-01', 'instagram' => 500]); // other business
    }

    public function test_tracker_splits_reception_and_artist_bookings_and_totals_by_week_and_month(): void
    {
        $report = app(ReportService::class)->tracker($this->flesh, CarbonImmutable::parse('2026-10-01'));
        $rows = collect($report['rows']);

        $first = $rows->first();
        $this->assertSame(['bookings' => 2, 'deposits' => 70.0, 'revenue' => 400.0], $first['reception']);
        $this->assertSame(0, $first['artist']['bookings']);

        // Thu 1st - Sun 4th is the first week.
        $week = $rows->firstWhere('type', 'week');
        $this->assertSame('2026-10-04', $week['date']->toDateString());
        $this->assertSame(3, $week['total']['bookings']);
        $this->assertSame(1, $week['artist']['bookings']);

        $total = $rows->firstWhere('type', 'total');
        $this->assertSame(['bookings' => 3, 'deposits' => 130.0, 'revenue' => 800.0], $total['reception']);
        $this->assertSame(['bookings' => 1, 'deposits' => 40.0, 'revenue' => 200.0], $total['artist']);
        $this->assertSame(4, $total['total']['bookings']);
        $this->assertSame(['Sam'], $report['technicians']->pluck('name')->all());
        $this->assertSame(31 + 5 + 1, $rows->count()); // days + week subtotals + month total
    }

    public function test_performance_counts_reception_leads_and_channel_spend(): void
    {
        $total = collect(app(ReportService::class)->performance($this->flesh, CarbonImmutable::parse('2026-10-01'))['rows'])->firstWhere('type', 'total');

        $this->assertSame(3, $total['leads']);
        $this->assertSame(800.0, $total['revenue']);
        $this->assertSame(70.0, $total['spend']['instagram']);
        $this->assertSame(30.0, $total['spend']['google']);
        // Instagram: one reception lead. Google channel includes Walk In (4) and Google (12).
        $this->assertSame(1, $total['channelLeads']['instagram']);
        $this->assertSame(2, $total['channelLeads']['google']);
    }

    public function test_spend_summary_works_out_cost_per_lead(): void
    {
        $summary = app(ReportService::class)->spendSummary($this->flesh, '2026-10-01', '2026-10-31');

        $this->assertSame(100.0, $summary['total']);
        $this->assertSame(70.0, $summary['channels']['instagram']['cpa']);
        $this->assertSame(15.0, $summary['channels']['google']['cpa']);
        $this->assertNull($summary['channels']['tiktok']['cpa']);
        $this->assertSame(1, $summary['entries']->total());
    }

    public function test_report_pages_render_with_data(): void
    {
        $this->actingAs($this->user());

        $this->get('/flesh/tracker?month=2026-10')->assertOk()->assertSee('Sam')->assertSee('Reception totals');
        $this->get('/flesh/marketing/performance?month=2026-10')->assertOk()->assertSee('Month total');
        $this->get('/flesh/marketing/spend?range=all')->assertOk()->assertSee('£100.00');
        $this->get('/flesh/customers')->assertOk();
        $this->get('/?range=all')->assertOk()->assertSee('Flesh Tattoo');
        $this->get('/search?q=front')->assertOk();
    }

    public function test_csv_export(): void
    {
        $response = $this->actingAs($this->user())->get('/flesh/customers/export');

        $response->assertOk()->assertDownload();
        $lines = explode("\n", trim($response->streamedContent()));
        $this->assertCount(6, $lines); // header + 5 bookings
        $this->assertStringContainsString('Instagram', $lines[1]);
    }
}
