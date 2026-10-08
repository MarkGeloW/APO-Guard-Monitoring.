<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\Location;
use App\Models\SecurityPersonnel;
use App\Models\SystemNotification;
use App\Http\Support\InertiaPageData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeploymentController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        if ($request->boolean('export')) {
            return $this->export($request);
        }

        $deployments = Deployment::with(['personnel', 'location', 'assigner', 'attendance'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->whereHas('personnel', function ($personnelQuery) use ($search) {
                        $personnelQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('badge_number', 'like', "%{$search}%");
                    })->orWhereHas('location', function ($locationQuery) use ($search) {
                        $locationQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->date, fn ($q, $date) => $q->whereDate('deployment_date', $date))
            ->when($request->deployment_status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->display_status === 'warning', fn ($q) => $q->whereHas('attendance', fn ($a) => $a->where('status', 'late')))
            ->when($request->display_status === 'active', fn ($q) => $q->whereHas('attendance', fn ($a) => $a
                ->whereIn('status', ['present', 'late'])
                ->whereNull('time_out')))
            ->when($request->display_status === 'pending', fn ($q) => $q->where('status', 'scheduled')->whereHas('attendance', fn ($a) => $a->where('status', 'pending')->whereNull('time_in')))
            ->when($request->display_status === 'absent', fn ($q) => $q->where('status', 'scheduled')->whereHas('attendance', fn ($a) => $a->where('status', 'pending')->whereNull('time_in')))
            ->latest('deployment_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Deployments/Index', [
            'deployments' => InertiaPageData::paginatedDeployments($deployments),
            'filters' => $request->only(['search', 'date', 'display_status', 'deployment_status']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $deployments = Deployment::with(['personnel', 'location', 'attendance'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('personnel', function ($personnelQuery) use ($search) {
                    $personnelQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('location', function ($locationQuery) use ($search) {
                    $locationQuery->where('name', 'like', "%{$search}%");
                });
            })
            ->latest('deployment_date')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="deployments-'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->stream(function () use ($deployments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Deploy ID', 'Guard Name', 'Duty Location', 'Date', 'Shift', 'Status']);

            foreach ($deployments as $deployment) {
                fputcsv($handle, [
                    $deployment->deployCode(),
                    $deployment->personnel->fullName(),
                    $deployment->location->name,
                    $deployment->deployment_date->format('M j, Y'),
                    $deployment->shiftRange(),
                    $deployment->displayStatusLabel(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function create(): View
    {
        return view('deployments.create', [
            'personnel' => SecurityPersonnel::where('status', 'active')->orderBy('last_name')->get(),
            'locations' => Location::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'security_personnel_id' => ['required', 'exists:security_personnel,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'deployment_date' => ['required', 'date'],
            'shift_start' => ['required', 'date_format:H:i'],
            'shift_end' => ['required', 'date_format:H:i', 'different:shift_start'],
            'status' => ['required', Rule::in(['scheduled', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        if ($overlap = Deployment::findOverlap(
            (int) $validated['security_personnel_id'],
            $validated['deployment_date'],
            $validated['shift_start'],
            $validated['shift_end'],
        )) {
            return back()
                ->withInput()
                ->withErrors([
                    'shift_end' => 'This shift overlaps with an existing assignment at '
                        .$overlap->location->name.' ('.$overlap->shiftRange().').',
                ]);
        }

        $deployment = Deployment::create([
            ...$validated,
            'assigned_by' => auth()->id(),
        ]);

        Attendance::create([
            'deployment_id' => $deployment->id,
            'security_personnel_id' => $deployment->security_personnel_id,
            'status' => 'pending',
        ]);

        $personnel = SecurityPersonnel::find($deployment->security_personnel_id);
        $deployment->load('location');

        if ($personnel?->user_id) {
            SystemNotification::create([
                'user_id' => $personnel->user_id,
                'type' => 'deployment_assigned',
                'title' => 'New Deployment Assignment',
                'message' => "You have been assigned to {$deployment->location->name} on {$deployment->deployment_date->format('M d, Y')}.",
                'data' => ['deployment_id' => $deployment->id],
            ]);
        }

        return redirect()->route('deployments.index')->with('success', 'Deployment assignment created successfully.');
    }

    public function edit(Deployment $deployment): View
    {
        return view('deployments.edit', [
            'deployment' => $deployment,
            'personnel' => SecurityPersonnel::where('status', 'active')->orderBy('last_name')->get(),
            'locations' => Location::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Deployment $deployment): RedirectResponse
    {
        $validated = $request->validate([
            'security_personnel_id' => ['required', 'exists:security_personnel,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'deployment_date' => ['required', 'date'],
            'shift_start' => ['required', 'date_format:H:i'],
            'shift_end' => ['required', 'date_format:H:i', 'different:shift_start'],
            'status' => ['required', Rule::in(['scheduled', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        if ($overlap = Deployment::findOverlap(
            (int) $validated['security_personnel_id'],
            $validated['deployment_date'],
            $validated['shift_start'],
            $validated['shift_end'],
            $deployment->id,
        )) {
            return back()
                ->withInput()
                ->withErrors([
                    'shift_end' => 'This shift overlaps with an existing assignment at '
                        .$overlap->location->name.' ('.$overlap->shiftRange().').',
                ]);
        }

        $deployment->update($validated);

        return redirect()->route('deployments.index')->with('success', 'Deployment updated successfully.');
    }

    public function destroy(Deployment $deployment): RedirectResponse
    {
        $deployment->delete();

        return redirect()->route('deployments.index')->with('success', 'Deployment deleted successfully.');
    }
}
