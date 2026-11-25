<?php

namespace App\Http\Controllers;

use App\Models\Lead;

class LeadController extends Controller
{
    public function create()
    {
        return view('leads.form', ['lead' => null]);
    }

    public function show(Lead $lead)
    {
        $this->authorize('access-business', $lead->business);
        $lead->load(['business', 'staffMember', 'technician', 'location', 'source', 'service', 'addedBy']);

        return view('leads.show', ['lead' => $lead]);
    }

    public function edit(Lead $lead)
    {
        $this->authorize('access-business', $lead->business);

        return view('leads.form', ['lead' => $lead]);
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('access-business', $lead->business);
        $business = $lead->business;
        $lead->delete();

        return redirect()->route('customers.index', $business)->with('status', 'Booking deleted.');
    }
}
