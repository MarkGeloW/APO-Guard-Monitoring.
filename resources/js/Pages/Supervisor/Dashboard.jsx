import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/StatCard';
import LiveDeploymentMap from '@/Components/LiveDeploymentMap';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Bar, Line, Doughnut } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend, Filler);

const alertStyles = {
    critical: { card: 'border-red-100 bg-red-50/80', icon: 'bg-red-100 text-red-600' },
    warning: { card: 'border-amber-100 bg-amber-50/80', icon: 'bg-amber-100 text-amber-600' },
    success: { card: 'border-emerald-100 bg-emerald-50/80', icon: 'bg-emerald-100 text-emerald-600' },
    info: { card: 'border-blue-100 bg-blue-50/80', icon: 'bg-blue-100 text-blue-600' },
};

function AlertIcon({ severity }) {
    if (severity === 'critical') {
        return (
            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
        );
    }
    if (severity === 'warning') {
        return (
            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path d="M12 9v4m0 4h.01M10.3 4.7 2.6 18a1 1 0 0 0 .9 1.5h16.9a1 1 0 0 0 .9-1.5L13.7 4.7a1 1 0 0 0-1.8 0Z" />
            </svg>
        );
    }
    if (severity === 'success') {
        return (
            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                <path d="m5 13 4 4L19 7" />
            </svg>
        );
    }
    return (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9m14.2 14.2c3.9-3.9 3.9-10.3 0-14.2M8.5 15.5l7-7" />
        </svg>
    );
}

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Overview Dashboard</span>
    </>
);

export default function SupervisorDashboard({
    stats = {},
    mapData,
    alerts = [],
    weeklyAttendance = [],
    checkInTrend = {},
    zoneCoverage = [],
}) {
    const criticalCount = alerts.filter((a) => a.severity === 'critical').length;

    const weeklyChartData = {
        labels: weeklyAttendance.map((item) => item.label),
        datasets: [
            {
                label: 'Present',
                data: weeklyAttendance.map((item) => item.present),
                backgroundColor: '#3b82f6',
                borderRadius: 6,
            },
            {
                label: 'Late',
                data: weeklyAttendance.map((item) => item.late),
                backgroundColor: '#f59e0b',
                borderRadius: 6,
            },
        ],
    };

    const trendChartData = {
        labels: Object.keys(checkInTrend),
        datasets: [
            {
                label: 'Check-ins',
                data: Object.values(checkInTrend),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
            },
        ],
    };

    const zoneChartData = {
        labels: zoneCoverage.map((item) => item.zone),
        datasets: [
            {
                data: zoneCoverage.map((item) => item.count),
                backgroundColor: zoneCoverage.map((item) => item.color),
                borderWidth: 0,
            },
        ],
    };

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="On Duty Now"
                    value={stats.on_duty ?? 0}
                    sub={`of ${stats.deployed_today ?? 0} deployed today`}
                    hint={`${stats.on_duty_delta >= 0 ? '+' : ''}${stats.on_duty_delta ?? 0} vs yesterday`}
                    hintClass={stats.on_duty_delta >= 0 ? 'text-emerald-600' : 'text-red-500'}
                    valueClass="text-emerald-600"
                    iconBg="bg-emerald-50 text-emerald-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
                        </svg>
                    }
                />
                <StatCard
                    label="Late Arrivals"
                    value={stats.late_count ?? 0}
                    sub={[stats.late_name ?? 'No late arrivals', stats.late_location].filter(Boolean).join(' · ')}
                    hint={`${stats.late_delta >= 0 ? '+' : ''}${stats.late_delta ?? 0} vs yesterday`}
                    hintClass={stats.late_delta <= 0 ? 'text-emerald-600' : 'text-amber-600'}
                    valueClass="text-amber-500"
                    iconBg="bg-amber-50 text-amber-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    }
                />
                <StatCard
                    label="Absent"
                    value={stats.absent_count ?? 0}
                    sub={stats.absent_name ?? 'All guards accounted for'}
                    hint={stats.absent_count > 0 ? 'Relief dispatched' : 'Coverage clear'}
                    hintClass={stats.absent_count > 0 ? 'text-red-500' : 'text-emerald-600'}
                    valueClass="text-red-500"
                    iconBg="bg-red-50 text-red-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="m15 9-6 6m0-6 6 6" />
                        </svg>
                    }
                />
                <StatCard
                    label="Active Posts"
                    value={stats.active_posts ?? 0}
                    sub={`across ${stats.zones_today ?? 0} zones today`}
                    hint="Full coverage"
                    hintClass="text-blue-500"
                    valueClass="text-blue-500"
                    iconBg="bg-blue-50 text-blue-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
                            <circle cx="12" cy="11" r="2.5" />
                        </svg>
                    }
                />
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div className="xl:col-span-2">
                    <LiveDeploymentMap mapData={mapData} />
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div className="flex items-center gap-2">
                            <svg className="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
                            </svg>
                            <h2 className="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Active Alerts</h2>
                        </div>
                        {criticalCount > 0 && (
                            <span className="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-red-500">
                                {criticalCount} Critical
                            </span>
                        )}
                    </div>

                    <div className="max-h-[420px] space-y-2.5 overflow-y-auto p-4">
                        {alerts.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                                <p className="text-sm font-semibold text-slate-700">No active alerts</p>
                                <p className="mt-1 text-xs text-slate-500">Attendance and deployment issues will show here.</p>
                            </div>
                        ) : (
                            alerts.map((alert, i) => {
                                const styles = alertStyles[alert.severity] ?? alertStyles.info;
                                const label =
                                    alert.severity === 'critical'
                                        ? 'Critical'
                                        : alert.severity === 'warning'
                                          ? 'Warning'
                                          : alert.severity === 'success'
                                            ? 'Update'
                                            : 'Info';
                                return (
                                    <div
                                        key={i}
                                        className={`relative overflow-hidden rounded-xl border bg-white px-3.5 py-3 shadow-sm ${styles.card}`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${styles.icon}`}>
                                                <AlertIcon severity={alert.severity} />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="mb-1 flex items-center gap-2">
                                                    <span className="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                                        {label}
                                                    </span>
                                                    <span className="text-[10px] font-semibold text-slate-400">
                                                        {alert.time_label ?? alert.time}
                                                    </span>
                                                </div>
                                                <p className="text-sm font-semibold leading-5 text-slate-900">{alert.message}</p>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Weekly Attendance</h2>
                    <div className="mt-4 h-56">
                        <Bar
                            data={weeklyChartData}
                            options={{
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: { boxWidth: 10, font: { size: 11, weight: '600' } },
                                    },
                                },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
                                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                                },
                            }}
                        />
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Check-in Trend Today</h2>
                    <div className="mt-4 h-56">
                        <Line
                            data={trendChartData}
                            options={{
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' } } },
                                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                                },
                            }}
                        />
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Zone Coverage</h2>
                    <div className="mt-4 h-56">
                        <Doughnut
                            data={zoneChartData}
                            options={{
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '62%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: { boxWidth: 10, font: { size: 11, weight: '600' } },
                                    },
                                },
                            }}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
