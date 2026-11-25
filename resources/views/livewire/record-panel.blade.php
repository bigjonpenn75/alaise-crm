<div>
    <x-card :padded="false">
        <div class="flex gap-1 border-b border-slate-200 px-3 pt-2 text-sm">
            @foreach (['notes' => 'Notes ('.$notes->count().')', 'documents' => 'Documents ('.$documents->count().')', 'history' => 'History'] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    @class(['-mb-px cursor-pointer border-b-2 px-3 py-2 font-medium', 'border-brand-600 text-brand-700' => $tab === $key, 'border-transparent text-slate-500 hover:text-slate-700' => $tab !== $key])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="p-4">
            @if ($tab === 'notes')
                <form wire:submit="addNote" class="mb-4">
                    <label for="note-{{ $this->getId() }}" class="mb-1 block text-sm font-medium text-slate-700">Add a note</label>
                    <textarea id="note-{{ $this->getId() }}" wire:model="note" rows="3" class="input"></textarea>
                    @error('note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <button class="btn btn-primary btn-sm mt-2">Save note</button>
                </form>
                <ul class="divide-y divide-slate-100">
                    @forelse ($notes as $n)
                        <li class="py-2.5">
                            <p class="text-sm whitespace-pre-line">{{ $n->body }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $n->user?->name ?? 'Unknown user' }} · {{ $n->created_at?->format('j M Y H:i') }}</p>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-400">No notes yet.</li>
                    @endforelse
                </ul>
            @elseif ($tab === 'documents')
                <form wire:submit="addDocument" class="mb-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="doc-title-{{ $this->getId() }}" class="mb-1 block text-sm font-medium text-slate-700">Document title</label>
                        <input id="doc-title-{{ $this->getId() }}" type="text" wire:model="documentTitle" class="input">
                        @error('documentTitle')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="doc-file-{{ $this->getId() }}" class="mb-1 block text-sm font-medium text-slate-700">File (max 20 MB)</label>
                        <input id="doc-file-{{ $this->getId() }}" type="file" wire:model="upload" class="input">
                        @error('upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="upload,addDocument">Upload</button>
                        <span wire:loading wire:target="upload" class="ml-2 text-xs text-slate-500">Uploading…</span>
                    </div>
                </form>
                <ul class="divide-y divide-slate-100">
                    @forelse ($documents as $d)
                        <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <div>
                                <a href="{{ route('documents.show', $d) }}" class="link font-medium">{{ $d->display_name }}</a>
                                <p class="text-xs text-slate-500">{{ $d->user?->name ?? 'Unknown user' }} · {{ $d->created_at?->format('j M Y H:i') }}</p>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" wire:click="deleteDocument({{ $d->id }})" wire:confirm="Delete this document?">Delete</button>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-400">No documents uploaded.</li>
                    @endforelse
                </ul>
            @else
                <ul class="divide-y divide-slate-100">
                    @forelse ($history as $h)
                        <li class="flex justify-between gap-3 py-2 text-sm">
                            <span>{{ $h->action }}</span>
                            <span class="shrink-0 text-xs text-slate-500">{{ $h->user?->name ?? '—' }} · {{ $h->created_at?->format('j M Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-400">Nothing recorded yet.</li>
                    @endforelse
                </ul>
            @endif
        </div>
    </x-card>
</div>
