<x-layouts.app title="Users">
    <x-page-header title="Users">
        @foreach (['active' => 'Active', 'inactive' => 'Deactivated', 'all' => 'All'] as $key => $label)
            <a href="{{ route('users.index', ['status' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $status === $key, 'btn-secondary' => $status !== $key])>{{ $label }}</a>
        @endforeach
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">Add user</a>
    </x-page-header>
    <x-card :padded="false">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Username</th><th>Level</th><th>Businesses</th><th>Technician</th><th>Email</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        @php($locked = $u->isSuperuser() && ! auth()->user()->isSuperuser())
                        <tr>
                            <td class="font-medium">{{ $u->name }}</td>
                            <td>{{ $u->username }}</td>
                            <td>{{ $u->level->label() }}@if ($u->is_reception) <span class="text-xs text-slate-500">· reception</span>@endif</td>
                            <td>{{ $u->isSuperuser() ? 'All' : ($u->businesses->pluck('short_name')->join(', ') ?: '—') }}</td>
                            <td>{{ $u->technician?->name ?? '—' }}</td>
                            <td>{{ $u->email ?? '—' }}</td>
                            <td>{{ $u->active ? 'Active' : 'Deactivated' }}</td>
                            <td class="text-right whitespace-nowrap">
                                @unless ($locked)
                                    <a href="{{ route('users.edit', $u) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    @unless ($u->is(auth()->user()))
                                        <form method="POST" action="{{ route('users.toggle', $u) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button @class(['btn btn-sm', 'btn-danger' => $u->active, 'btn-primary' => ! $u->active])>{{ $u->active ? 'Deactivate' : 'Activate' }}</button>
                                        </form>
                                    @endunless
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.app>
