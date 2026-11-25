<?php

return [
    // Shared secret for the Timely booking webhook. When set, callers must send it
    // as ?token=... or an X-Webhook-Token header. Leave empty to accept any caller
    // (how the old endpoint behaved).
    'timely_token' => env('TIMELY_WEBHOOK_TOKEN'),

    // Daily time the "mark past upcoming appointments as not dispositioned" job runs.
    'undispositioned_at' => env('ALAISE_UNDISPOSITIONED_AT', '22:00'),
];
