import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatusBadge from '@/Components/StatusBadge';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Security Personnel</span>
    </>
);

function Pagination({ links = [] }) {
    if (!links?.length || links.length <= 3) return null;

    return (
        <div className="mt-6 flex flex-wrap gap-1">
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

function personnelTone(person) {
    if (person.is_probation) return 'probation';
    if (person.status_label?.toLowerCase() === 'active') return 'active';
    return 'off_duty';
}

export default function Index({ personnel = { data: [], links: [] }, filters = {} }) {
    const submitSearch = (e) => {
        e.preventDefault();
        const data = new FormData(e.target);
        router.get('/personnel', { search: data.get('search') }, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                        Security Personnel
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Manage guard profiles, ranks, zones, and post assignments.
                    </p>
                </div>
                <Link
                    href="/personnel/create"
                    className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600"
                >
                    <span className="text-lg leading-none">+</span>
                    Add Personnel
                </Link>
            </div>

            <form onSubmit={submitSearch} className="mb-6">
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
                        name="search"
                        defaultValue={filters.search ?? ''}
                        placeholder="Search by name or rank..."
                        className="w-full rounded-2xl border-0 bg-white py-4 pl-11 pr-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
                    />
                </div>
            </form>

            <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                {!personnel.data?.length ? (
                    <div className="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <p className="text-slate-500">No security personnel found.</p>
                        <Link
                            href="/personnel/create"
                            className="mt-4 inline-flex items-center text-sm font-semibold text-blue-500 hover:text-blue-600"
                        >
                            + Add your first guard
                        </Link>
                    </div>
                ) : (
                    personnel.data.map((person) => (
                        <div
                            key={person.id}
                            className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex min-w-0 items-center gap-3">
                                    {person.face_image_url ? (
                                        <img
                                            src={person.face_image_url}
                                            alt={person.name}
                                            className="h-12 w-12 shrink-0 rounded-xl object-cover ring-1 ring-slate-200"
                                        />
                                    ) : (
                                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#1e4fd8] text-sm font-extrabold text-white">
                                            {person.initials}
                                        </div>
                                    )}
                                    <div className="min-w-0">
                                        <p className="truncate font-bold text-slate-900">{person.name}</p>
                                        <p className="text-sm text-slate-500">
                                            {person.rank}
                                            {person.face_enrolled ? ' · Face enrolled' : ''}
                                        </p>
                                    </div>
                                </div>
                                <StatusBadge
                                    label={(person.status_label || '').toUpperCase()}
                                    tone={personnelTone(person)}
                                />
                            </div>

                            <div className="mt-5 grid grid-cols-3 gap-3 border-y border-slate-100 py-4">
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wide text-slate-400">Zone</p>
                                    <p className="mt-1 text-sm font-semibold text-slate-800">{person.zone}</p>
                                </div>
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wide text-slate-400">Joined</p>
                                    <p className="mt-1 text-sm font-semibold text-slate-800">{person.joined}</p>
                                </div>
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wide text-slate-400">Posts</p>
                                    <p className="mt-1 text-sm font-semibold text-slate-800">{person.posts}</p>
                                </div>
                            </div>

                            <div className="mt-4 grid grid-cols-2 gap-3">
                                <a
                                    href={`/personnel/${person.id}`}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    View Profile
                                </a>
                                <a
                                    href={`/deployments/create?security_personnel_id=${person.id}`}
                                    className="inline-flex items-center justify-center rounded-xl bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-600 transition hover:bg-blue-100"
                                >
                                    Assign Post
                                </a>
                            </div>
                        </div>
                    ))
                )}
            </div>

            <Pagination links={personnel.links} />
        </AdminLayout>
    );
}
