import { Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Swal } from '@/lib/sweetalert';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Notifications</span>
    </>
);

const severityMeta = {
    error: {
        label: 'Needs attention',
        hint: 'Absent posts and critical issues',
        dot: 'bg-rose-500',
        badge: 'bg-rose-50 text-rose-700 ring-rose-100',
        icon: 'bg-rose-50 text-rose-600',
        bar: 'bg-rose-500',
    },
    warning: {
        label: 'Warning',
        hint: 'Late check-ins and delays',
        dot: 'bg-amber-500',
        badge: 'bg-amber-50 text-amber-700 ring-amber-100',
        icon: 'bg-amber-50 text-amber-600',
        bar: 'bg-amber-500',
    },
    success: {
        label: 'Good news',
        hint: 'On-time and completed shifts',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        icon: 'bg-emerald-50 text-emerald-600',
        bar: 'bg-emerald-500',
    },
    info: {
        label: 'Notice',
        hint: 'General updates',
        dot: 'bg-slate-400',
        badge: 'bg-slate-100 text-slate-600 ring-slate-200',
        icon: 'bg-slate-100 text-slate-600',
        bar: 'bg-slate-400',
    },
};

function SeverityIcon({ severity }) {
    if (severity === 'error') {
        return (
            <svg className="h-4.5 w-4.5 h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path d="M12 9v4m0 4h.01M10.3 3.3 1.8 18a1.2 1.2 0 0 0 1.1 1.8h18.2a1.2 1.2 0 0 0 1.1-1.8L13.7 3.3a1.2 1.2 0 0 0-2.1 0Z" />
            </svg>
        );
    }
    if (severity === 'warning') {
        return (
            <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path d="M12 8v5m0 3h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z" />
            </svg>
        );
    }
    if (severity === 'success') {
        return (
            <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                <path d="m5 13 4 4L19 7" />
            </svg>
        );
    }
    return (
        <svg className="h-[18px] w-[18px]" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path d="M12 16v-4m0-4h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z" />
        </svg>
    );
}

function dayGroup(iso) {
    const date = new Date(iso);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    const sameDay = (a, b) =>
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate();

    if (sameDay(date, today)) return 'Today';
    if (sameDay(date, yesterday)) return 'Yesterday';
    return 'Earlier';
}

function matchesTab(alert, tab) {
    if (tab === 'all') return true;
    if (tab === 'unread') return !alert.read;
    if (tab === 'updates') return alert.severity === 'success' || alert.severity === 'info';
    return alert.severity === tab;
}

function markOneRead(id) {
    router.patch(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Marked as read',
                showConfirmButton: false,
                timer: 1600,
                timerProgressBar: true,
            });
        },
    });
}

function markAllRead() {
    router.post('/notifications/read-all', {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'All notifications marked as read',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true,
            });
        },
    });
}

