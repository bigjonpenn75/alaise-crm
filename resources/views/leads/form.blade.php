<x-layouts.app :title="$lead ? 'Edit booking' : 'Add booking'">
    <x-page-header :title="$lead ? 'Edit booking' : 'Add booking'" :subtitle="$lead?->name" />
    <livewire:lead-form :lead="$lead" />
</x-layouts.app>
