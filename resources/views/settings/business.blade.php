<x-layouts.app :title="$business->short_name.' settings'">
    <x-page-header :title="$business->short_name.' settings'" :subtitle="$business->name" />
    <div class="grid gap-4 xl:grid-cols-2">
        <livewire:lookup-manager type="staff" :business="$business" />
        <livewire:lookup-manager type="locations" :business="$business" />
        <livewire:lookup-manager type="technicians" :business="$business" :title="$business->technicianLabel().'s'" />
        @if ($business->is_clinic)
            <livewire:lookup-manager type="services" :business="$business" />
            <livewire:lookup-manager type="dispositions" :business="$business" />
            <livewire:lookup-manager type="marketing-sources" :business="$business" />
        @endif
        <div class="xl:col-span-2"><livewire:channel-source-manager :business="$business" /></div>
    </div>
</x-layouts.app>
