<x-admin-layout>
    <x-slot name="header">Admin Dashboard</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Overview Dashboard</span>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">On Duty Now</p>
                    <p class="mt-2 text-4xl font-extrabold text-emerald-600">{{ $stats['on_duty'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">of {{ $stats['deployed_today'] }} deployed today</p>
                    <p class="mt-2 text-xs font-semibold {{ $stats['on_duty_delta'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $stats['on_duty_delta'] >= 0 ? '+' : '' }}{{ $stats['on_duty_delta'] }} vs yesterday
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Late Arrivals</p>
                    <p class="mt-2 text-4xl font-extrabold text-amber-500">{{ $stats['late_count'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stats['late_name'] ?? 'No late arrivals' }}@if($stats['late_location']) · {{ $stats['late_location'] }}@endif</p>
                    <p class="mt-2 text-xs font-semibold {{ $stats['late_delta'] <= 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $stats['late_delta'] >= 0 ? '+' : '' }}{{ $stats['late_delta'] }} vs yesterday
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Absent</p>
                    <p class="mt-2 text-4xl font-extrabold text-red-500">{{ $stats['absent_count'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stats['absent_name'] ?? 'All guards accounted for' }}</p>
                    <p class="mt-2 text-xs font-semibold text-red-500">Relief dispatched</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="m15 9-6 6m0-6 6 6"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Active Posts</p>
                    <p class="mt-2 text-4xl font-extrabold text-blue-500">{{ $stats['active_posts'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">across {{ $stats['zones_today'] }} zones today</p>
                    <p class="mt-2 text-xs font-semibold text-blue-500">Full coverage</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Live Deployment Map</h2>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Live
                    </span>
                </div>
                <a href="{{ route('monitoring.index') }}" class="text-xs font-semibold text-blue-500 hover:text-blue-600">Open Full Map</a>
            </div>
            <div id="dashboard-map" class="h-[360px]"></div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                <span>7.01°N · 125.09°E · Kidapawan City</span>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> On Duty</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Late</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span> Absent</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Active Alerts</h2>
                <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-red-500">{{ $alerts->where('severity', 'critical')->count() }} Critical</span>
            </div>
            <div class="max-h-[360px] space-y-3 overflow-y-auto p-4">
                @forelse($alerts as $alert)
                    @php
                        $styles = match($alert['severity']) {
                            'critical' => 'border-red-200 bg-red-50 text-red-700',
                            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            default => 'border-blue-200 bg-blue-50 text-blue-700',
                        };
                    @endphp
                    <div class="rounded-xl border px-4 py-3 {{ $styles }}">
                        <p class="text-sm font-medium leading-5">{{ $alert['message'] }}</p>
                        <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide opacity-70">{{ $alert['time']->format('h:i A') }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No active alerts right now.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Weekly Attendance</h2>
            <div class="mt-4 h-56"><canvas id="weeklyAttendanceChart"></canvas></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Check-in Trend Today</h2>
            <div class="mt-4 h-56"><canvas id="checkInTrendChart"></canvas></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Zone Coverage</h2>
            <div class="mt-4 h-56"><canvas id="zoneCoverageChart"></canvas></div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const mapData = @json($mapData);
        const statusColors = {
            present: '#10b981',
            completed: '#10b981',
            late: '#f59e0b',
            pending: '#ef4444',
            absent: '#ef4444',
        };

        const map = L.map('dashboard-map', { zoomControl: false }).setView([7.0083, 125.0928], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        mapData.locations.forEach((location) => {
            L.circle([location.lat, location.lng], {
                radius: location.radius,
                color: '#94a3b8',
                fillColor: '#cbd5e1',
                fillOpacity: 0.18,
                weight: 1,
            }).addTo(map).bindPopup(`<strong>${location.zone}</strong><br>${location.name}`);

            L.marker([location.lat, location.lng]).addTo(map);
        });

        mapData.guards.forEach((guard) => {
            const color = statusColors[guard.status] || '#64748b';
            const icon = L.divIcon({
                className: '',
                html: `<div style="background:${color};width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.25)"></div>`
            });

            L.marker([guard.lat, guard.lng], { icon })
                .bindPopup(`<strong>${guard.name}</strong><br>${guard.location}<br>${guard.status}`)
                .addTo(map);
        });

        const weeklyData = @json($weeklyAttendance);
        new Chart(document.getElementById('weeklyAttendanceChart'), {
            type: 'bar',
            data: {
                labels: weeklyData.map(item => item.label),
                datasets: [
                    {
                        label: 'Present',
                        data: weeklyData.map(item => item.present),
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                    },
                    {
                        label: 'Late',
                        data: weeklyData.map(item => item.late),
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });

        const trendData = @json($checkInTrend);
        new Chart(document.getElementById('checkInTrendChart'), {
            type: 'line',
            data: {
                labels: Object.keys(trendData),
                datasets: [{
                    label: 'Check-ins',
                    data: Object.values(trendData),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });

        const zoneData = @json($zoneCoverage);
        new Chart(document.getElementById('zoneCoverageChart'), {
            type: 'doughnut',
            data: {
                labels: zoneData.map(item => item.zone),
                datasets: [{
                    data: zoneData.map(item => item.count),
                    backgroundColor: zoneData.map(item => item.color),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    </script>
    @endpush
</x-admin-layout>