export default function Index({ alerts = [], unreadCount = 0, stats = {} }) {
    const [tab, setTab] = useState(unreadCount > 0 ? 'unread' : 'all');
    const [query, setQuery] = useState('');
    const [refreshing, setRefreshing] = useState(false);

    const tabCounts = useMemo(() => {
        const counts = { all: alerts.length, unread: 0, error: 0, warning: 0, updates: 0 };
        alerts.forEach((alert) => {
            if (!alert.read) counts.unread += 1;
            if (alert.severity === 'error') counts.error += 1;
            else if (alert.severity === 'warning') counts.warning += 1;
            else counts.updates += 1;
        });
        return counts;
    }, [alerts]);

    const tabs = [
        { id: 'all', label: 'All', count: tabCounts.all },
        { id: 'unread', label: 'Unread', count: tabCounts.unread },
        { id: 'error', label: 'Needs attention', count: tabCounts.error },
        { id: 'warning', label: 'Warnings', count: tabCounts.warning },
        { id: 'updates', label: 'Updates', count: tabCounts.updates },
    ];

    const filtered = useMemo(() => {
        const needle = query.trim().toLowerCase();
        return alerts.filter((alert) => {
            if (!matchesTab(alert, tab)) return false;
            if (!needle) return true;
            return (
                (alert.title || '').toLowerCase().includes(needle) ||
                (alert.message || '').toLowerCase().includes(needle)
            );
        });
    }, [alerts, tab, query]);

    const groups = useMemo(() => {
        const order = ['Today', 'Yesterday', 'Earlier'];
        const map = { Today: [], Yesterday: [], Earlier: [] };
        filtered.forEach((alert) => {
            map[dayGroup(alert.time)].push(alert);
        });
        return order
            .filter((label) => map[label].length > 0)
            .map((label) => ({ label, items: map[label] }));
    }, [filtered]);

    const summary = {
        critical: stats.critical ?? tabCounts.error,
        warnings: stats.warnings ?? tabCounts.warning,
        updates: stats.updates ?? tabCounts.updates,
        unread: stats.unread ?? unreadCount ?? tabCounts.unread,
    };

    const refresh = () => {
        setRefreshing(true);
        router.reload({
            only: ['alerts', 'unreadCount', 'stats'],
            onFinish: () => setRefreshing(false),
        });
    };

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                        Notifications
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Stay on top of absences, late check-ins, and duty updates.
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onClick={refresh}
                        disabled={refreshing}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                    >
                        <svg className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M4 4v5h5M20 20v-5h-5M20 4a9 9 0 0 0-14.7 3.3M4 20a9 9 0 0 0 14.7-3.3" />
                        </svg>
                        {refreshing ? 'Refreshing…' : 'Refresh'}
                    </button>
                    {summary.unread > 0 && (
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="inline-flex items-center rounded-lg bg-[#14233a] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1e3354]"
                        >
                            Mark all as read
                        </button>
                    )}
                </div>
            </div>

            {summary.unread > 0 && (
                <div className="mb-5 flex flex-col gap-3 rounded-xl border border-[#14233a]/10 bg-[#14233a]/[0.04] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#14233a] text-white">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm font-bold text-slate-900">
                                You have {summary.unread} unread notification{summary.unread === 1 ? '' : 's'}
                            </p>
                            <p className="text-sm text-slate-500">
                                Tap a card to mark it as read, or clear everything at once.
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => setTab('unread')}
                            className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
                        >
                            Show unread
                        </button>
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="rounded-lg bg-[#14233a] px-3 py-2 text-xs font-bold text-white hover:bg-[#1e3354]"
                        >
                            Clear all
                        </button>
                    </div>
                </div>
            )}

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <Stat
                    label="Needs attention"
                    value={summary.critical}
                    active={tab === 'error'}
                    onClick={() => setTab('error')}
                    hint="Absences"
                />
                <Stat
                    label="Warnings"
                    value={summary.warnings}
                    active={tab === 'warning'}
                    onClick={() => setTab('warning')}
                    hint="Late arrivals"
                />
                <Stat
                    label="Updates"
                    value={summary.updates}
                    active={tab === 'updates'}
                    onClick={() => setTab('updates')}
                    hint="Check-ins"
                />
                <Stat
                    label="Unread"
                    value={summary.unread}
                    active={tab === 'unread'}
                    onClick={() => setTab('unread')}
                    hint="Not reviewed"
                    emphasize
                />
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="space-y-3 border-b border-slate-200 px-4 py-4 sm:px-5">
                    <div className="relative">
                        <svg className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                        </svg>
                        <input
                            type="search"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search by guard name, post, or message…"
                            className="w-full rounded-lg border-slate-200 py-2.5 pl-10 pr-10 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#14233a] focus:ring-[#14233a]"
                        />
                        {query && (
                            <button
                                type="button"
                                onClick={() => setQuery('')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 hover:text-slate-700"
                            >
                                Clear
                            </button>
                        )}
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex flex-wrap gap-1">
                            {tabs.map((item) => {
                                const active = tab === item.id;
                                return (
                                    <button
                                        key={item.id}
                                        type="button"
                                        onClick={() => setTab(item.id)}
                                        className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition ${
                                            active
                                                ? 'bg-[#14233a] text-white'
                                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'
                                        }`}
                                    >
                                        {item.label}
                                        <span
                                            className={`rounded-md px-1.5 py-0.5 text-[10px] font-bold ${
                                                active ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'
                                            }`}
                                        >
                                            {item.count}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        <p className="text-xs text-slate-400">
                            Showing <span className="font-semibold text-slate-600">{filtered.length}</span>
                            {query ? ' matches' : ' notifications'}
                        </p>
                    </div>
                </div>

                {filtered.length === 0 ? (
                    <div className="px-6 py-16 text-center">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
                            </svg>
                        </div>
                        <p className="mt-4 text-base font-bold text-slate-900">
                            {alerts.length === 0
                                ? 'You’re all caught up'
                                : query
                                  ? 'No matches found'
                                  : 'Nothing in this filter'}
                        </p>
                        <p className="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                            {alerts.length === 0
                                ? 'New alerts will appear here when guards are late, absent, or finish duty.'
                                : query
                                  ? 'Try a different name or clear the search.'
                                  : 'Choose another filter above to see more notifications.'}
                        </p>
                        {(tab !== 'all' || query) && alerts.length > 0 && (
                            <button
                                type="button"
                                onClick={() => {
                                    setTab('all');
                                    setQuery('');
                                }}
                                className="mt-4 rounded-lg bg-[#14233a] px-4 py-2 text-sm font-bold text-white hover:bg-[#1e3354]"
                            >
                                Show all notifications
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="divide-y divide-slate-100">
                        {groups.map((group) => (
                            <section key={group.label}>
                                <div className="sticky top-0 z-10 border-b border-slate-100 bg-slate-50/95 px-4 py-2 backdrop-blur sm:px-5">
                                    <p className="text-[11px] font-bold uppercase tracking-[.14em] text-slate-500">
                                        {group.label}
                                        <span className="ml-2 font-semibold normal-case tracking-normal text-slate-400">
                                            · {group.items.length}
                                        </span>
                                    </p>
                                </div>
                                <ul className="divide-y divide-slate-100">
                                    {group.items.map((alert, index) => {
                                        const severity = alert.severity in severityMeta ? alert.severity : 'info';
                                        const meta = severityMeta[severity];
                                        const canMarkRead = alert.source === 'stored' && Number.isInteger(Number(alert.id)) && !alert.read;

                                        return (
                                            <li key={`${alert.source}-${alert.id ?? index}`}>
                                                <div
                                                    role={canMarkRead ? 'button' : undefined}
                                                    tabIndex={canMarkRead ? 0 : undefined}
                                                    onClick={() => {
                                                        if (canMarkRead) markOneRead(alert.id);
                                                    }}
                                                    onKeyDown={(e) => {
                                                        if (canMarkRead && (e.key === 'Enter' || e.key === ' ')) {
                                                            e.preventDefault();
                                                            markOneRead(alert.id);
                                                        }
                                                    }}
                                                    className={`relative flex items-start gap-3 px-4 py-4 text-left transition sm:gap-4 sm:px-5 ${
                                                        alert.read ? 'bg-white' : 'bg-[#f7f9fc]'
                                                    } ${canMarkRead ? 'cursor-pointer hover:bg-slate-50' : 'hover:bg-slate-50/80'}`}
                                                >
                                                    {!alert.read && (
                                                        <span className={`absolute inset-y-0 left-0 w-1 ${meta.bar}`} />
                                                    )}

                                                    <div className={`mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${meta.icon}`}>
                                                        <SeverityIcon severity={severity} />
                                                    </div>

                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <h3 className={`text-sm ${alert.read ? 'font-semibold text-slate-700' : 'font-bold text-slate-900'}`}>
                                                                {alert.title}
                                                            </h3>
                                                            <span className={`inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ${meta.badge}`}>
                                                                {meta.label}
                                                            </span>
                                                            {alert.source === 'live' && (
                                                                <span className="rounded-md bg-sky-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-700 ring-1 ring-sky-100">
                                                                    Happening now
                                                                </span>
                                                            )}
                                                        </div>

                                                        <p className={`mt-1 text-sm leading-relaxed ${alert.read ? 'text-slate-500' : 'text-slate-700'}`}>
                                                            {alert.message}
                                                        </p>

                                                        <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                                                            <span className="font-semibold text-slate-500">{alert.time_ago}</span>
                                                            <span>{alert.time_label}</span>
                                                            {canMarkRead && (
                                                                <span className="font-medium text-[#14233a]">Tap to mark as read</span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <div className="flex shrink-0 flex-col items-end gap-2">
                                                        {!alert.read && (
                                                            <span className={`h-2.5 w-2.5 rounded-full ${meta.dot}`} title="Unread" />
                                                        )}
                                                        {canMarkRead && (
                                                            <button
                                                                type="button"
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    markOneRead(alert.id);
                                                                }}
                                                                className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50"
                                                            >
                                                                Mark read
                                                            </button>
                                                        )}
                                                        {alert.source === 'live' && (
                                                            <Link
                                                                href="/attendance"
                                                                onClick={(e) => e.stopPropagation()}
                                                                className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50"
                                                            >
                                                                View attendance
                                                            </Link>
                                                        )}
                                                    </div>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function Stat({ label, value, hint, onClick, active = false, emphasize = false }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-xl border px-4 py-3 text-left shadow-sm transition ${
                active
                    ? 'border-[#14233a] bg-[#14233a] text-white'
                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
            }`}
        >
            <p className={`text-[11px] font-bold uppercase tracking-wide ${active ? 'text-white/70' : 'text-slate-400'}`}>
                {label}
            </p>
            <p className={`mt-1 text-2xl font-extrabold tracking-tight ${active ? 'text-white' : emphasize ? 'text-[#14233a]' : 'text-slate-900'}`}>
                {value}
            </p>
            <p className={`mt-0.5 text-xs ${active ? 'text-white/65' : 'text-slate-400'}`}>{hint}</p>
        </button>
    );
}
