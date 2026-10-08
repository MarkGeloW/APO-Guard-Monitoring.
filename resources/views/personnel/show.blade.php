<x-admin-layout>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">{{ $personnel->fullName() }}</span>
    </x-slot>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            @if($personnel->faceImageUrl())
                <img
                    src="{{ $personnel->faceImageUrl() }}"
                    alt="{{ $personnel->fullName() }} enrolled face"
                    class="h-16 w-16 rounded-2xl object-cover shadow-lg shadow-slate-300/40 ring-1 ring-slate-200"
                >
            @else
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#162d4a] text-xl font-bold text-white shadow-lg shadow-slate-300/40">
                    {{ $personnel->initials() }}
                </div>
            @endif
            <div>
                <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">{{ $personnel->fullName() }}</h2>
                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">Badge {{ $personnel->badge_number }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">{{ $personnel->guardCode() }}</span>
                    <x-status-badge :status="$personnel->status" />
                    @if($personnel->hasFaceEnrolled())
                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-600 ring-1 ring-blue-100">Face Enrolled</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600 ring-1 ring-amber-100">Face Not Enrolled</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('personnel.edit', $personnel) }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600">
                Edit Profile
            </a>
            <a href="{{ route('personnel.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-900">Enrolled Face</p>
                <p class="mt-1 text-xs text-slate-500">Used to verify check-in and check-out</p>
                @if($personnel->faceImageUrl())
                    <a href="{{ $personnel->faceImageUrl() }}" target="_blank" rel="noopener" class="mt-4 block overflow-hidden rounded-2xl ring-1 ring-slate-200">
                        <img src="{{ $personnel->faceImageUrl() }}" alt="Enrolled face" class="aspect-square w-full object-cover">
                    </a>
                    <p class="mt-3 text-xs text-slate-500">
                        Enrolled {{ $personnel->face_enrolled_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
                    </p>
                @else
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No face enrolled yet. The guard must enroll in the mobile app before attendance.
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-900">Contact & employment</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $personnel->phone ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Email</dt>
                        <dd class="max-w-[60%] truncate text-right font-semibold text-slate-900">{{ $personnel->email ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Address</dt>
                        <dd class="max-w-[60%] text-right text-slate-700">{{ $personnel->address ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Date hired</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $personnel->date_hired?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Rank</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $personnel->rank() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Linked account</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $personnel->user?->email ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            @if($personnel->emergency_contact || $personnel->emergency_phone || $personnel->notes)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-bold text-slate-900">Emergency & notes</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                            <dt class="text-slate-500">Contact</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $personnel->emergency_contact ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                            <dt class="text-slate-500">Phone</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $personnel->emergency_phone ?: '—' }}</dd>
                        </div>
                        @if($personnel->notes)
                            <div>
                                <dt class="mb-1 text-slate-500">Notes</dt>
                                <dd class="rounded-xl bg-slate-50 p-3 text-slate-700">{{ $personnel->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Recent Deployments</p>
                        <p class="text-xs text-slate-500">Latest 10 duty assignments</p>
                    </div>
                    <a href="{{ route('deployments.create', ['security_personnel_id' => $personnel->id]) }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">+ Assign</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 text-left">Date</th>
                                <th class="px-5 py-3 text-left">Location</th>
                                <th class="px-5 py-3 text-left">Shift</th>
                                <th class="px-5 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($personnel->deployments->sortByDesc('deployment_date')->take(10) as $d)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $d->deployment_date->format('M d, Y') }}</td>
                                    <td class="px-5 py-3 text-slate-700">{{ $d->location->name }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $d->shiftRange() }}</td>
                                    <td class="px-5 py-3"><x-status-badge :status="$d->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center">
                                        <p class="text-sm font-semibold text-slate-700">No deployments yet</p>
                                        <p class="mt-1 text-xs text-slate-500">Assign this guard to a duty post to get started.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <p class="text-sm font-bold text-slate-900">Recent Attendance</p>
                    <p class="text-xs text-slate-500">Latest check-in records</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 text-left">Date</th>
                                <th class="px-5 py-3 text-left">Location</th>
                                <th class="px-5 py-3 text-left">Face</th>
                                <th class="px-5 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($personnel->attendances->sortByDesc('created_at')->take(8) as $a)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $a->deployment?->deployment_date?->format('M d, Y') ?? $a->created_at?->format('M d, Y') }}</td>
                                    <td class="px-5 py-3 text-slate-700">{{ $a->deployment?->location?->name ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            @if($a->timeInFaceUrl())
                                                <a href="{{ $a->timeInFaceUrl() }}" target="_blank" rel="noopener" title="Check-in face">
                                                    <img src="{{ $a->timeInFaceUrl() }}" alt="Check-in face" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200">
                                                </a>
                                            @endif
                                            @if($a->timeOutFaceUrl())
                                                <a href="{{ $a->timeOutFaceUrl() }}" target="_blank" rel="noopener" title="Check-out face">
                                                    <img src="{{ $a->timeOutFaceUrl() }}" alt="Check-out face" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200">
                                                </a>
                                            @endif
                                            @if(! $a->timeInFaceUrl() && ! $a->timeOutFaceUrl())
                                                <span class="text-xs font-semibold text-slate-300">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-3"><x-status-badge :status="$a->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">No attendance records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
