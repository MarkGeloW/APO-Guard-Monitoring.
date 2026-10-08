@extends('layouts.report-pdf')

@section('title', 'Attendance Report')
@section('heading', 'Attendance & Verification Report')
@section('period', $periodLabel)
@section('filter-summary', $filterSummary)
@section('doc-ref')
APO-ATT-{{ now()->format('Ymd-Hi') }}
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
                <th style="width:15%">Guard</th>
                <th style="width:14%">Location</th>
                <th style="width:13%">Time In</th>
                <th style="width:13%">Time Out</th>
                <th style="width:9%">Status</th>
                <th style="width:8%">GPS</th>
                <th style="width:8%">Face</th>
                <th style="width:10%">Late</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $a)
                @php $late = $a->deployment ? $a->checkInLatenessMinutes($a->deployment) : null; @endphp
                <tr>
                    <td>{{ $a->deployment?->deployment_date?->format('M j, Y') ?? '—' }}</td>
                    <td>
                        <strong>{{ $a->personnel?->fullName() ?? '—' }}</strong><br>
                        <span class="muted">{{ $a->personnel?->badge_number }}</span>
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
                <tr><td colspan="9" class="empty">No attendance records found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
