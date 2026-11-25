<x-layouts.app :title="$business->short_name.' marketing spend'">
    <x-page-header :title="$business->short_name.' marketing spend'" :subtitle="$range->label()">
        @include('partials.range-filter')
        <a href="{{ route('spend.create', ['business' => $business->id]) }}" class="btn btn-primary btn-sm">Add spend</a>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <x-stat label="Total spend" :value="money($total)" />
        @foreach ($channels as $channel)
            <x-stat :label="$channel['label']" :value="money($channel['spend'])" />
        @endforeach
        <x-stat label="Staff" :value="money($staff)" />
    </div>

    <x-card title="Return by channel" class="mt-4" :padded="false">
        <table class="data-table">
            <thead><tr><th>Channel</th><th class="text-right">Spend</th><th class="text-right">Leads</th><th class="text-right">Cost per lead</th><th class="text-right">Lead value</th><th class="text-right">Ave lead value</th></tr></thead>
            <tbody>
                @foreach ($channels as $channel)
                    <tr>
                        <td>{{ $channel['label'] }}</td>
                        <td class="text-right tabular-nums">{{ money($channel['spend']) }}</td>
                        <td class="text-right tabular-nums">{{ number_format($channel['leads']) }}</td>
                        <td class="text-right tabular-nums">{{ money($channel['cpa']) }}</td>
                        <td class="text-right tabular-nums">{{ money($channel['value']) }}</td>
                        <td class="text-right tabular-nums">{{ money($channel['average']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    <x-card title="Spend entries" class="mt-4" :padded="false">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Spend date</th><th class="text-right">Instagram</th><th class="text-right">Google</th><th class="text-right">Facebook</th><th class="text-right">TikTok</th><th class="text-right">Staff</th><th>Added by</th><th>Date added</th><th></th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="whitespace-nowrap">{{ $entry->spend_date->format('D j M Y') }}</td>
                            @foreach (['instagram', 'google', 'facebook', 'tiktok', 'staff'] as $c)<td class="text-right tabular-nums">{{ money($entry->{$c}) }}</td>@endforeach
                            <td>{{ $entry->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-slate-500">{{ $entry->created_at?->format('j M Y H:i') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('spend.edit', $entry) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <x-confirm-delete :action="route('spend.destroy', $entry)" message="Delete this spend entry?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-slate-400">No spend recorded in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())<div class="border-t border-slate-200 p-3">{{ $entries->links() }}</div>@endif
    </x-card>
</x-layouts.app>
