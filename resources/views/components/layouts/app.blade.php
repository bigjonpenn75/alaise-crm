@php
    $user = auth()->user();
    $navBusinesses = $user->accessibleBusinesses();
    $clinic = $navBusinesses->firstWhere('is_clinic', true);
    $item = fn (string $pattern) => request()->routeIs($pattern) ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white';
    $here = fn ($business, string $route) => request()->routeIs($route) && request()->route('business')?->is($business)
        ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white';
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full" x-data="{ nav: false }">
    <div class="flex min-h-full">
        <aside class="fixed inset-y-0 left-0 z-30 w-60 -translate-x-full overflow-y-auto bg-brand-900 pb-8 transition lg:translate-x-0"
            :class="nav && 'translate-x-0'">
            <a href="{{ route('dashboard') }}" class="block px-5 py-4 text-base font-semibold text-white">{{ config('app.name') }}</a>
            <nav class="space-y-5 px-3 text-sm">
                <div class="space-y-0.5">
                    <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-1.5 {{ $item('dashboard') }}">Dashboard</a>
                    <a href="{{ route('leads.create') }}" class="block rounded-md px-3 py-1.5 {{ $item('leads.create') }}">Add booking</a>
                    @if ($user->technician_id)
                        <a href="{{ route('technicians.diary', $user->technician_id) }}" class="block rounded-md px-3 py-1.5 {{ $item('technicians.diary') }}">My appointments</a>
                    @endif
                </div>

                @can('manage')
                    @foreach ($navBusinesses as $b)
                        <div>
                            <p class="px-3 pb-1 text-xs font-semibold tracking-wider text-slate-400 uppercase">{{ $b->short_name }}</p>
                            <div class="space-y-0.5">
                                @if ($b->is_clinic)
                                    <a href="{{ route('bookings.index', $b) }}" class="block rounded-md px-3 py-1.5 {{ $here($b, 'bookings.*') }}">New bookings</a>
                                @else
                                    <a href="{{ route('tracker', $b) }}" class="block rounded-md px-3 py-1.5 {{ $here($b, 'tracker') }}">Daily tracker</a>
                                @endif
                                <a href="{{ route('customers.index', $b) }}" class="block rounded-md px-3 py-1.5 {{ $here($b, 'customers.*') }}">Customers</a>
                                <a href="{{ route('spend.index', $b) }}" class="block rounded-md px-3 py-1.5 {{ $here($b, 'spend.index') }}">Marketing spend</a>
                                <a href="{{ route('performance', $b) }}" class="block rounded-md px-3 py-1.5 {{ $here($b, 'performance') }}">Performance</a>
                                @if ($b->is_clinic && $user->isSuperuser())
                                    <a href="{{ route('month-on-month', $b) }}" class="block rounded-md px-3 py-1.5 {{ $here($b, 'month-on-month') }}">Month on month</a>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if ($clinic)
                        <div>
                            <p class="px-3 pb-1 text-xs font-semibold tracking-wider text-slate-400 uppercase">Appointments</p>
                            <div class="space-y-0.5">
                                <a href="{{ route('technicians.performance') }}" class="block rounded-md px-3 py-1.5 {{ $item('technicians.performance') }}">Technician performance</a>
                                <a href="{{ route('calls') }}" class="block rounded-md px-3 py-1.5 {{ $item('calls') }}">Calls to make</a>
                                <a href="{{ route('appointments.unassigned') }}" class="block rounded-md px-3 py-1.5 {{ $item('appointments.unassigned') }}">Unassigned</a>
                            </div>
                        </div>
                    @endif

                    <div>
                        <p class="px-3 pb-1 text-xs font-semibold tracking-wider text-slate-400 uppercase">Tools</p>
                        <div class="space-y-0.5">
                            <a href="{{ route('users.index') }}" class="block rounded-md px-3 py-1.5 {{ $item('users.*') }}">Users</a>
                            <a href="{{ route('spend.create') }}" class="block rounded-md px-3 py-1.5 {{ $item('spend.create') }}">Add marketing spend</a>
                            @foreach ($navBusinesses as $b)
                                <a href="{{ route('settings.business', $b) }}" class="block rounded-md px-3 py-1.5 {{ $here($b, 'settings.business') }}">{{ $b->short_name }} settings</a>
                            @endforeach
                            <a href="{{ route('settings.system') }}" class="block rounded-md px-3 py-1.5 {{ $item('settings.system') }}">System settings</a>
                        </div>
                    </div>
                @endcan
            </nav>
        </aside>
        <div class="fixed inset-0 z-20 bg-black/40 lg:hidden" x-show="nav" x-cloak @click="nav = false"></div>

        <div class="flex min-w-0 flex-1 flex-col lg:pl-60">
            <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-2.5">
                <button type="button" class="btn btn-secondary btn-sm lg:hidden" @click="nav = true" aria-label="Open menu">Menu</button>
                <form action="{{ route('search') }}" method="GET" class="max-w-md flex-1">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, phone or email…" class="input py-1.5" aria-label="Search customers">
                </form>
                <div class="ml-auto flex items-center gap-3 text-sm">
                    <span class="hidden text-slate-600 sm:inline">{{ $user->name }} <span class="text-slate-400">· {{ $user->level->label() }}</span></span>
                    <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn btn-secondary btn-sm">Sign out</button></form>
                </div>
            </header>

            <main class="min-w-0 flex-1 p-4 sm:p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800">{{ session('error') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
