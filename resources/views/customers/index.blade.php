<x-layouts.app :title="$business->short_name.' customers'">
    <x-page-header :title="$business->short_name.' customers'">
        <a href="{{ route('customers.export', $business) }}" class="btn btn-secondary btn-sm">Export CSV</a>
        @unless ($business->is_clinic)<a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm">Add booking</a>@endunless
    </x-page-header>
    <livewire:customer-table :business="$business" />
</x-layouts.app>
