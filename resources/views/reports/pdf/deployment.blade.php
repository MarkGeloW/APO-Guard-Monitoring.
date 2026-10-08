@extends('layouts.report-pdf')

@section('title', 'Deployment Schedule Report')
@section('heading', 'Deployment Schedule Report')
@section('period', $periodLabel)
@section('filter-summary', $filterSummary)
@section('doc-ref')
APO-DEP-{{ now()->format('Ymd-Hi') }}
@endsection

@section('summary')
    <table class="summary">
        <tr>
            @foreach($summary as $card)
                <td>
                    <span class="label">{{ $card['label'] }}</span>
                    <span class="value">{{ $card['value'] }}</span>
                </td>
            @endforeach
        </tr>
    </table>
@endsection

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th style="width:10%">Date</th>
                <th style="width:10%">Code</th>
                <th style="width:16%">Guard</th>
                <th style="width:16%">Location</th>
                <th style="width:15%">Shift</th>
                <th style="width:8%">Type</th>
                <th style="width:12%">Status</th>
                <th style="width:13%">Attendance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deployments as $d)
                <tr>
                    <td>{{ $d->deployment_date->format('M j, Y') }}</td>
                    <td>{{ $d->deployCode() }}</td>
                    <td>
                        <strong>{{ $d->personnel?->fullName() ?? '—' }}</strong><br>
                        <span class="muted">{{ $d->personnel?->badge_number }}</span>
                    </td>
                    <td>{{ $d->location?->name ?? '—' }}</td>
                    <td>{{ $d->formattedShiftStart() }} – {{ $d->formattedShiftEnd() }}</td>
                    <td>{{ $d->shiftType() }}</td>
                    <td>{{ $d->displayStatusLabel() }}</td>
                    <td>{{ $d->attendanceStatusLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No deployments found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
