<x-admin-layout>
    <x-slot name="header">Deployment Management</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Deployment Management</span>
    </x-slot>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm text-slate-500">Assign and manage security officer duty locations and shift schedules.</p>
        </div>
        <a href="{{ route('deployments.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600">
            <span class="text-lg leading-none">+</span>
            New Assignment
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="{ showFilters: {{ request()->hasAny(['date', 'display_status', 'deployment_status']) ? 'true' : 'false' }} }">
        <div class="border-b border-slate-100 p-4">
            <form method="GET" class="space-y-4">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                    <div class="relative flex-1">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search guard or location..."
                            class="w-full rounded-xl border-0 bg-slate-50 py-3.5 pl-11 pr-4 text-sm text-slate-700 ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
                        >
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
                            Filter
                        </button>
                        <a href="{{ route('deployments.index', array_merge(request()->except('page'), ['export' => 1])) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/></svg>
                            Export
                        </a>
                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Search</button>
                        @if(request()->hasAny(['search', 'date', 'display_status', 'deployment_status']))
                            <a href="{{ route('deployments.index') }}" class="inline-flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700">Clear</a>
                        @endif
                    </div>
                </div>

                <div x-show="showFilters" x-cloak class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Date</label>
                        <input type="date" name="date" value="{{ request('date') }}" class="w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Display Status</label>
                        <select name="display_status" class="w-full rounded-xl border-slate-200 text-sm">
                            <option value="">All Status</option>
                            @foreach(['active' => 'Active', 'warning' => 'Warning', 'pending' => 'Pending', 'absent' => 'Absent'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('display_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Deployment Status</label>
                        <select name="deployment_status" class="w-full rounded-xl border-slate-200 text-sm">
                            <option value="">All</option>
                            @foreach(['scheduled', 'active', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(request('deployment_status') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">
                    <tr>
                        <th class="px-5 py-4 text-left">Deploy ID</th>
                        <th class="px-5 py-4 text-left">Guard Name</th>
                        <th class="px-5 py-4 text-left">Duty Location</th>
                        <th class="px-5 py-4 text-left">Date</th>
                        <th class="px-5 py-4 text-left">Shift</th>
                        <th class="px-5 py-4 text-left">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deployments as $deployment)
                        @php
                            $status = $deployment->displayStatus();
                            $statusClasses = match($status) {
                                'active' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                                'warning' => 'bg-amber-50 text-amber-600 ring-amber-100',
                                'pending' => 'bg-blue-50 text-blue-600 ring-blue-100',
                                'absent' => 'bg-red-50 text-red-600 ring-red-100',
                                default => 'bg-slate-50 text-slate-600 ring-slate-100',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-500">{{ $deployment->deployCode() }}</td>
                            <td class="px-5 py-4 font-bold text-slate-900">{{ $deployment->personnel->fullName() }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $deployment->location->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $deployment->deployment_date->format('M j, Y') }}</td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $deployment->shiftRange() }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide ring-1 {{ $statusClasses }}">
                                    {{ $deployment->displayStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><circle cx="4" cy="10" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="16" cy="10" r="1.5"/></svg>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 z-20 mt-2 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                        <a href="{{ route('deployments.edit', $deployment) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Edit Assignment</a>
                                        <form method="POST" action="{{ route('deployments.destroy', $deployment) }}" data-swal-confirm="This deployment assignment will be permanently removed." data-swal-title="Delete deployment?" data-swal-type="delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">No deployment assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $deployments->links() }}</div>
</x-admin-layout>
