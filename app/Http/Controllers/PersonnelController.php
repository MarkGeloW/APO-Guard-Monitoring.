<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SecurityPersonnel;
use App\Models\User;
use App\Http\Support\InertiaPageData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class PersonnelController extends Controller
{
    public function index(Request $request): Response
    {
        User::syncMissingGuardPersonnelProfiles();

        $showArchived = $request->boolean('archived');

        $personnelQuery = $showArchived
            ? SecurityPersonnel::onlyTrashed()
            : SecurityPersonnel::query();

        $personnel = $personnelQuery
            ->with(['deployments' => fn ($q) => $q->latest('deployment_date')->limit(1)])
            ->withCount('deployments')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($request->status && ! $showArchived, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Personnel/Index', [
            'personnel' => InertiaPageData::paginatedPersonnel($personnel),
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status'),
                'archived' => $showArchived,
            ],
            'stats' => [
                'active' => SecurityPersonnel::count(),
                'archived' => SecurityPersonnel::onlyTrashed()->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        User::syncMissingGuardPersonnelProfiles();

        $guardUsers = User::guardsWithoutPersonnelProfile()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $linkedGuardCount = User::where('role', UserRole::Guard)
            ->whereNotIn('id', $guardUsers->pluck('id'))
            ->count();

        return Inertia::render('Personnel/Create', [
            'guardUsers' => $guardUsers,
            'linkedGuardCount' => $linkedGuardCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $availableGuardIds = User::guardsWithoutPersonnelProfile()
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'license_number' => ['required', 'string', 'max:50', 'unique:security_personnel,license_number'],
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
            'user_id' => [
                'required',
                Rule::in($availableGuardIds),
            ],
            'id_pictures' => ['required', 'array', 'min:1'],
            'id_pictures.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'user_id.required' => 'Please create an account first in User Management before adding personnel.',
            'user_id.in' => 'Please create an account first in User Management before adding personnel.',
            'license_number.required' => 'Guard License Number is required.',
            'license_number.unique' => 'This Guard License Number is already assigned to another guard.',
            'id_pictures.required' => 'Please upload at least one valid ID picture.',
            'id_pictures.min' => 'Please upload at least one valid ID picture.',
        ]);

        $account = User::findOrFail($validated['user_id']);
        $idPaths = SecurityPersonnel::storeIdPictures($request->file('id_pictures', []));

        $personnel = SecurityPersonnel::create([
            ...collect($validated)->only([
                'license_number', 'first_name', 'last_name', 'email', 'phone',
                'address', 'date_hired', 'status', 'emergency_contact',
                'emergency_phone', 'notes', 'user_id',
            ])->toArray(),
            'id_picture_paths' => $idPaths,
            'email' => $validated['email'] ?: $account->email,
            'phone' => $validated['phone'] ?: $account->phone,
        ]);

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

        return redirect()->route('personnel.index')->with('success', 'Security personnel added and linked to the login account.');
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
        $availableGuardIds = User::where('role', UserRole::Guard)
            ->where(function ($q) use ($personnel) {
                $q->whereDoesntHave('securityPersonnel')
                    ->orWhere('id', $personnel->user_id);
            })
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'license_number' => ['required', 'string', 'max:50', Rule::unique('security_personnel', 'license_number')->ignore($personnel->id)],
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
            'user_id' => [
                'required',
                Rule::in($availableGuardIds),
            ],
            'id_pictures' => [$personnel->hasIdPicture() ? 'nullable' : 'required', 'array', 'min:1'],
            'id_pictures.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'user_id.required' => 'Please create an account first in User Management before adding personnel.',
            'user_id.in' => 'Please create an account first in User Management before adding personnel.',
            'license_number.required' => 'Guard License Number is required.',
            'license_number.unique' => 'This Guard License Number is already assigned to another guard.',
            'id_pictures.required' => 'Please upload at least one valid ID picture.',
            'id_pictures.min' => 'Please upload at least one valid ID picture.',
        ]);

        $data = collect($validated)->except('id_pictures')->toArray();

        if ($request->hasFile('id_pictures')) {
            $newPaths = SecurityPersonnel::storeIdPictures(
                $request->file('id_pictures', []),
                $personnel->id,
            );
            $data['id_picture_paths'] = array_values(array_merge(
                $personnel->id_picture_paths ?? [],
                $newPaths,
            ));
        }

        $personnel->update($data);

        if (in_array($validated['status'], ['inactive'], true) && $personnel->user) {
            $personnel->user->update(['is_active' => false]);
            $personnel->user->revokeAccess();
        }

        return redirect()->route('personnel.index')->with('success', 'Personnel record updated successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $personnel = SecurityPersonnel::onlyTrashed()->findOrFail($id);
        $personnel->restoreProfile();

        return redirect()
            ->route('personnel.index')
            ->with('success', 'Personnel restored. Linked login access was restored too.');
    }

    public function destroy(SecurityPersonnel $personnel): RedirectResponse
    {
        $personnel->archive();

        return redirect()
            ->route('personnel.index', ['archived' => 1])
            ->with('success', 'Personnel removed. Open deployments were cancelled and the login account was archived.');
    }
}
