<?php

namespace App\Console\Commands;

use App\Support\LegacyMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacy extends Command
{
    protected $signature = 'alaise:import-legacy
        {--fresh : Empty the new tables first (needed when re-running at cutover)}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Copy data from the old alaiseuk_leadsadmin database (the "legacy" connection) into the new schema';

    /** New tables in dependency order. */
    private const TABLES = [
        'businesses', 'technicians', 'users', 'business_user', 'staff_members', 'locations', 'services',
        'lead_sources', 'channel_sources', 'dispositions', 'marketing_sources', 'leads', 'marketing_spends',
        'clients', 'appointments', 'timely_bookings', 'notes', 'documents', 'histories',
    ];

    /** @var array<string, array<int, true>> valid IDs per new table, for cleaning dangling references */
    private array $ids = [];

    private array $report = [];

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        try {
            $legacy->table('admin_users')->count();
        } catch (\Throwable $e) {
            $this->error('Cannot read the legacy database. Check the LEGACY_DB_* values in .env.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $hasData = DB::table('users')->exists() || DB::table('leads')->exists();
        if ($hasData && ! $this->option('fresh')) {
            $this->error('The new database already has data. Re-run with --fresh to replace it.');

            return self::FAILURE;
        }
        if ($hasData && ! $this->option('force') && ! $this->confirm('This deletes everything in the new database and re-imports it. Continue?')) {
            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach (array_reverse(self::TABLES) as $table) {
                DB::table($table)->delete();
            }
            DB::table('sessions')->delete();

            $this->importReferenceData();
            $this->importUsers();
            $this->importLeads();
            $this->importSpend();
            $this->importClients();
            $this->importAppointments();
            $this->importTimely();
            $this->importRecords();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->table(['Old table', 'Rows read', 'New table', 'Rows written', 'Note'], $this->report);
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function importReferenceData(): void
    {
        $this->copy('user_business', 'businesses', fn ($r) => [
            'id' => $r->ID, 'name' => trim($r->business), 'active' => (bool) $r->active,
            ...(LegacyMap::BUSINESSES[$r->ID] ?? [
                'code' => 'business-'.$r->ID, 'short_name' => trim($r->business), 'is_clinic' => false, 'uses_reception_split' => true,
            ]),
        ]);

        $this->copy('lead_tech', 'technicians', fn ($r) => $this->lookupRow($r, $r->tech));
        $this->copy('lead_users', 'staff_members', fn ($r) => $this->lookupRow($r, $r->username) + [
            'is_reception' => in_array((int) $r->ID, LegacyMap::RECEPTION_STAFF_IDS, true),
        ]);
        $this->copy('lead_location', 'locations', fn ($r) => $this->lookupRow($r, $r->location));
        $this->copy('lead_service', 'services', fn ($r) => $this->lookupRow($r, $r->service));
        $this->copy('truk_marketing_sources', 'marketing_sources', fn ($r) => $this->lookupRow($r, $r->marketingSource));
        $this->copy('truk_dispositions', 'dispositions', fn ($r) => $this->lookupRow($r, $r->disposition) + [
            'category' => LegacyMap::DISPOSITIONS[$r->ID][0] ?? 'other',
            'slug' => LegacyMap::DISPOSITIONS[$r->ID][1] ?? null,
        ]);

        // Source 0 ("No Source Added") becomes NULL rather than a row.
        $this->copy('lead_sources', 'lead_sources', fn ($r) => (int) $r->ID === 0 ? null : [
            'id' => $r->ID, 'name' => trim($r->source), 'active' => (bool) $r->active,
            'counts_as_reception' => in_array((int) $r->ID, LegacyMap::RECEPTION_SOURCE_IDS, true),
        ], note: 'source 0 "No Source Added" is stored as blank');

        $rows = [];
        foreach (array_keys($this->ids['businesses']) as $businessId) {
            $map = LegacyMap::CHANNEL_SOURCES[$businessId] ?? LegacyMap::CHANNEL_SOURCES['default'];
            foreach ($map as $channel => $sourceIds) {
                foreach ($sourceIds as $sourceId) {
                    if (isset($this->ids['lead_sources'][$sourceId])) {
                        $rows[] = ['business_id' => $businessId, 'channel' => $channel, 'lead_source_id' => $sourceId];
                    }
                }
            }
        }
        DB::table('channel_sources')->insert($rows);
    }

    private function lookupRow(object $r, ?string $name): ?array
    {
        if (! isset($this->ids['businesses'][(int) $r->businessID])) {
            return null;
        }

        return ['id' => $r->ID, 'business_id' => $r->businessID, 'name' => trim((string) $name), 'active' => (bool) $r->active];
    }

    private function importUsers(): void
    {
        $pivot = [];
        $seen = [];
        $this->copy('admin_users', 'users', function ($r) use (&$pivot, &$seen) {
            $username = trim($r->username);
            if (isset($seen[strtolower($username)])) {
                $username .= '-'.$r->userID; // usernames must be unique to log in
            }
            $seen[strtolower($username)] = true;
            foreach ([1 => 'truk', 2 => 'flesh', 3 => 'salford', 4 => 'mts'] as $businessId => $flag) {
                if (! empty($r->{$flag}) && isset($this->ids['businesses'][$businessId])) {
                    $pivot[] = ['business_id' => $businessId, 'user_id' => $r->userID];
                }
            }

            return [
                'id' => $r->userID,
                'username' => $username,
                'password' => $r->password, // already bcrypt; Laravel verifies it unchanged
                'level' => in_array((int) $r->userLevel, [1, 2, 5, 6, 7], true) ? (int) $r->userLevel : 2,
                'first_name' => trim($r->firstName),
                'last_name' => trim($r->lastName),
                'email' => $this->blank($r->emailAddress),
                'tel' => $this->blank($r->tel),
                'technician_id' => $this->ref('technicians', $r->techID),
                'is_reception' => (bool) $r->reception,
                'active' => (bool) $r->active,
                'created_at' => $this->dateTime($r->createDate),
                'updated_at' => $this->dateTime($r->createDate),
            ];
        }, key: 'userID');
        DB::table('business_user')->insert($pivot);
    }

    private function importLeads(): void
    {
        $this->copy('leads', 'leads', fn ($r) => isset($this->ids['businesses'][(int) $r->businessID]) ? [
            'id' => $r->ID,
            'business_id' => $r->businessID,
            'first_name' => trim($r->firstName),
            'last_name' => trim($r->lastName),
            'email' => $this->blank($r->emailAddress),
            'tel' => $this->blank($r->tel),
            'staff_member_id' => $this->ref('staff_members', $r->userID),
            'technician_id' => $this->ref('technicians', $r->techID),
            'location_id' => $this->ref('locations', $r->locationID),
            'lead_source_id' => $this->ref('lead_sources', $r->sourceID),
            'service_id' => $this->ref('services', $r->serviceID),
            'added_by' => $this->ref('users', $r->addedBy),
            'deposit' => $r->deposit,
            'full_cost' => $r->fullCost ?? 0,
            'booking_type' => (int) $r->bookingType === 2 ? 2 : 1,
            'date_added' => $this->date($r->dateAdded),
            'created_at' => $this->dateTime($r->createDate),
            'updated_at' => $this->dateTime($r->createDate),
            'deleted_at' => $r->active ? null : $this->dateTime($r->createDate),
        ] : null, note: 'rows with no valid business are skipped');
    }

    private function importSpend(): void
    {
        $this->copy('marketing_spend', 'marketing_spends', fn ($r) => isset($this->ids['businesses'][(int) $r->businessID]) && $this->date($r->spendDate) ? [
            'id' => $r->ID,
            'business_id' => $r->businessID,
            'spend_date' => $this->date($r->spendDate),
            'instagram' => $r->instagram, 'google' => $r->google, 'facebook' => $r->facebook,
            'tiktok' => $r->tiktok, 'staff' => $r->staff,
            'user_id' => $this->ref('users', $r->userID),
            'created_at' => $this->dateTime($r->createDate),
            'updated_at' => $this->dateTime($r->createDate),
        ] : null);
    }

    private function importClients(): void
    {
        $clinic = collect(LegacyMap::BUSINESSES)->search(fn ($b) => $b['is_clinic']) ?: 1;
        $this->copy('truk_customer', 'clients', fn ($r) => [
            'id' => $r->ID,
            'business_id' => $clinic,
            'first_name' => $this->blank($r->firstName),
            'last_name' => $this->blank($r->lastName),
            'tel' => $this->blank($r->tel),
            'email' => $this->blank($r->emailAddress),
            'staff_member_id' => $this->ref('staff_members', $r->addedBy),
            'added_by' => $this->ref('users', $r->addedBy),
            'location_id' => $this->ref('locations', $r->location),
            'technician_id' => $this->ref('technicians', $r->tech),
            'service_id' => $this->ref('services', $r->service),
            'created_date' => $this->date($r->createDate),
            'created_at' => $this->dateTime($r->createDate),
            'updated_at' => $this->dateTime($r->createDate),
        ]);
    }

    private function importAppointments(): void
    {
        $this->copy('truk_appointments', 'appointments', fn ($r) => [
            'id' => $r->ID,
            'client_id' => $this->ref('clients', $r->custID),
            'client_name' => $this->blank($r->custName),
            'email' => $this->blank($r->emailAddress),
            'tel' => $this->blank($r->tel),
            'location_id' => $this->ref('locations', $r->location),
            // The old addedBy column held either a staff name ID or a login ID, depending on the page.
            'staff_member_id' => $this->ref('staff_members', $r->addedBy),
            'added_by' => $this->ref('users', $r->addedBy),
            'appointment_date' => $this->date($r->appointmentDate),
            'appointment_time' => $this->blank($r->appointmentTime),
            'technician_id' => $this->ref('technicians', $r->tech),
            'service_id' => $this->ref('services', $r->service),
            'booking_type' => in_array((int) $r->bookingType, [1, 2], true) ? (int) $r->bookingType : null,
            'disposition_id' => $this->ref('dispositions', $r->disposition),
            'status_updated_at' => $this->dateTime($r->statusUpdate),
            'marketing_source_id' => $this->ref('marketing_sources', $r->marketingSource),
            'fee' => $r->fee,
            'created_date' => $this->date($r->createDate),
            'created_at' => $this->dateTime($r->createDate) ?? $this->dateTime($r->statusUpdate),
            'updated_at' => $this->dateTime($r->statusUpdate),
            'deleted_at' => $r->active ? null : ($this->dateTime($r->statusUpdate) ?? now()),
        ], note: 'active = 0 becomes soft-deleted');
    }

    private function importTimely(): void
    {
        $this->copy('lead_test', 'timely_bookings', fn ($r) => [
            'id' => $r->ID,
            'first_name' => $this->blank($r->firstName),
            'last_name' => $this->blank($r->lastName),
            'email' => $this->blank($r->emailAddress),
            'tel' => $this->blank($r->tel),
            'location' => $this->blank($r->location),
            'next_appointment' => $this->dateTime($r->nextAppointment),
            'is_new' => (bool) $r->new,
            'created_at' => $this->dateTime($r->createDate),
            'updated_at' => $this->dateTime($r->createDate),
        ]);
    }

    private function importRecords(): void
    {
        // [old table, new table, parent table, morph prefix, morph type, old value column => new column]
        $sets = [
            ['customer_notes', 'notes', 'leads', 'notable', 'lead', ['note' => 'body']],
            ['truk_notes', 'notes', 'clients', 'notable', 'client', ['note' => 'body']],
            ['customer_history', 'histories', 'leads', 'subject', 'lead', ['action' => 'action']],
            ['truk_history', 'histories', 'clients', 'subject', 'client', ['action' => 'action']],
            ['customer_docs', 'documents', 'leads', 'documentable', 'lead', ['docName' => 'path', 'displayName' => 'display_name']],
            ['truk_docs', 'documents', 'clients', 'documentable', 'client', ['docName' => 'path', 'displayName' => 'display_name']],
        ];
        foreach ($sets as [$old, $new, $parent, $morph, $type, $columns]) {
            // Lead and client records share a table now, so IDs are re-issued here.
            $this->copy($old, $new, function ($r) use ($parent, $morph, $type, $columns, $new) {
                if (! isset($this->ids[$parent][(int) $r->custID])) {
                    return null;
                }
                $row = [
                    $morph.'_type' => $type, $morph.'_id' => $r->custID,
                    'user_id' => $this->ref('users', $r->userID),
                    'created_at' => $this->dateTime($r->createDate), 'updated_at' => $this->dateTime($r->createDate),
                ];
                foreach ($columns as $from => $to) {
                    $row[$to] = (string) $r->{$from};
                }
                if ($new === 'documents') {
                    $row['path'] = 'documents/legacy/'.$row['path'];
                }

                return $row;
            }, track: false, note: 'rows whose customer no longer exists are skipped');
        }
    }

    /**
     * Stream one old table into a new one.
     *
     * @param  callable(object): ?array  $map  return null to skip a row
     */
    private function copy(string $old, string $new, callable $map, string $key = 'ID', bool $track = true, string $note = ''): void
    {
        $read = $written = 0;
        $batch = [];
        $flush = function () use (&$batch, $new) {
            foreach (array_chunk($batch, 200) as $chunk) {
                DB::table($new)->insert($chunk);
            }
            $batch = [];
        };

        DB::connection('legacy')->table($old)->orderBy($key)->chunk(2000, function ($rows) use (&$read, &$written, &$batch, $map, $new, $track, $flush) {
            foreach ($rows as $r) {
                $read++;
                $row = $map($r);
                if ($row === null) {
                    continue;
                }
                $written++;
                if ($track) {
                    $this->ids[$new][(int) $row['id']] = true;
                }
                $batch[] = $row;
            }
            $flush();
        });

        $this->ids[$new] ??= [];
        $skipped = $read - $written;
        $this->report[] = [$old, $read, $new, $written, $skipped ? "$skipped skipped".($note ? " ($note)" : '') : ''];
    }

    /** A foreign key value, or NULL when it is 0/blank or points at a row that does not exist. */
    private function ref(string $table, mixed $id): ?int
    {
        $id = (int) $id;

        return $id > 0 && isset($this->ids[$table][$id]) ? $id : null;
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?string
    {
        $value = substr((string) $value, 0, 10);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && $value >= '1990-01-01' ? $value : null;
    }

    private function dateTime(mixed $value): ?string
    {
        $value = (string) $value;
        if ($this->date($value) === null) {
            return null;
        }

        return strlen($value) > 10 ? substr($value, 0, 19) : $value.' 00:00:00';
    }
}
