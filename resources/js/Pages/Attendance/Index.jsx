import { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatusBadge from '@/Components/StatusBadge';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Attendance Monitoring</span>
    </>
);

function FaceThumb({ url, label, onOpen }) {
    if (!url) {
        return <span className="text-xs font-semibold text-slate-300">—</span>;
    }

    return (
        <button
            type="button"
            onClick={() => onOpen({ url, label })}
            className="group relative inline-flex overflow-hidden rounded-xl ring-1 ring-slate-200 transition hover:ring-blue-300"
            title={`View ${label}`}
        >
            <img src={url} alt={label} className="h-12 w-12 object-cover" />
            <span className="absolute inset-0 flex items-center justify-center bg-slate-900/0 text-[10px] font-bold uppercase tracking-wide text-white opacity-0 transition group-hover:bg-slate-900/45 group-hover:opacity-100">
                View
            </span>
        </button>
    );
}

export default function Index({
    records = [],
    date,
    dateValue,
    filter = 'all',
    shift = 'all',
    shiftLabel,
    isLiveDay = false,
    exportPdfUrl,
    exportCsvUrl,
    exportUrl,
    tabCounts = {},
    tabs = {},
}) {
    const [preview, setPreview] = useState(null);

    const changeFilter = (key) => {
        router.get(
            '/attendance',
            {
                filter: key,
                ...(dateValue ? { date: dateValue } : {}),
                ...(shift && shift !== 'all' ? { shift } : {}),
            },
            { preserveState: true, replace: true },
        );
    };

    const pdfHref = exportPdfUrl || exportUrl;
    const csvHref = exportCsvUrl;

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                        Attendance Monitoring
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Face + GPS verified check-in records — {date} · {shiftLabel}
                        {isLiveDay ? ' · includes overnight shifts still on duty' : ''}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {csvHref && (
                        <a
                            href={csvHref}
                            className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            Export CSV
                        </a>
                    )}
                    {pdfHref && (
                        <a
                            href={pdfHref}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#14233a] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e3354]"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" />
                                <path d="M14 3v5h5M9 13h6M9 17h4" />
                            </svg>
                            Download PDF
                        </a>
                    )}
                </div>
            </div>

            <div className="mb-5 flex flex-wrap gap-2">
                {Object.entries(tabs).map(([key, label]) => {
                    const count = tabCounts[key];
                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() => changeFilter(key)}
                            className={`inline-flex items-center gap-2 rounded-full px-4 py-2 text-[11px] font-bold uppercase tracking-[.14em] transition ${
                                filter === key
                                    ? 'bg-[#14233a] text-white shadow-md'
                                    : 'bg-white text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-slate-700'
                            }`}
                        >
                            {label}
                            {typeof count === 'number' && (
                                <span
                                    className={`rounded-full px-1.5 py-0.5 text-[10px] ${
                                        filter === key ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'
                                    }`}
                                >
                                    {count}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-50/80 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">
                            <tr>
                                <th className="px-5 py-4 text-left">Guard ID</th>
                                <th className="px-5 py-4 text-left">Name</th>
                                <th className="px-5 py-4 text-left">Assigned Post</th>
                                <th className="px-5 py-4 text-left">Time In</th>
                                <th className="px-5 py-4 text-left">Time Out</th>
                                <th className="px-5 py-4 text-left">Face In</th>
                                <th className="px-5 py-4 text-left">Face Out</th>
                                <th className="px-5 py-4 text-left">Shift</th>
                                <th className="px-5 py-4 text-center">Verified</th>
                                <th className="px-5 py-4 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {records.length === 0 ? (
                                <tr>
                                    <td colSpan={10} className="px-5 py-12 text-center text-slate-500">
                                        No attendance records found for this filter.
                                    </td>
                                </tr>
                            ) : (
                                records.map((record) => (
                                    <tr key={record.id} className="hover:bg-slate-50/60">
                                        <td className="px-5 py-4 font-mono text-xs font-semibold text-slate-500">
                                            {record.guard_id}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                {record.face_enrolled_url ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setPreview({
                                                                url: record.face_enrolled_url,
                                                                label: `${record.name} · enrolled face`,
                                                            })
                                                        }
                                                        className="overflow-hidden rounded-full ring-1 ring-slate-200"
                                                    >
                                                        <img
                                                            src={record.face_enrolled_url}
                                                            alt=""
                                                            className="h-9 w-9 object-cover"
                                                        />
                                                    </button>
                                                ) : (
                                                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-400">
                                                        —
                                                    </span>
                                                )}
                                                <span className="font-bold text-slate-900">{record.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 text-slate-600">{record.post}</td>
                                        <td className="px-5 py-4 font-medium text-slate-700">
                                            {record.time_in ?? '—'}
                                        </td>
                                        <td className="px-5 py-4 font-medium text-slate-700">
                                            {record.time_out ?? '—'}
                                        </td>
                                        <td className="px-5 py-4">
                                            <FaceThumb
                                                url={record.time_in_face_url}
                                                label={`${record.name} · check-in face`}
                                                onOpen={setPreview}
                                            />
                                        </td>
                                        <td className="px-5 py-4">
                                            <FaceThumb
                                                url={record.time_out_face_url}
                                                label={`${record.name} · check-out face`}
                                                onOpen={setPreview}
                                            />
                                        </td>
                                        <td className="px-5 py-4 text-slate-600">{record.shift}</td>
                                        <td className="px-5 py-4">
                                            <div className="flex justify-center gap-1.5">
                                                <span
                                                    title={record.time_in_face_verified ? 'Face verified' : 'Face not verified'}
                                                    className={`inline-flex h-7 w-7 items-center justify-center rounded-full text-white ${
                                                        record.time_in_face_verified ? 'bg-blue-500' : 'bg-slate-300'
                                                    }`}
                                                >
                                                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                        <path d="M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
                                                        <path d="M4 20a8 8 0 0 1 16 0" />
                                                    </svg>
                                                </span>
                                                <span
                                                    title={record.gps_verified ? 'GPS verified' : 'GPS not verified'}
                                                    className={`inline-flex h-7 w-7 items-center justify-center rounded-full text-white ${
                                                        record.gps_verified ? 'bg-emerald-500' : 'bg-slate-300'
                                                    }`}
                                                >
                                                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                                                        <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
                                                        <circle cx="12" cy="11" r="2.5" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <StatusBadge
                                                label={(record.status_label || '').toUpperCase()}
                                                tone={record.tab_status}
                                            />
                                            {record.signal_label && record.signal_label !== 'Not on duty' && (
                                                <p className="mt-1 inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500">
                                                    <span
                                                        className={`h-1.5 w-1.5 rounded-full ${
                                                            record.signal_tone === 'green'
                                                                ? 'bg-emerald-500'
                                                                : record.signal_tone === 'amber'
                                                                  ? 'bg-amber-500'
                                                                  : 'bg-red-500'
                                                        }`}
                                                    />
                                                    {record.signal_label}
                                                </p>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {preview && (
                <div className="fixed inset-0 z-[10000] flex items-center justify-center p-4">
                    <button
                        type="button"
                        className="absolute inset-0 bg-slate-950/60"
                        aria-label="Close face preview"
                        onClick={() => setPreview(null)}
                    />
                    <div className="relative z-[10001] w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                            <div>
                                <p className="text-sm font-extrabold text-slate-900">Face Capture</p>
                                <p className="text-xs text-slate-500">{preview.label}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setPreview(null)}
                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                            >
                                ✕
                            </button>
                        </div>
                        <img src={preview.url} alt={preview.label} className="max-h-[70vh] w-full object-contain bg-slate-50" />
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
