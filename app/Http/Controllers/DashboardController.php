<?php

namespace App\Http\Controllers;

use App\Enums\UserLevel;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        // Standard users only log bookings, so that is where they land.
        if ($request->user()->level === UserLevel::Standard) {
            return redirect()->route('leads.create');
        }

        $range = DateRange::fromRequest($request);
        $businesses = $request->user()->accessibleBusinesses();

        return view('dashboard.index', [
            'range' => $range,
            'businesses' => $businesses,
            'data' => $businesses->mapWithKeys(fn ($b) => [$b->id => $reports->dashboard($b, $range->from, $range->to)]),
        ]);
    }
}
