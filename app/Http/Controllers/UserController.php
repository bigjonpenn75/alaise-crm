<?php

namespace App\Http\Controllers;

use App\Enums\UserLevel;
use App\Models\Business;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'active');

        return view('users.index', [
            'status' => $status,
            'users' => User::with(['businesses', 'technician'])
                ->when($status === 'active', fn ($q) => $q->where('active', true))
                ->when($status === 'inactive', fn ($q) => $q->where('active', false))
                ->orderBy('first_name')->orderBy('last_name')->get(),
        ]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['active' => true]), ...$this->options()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password'] = $request->validate(['password' => ['required', 'confirmed', Password::min(8)]])['password'];
        $user = User::create(collect($data)->except('businesses')->all());
        $user->businesses()->sync($data['businesses'] ?? []);

        return redirect()->route('users.index')->with('status', "User {$user->username} created.");
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, ...$this->options()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $user->update(collect($data)->except('businesses')->all());
        $user->businesses()->sync($data['businesses'] ?? []);

        return redirect()->route('users.index')->with('status', "User {$user->username} updated.");
    }

    public function password(Request $request, User $user)
    {
        $this->guardLevel($request, $user);
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(8)]]);
        $user->update(['password' => $data['password']]);

        return back()->with('status', "Password changed for {$user->username}.");
    }

    public function toggle(Request $request, User $user)
    {
        $this->guardLevel($request, $user);
        abort_if($user->is($request->user()), 422, 'You cannot deactivate your own account.');
        $user->update(['active' => ! $user->active]);

        return back()->with('status', "{$user->username} ".($user->active ? 'activated' : 'deactivated').'.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        if ($user) {
            $this->guardLevel($request, $user);
        }
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:150'],
            'tel' => ['nullable', 'string', 'max:20'],
            'level' => ['required', Rule::enum(UserLevel::class)],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'is_reception' => ['boolean'],
            'businesses' => ['array'],
            'businesses.*' => ['exists:businesses,id'],
        ]);
        // Only a superuser may hand out (or keep handing out) superuser access.
        abort_if((int) $data['level'] === UserLevel::Superuser->value && ! $request->user()->isSuperuser(), 403);
        $data['is_reception'] = $request->boolean('is_reception');

        return $data;
    }

    /** Managers cannot edit, deactivate or reset a superuser. */
    private function guardLevel(Request $request, User $user): void
    {
        abort_if($user->isSuperuser() && ! $request->user()->isSuperuser(), 403);
    }

    private function options(): array
    {
        return [
            'businesses' => Business::active()->orderBy('id')->get(),
            'technicians' => Technician::active()->with('business')->orderBy('name')->get()
                ->mapWithKeys(fn ($t) => [$t->id => "{$t->name} ({$t->business->short_name})"]),
            'levels' => collect(UserLevel::cases())
                ->reject(fn ($l) => $l === UserLevel::Superuser && ! auth()->user()->isSuperuser())
                ->mapWithKeys(fn ($l) => [$l->value => $l->label()]),
        ];
    }
}
