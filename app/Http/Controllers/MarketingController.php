<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\MarketingSpend;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingController extends Controller
{
    public function index(Request $request, Business $business, ReportService $reports)
    {
        $range = DateRange::fromRequest($request, 'month');

        return view('marketing.spend', ['business' => $business, 'range' => $range] + $reports->spendSummary($business, $range->from, $range->to));
    }

    public function performance(Request $request, Business $business, ReportService $reports)
    {
        $month = DateRange::month($request);

        return view('marketing.performance', ['business' => $business, 'month' => $month] + $reports->performance($business, $month));
    }

    public function monthOnMonth(Business $business, ReportService $reports)
    {
        abort_unless($business->is_clinic, 404);

        return view('marketing.month-on-month', ['business' => $business] + $reports->monthOnMonth($business));
    }

    public function create(Request $request)
    {
        return view('marketing.spend-form', ['spend' => new MarketingSpend(['spend_date' => today()]), 'businesses' => $request->user()->accessibleBusinesses()]);
    }

    public function store(Request $request)
    {
        $spend = MarketingSpend::create($this->validated($request) + ['user_id' => $request->user()->id]);

        return redirect()->route('spend.index', $spend->business)->with('status', 'Marketing spend added.');
    }

    public function edit(Request $request, MarketingSpend $spend)
    {
        $this->authorize('access-business', $spend->business);

        return view('marketing.spend-form', ['spend' => $spend, 'businesses' => $request->user()->accessibleBusinesses()]);
    }

    public function update(Request $request, MarketingSpend $spend)
    {
        $this->authorize('access-business', $spend->business);
        $spend->update($this->validated($request));

        return redirect()->route('spend.index', $spend->business)->with('status', 'Marketing spend updated.');
    }

    public function destroy(MarketingSpend $spend)
    {
        $this->authorize('access-business', $spend->business);
        $spend->delete();

        return back()->with('status', 'Marketing spend deleted.');
    }

    private function validated(Request $request): array
    {
        $amount = ['nullable', 'numeric', 'min:0', 'max:99999999'];
        $data = $request->validate([
            'business_id' => ['required', Rule::in($request->user()->accessibleBusinesses()->pluck('id'))],
            'spend_date' => ['required', 'date'],
            'instagram' => $amount, 'google' => $amount, 'facebook' => $amount, 'tiktok' => $amount, 'staff' => $amount,
        ]);
        foreach (MarketingSpend::CHANNELS as $channel) {
            $data[$channel] = $data[$channel] ?? 0;
        }

        return $data;
    }
}
