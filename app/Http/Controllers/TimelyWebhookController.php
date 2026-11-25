<?php

namespace App\Http\Controllers;

use App\Models\TimelyBooking;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives a booking from Timely. Takes the same form fields and returns the same
 * JSON shape as the old api/timely_leads.php so the sender needs no changes.
 */
class TimelyWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $started = microtime(true);
        $token = config('alaise.timely_token');
        if ($token && ! hash_equals($token, (string) ($request->header('X-Webhook-Token') ?? $request->query('token')))) {
            return $this->respond($started, -99, 'ERR_UNAUTHORISED', 'Missing or incorrect token', 401);
        }

        $data = $request->validate([
            'firstName' => ['nullable', 'string', 'max:100'],
            'lastName' => ['nullable', 'string', 'max:100'],
            'tel' => ['nullable', 'string', 'max:20'],
            'emailAddress' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'nextAppointment' => ['nullable', 'string', 'max:50'],
        ]);

        $when = null;
        try {
            $when = filled($data['nextAppointment'] ?? null) ? CarbonImmutable::parse($data['nextAppointment']) : null;
        } catch (\Throwable) {
            // An unreadable date is stored as "no appointment" rather than rejecting the booking.
        }

        // Timely re-sends bookings; the same phone number and time is the same booking.
        $duplicate = TimelyBooking::where('tel', $data['tel'] ?? null)
            ->when($when, fn ($q) => $q->where('next_appointment', $when), fn ($q) => $q->whereNull('next_appointment'))->exists();

        $booking = $duplicate ? null : TimelyBooking::create([
            'first_name' => $data['firstName'] ?? null, 'last_name' => $data['lastName'] ?? null,
            'tel' => $data['tel'] ?? null, 'email' => $data['emailAddress'] ?? null,
            'location' => $data['location'] ?? null, 'next_appointment' => $when,
        ]);

        return $this->respond($started, 1, 'OK', $duplicate ? 'Duplicate ignored' : 'Success', 200, $booking?->id ?? 0);
    }

    private function respond(float $started, int $code, string $status, string $info, int $http, int $id = 0): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'response' => ['status' => $status, 'info' => $info],
            'custID' => $id,
            'responseTime' => microtime(true) - $started,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], $http);
    }
}
