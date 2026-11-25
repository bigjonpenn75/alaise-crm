<?php

namespace Database\Seeders;

use App\Enums\UserLevel;
use App\Models\Business;
use App\Models\ChannelSource;
use App\Models\Disposition;
use App\Models\LeadSource;
use App\Models\User;
use App\Support\LegacyMap;
use Illuminate\Database\Seeder;

/**
 * Starting data for an install that is NOT importing the old database (local
 * development, tests). For the real cutover use `php artisan alaise:import-legacy`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $names = [1 => 'Tattoo Removal UK', 2 => 'Flesh Tattoo', 3 => 'Salford Tattoos', 4 => 'Manchester Tattoo Studio'];
        foreach (LegacyMap::BUSINESSES as $id => $attributes) {
            Business::create(['id' => $id, 'name' => $names[$id]] + $attributes);
        }

        $sources = [1 => 'Email', 2 => 'Text', 3 => 'Phone Call', 4 => 'Walk In', 5 => 'Facebook', 6 => 'Flesh Enquiry',
            7 => 'Tattoo Removal Enquiry', 8 => 'Friend / Family', 9 => 'Instagram', 11 => 'Online Booking', 12 => 'Google', 14 => 'TikTok', 15 => 'WhatsApp'];
        foreach ($sources as $id => $name) {
            LeadSource::create(['id' => $id, 'name' => $name, 'counts_as_reception' => in_array($id, LegacyMap::RECEPTION_SOURCE_IDS, true)]);
        }
        foreach (array_keys(LegacyMap::BUSINESSES) as $businessId) {
            foreach (LegacyMap::CHANNEL_SOURCES[$businessId] ?? LegacyMap::CHANNEL_SOURCES['default'] as $channel => $sourceIds) {
                foreach ($sourceIds as $sourceId) {
                    ChannelSource::create(['business_id' => $businessId, 'channel' => $channel, 'lead_source_id' => $sourceId]);
                }
            }
        }

        $dispositions = [1 => 'Upcoming', 3 => 'Did Not Show', 6 => 'New Booking', 7 => 'Cancelled', 8 => 'Re-Booked', 9 => 'DNRB - Closed',
            12 => 'Called Once', 17 => 'DNRB - To Be Contacted', 18 => 'Not Dispositioned'];
        foreach ($dispositions as $id => $name) {
            Disposition::create(['id' => $id, 'business_id' => 1, 'name' => $name,
                'category' => LegacyMap::DISPOSITIONS[$id][0] ?? 'other', 'slug' => LegacyMap::DISPOSITIONS[$id][1] ?? null]);
        }

        if (! app()->environment('testing')) {
            $password = str()->password(16, symbols: false);
            User::create(['username' => 'admin', 'password' => $password, 'level' => UserLevel::Superuser, 'first_name' => 'Admin', 'last_name' => 'User']);
            $this->command?->warn("Created superuser \"admin\" with password: $password");
        }
    }
}
