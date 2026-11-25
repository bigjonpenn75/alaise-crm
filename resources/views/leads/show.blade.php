<x-layouts.app :title="$lead->name">
    <x-page-header :title="$lead->name" :subtitle="$lead->business->name.' booking'">
        <a href="{{ route('customers.index', $lead->business) }}" class="btn btn-secondary btn-sm">Back to customers</a>
        <a href="{{ route('leads.edit', $lead) }}" class="btn btn-primary btn-sm">Edit</a>
        <x-confirm-delete :action="route('leads.destroy', $lead)" message="Delete this booking?" />
    </x-page-header>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-card title="Overview" class="xl:col-span-1">
            <dl class="grid grid-cols-3 gap-x-3 gap-y-2 text-sm">
                @foreach ([
                    'Email' => $lead->email, 'Telephone' => $lead->tel,
                    'Date' => $lead->date_added?->format('j M Y'),
                    'Type' => $lead->booking_type->label(),
                    'Added by' => $lead->staffMember?->name,
                    $lead->business->technicianLabel() => $lead->technician?->name,
                    'Location' => $lead->location?->name,
                    'Source' => $lead->source?->name ?? 'No source added',
                    'Service' => $lead->service?->name,
                    'Deposit' => money($lead->deposit), 'Total cost' => money($lead->full_cost),
                    'Logged by' => $lead->addedBy?->name,
                ] as $label => $value)
                    <dt class="text-slate-500">{{ $label }}</dt><dd class="col-span-2 break-words">{{ $value ?: '—' }}</dd>
                @endforeach
            </dl>
        </x-card>
        <div class="xl:col-span-2"><livewire:record-panel :record="$lead" /></div>
    </div>
</x-layouts.app>
