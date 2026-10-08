@extends('layouts.report')

@section('title', 'Attendance Report')
@section('heading', 'Attendance & Verification Report')
@section('period', $periodLabel)
@section('filter-summary', $filterSummary)
@section('doc-ref')
APO-ATT-{{ now()->format('Ymd-Hi') }}
@endsection

@section('filters')
    <form method="GET" action="{{ route('reports.attendance') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">
            Start date
            <input type="date" name="start_date" value="{{ $startDate }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
        </label>
        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">
            End date
            <input type="date" name="end_date" value="{{ $endDate }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
        </label>
        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">
            Location
            <select name="location_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All locations</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected($locationId == $location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">
            Guard
            <select name="personnel_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All guards</option>
                @foreach($personnelOptions as $person)
                    <option value="{{ $person->id }}" @selected($personnelId == $person->id)>{{ $person->fullName() }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">
            Status
            <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All statuses</option>
                @foreach(['present','late','absent','completed'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-lg bg-[#14233a] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1d3a5c]">
                Apply Filters
            </button>
        </div>
    </form>
@endsection

@section('summary')
    <div class="kpi-strip">
        @foreach($summary as $card)
            <div>
                <p class="text-[9px] font-bold uppercase tracking-[.12em] text-[var(--apo-muted)]">{{ $card['label'] }}</p>
                <p class="mt-1 text-xl font-extrabold text-[var(--apo-navy)]">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>
@endsection

@section('content')
    <table class="report-table">
        <colgroup>
            <col style="width:10%">
            <col style="width:15%">
            <col style="width:14%">
            <col style="width:13%">
            <col style="width:13%">
            <col style="width:9%">
            <col style="width:8%">
            <col style="width:8%">
            <col style="width:10%">
        </colgroup>
        <thead>
            <tr>
                <th>Date</th>
                <th>Guard</th>
                <th>Location</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>GPS</th>
                <th>Face</th>
                <th>Late</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $a)
                @php $late = $a->deployment ? $a->checkInLatenessMinutes($a->deployment) : null; @endphp
                <tr>
                    <td>{{ $a->deployment?->deployment_date?->format('M j, Y') ?? '—' }}</td>
                    <td>
                        <strong>{{ $a->personnel?->fullName() ?? '—' }}</strong><br>
                        <span style="color:#5b6b7f;font-size:10px;">{{ $a->personnel?->badge_number }}</span>
                    </td>
                    <td>{{ $a->deployment?->location?->name ?? '—' }}</td>
                    <td>{{ $a->time_in?->format('M j, g:i A') ?? '—' }}</td>
                    <td>{{ $a->time_out?->format('M j, g:i A') ?? '—' }}</td>
                    <td>{{ ucfirst($a->status) }}</td>
                    <td>{{ $a->time_in_verified ? 'Yes' : 'No' }}</td>
                    <td>{{ $a->time_in_face_verified ? 'Yes' : 'No' }}</td>
                    <td>{{ $late !== null ? $late.' min' : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:28px;color:#5b6b7f;">No attendance records found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
