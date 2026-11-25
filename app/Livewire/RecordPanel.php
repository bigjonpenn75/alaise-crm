<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Notes, documents and history for a booking or a clinic client. */
class RecordPanel extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $type;

    #[Locked]
    public int $recordId;

    public string $tab = 'notes';
    public string $note = '';
    public string $documentTitle = '';
    public $upload = null;

    public function mount(Model $record): void
    {
        $this->type = $record->getMorphClass();
        $this->recordId = $record->getKey();
    }

    private function record(): Lead|Client
    {
        $record = ($this->type === 'client' ? Client::class : Lead::class)::with('business')->findOrFail($this->recordId);
        abort_unless(auth()->user()->can('manage-business', $record->business), 403);

        return $record;
    }

    public function addNote(): void
    {
        $this->validate(['note' => ['required', 'string', 'max:5000']]);
        $record = $this->record();
        $record->notes()->create(['body' => trim($this->note), 'user_id' => auth()->id()]);
        $record->logHistory('New note left on account.');
        $this->reset('note');
    }

    public function addDocument(): void
    {
        $this->validate([
            'documentTitle' => ['required', 'string', 'max:150'],
            'upload' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,heic,doc,docx,xls,xlsx,csv,txt'],
        ], attributes: ['documentTitle' => 'title', 'upload' => 'file']);
        $record = $this->record();
        $path = $this->upload->store("documents/{$this->type}/{$this->recordId}", 'local');
        $record->documents()->create(['path' => $path, 'display_name' => trim($this->documentTitle), 'user_id' => auth()->id()]);
        $record->logHistory("The document - '{$this->documentTitle}' was uploaded");
        $this->reset('documentTitle', 'upload');
    }

    public function deleteDocument(int $id): void
    {
        $record = $this->record();
        $document = $record->documents()->findOrFail($id);
        Storage::disk('local')->delete($document->path);
        $document->delete();
        $record->logHistory("The document - '{$document->display_name}' was deleted");
    }

    #[On('record-changed')]
    public function refresh(): void
    {
        // Re-render so history written elsewhere on the page shows up.
    }

    public function render()
    {
        $record = $this->record();

        return view('livewire.record-panel', [
            'notes' => $record->notes()->with('user')->get(),
            'documents' => $record->documents()->with('user')->get(),
            'history' => $record->histories()->with('user')->limit(200)->get(),
        ]);
    }
}
