@extends('layouts.report-pdf')

@section('title', 'Personnel Report')
@section('heading', 'Security Personnel Roster')
@section('period', $periodLabel)
@section('filter-summary', $filterSummary)
@section('doc-ref')
APO-PER-{{ now()->format('Ymd-Hi') }}
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
                <th style="width:10%">Badge</th>
                <th style="width:16%">Name</th>
                <th style="width:10%">Status</th>
                <th style="width:12%">Phone</th>
                <th style="width:16%">Email</th>
                <th style="width:10%">Hired</th>
                <th style="width:10%">Face</th>
                <th style="width:8%">Deploys</th>
                <th style="width:8%">Attend.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($personnel as $p)
                <tr>
                    <td>{{ $p->badge_number }}</td>
                    <td><strong>{{ $p->fullName() }}</strong></td>
                    <td>{{ ucwords(str_replace('_', ' ', $p->status)) }}</td>
                    <td>{{ $p->phone ?: '—' }}</td>
                    <td>{{ $p->email ?: '—' }}</td>
                    <td>{{ $p->date_hired?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $p->hasFaceEnrolled() ? 'Enrolled' : 'Pending' }}</td>
                    <td>{{ $p->deployments_count }}</td>
                    <td>{{ $p->attendances_count }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No personnel found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
