<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Disposition;
use Illuminate\Console\Command;

class MarkUndispositioned extends Command
{
    protected $signature = 'alaise:mark-undispositioned';

    protected $description = 'Move appointments that are in the past but still "Upcoming" to "Not Dispositioned"';

    public function handle(): int
    {
        $upcoming = Disposition::idFor(Disposition::UPCOMING);
        $target = Disposition::idFor(Disposition::NOT_DISPOSITIONED);
        if (! $upcoming || ! $target) {
            $this->warn('The Upcoming / Not Dispositioned dispositions are not set up; nothing to do.');

            return self::SUCCESS;
        }

        $count = Appointment::where('disposition_id', $upcoming)->whereDate('appointment_date', '<', today())
            ->update(['disposition_id' => $target, 'status_updated_at' => now()]);
        $this->info("$count appointment(s) marked as not dispositioned.");

        return self::SUCCESS;
    }
}
