<div>
    <x-card :title="$title" :padded="false">
        <form wire:submit="add" class="flex items-start gap-2 border-b border-slate-200 p-3">
            <div class="flex-1">
                <input type="text" wire:model="newName" placeholder="Add new…" class="input py-1.5" aria-label="New {{ strtolower($title) }} name">
                @error('newName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-primary btn-sm mt-0.5">Add</button>
        </form>
        <div class="max-h-96 overflow-auto">
            <table class="data-table">
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="row-{{ $row->id }}" @class(['text-slate-400' => ! $row->active])>
                            <td>
                                @if ($editing === $row->id)
                                    <form wire:submit="rename" class="flex items-center gap-2">
                                        <input type="text" wire:model="editName" class="input py-1" aria-label="Name">
                                        <button class="btn btn-primary btn-sm">Save</button>
                                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('editing', null)">Cancel</button>
                                    </form>
                                    @error('editName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                @else
                                    {{ $row->name }}
                                @endif
                            </td>
                            @if ($categories)
                                <td class="w-44">
                                    <select class="input py-1 text-xs" aria-label="Report bucket" wire:change="setCategory({{ $row->id }}, $event.target.value)">
                                        @foreach ($categories as $category)<option value="{{ $category->value }}" @selected($row->category === $category)>{{ $category->label() }}</option>@endforeach
                                    </select>
                                </td>
                            @endif
                            @if ($flag)
                                <td class="w-40 whitespace-nowrap">
                                    <label class="flex items-center gap-1.5 text-xs">
                                        <input type="checkbox" @checked($row->{$flag[0]}) wire:click="toggle({{ $row->id }}, '{{ $flag[0] }}')" class="rounded border-slate-300"> {{ $flag[1] }}
                                    </label>
                                </td>
                            @endif
                            <td class="w-40 text-right whitespace-nowrap">
                                <button type="button" class="btn btn-secondary btn-sm" wire:click="edit({{ $row->id }})">Rename</button>
                                <button type="button" @class(['btn btn-sm', 'btn-secondary' => $row->active, 'btn-primary' => ! $row->active]) wire:click="toggle({{ $row->id }})">{{ $row->active ? 'Deactivate' : 'Activate' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-slate-400">Nothing set up yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
