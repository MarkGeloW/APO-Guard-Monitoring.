import { useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/StatCard';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M9 17v-6m3 6V7m3 10v-4M5 21h14a1 1 0 0 0 1-1V6l-4-4H5a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1Z" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Reports & Records</span>
    </>
);

const reportTypes = [
    {
        key: 'deployments',
        title: 'Deployment Schedule',
        description: 'Assignments by date, location, shift, and duty status.',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                <path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z" />
            </svg>
        ),
    },
    {
        key: 'attendance',
        title: 'Attendance & Verification',
        description: 'Check-in/out times with GPS and face verification.',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        ),
    },
    {
        key: 'personnel',
        title: 'Personnel Roster',
        description: 'Guard roster with enrollment and assignment counts.',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
            </svg>
        ),
    },
];

function buildUrl(base, params) {
    const query = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            query.set(key, String(value));
        }
    });
    const qs = query.toString();
    return qs ? `${base}?${qs}` : base;
}

export default function Index({
    stats = {},
    quickLinks = [],
    filters = { locations: [], personnel: [] },
    defaults = {},
    routes = {},
}) {
    const [showGenerate, setShowGenerate] = useState(false);
    const [reportKey, setReportKey] = useState('deployments');
    const [form, setForm] = useState({
        start_date: defaults.start_date || '',
        end_date: defaults.end_date || '',
        location_id: '',
        personnel_id: '',
        status: '',
    });

    const needsDate = reportKey !== 'personnel';

    const queryParams = useMemo(() => {
        if (reportKey === 'personnel') {
            return { status: form.status };
        }

        return {
            start_date: form.start_date,
            end_date: form.end_date,
            location_id: form.location_id,
            personnel_id: form.personnel_id,
            status: form.status,
        };
    }, [form, reportKey]);

    const viewUrl = useMemo(() => {
        const base = routes[reportKey];
        return base ? buildUrl(base, queryParams) : '#';
    }, [queryParams, reportKey, routes]);

    const pdfUrl = useMemo(() => {
        const base = routes[`${reportKey}_pdf`];
        return base ? buildUrl(base, queryParams) : '#';
    }, [queryParams, reportKey, routes]);

    const statusOptions =
        reportKey === 'personnel'
            ? [
                  { value: '', label: 'All statuses' },
                  { value: 'active', label: 'Active' },
                  { value: 'on_leave', label: 'On leave' },
                  { value: 'inactive', label: 'Inactive' },
              ]
            : reportKey === 'attendance'
              ? [
                    { value: '', label: 'All statuses' },
                    { value: 'present', label: 'Present' },
                    { value: 'late', label: 'Late' },
                    { value: 'absent', label: 'Absent' },
                    { value: 'completed', label: 'Completed' },
                ]
              : [
                    { value: '', label: 'All statuses' },
                    { value: 'scheduled', label: 'Scheduled' },
                    { value: 'active', label: 'Active' },
                    { value: 'completed', label: 'Completed' },
                    { value: 'cancelled', label: 'Cancelled' },
                ];

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                        Reports & Records
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Generate official deployment, attendance, and personnel reports with PDF download.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => setShowGenerate(true)}
                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#162d4a] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-300/40 transition hover:bg-[#1d3a5c]"
                >
                    <span className="text-lg leading-none">+</span>
                    Generate Report
                </button>
            </div>

            <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Deployments (Month)"
                    value={stats.deployments_month ?? 0}
                    iconBg="bg-blue-50 text-blue-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z" />
                        </svg>
                    }
                />
                <StatCard
                    label="Attendance (Month)"
                    value={stats.attendance_month ?? 0}
                    iconBg="bg-emerald-50 text-emerald-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    }
                />
                <StatCard
                    label="Active Personnel"
                    value={stats.personnel_active ?? 0}
                    iconBg="bg-slate-100 text-slate-600"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
                        </svg>
                    }
                />
                <StatCard
                    label="Face Verified Today"
                    value={stats.face_verified_today ?? 0}
                    iconBg="bg-amber-50 text-amber-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2" />
                            <circle cx="12" cy="11" r="3" />
                        </svg>
                    }
                />
            </div>

            <div className="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-5 py-4">
                    <h3 className="text-sm font-extrabold uppercase tracking-wide text-slate-900">Quick Reports</h3>
                    <p className="mt-1 text-sm text-slate-500">Open a ready-made report, then download PDF from the toolbar.</p>
                </div>
                <div className="divide-y divide-slate-100">
                    {!quickLinks.length ? (
                        <div className="px-5 py-10 text-center text-slate-500">No quick reports available.</div>
                    ) : (
                        quickLinks.map((link) => (
                            <div key={link.name} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="font-bold text-slate-900">{link.name}</p>
                                    <p className="mt-1 text-sm text-slate-500">{link.description}</p>
                                    <p className="mt-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">{link.type}</p>
                                </div>
                                <a
                                    href={link.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                                >
                                    Open Report
                                </a>
                            </div>
                        ))
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                {reportTypes.map((type) => (
                    <button
                        key={type.key}
                        type="button"
                        onClick={() => {
                            setReportKey(type.key);
                            setShowGenerate(true);
                        }}
                        className="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#162d4a]/30 hover:shadow-md"
                    >
                        <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-[#162d4a]/10 text-[#162d4a]">
                            {type.icon}
                        </div>
                        <h4 className="font-bold text-slate-900">{type.title}</h4>
                        <p className="mt-2 text-sm leading-relaxed text-slate-500">{type.description}</p>
                    </button>
                ))}
            </div>

            {showGenerate && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <button
                        type="button"
                        className="absolute inset-0 bg-slate-900/50"
                        aria-label="Close overlay"
                        onClick={() => setShowGenerate(false)}
                    />
                    <div className="relative w-full max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div className="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-xl font-extrabold uppercase tracking-wide text-slate-900">
                                    Generate Report
                                </h3>
                                <p className="mt-1 text-sm text-slate-500">
                                    Choose filters, then view online or download a professional PDF.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setShowGenerate(false)}
                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                aria-label="Close"
                            >
                                &times;
                            </button>
                        </div>

                        <div className="mb-5 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            {reportTypes.map((type) => (
                                <button
                                    key={type.key}
                                    type="button"
                                    onClick={() => setReportKey(type.key)}
                                    className={`rounded-xl border px-3 py-3 text-left text-sm font-bold transition ${
                                        reportKey === type.key
                                            ? 'border-[#162d4a] bg-[#162d4a] text-white'
                                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                    }`}
                                >
                                    {type.title}
                                </button>
                            ))}
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            {needsDate && (
                                <>
                                    <label className="block text-xs font-bold uppercase tracking-wide text-slate-500">
                                        Start date
                                        <input
                                            type="date"
                                            value={form.start_date}
                                            onChange={(e) => setForm((prev) => ({ ...prev, start_date: e.target.value }))}
                                            className="mt-1 w-full rounded-xl border-slate-200 text-sm"
                                        />
                                    </label>
                                    <label className="block text-xs font-bold uppercase tracking-wide text-slate-500">
                                        End date
                                        <input
                                            type="date"
                                            value={form.end_date}
                                            onChange={(e) => setForm((prev) => ({ ...prev, end_date: e.target.value }))}
                                            className="mt-1 w-full rounded-xl border-slate-200 text-sm"
                                        />
                                    </label>
                                    <label className="block text-xs font-bold uppercase tracking-wide text-slate-500">
                                        Location
                                        <select
                                            value={form.location_id}
                                            onChange={(e) => setForm((prev) => ({ ...prev, location_id: e.target.value }))}
                                            className="mt-1 w-full rounded-xl border-slate-200 text-sm"
                                        >
                                            <option value="">All locations</option>
                                            {(filters.locations || []).map((location) => (
                                                <option key={location.id} value={location.id}>
                                                    {location.name}
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                    <label className="block text-xs font-bold uppercase tracking-wide text-slate-500">
                                        Guard
                                        <select
                                            value={form.personnel_id}
                                            onChange={(e) => setForm((prev) => ({ ...prev, personnel_id: e.target.value }))}
                                            className="mt-1 w-full rounded-xl border-slate-200 text-sm"
                                        >
                                            <option value="">All guards</option>
                                            {(filters.personnel || []).map((person) => (
                                                <option key={person.id} value={person.id}>
                                                    {person.name} ({person.badge})
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                </>
                            )}
                            <label className={`block text-xs font-bold uppercase tracking-wide text-slate-500 ${needsDate ? '' : 'md:col-span-2'}`}>
                                Status
                                <select
                                    value={form.status}
                                    onChange={(e) => setForm((prev) => ({ ...prev, status: e.target.value }))}
                                    className="mt-1 w-full rounded-xl border-slate-200 text-sm"
                                >
                                    {statusOptions.map((option) => (
                                        <option key={option.value || 'all'} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>

                        <div className="mt-6 flex flex-wrap items-center justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowGenerate(false)}
                                className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <a
                                href={viewUrl}
                                target="_blank"
                                rel="noreferrer"
                                className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                            >
                                View Report
                            </a>
                            <a
                                href={pdfUrl}
                                className="rounded-xl bg-[#162d4a] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1d3a5c]"
                            >
                                Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
