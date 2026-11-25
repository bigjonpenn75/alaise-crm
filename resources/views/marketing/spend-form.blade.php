<x-layouts.app :title="$spend->exists ? 'Edit marketing spend' : 'Add marketing spend'">
    <x-page-header :title="$spend->exists ? 'Edit marketing spend' : 'Add marketing spend'" />
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ $spend->exists ? route('spend.update', $spend) : route('spend.store') }}" class="space-y-4">
            @csrf
            @if ($spend->exists) @method('PUT') @endif
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Business" name="business_id" :options="$businesses->pluck('name', 'id')" :value="$spend->business_id ?? request('business')" required />
                <x-field label="Spend date" name="spend_date" type="date" :value="$spend->spend_date?->toDateString()" required />
                @foreach (['instagram' => 'Instagram (£)', 'google' => 'Google (£)', 'facebook' => 'Facebook (£)', 'tiktok' => 'TikTok (£)', 'staff' => 'Staff (£)'] as $name => $label)
                    <x-field :label="$label" :name="$name" type="number" step="0.01" min="0" :value="$spend->exists ? $spend->{$name} : null" placeholder="0.00" />
                @endforeach
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary">{{ $spend->exists ? 'Save changes' : 'Add spend' }}</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </x-card>
</x-layouts.app>
