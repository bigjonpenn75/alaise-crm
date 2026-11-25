<div>
    <x-card title="Marketing channels" :padded="false">
        <p class="border-b border-slate-200 px-4 py-2.5 text-xs text-slate-500">Tick the lead sources that count towards each paid channel in this business's marketing spend and performance reports.</p>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Lead source</th>@foreach ($channels as $channel)<th class="text-center">{{ $channel->label() }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($sources as $source)
                        <tr wire:key="source-{{ $source->id }}" @class(['text-slate-400' => ! $source->active])>
                            <td>{{ $source->name }}</td>
                            @foreach ($channels as $channel)
                                <td class="text-center">
                                    <input type="checkbox" class="rounded border-slate-300" aria-label="{{ $source->name }} counts as {{ $channel->label() }}"
                                        @checked(in_array($source->id, $map[$channel->value])) wire:click="toggle('{{ $channel->value }}', {{ $source->id }})">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>
