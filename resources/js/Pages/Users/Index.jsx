import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import { confirmAction, confirmDelete } from '@/lib/sweetalert';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">User Management</span>
    </>
);

export default function Index({ users = [], stats = {}, search = '' }) {
    const { auth } = usePage().props;
    const currentUserId = auth.user.id;

    const submitSearch = (e) => {
        e.preventDefault();
        const data = new FormData(e.target);
        router.get('/users', { search: data.get('search') }, { preserveState: true });
    };

    return (
        <AdminLayout header="User Management" breadcrumb={breadcrumb}>
            <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">User Management</h2>
                    <p className="mt-1 text-sm text-slate-500">Manage system accounts, roles, and access permissions.</p>
                </div>
                <a
                    href="/users/create"
                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600"
                >
                    <span className="text-lg leading-none">+</span>
                    Create Account
                </a>
            </div>

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Total Accounts"
                    value={stats.total ?? 0}
                    iconBg="bg-blue-50 text-blue-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
                        </svg>
                    }
                />
                <StatCard
                    label="Administrators"
                    value={stats.admins ?? 0}
                    valueClass="text-purple-600"
                    iconBg="bg-purple-50 text-purple-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M12 3 5.5 5.4v5.2c0 4.1 2.6 7.8 6.5 9.4 3.9-1.6 6.5-5.3 6.5-9.4V5.4L12 3Z" />
                        </svg>
                    }
                />
                <StatCard
                    label="Supervisors"
                    value={stats.supervisors ?? 0}
                    valueClass="text-blue-600"
                    iconBg="bg-blue-50 text-blue-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M12 14a4 4 0 0 0 4-4V7a4 4 0 1 0-8 0v3a4 4 0 0 0 4 4Zm-7 6a7 7 0 0 1 14 0" />
                        </svg>
                    }
                />
                <StatCard
                    label="Guards"
                    value={stats.guards ?? 0}
                    valueClass="text-emerald-600"
                    iconBg="bg-emerald-50 text-emerald-500"
                    icon={
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
                        </svg>
                    }
                />
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
                        defaultValue={search}
                        placeholder="Search by name, username or role..."
                        className="w-full rounded-2xl border-0 bg-white py-4 pl-11 pr-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
                    />
                </div>
            </form>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-50/80 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">
                            <tr>
                                <th className="px-5 py-4 text-left">User ID</th>
                                <th className="px-5 py-4 text-left">Full Name</th>
                                <th className="px-5 py-4 text-left">Username</th>
                                <th className="px-5 py-4 text-left">Role</th>
                                <th className="px-5 py-4 text-left">Status</th>
                                <th className="px-5 py-4 text-left">Joined</th>
                                <th className="px-5 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {users.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-5 py-12 text-center text-slate-500">
                                        No users found matching your search.
                                    </td>
                                </tr>
                            ) : (
                                users.map((user) => (
                                    <tr key={user.id} className="hover:bg-slate-50/60">
                                        <td className="px-5 py-4 font-mono text-xs font-semibold text-slate-500">{user.user_code}</td>
                                        <td className="px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                <div
                                                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-extrabold text-white ${user.avatar_classes}`}
                                                >
                                                    {user.initials}
                                                </div>
                                                <span className="font-bold text-slate-900">{user.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 font-medium text-slate-600">{user.username}</td>
                                        <td className="px-5 py-4">
                                            <StatusBadge label={user.role_label} tone={user.role_tone} />
                                        </td>
                                        <td className="px-5 py-4">
                                            {user.is_active ? (
                                                <StatusBadge label="Active" tone="active" />
                                            ) : (
                                                <StatusBadge label="Inactive" tone="off_duty" />
                                            )}
                                        </td>
                                        <td className="px-5 py-4 text-slate-600">{user.joined}</td>
                                        <td className="px-5 py-4">
                                            <div className="flex flex-wrap items-center justify-end gap-2">
                                                <a
                                                    href={`/users/${user.id}/edit`}
                                                    className="inline-flex items-center rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-600 transition hover:bg-blue-50"
                                                >
                                                    Edit
                                                </a>
                                                {user.id !== currentUserId && (
                                                    <>
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-bold text-amber-600 transition hover:bg-amber-50"
                                                            onClick={async () => {
                                                                const action = user.is_active ? 'Deactivate' : 'Activate';
                                                                if (
                                                                    await confirmAction({
                                                                        title: `${action} user?`,
                                                                        text: `Are you sure you want to ${action.toLowerCase()} ${user.name}?`,
                                                                        confirmText: action,
                                                                        confirmColor: '#d97706',
                                                                    })
                                                                ) {
                                                                    router.patch(`/users/${user.id}/toggle-status`);
                                                                }
                                                            }}
                                                        >
                                                            {user.is_active ? 'Deactivate' : 'Activate'}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-50"
                                                            onClick={async () => {
                                                                if (await confirmDelete('Delete this user account?', 'Delete user?')) {
                                                                    router.delete(`/users/${user.id}`);
                                                                }
                                                            }}
                                                        >
                                                            Delete
                                                        </button>
                                                    </>
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
        </AdminLayout>
    );
}
