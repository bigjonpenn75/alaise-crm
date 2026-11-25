<?php

use App\Http\Controllers\TimelyWebhookController;
use Illuminate\Support\Facades\Route;

// Timely pushes new bookings here. The second path is the old system's URL,
// kept so the existing Timely/Zapier configuration carries on working.
Route::post('timely/leads', TimelyWebhookController::class)->name('api.timely');
Route::post('timely_leads.php', TimelyWebhookController::class);
