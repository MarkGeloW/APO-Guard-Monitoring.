@extends('layouts.report')

@section('title', 'Deployment Schedule Report')
@section('heading', 'Deployment Schedule Report')
@section('period', $periodLabel)
@section('filter-summary', $filterSummary)
@section('doc-ref')
APO-DEP-{{ now()->format('Ymd-Hi') }}
@endsection

@section('filters')
    <form method="GET" action="{{ route('reports.deployments') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
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
                @foreach(['scheduled','active','completed','cancelled'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-lg bg-[#14233a] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1e3354]">
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
            <col style="width:11%">
            <col style="width:10%">
            <col style="width:16%">
            <col style="width:16%">
            <col style="width:16%">
            <col style="width:8%">
            <col style="width:11%">
            <col style="width:12%">
        </colgroup>
        <thead>
            <tr>
                <th>Date</th>
                <th>Code</th>
                <th>Guard</th>
                <th>Location</th>
                <th>Shift</th>
                <th>Type</th>
                <th>Status</th>
                <th>Attendance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deployments as $d)
                <tr>
                    <td>{{ $d->deployment_date->format('M j, Y') }}</td>
                    <td style="font-family: ui-monospace, monospace; font-size: 10px;">{{ $d->deployCode() }}</td>
                    <td>
                        <strong>{{ $d->personnel?->fullName() ?? '—' }}</strong><br>
                        <span style="color:#5b6b7f;font-size:10px;">{{ $d->personnel?->badge_number }}</span>
                    </td>
                    <td>{{ $d->location?->name ?? '—' }}</td>
                    <td>{{ $d->formattedShiftStart() }} – {{ $d->formattedShiftEnd() }}</td>
                    <td>{{ $d->shiftType() }}</td>
                    <td>{{ $d->displayStatusLabel() }}</td>
                    <td>{{ $d->attendanceStatusLabel() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:28px;color:#5b6b7f;">No deployments found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
