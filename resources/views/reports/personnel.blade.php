@extends('layouts.report')

@section('title', 'Personnel Report')
@section('heading', 'Security Personnel Roster')
@section('period', $periodLabel)
@section('filter-summary', $filterSummary)
@section('page-size', 'A4 portrait')

@section('doc-ref')
APO-PER-{{ now()->format('Ymd-Hi') }}
@endsection

@section('filters')
    <form method="GET" action="{{ route('reports.personnel') }}" class="flex flex-wrap items-end gap-3">
        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">
            Status
            <select name="status" class="mt-1 w-56 rounded-lg border-slate-200 text-sm">
                <option value="">All statuses</option>
                @foreach(['active','on_leave','inactive'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded-lg bg-[#14233a] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1e3354]">
            Apply Filters
        </button>
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
            <col style="width:16%">
            <col style="width:10%">
            <col style="width:14%">
            <col style="width:16%">
            <col style="width:10%">
            <col style="width:10%">
            <col style="width:7%">
            <col style="width:7%">
        </colgroup>
        <thead>
            <tr>
                <th>Badge</th>
                <th>Name</th>
                <th>Status</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Hired</th>
                <th>Face</th>
                <th>Deploys</th>
                <th>Attend.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($personnel as $p)
                <tr>
                    <td style="font-family: ui-monospace, monospace;">{{ $p->badge_number }}</td>
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
                <tr>
                    <td colspan="9" style="text-align:center;padding:28px;color:#5b6b7f;">No personnel found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
