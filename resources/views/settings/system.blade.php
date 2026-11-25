<x-layouts.app title="System settings">
    <x-page-header title="System settings" />
    <div class="grid gap-4 xl:grid-cols-2">
        <livewire:lookup-manager type="businesses" />
        <livewire:lookup-manager type="lead-sources" />
    </div>
    <x-card title="User levels" class="mt-4 max-w-3xl">
        <ul class="space-y-1.5 text-sm text-slate-700">
            <li><strong>Superuser</strong>: everything, in every business, including month on month and other superusers' accounts.</li>
            <li><strong>Management</strong> and <strong>Marketing</strong>: reports, customers, users and settings for the businesses ticked on their account.</li>
            <li><strong>Reception</strong>: dashboard and adding bookings.</li>
            <li><strong>Standard</strong>: adding bookings, plus My appointments when linked to a technician.</li>
        </ul>
        <p class="mt-3 text-xs text-slate-500">Levels are fixed because each one maps to specific permissions in the application.</p>
    </x-card>
</x-layouts.app>
