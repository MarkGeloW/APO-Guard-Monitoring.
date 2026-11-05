<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SecurityPersonnel;
use App\Models\User;
use App\Http\Support\InertiaPageData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $showArchived = $request->boolean('archived');

        $usersQuery = $showArchived ? User::onlyTrashed() : User::query();

        $users = $usersQuery
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
            'archived' => User::onlyTrashed()->count(),
        ];

        return Inertia::render('Users/Index', [
            'users' => $users->map(fn (User $user) => InertiaPageData::userRow($user))->values(),
            'stats' => $stats,
            'search' => $search ?? '',
            'archived' => $showArchived,
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['roles' => UserRole::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');
        $isGuard = $role === UserRole::Guard->value;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', ...self::uniqueLoginEmail()],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'badge_number' => [
                Rule::requiredIf($isGuard),
                'nullable',
                'string',
                'max:50',
                'unique:security_personnel,badge_number',
            ],
            'date_hired' => ['nullable', 'date'],
        ], [
            'badge_number.required' => 'Personnel ID is required when creating a Security Guard account.',
            'badge_number.unique' => 'This Personnel ID is already assigned to another guard.',
        ]);

        $user = DB::transaction(function () use ($request, $validated, $isGuard) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'password' => $validated['password'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($isGuard) {
                $nameParts = preg_split('/\s+/', trim($validated['name']), 2) ?: ['Guard', ''];

                SecurityPersonnel::create([
                    'user_id' => $user->id,
                    'badge_number' => $validated['badge_number'],
                    'first_name' => $nameParts[0] ?: 'Guard',
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'date_hired' => $validated['date_hired'] ?? now()->toDateString(),
                    'status' => 'active',
                ]);
            }

            return $user;
        });

        $message = $isGuard
            ? 'Guard account created with Personnel ID. Ask the guard to enroll their face in the mobile app before Face In / Face Out.'
            : 'User created successfully.';

        return redirect()->route('users.index')->with('success', $message);
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', ...self::uniqueLoginEmail($user->id)],
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

        if (! $user->is_active) {
            $user->revokeAccess();
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $user->revokeAccess();
        }

        $message = $user->is_active
            ? 'User activated successfully.'
            : 'User deactivated. All open sessions and app logins were signed out.';

        return back()->with('success', $message);
    }

    public function restore(int $id): RedirectResponse
    {
        $account = User::onlyTrashed()->findOrFail($id);
        $account->restoreAccount();

        return redirect()
            ->route('users.index')
            ->with('success', 'Account restored. The user can sign in again.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot archive your own account.');
        }

        $user->archive();

        return redirect()
            ->route('users.index', ['archived' => 1])
            ->with('success', 'Account archived. Login access was removed, and the record can be restored later.');
    }

    /**
     * @return array<int, mixed>
     */
    private static function uniqueLoginEmail(?int $ignoreId = null): array
    {
        return [
            'bail',
            function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId) {
                $archived = User::onlyTrashed()
                    ->where('email', $value)
                    ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                    ->exists();

                if ($archived) {
                    $fail('This email belongs to an archived account. Restore it from User Management instead of creating a new login.');
                }
            },
            Rule::unique('users', 'email')->ignore($ignoreId),
        ];
    }
}
