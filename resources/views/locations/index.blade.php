<x-admin-layout>
    <x-slot name="header">Deployment Locations</x-slot>
    <x-slot name="subheader">Manage duty posts with GIS coordinates and geofence radius</x-slot>
    <div class="mb-4 flex justify-end"><a href="{{ route('locations.create') }}" class="{{ $btnPrimary }}">+ Add Location</a></div>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="px-5 py-3 text-left">Name</th><th class="px-5 py-3 text-left">Client</th><th class="px-5 py-3 text-left">Coordinates</th><th class="px-5 py-3 text-left">Geofence</th><th class="px-5 py-3 text-left">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($locations as $location)
                    <tr>
                        <td class="px-5 py-3 font-medium">{{ $location->name }}</td>
                        <td class="px-5 py-3">{{ $location->client_name ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $location->latitude }}, {{ $location->longitude }}</td>
                        <td class="px-5 py-3">{{ $location->geofence_radius }}m</td>
                        <td class="px-5 py-3"><x-status-badge :status="$location->status" /></td>
                        <td class="px-5 py-3 text-right space-x-2">
                            <a href="{{ route('locations.edit', $location) }}" class="text-amber-600">Edit</a>
                            <form action="{{ route('locations.destroy', $location) }}" method="POST" class="inline" data-swal-confirm="This location will be permanently removed." data-swal-title="Delete location?" data-swal-type="delete">@csrf @method('DELETE')<button type="submit" class="text-red-600">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $locations->links() }}</div>
</x-admin-layout>
