<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\Location;
use App\Models\SecurityPersonnel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $today = today();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return Inertia::render('Reports/Index', [
            'stats' => [
                'deployments_month' => Deployment::whereBetween('deployment_date', [$monthStart, $monthEnd])->count(),
                'attendance_month' => Attendance::query()
                    ->whereHas('deployment', fn ($q) => $q->whereBetween('deployment_date', [$monthStart, $monthEnd]))
                    ->count(),
                'personnel_active' => SecurityPersonnel::where('status', 'active')->count(),
                'face_verified_today' => Attendance::query()
                    ->whereDate('time_in', $today)
                    ->where('time_in_face_verified', true)
                    ->count(),
            ],
            'quickLinks' => [
                [
                    'name' => "Today's Deployments",
                    'type' => 'Deployment',
                    'description' => 'All guard assignments scheduled for today.',
                    'url' => route('reports.deployments', [
                        'start_date' => $today->toDateString(),
                        'end_date' => $today->toDateString(),
                    ]),
                ],
                [
                    'name' => "This Week's Attendance",
                    'type' => 'Attendance',
                    'description' => 'Check-ins, lateness, and face/GPS verification this week.',
                    'url' => route('reports.attendance', [
                        'start_date' => now()->startOfWeek()->toDateString(),
                        'end_date' => now()->endOfWeek()->toDateString(),
                    ]),
                ],
                [
                    'name' => "This Month's Deployments",
                    'type' => 'Deployment',
                    'description' => 'Full monthly deployment schedule with status.',
                    'url' => route('reports.deployments', [
                        'start_date' => $monthStart,
                        'end_date' => $monthEnd,
                    ]),
                ],
                [
                    'name' => 'Active Personnel Roster',
                    'type' => 'Personnel',
                    'description' => 'Current active guards with deployment counts.',
                    'url' => route('reports.personnel', ['status' => 'active']),
                ],
            ],
            'filters' => [
                'locations' => Location::query()
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Location $l) => ['id' => $l->id, 'name' => $l->name])
                    ->values(),
                'personnel' => SecurityPersonnel::query()
                    ->where('status', 'active')
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get()
                    ->map(fn (SecurityPersonnel $p) => [
                        'id' => $p->id,
                        'name' => $p->fullName(),
                        'badge' => $p->badge_number,
                    ])
                    ->values(),
            ],
            'defaults' => [
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ],
            'routes' => [
                'deployments' => route('reports.deployments'),
                'attendance' => route('reports.attendance'),
                'personnel' => route('reports.personnel'),
                'deployments_pdf' => route('reports.deployments.pdf'),
                'attendance_pdf' => route('reports.attendance.pdf'),
                'personnel_pdf' => route('reports.personnel.pdf'),
            ],
        ]);
    }

    public function deployment(Request $request): View
    {
        return view('reports.deployment', $this->deploymentData($request));
    }

    public function deploymentPdf(Request $request): SymfonyResponse
    {
        $data = $this->deploymentData($request);

        return Pdf::loadView('reports.pdf.deployment', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->csvFilename('deployment-report', $data['startDate'], $data['endDate'], 'pdf'));
    }

    public function exportDeployments(Request $request): StreamedResponse
    {
        $data = $this->deploymentData($request);

        return $this->streamCsv(
            $this->csvFilename('deployment-report', $data['startDate'], $data['endDate']),
            'Deployment Schedule Report',
            $data['periodLabel'],
            $data['filterSummary'],
            ['Date', 'Deploy Code', 'Guard', 'Badge', 'Location', 'Shift Start', 'Shift End', 'Shift Type', 'Status', 'Attendance'],
            $data['deployments']->map(fn (Deployment $d) => [
                $this->csvDate($d->deployment_date),
                $d->deployCode(),
                $d->personnel?->fullName() ?? '',
                $d->personnel?->badge_number ?? '',
                $d->location?->name ?? '',
                $d->formattedShiftStart(),
                $d->formattedShiftEnd(),
                $d->shiftType(),
                $d->displayStatusLabel(),
                $d->attendanceStatusLabel(),
            ])
        );
    }

    public function attendance(Request $request): View
    {
        return view('reports.attendance', $this->attendanceData($request));
    }

    public function attendancePdf(Request $request): SymfonyResponse
    {
        $data = $this->attendanceData($request);

        return Pdf::loadView('reports.pdf.attendance', $data)
            ->setPaper('a4', 'landscape')
            ->download($this->csvFilename('attendance-report', $data['startDate'], $data['endDate'], 'pdf'));
    }

    public function exportAttendance(Request $request): StreamedResponse
    {
        $data = $this->attendanceData($request);

        return $this->streamCsv(
            $this->csvFilename('attendance-report', $data['startDate'], $data['endDate']),
            'Attendance & Verification Report',
            $data['periodLabel'],
            $data['filterSummary'],
            ['Date', 'Guard', 'Badge', 'Location', 'Time In', 'Time Out', 'Status', 'GPS Verified', 'Face Verified', 'Late (Minutes)'],
            $data['attendances']->map(function (Attendance $a) {
                $late = $a->deployment ? $a->checkInLatenessMinutes($a->deployment) : null;

                return [
                    $this->csvDate($a->deployment?->deployment_date),
                    $a->personnel?->fullName() ?? '',
                    $a->personnel?->badge_number ?? '',
                    $a->deployment?->location?->name ?? '',
                    $this->csvDateTime($a->time_in),
                    $this->csvDateTime($a->time_out),
                    $a->status ? ucwords(str_replace('_', ' ', $a->status)) : '',
                    $a->time_in_verified ? 'Yes' : 'No',
                    $a->time_in_face_verified ? 'Yes' : 'No',
                    $late ?? '',
                ];
            })
        );
    }

    public function personnel(Request $request): View
    {
        return view('reports.personnel', $this->personnelData($request));
    }

    public function personnelPdf(Request $request): SymfonyResponse
    {
        $data = $this->personnelData($request);

        return Pdf::loadView('reports.pdf.personnel', $data)
            ->setPaper('a4', 'portrait')
            ->download('personnel-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportPersonnel(Request $request): StreamedResponse
    {
        $data = $this->personnelData($request);

        return $this->streamCsv(
            $this->csvFilename('personnel-report'),
            'Security Personnel Roster',
            $data['periodLabel'],
            $data['filterSummary'],
            ['Badge', 'Name', 'Status', 'Phone', 'Email', 'Date Hired', 'Face Enrolled', 'Deployments', 'Attendance Records'],
            $data['personnel']->map(fn (SecurityPersonnel $p) => [
                $p->badge_number ?? '',
                $p->fullName(),
                ucwords(str_replace('_', ' ', $p->status)),
                $p->phone ?? '',
                $p->email ?? '',
                $this->csvDate($p->date_hired),
                $p->hasFaceEnrolled() ? 'Yes' : 'No',
                $p->deployments_count,
                $p->attendances_count,
            ])
        );
    }

    private function deploymentData(Request $request): array
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $locationId = $request->integer('location_id') ?: null;
        $personnelId = $request->integer('personnel_id') ?: null;
        $status = $request->string('status')->toString() ?: null;
        $shift = $request->string('shift')->toString() ?: null;

        $deployments = $this->deploymentQuery($startDate, $endDate, $locationId, $personnelId, $status)->get();

        if ($shift) {
            $deployments = $deployments
                ->filter(fn (Deployment $d) => strcasecmp($d->shiftType(), $shift) === 0)
                ->values();
        }

        $summary = [
            ['label' => 'Total Assignments', 'value' => $deployments->count()],
            ['label' => 'Active / On Duty', 'value' => $deployments->filter(fn (Deployment $d) => in_array($d->attendanceTabStatus(), ['on_duty', 'late'], true))->count()],
            ['label' => 'Completed', 'value' => $deployments->filter(fn (Deployment $d) => $d->attendanceTabStatus() === 'off_duty' && $d->attendance?->time_out)->count()],
            ['label' => 'Absent', 'value' => $deployments->filter(fn (Deployment $d) => $d->attendanceTabStatus() === 'absent')->count()],
            ['label' => 'Locations', 'value' => $deployments->pluck('location_id')->unique()->filter()->count()],
        ];

        return [
            'deployments' => $deployments,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'locationId' => $locationId,
            'personnelId' => $personnelId,
            'status' => $status,
            'shift' => $shift,
            'locations' => Location::orderBy('name')->get(['id', 'name']),
            'personnelOptions' => SecurityPersonnel::orderBy('last_name')->orderBy('first_name')->get(),
            'filterSummary' => $this->describeFilters([
                'Location' => $locationId ? Location::find($locationId)?->name : null,
                'Guard' => $personnelId ? SecurityPersonnel::find($personnelId)?->fullName() : null,
                'Status' => $status,
                'Shift' => $shift ? ucfirst($shift) : null,
            ]),
            'csvUrl' => route('reports.deployments.export', $request->query()),
            'pdfUrl' => route('reports.deployments.pdf', $request->query()),
            'periodLabel' => $this->periodLabel($startDate, $endDate),
        ];
    }

    private function attendanceData(Request $request): array
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $locationId = $request->integer('location_id') ?: null;
        $personnelId = $request->integer('personnel_id') ?: null;
        $status = $request->string('status')->toString() ?: null;

        $attendances = $this->attendanceQuery($startDate, $endDate, $locationId, $personnelId, $status)->get();

        $deployments = Deployment::with(['location', 'attendance'])
            ->whereBetween('deployment_date', [$startDate, $endDate])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($personnelId, fn ($q) => $q->where('security_personnel_id', $personnelId))
            ->get();

        $summary = [
            ['label' => 'Total Records', 'value' => $attendances->count()],
            ['label' => 'On Time', 'value' => $attendances->filter(fn (Attendance $a) => $a->time_in && $a->deployment && $a->checkInLatenessMinutes($a->deployment) === null)->count()],
            ['label' => 'Late', 'value' => $attendances->filter(fn (Attendance $a) => $a->deployment && $a->checkInLatenessMinutes($a->deployment) !== null)->count()],
            ['label' => 'Face Verified', 'value' => $attendances->where('time_in_face_verified', true)->count()],
            ['label' => 'Absent Posts', 'value' => $deployments->filter(fn (Deployment $d) => $d->attendanceTabStatus() === 'absent')->count()],
        ];

        return [
            'attendances' => $attendances,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'locationId' => $locationId,
            'personnelId' => $personnelId,
            'status' => $status,
            'locations' => Location::orderBy('name')->get(['id', 'name']),
            'personnelOptions' => SecurityPersonnel::orderBy('last_name')->orderBy('first_name')->get(),
            'filterSummary' => $this->describeFilters([
                'Location' => $locationId ? Location::find($locationId)?->name : null,
                'Guard' => $personnelId ? SecurityPersonnel::find($personnelId)?->fullName() : null,
                'Status' => $status,
            ]),
            'csvUrl' => route('reports.attendance.export', $request->query()),
            'pdfUrl' => route('reports.attendance.pdf', $request->query()),
            'periodLabel' => $this->periodLabel($startDate, $endDate),
        ];
    }

    private function personnelData(Request $request): array
    {
        $status = $request->string('status')->toString() ?: null;

        $personnel = SecurityPersonnel::withCount(['deployments', 'attendances'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $summary = [
            ['label' => 'Total Personnel', 'value' => $personnel->count()],
            ['label' => 'Active', 'value' => $personnel->where('status', 'active')->count()],
            ['label' => 'On Leave', 'value' => $personnel->where('status', 'on_leave')->count()],
            ['label' => 'Inactive', 'value' => $personnel->where('status', 'inactive')->count()],
            ['label' => 'Face Enrolled', 'value' => $personnel->filter(fn (SecurityPersonnel $p) => $p->hasFaceEnrolled())->count()],
        ];

        return [
            'personnel' => $personnel,
            'summary' => $summary,
            'status' => $status,
            'filterSummary' => $this->describeFilters([
                'Status' => $status ? str_replace('_', ' ', $status) : null,
            ]),
            'csvUrl' => route('reports.personnel.export', $request->query()),
            'pdfUrl' => route('reports.personnel.pdf', $request->query()),
            'periodLabel' => 'As of '.now()->format('M j, Y'),
        ];
    }

    private function dateRange(Request $request): array
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function deploymentQuery(string $startDate, string $endDate, ?int $locationId, ?int $personnelId, ?string $status): Builder
    {
        return Deployment::with(['personnel', 'location', 'assigner', 'attendance'])
            ->whereBetween('deployment_date', [$startDate, $endDate])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($personnelId, fn ($q) => $q->where('security_personnel_id', $personnelId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('deployment_date')
            ->orderBy('shift_start');
    }

    private function attendanceQuery(string $startDate, string $endDate, ?int $locationId, ?int $personnelId, ?string $status): Builder
    {
        return Attendance::with(['personnel', 'deployment.location'])
            ->whereHas('deployment', function ($q) use ($startDate, $endDate, $locationId, $personnelId) {
                $q->whereBetween('deployment_date', [$startDate, $endDate])
                    ->when($locationId, fn ($qq) => $qq->where('location_id', $locationId))
                    ->when($personnelId, fn ($qq) => $qq->where('security_personnel_id', $personnelId));
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($personnelId, fn ($q) => $q->where('security_personnel_id', $personnelId))
            ->latest('time_in');
    }

    private function describeFilters(array $filters): string
    {
        $parts = collect($filters)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, $label) => $label.': '.ucwords(str_replace('_', ' ', (string) $value)))
            ->values();

        return $parts->isEmpty()
            ? 'Filters: All records in range'
            : 'Filters: '.$parts->implode(' · ');
    }

    private function periodLabel(string $startDate, string $endDate): string
    {
        $start = \Carbon\Carbon::parse($startDate)->format('M j, Y');
        $end = \Carbon\Carbon::parse($endDate)->format('M j, Y');

        return $startDate === $endDate ? $start : $start.' – '.$end;
    }

    private function csvFilename(string $prefix, ?string $startDate = null, ?string $endDate = null, string $extension = 'csv'): string
    {
        if ($startDate && $endDate) {
            $range = $startDate === $endDate
                ? $startDate
                : $startDate.'_to_'.$endDate;

            return $prefix.'-'.$range.'.'.$extension;
        }

        return $prefix.'-'.now()->format('Y-m-d').'.'.$extension;
    }

    private function csvDate(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        return \Carbon\Carbon::parse($value)->format('M j, Y');
    }

    private function csvDateTime(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        return \Carbon\Carbon::parse($value)->format('M j, Y g:i A');
    }

    private function streamCsv(
        string $filename,
        string $reportTitle,
        string $periodLabel,
        string $filterSummary,
        array $headers,
        Collection $rows,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($reportTitle, $periodLabel, $filterSummary, $headers, $rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens accented characters correctly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['APO Security Agency']);
            fputcsv($handle, ['Kidapawan City, Cotabato']);
            fputcsv($handle, [$reportTitle]);
            fputcsv($handle, ['Reporting Period', $periodLabel]);
            fputcsv($handle, ['Generated', now()->timezone(config('app.timezone'))->format('M j, Y g:i A')]);
            fputcsv($handle, ['Prepared By', auth()->user()?->name ?? 'System']);
            fputcsv($handle, ['Notes', $filterSummary]);
            fputcsv($handle, []); // blank separator row
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                $values = is_array($row) ? $row : $row->toArray();
                fputcsv($handle, array_map(
                    fn ($value) => $value === null ? '' : (is_scalar($value) ? (string) $value : json_encode($value)),
                    $values
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
