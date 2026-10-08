<x-admin-layout>
    <x-slot name="header">Attendance Monitoring</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Attendance Monitoring</span>
    </x-slot>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Attendance Monitoring</h2>
            <p class="mt-1 text-sm text-slate-500">
                GPS-verified check-in records — {{ $date->format('M j, Y') }} · {{ $shiftLabel }}
            </p>
        </div>
        <a
            href="{{ $exportUrl }}"
            target="_blank"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/></svg>
            Export PDF
        </a>
    </div>

    @php
        $tabs = [
            'all' => 'All Guards',
            'on_duty' => 'On Duty',
            'late' => 'Late',
            'absent' => 'Absent',
            'off_duty' => 'Off Duty',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach($tabs as $key => $label)
            <a
                href="{{ route('attendance.index', array_merge(request()->except('page'), ['filter' => $key])) }}"
                class="inline-flex items-center rounded-full px-4 py-2 text-[11px] font-bold uppercase tracking-[.14em] transition {{ $filter === $key ? 'bg-blue-500 text-white shadow-md shadow-blue-300/30' : 'bg-white text-slate-500 ring-1 ring-slate-200 hover:text-slate-700' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">
                    <tr>
                        <th class="px-5 py-4 text-left">Guard ID</th>
                        <th class="px-5 py-4 text-left">Name</th>
                        <th class="px-5 py-4 text-left">Assigned Post</th>
                        <th class="px-5 py-4 text-left">Time In</th>
                        <th class="px-5 py-4 text-left">Time Out</th>
                        <th class="px-5 py-4 text-left">Shift</th>
                        <th class="px-5 py-4 text-center">GPS Verified</th>
                        <th class="px-5 py-4 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        @php
                            $statusClasses = match($record['tab_status']) {
                                'on_duty' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                                'late' => 'bg-amber-50 text-amber-600 ring-amber-100',
                                'absent' => 'bg-red-50 text-red-600 ring-red-100',
                                'off_duty' => 'bg-slate-100 text-slate-500 ring-slate-200',
                                default => 'bg-slate-100 text-slate-500 ring-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-500">{{ $record['guard_id'] }}</td>
                            <td class="px-5 py-4 font-bold text-slate-900">{{ $record['name'] }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $record['post'] }}</td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $record['time_in'] ?? '—' }}</td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $record['time_out'] ?? '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $record['shift'] }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-center">
                                    @if($record['gps_verified'])
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="m5 13 4 4L19 7"/></svg>
                                        </span>
                                    @else
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide ring-1 {{ $statusClasses }}">
                                    {{ $record['status_label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                No attendance records found for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <button type="button" class="fixed bottom-6 right-6 z-50 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-bold text-slate-500 shadow-lg hover:text-slate-800" aria-label="Help">?</button>
</x-admin-layout>
