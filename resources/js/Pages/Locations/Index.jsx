import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatusBadge from '@/Components/StatusBadge';
import StatCard from '@/Components/StatCard';
import { confirmDelete } from '@/lib/sweetalert';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
            <circle cx="12" cy="11" r="2.5" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Deployment Locations</span>
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

export default function Index({
    locations = { data: [], links: [] },
    filters = {},
    stats = {},
}) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch = (e) => {
        e.preventDefault();
        router.get('/locations', { search }, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                        Deployment Locations
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Manage duty posts with GIS coordinates and GPS geofence radius.
                    </p>
                </div>
                <a
                    href="/locations/create"
                    className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600"
                >
                    <span className="text-lg leading-none">+</span>
                    Add Location
                </a>
            </div>

            <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <StatCard
                    label="Total Locations"
                    value={stats.total ?? 0}
                    iconBg="bg-blue-50 text-blue-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
                            <circle cx="12" cy="11" r="2.5" />
                        </svg>
                    }
                />
                <StatCard
                    label="Active Posts"
                    value={stats.active ?? 0}
                    valueClass="text-emerald-600"
                    iconBg="bg-emerald-50 text-emerald-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="m5 13 4 4L19 7" />
                        </svg>
                    }
                />
                <StatCard
                    label="Avg Geofence"
                    value={`${stats.avg_radius ?? 0}m`}
                    iconBg="bg-amber-50 text-amber-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="8" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    }
                />
            </div>

            <form onSubmit={submitSearch} className="mb-5">
                <div className="relative">
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
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search location, client, or address..."
                        className="w-full rounded-2xl border-0 bg-white py-4 pl-11 pr-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
                    />
                </div>
            </form>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                {!locations.data?.length ? (
                    <div className="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <p className="text-slate-500">No deployment locations found.</p>
                        <a href="/locations/create" className="mt-4 inline-flex text-sm font-semibold text-blue-500 hover:text-blue-600">
                            + Add your first location
                        </a>
                    </div>
                ) : (
                    locations.data.map((location) => (
                        <div
                            key={location.id}
                            className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex min-w-0 items-start gap-3">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                            <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
                                            <circle cx="12" cy="11" r="2.5" />
                                        </svg>
                                    </div>
                                    <div className="min-w-0">
                                        <p className="truncate font-bold text-slate-900">{location.name}</p>
                                        <p className="mt-0.5 text-sm text-slate-500">
                                            {location.client_name || 'No client set'}
                                        </p>
                                    </div>
                                </div>
                                <StatusBadge
                                    label={(location.status || '').toUpperCase()}
                                    tone={location.status === 'active' ? 'active' : 'off_duty'}
                                />
                            </div>

                            <p className="mt-4 line-clamp-2 text-sm text-slate-600">{location.address}</p>

                            <div className="mt-4 grid grid-cols-3 gap-3 border-y border-slate-100 py-4">
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wide text-slate-400">Coordinates</p>
                                    <p className="mt-1 font-mono text-xs font-semibold text-slate-700">
                                        {Number(location.latitude).toFixed(4)}, {Number(location.longitude).toFixed(4)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wide text-slate-400">Geofence</p>
                                    <p className="mt-1 text-sm font-semibold text-slate-800">{location.geofence_radius}m</p>
                                </div>
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wide text-slate-400">Posts</p>
                                    <p className="mt-1 text-sm font-semibold text-slate-800">{location.deployments_count}</p>
                                </div>
                            </div>

                            <div className="mt-4 flex flex-wrap gap-2">
                                <a
                                    href={`/locations/${location.id}/edit`}
                                    className="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Edit Location
                                </a>
                                <button
                                    type="button"
                                    className="inline-flex items-center rounded-xl border border-red-100 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100"
                                    onClick={async () => {
                                        if (await confirmDelete('Delete this location?', 'Delete location?')) {
                                            router.delete(`/locations/${location.id}`);
                                        }
                                    }}
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    ))
                )}
            </div>

            <Pagination links={locations.links} />
        </AdminLayout>
    );
}
