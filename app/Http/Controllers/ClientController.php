<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function show(Client $client)
    {
        $this->authorize('access-business', $client->business);
        $client->load(['business', 'staffMember', 'technician', 'location', 'service']);

        return view('clients.show', [
            'client' => $client,
            'business' => $client->business,
            'feesTotal' => (float) $client->appointments()->sum('fee'),
        ]);
    }

    public function edit(Client $client)
    {
        $this->authorize('access-business', $client->business);

        return view('clients.edit', ['client' => $client, 'business' => $client->business]);
    }

    public function update(Request $request, Client $client)
    {
        $this->authorize('access-business', $client->business);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'tel' => ['nullable', 'string', 'max:20'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'service_id' => ['nullable', 'exists:services,id'],
        ]);
        $client->update($data);
        $client->logHistory('Customer details updated');

        return redirect()->route('clients.show', $client)->with('status', 'Customer updated.');
    }

    public function destroy(Client $client)
    {
        $this->authorize('access-business', $client->business);
        $business = $client->business;
        $client->appointments()->delete();
        $client->delete();

        return redirect()->route('customers.index', $business)->with('status', 'Customer and their appointments deleted.');
    }
}
