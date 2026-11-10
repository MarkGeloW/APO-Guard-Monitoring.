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
use Illuminate\Support\Facades\Storage;
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
        return view('users.create', [
            'roles' => self::creatableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');
        $isGuard = $role === UserRole::Guard->value;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', ...self::uniqueLoginEmail()],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_map(
                fn (UserRole $role) => $role->value,
                self::creatableRoles(),
            ))],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'license_number' => [
                Rule::requiredIf($isGuard),
                'nullable',
                'string',
                'max:50',
                'unique:security_personnel,license_number',
            ],
            'date_hired' => ['nullable', 'date'],
            'id_pictures' => [Rule::requiredIf($isGuard), 'nullable', 'array', 'min:1'],
            'id_pictures.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'role.in' => 'Administrator accounts cannot be created here. Choose Supervisor or Security Guard.',
            'license_number.required' => 'Guard License Number is required when creating a Security Guard account.',
            'license_number.unique' => 'This Guard License Number is already assigned to another guard.',
            'id_pictures.required' => 'Please upload at least one valid ID picture.',
            'id_pictures.min' => 'Please upload at least one valid ID picture.',
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
                $idPaths = SecurityPersonnel::storeIdPictures($request->file('id_pictures', []));

                $personnel = $user->ensurePersonnelProfile(
                    $validated['license_number'],
                    $idPaths,
                    $validated['date_hired'] ?? null,
                );

                if ($idPaths !== []) {
                    $moved = [];
                    foreach ($idPaths as $path) {
                        $filename = basename($path);
                        $target = 'ids/personnel/'.$personnel->id.'/'.$filename;
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->move($path, $target);
                            $moved[] = $target;
                        }
                    }
                    $personnel->update(['id_picture_paths' => $moved]);
                }
            }

            return $user;
        });

        if ($isGuard) {
            return redirect()
                ->route('personnel.index')
                ->with('success', 'Guard account created and added to Personnel. Enroll their face in the mobile app before Face In / Face Out or deployment.');
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => $user->isAdmin()
                ? UserRole::cases()
                : self::creatableRoles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $allowedRoles = $user->isAdmin()
            ? array_map(fn (UserRole $role) => $role->value, UserRole::cases())
            : array_map(fn (UserRole $role) => $role->value, self::creatableRoles());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', ...self::uniqueLoginEmail($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in($allowedRoles)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ], [
            'role.in' => 'Administrator accounts cannot be assigned here. Choose Supervisor or Security Guard.',
        ]);

        $data = collect($validated)->except('password')->toArray();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($user->isBreakGlassAdmin() || $user->isLastActiveAdmin()) {
            $data['role'] = UserRole::Admin->value;
            $data['is_active'] = true;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        if (! $user->is_active) {
            $user->revokeAccess();
        }

        $user->refresh();

        if ($user->isGuard() && $user->is_active && ! $user->trashed()) {
            $user->ensurePersonnelProfile();
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->is_active && $user->isBreakGlassAdmin()) {
            return back()->with('error', 'The emergency administrator account cannot be deactivated. Use break-glass CLI recovery if needed.');
        }

        if ($user->is_active && $user->isLastActiveAdmin()) {
            return back()->with('error', 'You cannot deactivate the last active administrator. Use break-glass access if locked out.');
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

        if ($user->isBreakGlassAdmin()) {
            return back()->with('error', 'The emergency administrator account cannot be archived.');
        }

        if ($user->isLastActiveAdmin()) {
            return back()->with('error', 'You cannot archive the last active administrator. Use break-glass access if locked out.');
        }

        $user->archive();

        return redirect()
            ->route('users.index', ['archived' => 1])
            ->with('success', 'Account archived. Login access was removed, and the record can be restored later.');
    }

    /**
     * @return list<UserRole>
     */
    private static function creatableRoles(): array
    {
        return [
            UserRole::Supervisor,
            UserRole::Guard,
        ];
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
