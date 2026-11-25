<x-layouts.guest title="Sign in">
    <div class="w-full max-w-sm rounded-xl bg-white p-8 shadow-xl">
        <h1 class="text-xl font-semibold text-slate-900">{{ config('app.name') }}</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in to continue.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <x-field label="Username" name="username" autofocus autocomplete="username" required />
            <x-field label="Password" name="password" type="password" autocomplete="current-password" required />
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Remember me
            </label>
            <button class="btn btn-primary w-full">Sign in</button>
        </form>
    </div>
</x-layouts.guest>
