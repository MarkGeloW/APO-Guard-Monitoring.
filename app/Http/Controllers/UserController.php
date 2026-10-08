<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Http\Support\InertiaPageData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->get();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', UserRole::Admin)->count(),
            'supervisors' => User::where('role', UserRole::Supervisor)->count(),
            'guards' => User::where('role', UserRole::Guard)->count(),
        ];

        return Inertia::render('Users/Index', [
            'users' => $users->map(fn (User $user) => InertiaPageData::userRow($user))->values(),
            'stats' => $stats,
            'search' => $search ?? '',
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['roles' => UserRole::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ]);

        User::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ]);

        $data = collect($validated)->except('password')->toArray();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $message = $user->is_active
            ? 'User activated successfully.'
            : 'User deactivated successfully.';

        return back()->with('success', $message);
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
