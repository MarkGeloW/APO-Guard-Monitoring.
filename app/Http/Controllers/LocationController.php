<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        $locations = Location::query()
            ->withCount('deployments')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Locations/Index', [
            'locations' => $locations->through(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'client_name' => $location->client_name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'geofence_radius' => $location->geofence_radius,
                'status' => $location->status,
                'deployments_count' => $location->deployments_count,
            ]),
            'filters' => $request->only(['search']),
            'stats' => [
                'total' => Location::count(),
                'active' => Location::where('status', 'active')->count(),
                'avg_radius' => (int) round(Location::avg('geofence_radius') ?? 0),
            ],
        ]);
    }

    public function create(): View
    {
        return view('locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'geofence_radius' => ['required', 'integer', 'min:10', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
        ]);

        Location::create($validated);

        return redirect()->route('locations.index')->with('success', 'Deployment location added successfully.');
    }

    public function edit(Location $location): View
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'geofence_radius' => ['required', 'integer', 'min:10', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
        ]);

        $location->update($validated);

        return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Location deleted successfully.');
    }
}
