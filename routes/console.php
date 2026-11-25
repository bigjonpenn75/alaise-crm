<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron entry on the server: * * * * * php /path/to/artisan schedule:run
Schedule::command('alaise:process-timely-bookings')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('alaise:mark-undispositioned')->dailyAt(config('alaise.undispositioned_at'));
