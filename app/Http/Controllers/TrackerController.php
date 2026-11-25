<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Http\Request;

class TrackerController extends Controller
{
    public function __invoke(Request $request, Business $business, ReportService $reports)
    {
        $month = DateRange::month($request);

        return view('marketing.tracker', ['business' => $business, 'month' => $month] + $reports->tracker($business, $month));
    }
}
