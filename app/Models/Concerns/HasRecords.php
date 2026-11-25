<?php

namespace App\Models\Concerns;

use App\Models\Document;
use App\Models\History;
use App\Models\Note;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Notes, uploaded documents and an activity trail, shared by leads and clinic clients. */
trait HasRecords
{
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest('id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest('id');
    }

    public function histories(): MorphMany
    {
        return $this->morphMany(History::class, 'subject')->latest('id');
    }

    public function logHistory(string $action, ?int $userId = null): History
    {
        return $this->histories()->create(['action' => $action, 'user_id' => $userId ?? auth()->id()]);
    }
}
