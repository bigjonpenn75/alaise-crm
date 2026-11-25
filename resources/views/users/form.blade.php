<x-layouts.app :title="$user->exists ? 'Edit user' : 'Add user'">
    <x-page-header :title="$user->exists ? 'Edit user' : 'Add user'" :subtitle="$user->exists ? $user->name : null" />
    <div class="grid max-w-5xl gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="space-y-4">
                @csrf
                @if ($user->exists) @method('PUT') @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="First name" name="first_name" :value="$user->first_name" required />
                    <x-field label="Last name" name="last_name" :value="$user->last_name" required />
                    <x-field label="Username" name="username" :value="$user->username" autocomplete="off" required />
                    <x-select label="User level" name="level" :options="$levels" :value="$user->level?->value" required />
                    <x-field label="Email address" name="email" type="email" :value="$user->email" />
                    <x-field label="Telephone" name="tel" :value="$user->tel" />
                    <x-select label="Linked technician" name="technician_id" :options="$technicians" :value="$user->technician_id" placeholder="Not a technician"
                        hint="Gives this user a My appointments page." />
                    @unless ($user->exists)
                        <div></div>
                        <x-field label="Password" name="password" type="password" autocomplete="new-password" hint="At least 8 characters." required />
                        <x-field label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password" required />
                    @endunless
                </div>
                <fieldset>
                    <legend class="mb-1 text-sm font-medium text-slate-700">Businesses this user can see</legend>
                    <div class="flex flex-wrap gap-x-5 gap-y-1.5">
                        @php($selected = old('businesses', $user->exists ? $user->businesses->pluck('id')->all() : []))
                        @foreach ($businesses as $b)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="businesses[]" value="{{ $b->id }}" @checked(in_array($b->id, $selected)) class="rounded border-slate-300"> {{ $b->name }}</label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Superusers see every business whatever is ticked.</p>
                </fieldset>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_reception" value="0">
                    <input type="checkbox" name="is_reception" value="1" @checked(old('is_reception', $user->is_reception)) class="rounded border-slate-300"> Reception user
                </label>
                <div class="flex gap-2">
                    <button class="btn btn-primary">{{ $user->exists ? 'Save changes' : 'Add user' }}</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </x-card>

        @if ($user->exists)
            <x-card title="Reset password">
                <form method="POST" action="{{ route('users.password', $user) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <x-field label="New password" name="password" type="password" autocomplete="new-password" hint="At least 8 characters." required />
                    <x-field label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" required />
                    <button class="btn btn-primary">Change password</button>
                </form>
            </x-card>
        @endif
    </div>
</x-layouts.app>
