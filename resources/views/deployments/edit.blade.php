@php
    $personnelMap = $personnel->mapWithKeys(fn ($p) => [
        $p->id => [
            'name' => $p->fullName(),
            'badge' => $p->badge_number,
            'initials' => $p->initials(),
            'phone' => $p->phone ?: '—',
            'rank' => $p->rank(),
        ],
    ]);
    $locationMap = $locations->mapWithKeys(fn ($l) => [
        $l->id => [
            'name' => $l->name,
            'address' => $l->address ?: '—',
            'client' => $l->client_name ?: '—',
            'radius' => $l->geofence_radius,
        ],
    ]);
    $defaultPersonnelId = (string) old('security_personnel_id', $deployment->security_personnel_id);
    $defaultLocationId = (string) old('location_id', $deployment->location_id);
@endphp

<x-admin-layout>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Edit Deployment</span>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Edit Deployment Assignment</h2>
        <p class="mt-1 text-sm text-slate-500">Update guard, location, schedule, or status for this assignment.</p>
    </div>

    <div
        class="grid grid-cols-1 gap-6 xl:grid-cols-5"
        x-data="{
            personnelId: '{{ $defaultPersonnelId }}',
            locationId: '{{ $defaultLocationId }}',
            date: '{{ old('deployment_date', $deployment->deployment_date->format('Y-m-d')) }}',
            start: '{{ old('shift_start', $deployment->formattedShiftStart()) }}',
            end: '{{ old('shift_end', $deployment->formattedShiftEnd()) }}',
            status: '{{ old('status', $deployment->status) }}',
            personnel: {{ Js::from($personnelMap) }},
            locations: {{ Js::from($locationMap) }},
            get guard() { return this.personnel[this.personnelId] || null },
            get site() { return this.locations[this.locationId] || null },
            formatDate(value) {
                if (!value) return '—';
                const d = new Date(value + 'T00:00:00');
                return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric', timeZone: 'Asia/Manila' });
            },
            formatTime(value) {
                if (!value) return '—';
                const [h, m] = value.split(':').map(Number);
                const ampm = h >= 12 ? 'PM' : 'AM';
                const hour = ((h + 11) % 12) + 1;
                return `${String(hour).padStart(2, '0')}:${String(m).padStart(2, '0')} ${ampm}`;
            },
            statusLabel(value) {
                return (value || 'scheduled').replace(/^\w/, c => c.toUpperCase());
            }
        }"
    >
        <form method="POST" action="{{ route('deployments.update', $deployment) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-3">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">Assignment Details</p>
                    <p class="text-xs text-slate-500">Changes apply immediately after save</p>
                </div>
            </div>

            <div>
                <label class="{{ $labelClass }}">Security Guard</label>
                <select name="security_personnel_id" required class="{{ $inputClass }}" x-model="personnelId">
                    @foreach($personnel as $p)
                        <option value="{{ $p->id }}" @selected($defaultPersonnelId == $p->id)>{{ $p->fullName() }} ({{ $p->badge_number }})</option>
                    @endforeach
                </select>
                @error('security_personnel_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Deployment Location</label>
                <select name="location_id" required class="{{ $inputClass }}" x-model="locationId">
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected($defaultLocationId == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Guard mobile check-in will use this post’s geofence radius.</p>
                @error('location_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Deployment Date</label>
                <input type="date" name="deployment_date" required class="{{ $inputClass }}" x-model="date">
                @error('deployment_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">Shift Start</label>
                    <input type="time" name="shift_start" required class="{{ $inputClass }}" x-model="start">
                    @error('shift_start')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Shift End</label>
                    <input type="time" name="shift_end" required class="{{ $inputClass }}" x-model="end">
                    @error('shift_end')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="{{ $labelClass }}">Status</label>
                <select name="status" class="{{ $inputClass }}" x-model="status">
                    @foreach(['scheduled', 'active', 'completed', 'cancelled'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $deployment->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $labelClass }}">Notes</label>
                <textarea name="notes" rows="3" placeholder="Optional instructions for the assigned guard" class="{{ $inputClass }}">{{ old('notes', $deployment->notes) }}</textarea>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
                    Update Deployment
                </button>
                <a href="{{ route('deployments.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>

        <aside class="space-y-4 xl:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Assignment Preview</p>
                        <p class="text-xs text-slate-500">Live view of current selections</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-600" x-text="statusLabel(status)"></span>
                </div>

                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#162d4a] text-sm font-bold text-white" x-text="guard ? guard.initials : '—'"></div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900" x-text="guard ? guard.name : 'Select a guard'"></p>
                        <p class="truncate text-xs text-slate-500">
                            <span x-text="guard ? guard.rank : ''"></span>
                            <span x-show="guard"> · </span>
                            Badge <span x-text="guard ? guard.badge : '—'"></span>
                        </p>
                    </div>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Location</dt>
                        <dd class="text-right font-semibold text-slate-900" x-text="site ? site.name : '—'"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Address</dt>
                        <dd class="max-w-[60%] text-right text-slate-700" x-text="site ? site.address : '—'"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Date</dt>
                        <dd class="text-right font-semibold text-slate-900" x-text="formatDate(date)"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Shift</dt>
                        <dd class="text-right font-semibold text-slate-900">
                            <span x-text="formatTime(start)"></span>
                            <span class="text-slate-400"> – </span>
                            <span x-text="formatTime(end)"></span>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Geofence</dt>
                        <dd class="text-right font-semibold text-slate-900">
                            <span x-text="site ? site.radius : '—'"></span><span x-show="site"> m</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-5">
                <p class="text-sm font-bold text-slate-900">Editing tip</p>
                <p class="mt-2 text-xs leading-relaxed text-slate-600">
                    Changing the location updates where the guard must check in. Status changes to completed or cancelled keep the schedule history but stop mobile check-in for this shift.
                </p>
            </div>
        </aside>
    </div>
</x-admin-layout>
