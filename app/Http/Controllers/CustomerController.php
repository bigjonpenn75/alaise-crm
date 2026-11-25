<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Lead;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Business $business)
    {
        return view('customers.index', ['business' => $business]);
    }

    /** Every customer for the business as a CSV file. */
    public function export(Business $business): StreamedResponse
    {
        $filename = $business->code.'-customers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($business) {
            $out = fopen('php://output', 'w');
            if ($business->is_clinic) {
                fputcsv($out, ['ID', 'First name', 'Last name', 'Email', 'Tel', 'Added by', 'Technician', 'Location', 'Service', 'Appointments', 'Date added']);
                Client::where('business_id', $business->id)->with(['staffMember', 'technician', 'location', 'service'])->withCount('appointments')
                    ->chunkById(1000, function ($clients) use ($out) {
                        foreach ($clients as $c) {
                            fputcsv($out, [$c->id, $c->first_name, $c->last_name, $c->email, $c->tel, $c->staffMember?->name, $c->technician?->name,
                                $c->location?->name, $c->service?->name, $c->appointments_count, $c->created_date?->format('Y-m-d')]);
                        }
                    });
            } else {
                fputcsv($out, ['ID', 'First name', 'Last name', 'Email', 'Tel', 'Added by', $business->technicianLabel(), 'Location', 'Source', 'Type', 'Deposit', 'Full cost', 'Date added']);
                Lead::where('business_id', $business->id)->with(['staffMember', 'technician', 'location', 'source'])
                    ->chunkById(1000, function ($leads) use ($out) {
                        foreach ($leads as $l) {
                            fputcsv($out, [$l->id, $l->first_name, $l->last_name, $l->email, $l->tel, $l->staffMember?->name, $l->technician?->name,
                                $l->location?->name, $l->source?->name, $l->booking_type->label(), $l->deposit, $l->full_cost, $l->date_added?->format('Y-m-d')]);
                        }
                    });
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
