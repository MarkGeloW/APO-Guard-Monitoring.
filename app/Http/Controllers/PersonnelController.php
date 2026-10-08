<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SecurityPersonnel;
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

class PersonnelController extends Controller
{
    public function index(Request $request): Response
    {
        $personnel = SecurityPersonnel::with(['deployments' => fn ($q) => $q->latest('deployment_date')->limit(1)])
            ->withCount('deployments')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('badge_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Personnel/Index', [
            'personnel' => InertiaPageData::paginatedPersonnel($personnel),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Personnel/Create', [
            'guardUsers' => User::where('role', UserRole::Guard)
                ->whereDoesntHave('securityPersonnel')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'badge_number' => ['required', 'string', 'max:50', 'unique:security_personnel,badge_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_hired' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'on_leave'])],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'create_account' => ['boolean'],
            'user_id' => ['nullable', 'exists:users,id'],
            'account_email' => ['nullable', 'email', 'unique:users,email'],
            'account_password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $userId = $validated['user_id'] ?? null;

        if ($request->boolean('create_account') && ! $userId) {
            $request->validate([
                'account_email' => ['required', 'email', 'unique:users,email'],
                'account_password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = User::create([
                'name' => "{$validated['first_name']} {$validated['last_name']}",
                'email' => $validated['account_email'],
                'password' => Hash::make($validated['account_password']),
                'role' => UserRole::Guard,
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            $userId = $user->id;
        }

        SecurityPersonnel::create([
            ...collect($validated)->only([
                'badge_number', 'first_name', 'last_name', 'email', 'phone',
                'address', 'date_hired', 'status', 'emergency_contact',
                'emergency_phone', 'notes',
            ])->toArray(),
            'user_id' => $userId,
        ]);

        return redirect()->route('personnel.index')->with('success', 'Security personnel added successfully.');
    }

    public function show(SecurityPersonnel $personnel): View
    {
        $personnel->load(['user', 'deployments.location', 'attendances.deployment.location']);

        return view('personnel.show', compact('personnel'));
    }

    public function edit(SecurityPersonnel $personnel): View
    {
        return view('personnel.edit', [
            'personnel' => $personnel,
            'guardUsers' => User::where('role', UserRole::Guard)
                ->where(function ($q) use ($personnel) {
                    $q->whereDoesntHave('securityPersonnel')
                        ->orWhere('id', $personnel->user_id);
                })
                ->get(),
        ]);
    }

    public function update(Request $request, SecurityPersonnel $personnel): RedirectResponse
    {
        $validated = $request->validate([
            'badge_number' => ['required', 'string', 'max:50', Rule::unique('security_personnel', 'badge_number')->ignore($personnel->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_hired' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'on_leave'])],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $personnel->update($validated);

        return redirect()->route('personnel.index')->with('success', 'Personnel record updated successfully.');
    }

    public function destroy(SecurityPersonnel $personnel): RedirectResponse
    {
        $personnel->delete();

        return redirect()->route('personnel.index')->with('success', 'Personnel record deleted successfully.');
    }
}
