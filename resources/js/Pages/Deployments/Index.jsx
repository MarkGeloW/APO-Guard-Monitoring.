import { useEffect, useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatusBadge from '@/Components/StatusBadge';
import { confirmDelete } from '@/lib/sweetalert';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Deployment Management</span>
    </>
);

function Pagination({ links = [] }) {
    if (!links?.length || links.length <= 3) return null;

    return (
        <div className="mt-4 flex flex-wrap gap-1">
            {links.map((link, i) =>
                link.url ? (
                    <Link
                        key={i}
                        href={link.url}
                        preserveState
                        className={`rounded-lg px-3 py-2 text-sm font-semibold ${
                            link.active
                                ? 'bg-blue-500 text-white'
                                : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={i}
                        className="rounded-lg px-3 py-2 text-sm text-slate-300 ring-1 ring-slate-100"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </div>
    );
}

export default function Index({ deployments = { data: [], links: [] }, filters = {} }) {
    const [showFilters, setShowFilters] = useState(
        Boolean(filters.date || filters.display_status || filters.deployment_status),
    );
    const [openMenu, setOpenMenu] = useState(null);
    const menuRef = useRef(null);
    const [form, setForm] = useState({
        search: filters.search ?? '',
        date: filters.date ?? '',
        display_status: filters.display_status ?? '',
        deployment_status: filters.deployment_status ?? '',
    });

    useEffect(() => {
        const onDocClick = (e) => {
            if (menuRef.current && !menuRef.current.contains(e.target)) {
                setOpenMenu(null);
            }
        };
        document.addEventListener('mousedown', onDocClick);
        return () => document.removeEventListener('mousedown', onDocClick);
    }, []);

    const submitSearch = (e) => {
        e?.preventDefault?.();
        router.get('/deployments', form, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        const empty = { search: '', date: '', display_status: '', deployment_status: '' };
        setForm(empty);
        setShowFilters(false);
        router.get('/deployments', {}, { preserveState: true, replace: true });
    };

    const exportHref = `/deployments?${new URLSearchParams(
        Object.fromEntries(
            Object.entries({ ...form, export: '1' }).filter(([, v]) => v !== '' && v != null),
        ),
    ).toString()}`;

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                        Deployment Management
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Assign and manage security officer duty locations and shift schedules.
                    </p>
                </div>
                <a
                    href="/deployments/create"
                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600"
                >
                    <span className="text-lg leading-none">+</span>
                    New Assignment
                </a>
            </div>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 p-4">
                    <form onSubmit={submitSearch} className="space-y-4">
                        <div className="flex flex-col gap-3 xl:flex-row xl:items-center">
                            <div className="relative flex-1">
                                <svg
                                    className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="m20 20-3.5-3.5" />
                                </svg>
                                <input
                                    type="text"
                                    value={form.search}
                                    onChange={(e) => setForm({ ...form, search: e.target.value })}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') submitSearch(e);
                                    }}
                                    placeholder="Search guard or location..."
                                    className="w-full rounded-xl border-0 bg-slate-50 py-3.5 pl-11 pr-4 text-sm text-slate-700 ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
                                />
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    onClick={() => setShowFilters(!showFilters)}
                                    className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path d="M3 5h18M6 12h12M10 19h4" />
                                    </svg>
                                    Filter
                                </button>
                                <a
                                    href={exportHref}
                                    className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16" />
                                    </svg>
                                    Export
                                </a>
                                {(form.search || form.date || form.display_status || form.deployment_status) && (
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="inline-flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700"
                                    >
                                        Clear
                                    </button>
                                )}
                            </div>
                        </div>

                        {showFilters && (
                            <div className="grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 md:grid-cols-3">
                                <div>
                                    <label className="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                        Date
                                    </label>
                                    <input
                                        type="date"
                                        value={form.date}
                                        onChange={(e) => setForm({ ...form, date: e.target.value })}
                                        className="w-full rounded-xl border-slate-200 text-sm"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                        Display Status
                                    </label>
                                    <select
                                        value={form.display_status}
                                        onChange={(e) => setForm({ ...form, display_status: e.target.value })}
                                        className="w-full rounded-xl border-slate-200 text-sm"
                                    >
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="warning">Warning</option>
                                        <option value="pending">Pending</option>
                                        <option value="absent">Absent</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                        Deployment Status
                                    </label>
                                    <select
                                        value={form.deployment_status}
                                        onChange={(e) => setForm({ ...form, deployment_status: e.target.value })}
                                        className="w-full rounded-xl border-slate-200 text-sm"
                                    >
                                        <option value="">All</option>
                                        <option value="scheduled">Scheduled</option>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div className="md:col-span-3">
                                    <button
                                        type="submit"
                                        className="inline-flex items-center rounded-xl bg-blue-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-600"
                                    >
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        )}
                    </form>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-50/80 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">
                            <tr>
                                <th className="px-5 py-4 text-left">Deploy ID</th>
                                <th className="px-5 py-4 text-left">Guard Name</th>
                                <th className="px-5 py-4 text-left">Duty Location</th>
                                <th className="px-5 py-4 text-left">Date</th>
                                <th className="px-5 py-4 text-left">Shift</th>
                                <th className="px-5 py-4 text-left">Status</th>
                                <th className="px-5 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {!deployments.data?.length ? (
                                <tr>
                                    <td colSpan={7} className="px-5 py-12 text-center text-slate-500">
                                        No deployment assignments found.
                                    </td>
                                </tr>
                            ) : (
                                deployments.data.map((deployment) => (
                                    <tr key={deployment.id} className="hover:bg-slate-50/60">
                                        <td className="px-5 py-4 font-mono text-xs font-semibold text-slate-500">
                                            {deployment.deploy_code}
                                        </td>
                                        <td className="px-5 py-4 font-bold text-slate-900">{deployment.guard_name}</td>
                                        <td className="px-5 py-4 text-slate-600">{deployment.location}</td>
                                        <td className="px-5 py-4 text-slate-600">{deployment.date}</td>
                                        <td className="px-5 py-4 font-medium text-slate-700">
                                            {(deployment.shift || '').replace('-', '–')}
                                        </td>
                                        <td className="px-5 py-4">
                                            <StatusBadge
                                                label={(deployment.display_status_label || '').toUpperCase()}
                                                tone={deployment.display_status}
                                            />
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <div className="relative inline-block text-left" ref={openMenu === deployment.id ? menuRef : null}>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setOpenMenu(openMenu === deployment.id ? null : deployment.id)
                                                    }
                                                    className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                    aria-label="Actions"
                                                >
                                                    <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <circle cx="4" cy="10" r="1.5" />
                                                        <circle cx="10" cy="10" r="1.5" />
                                                        <circle cx="16" cy="10" r="1.5" />
                                                    </svg>
                                                </button>
                                                {openMenu === deployment.id && (
                                                    <div className="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                                        <a
                                                            href={`/deployments/${deployment.id}/edit`}
                                                            className="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
                                                        >
                                                            Edit Assignment
                                                        </a>
                                                        <button
                                                            type="button"
                                                            className="block w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"
                                                            onClick={async () => {
                                                                if (await confirmDelete('Delete this deployment?', 'Delete deployment?')) {
                                                                    router.delete(`/deployments/${deployment.id}`);
                                                                }
                                                            }}
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination links={deployments.links} />
        </AdminLayout>
    );
}
