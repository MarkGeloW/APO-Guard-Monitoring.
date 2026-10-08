import { Link, usePage, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import HelpButton from '@/Components/HelpButton';
import FlashMessages from '@/Components/FlashMessages';
import { formatPhilippinesHeaderDate } from '@/lib/datetime';
import { confirmSignOut } from '@/lib/sweetalert';

const iconClass = 'h-4 w-4 shrink-0';

const icons = {
    dashboard: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" />
        </svg>
    ),
    deployment: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z" />
        </svg>
    ),
    locations: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
            <circle cx="12" cy="11" r="2.5" />
        </svg>
    ),
    personnel: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
        </svg>
    ),
    map: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
            <circle cx="12" cy="11" r="2.5" />
        </svg>
    ),
    attendance: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    ),
    reports: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M9 17v-6m3 6V7m3 10v-4M5 21h14a1 1 0 0 0 1-1V6l-4-4H5a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1Z" />
        </svg>
    ),
    users: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M16 11a4 4 0 1 0-8 0m12 8a8 8 0 1 0-16 0" />
        </svg>
    ),
    alerts: (
        <svg className={iconClass} fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
        </svg>
    ),
};

const navItems = [
    { href: '/dashboard', label: 'Dashboard', match: 'dashboard', roles: ['admin', 'supervisor'], icon: 'dashboard' },
    { href: '/deployments', label: 'Deployment', match: 'deployments', roles: ['admin', 'supervisor'], icon: 'deployment' },
    { href: '/locations', label: 'Locations', match: 'locations', roles: ['admin', 'supervisor'], icon: 'locations' },
    { href: '/personnel', label: 'Personnel', match: 'personnel', roles: ['admin', 'supervisor'], icon: 'personnel' },
    { href: '/monitoring', label: 'Live Map', match: 'monitoring', roles: ['admin', 'supervisor'], icon: 'map' },
    { href: '/attendance', label: 'Attendance', match: 'attendance', roles: ['admin', 'supervisor'], icon: 'attendance' },
    { href: '/reports', label: 'Reports', match: 'reports', roles: ['admin', 'supervisor'], icon: 'reports' },
    { href: '/users', label: 'User Management', match: 'users', roles: ['admin'], icon: 'users' },
    { href: '/notifications', label: 'Alerts', match: 'notifications', roles: ['admin', 'supervisor'], icon: 'alerts' },
];

function isActive(pathname, match) {
    if (match === 'dashboard') {
        return pathname === '/dashboard' || pathname === '/';
    }
    return pathname === `/${match}` || pathname.startsWith(`/${match}/`);
}

export default function AdminLayout({ header, breadcrumb, children }) {
    const { auth, unreadNotifications } = usePage().props;
    const user = auth.user;
    const [mobileNav, setMobileNav] = useState(false);
    const [clockLabel, setClockLabel] = useState(() => formatPhilippinesHeaderDate());
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '/dashboard';

    useEffect(() => {
        const tick = () => setClockLabel(formatPhilippinesHeaderDate());
        tick();
        const interval = window.setInterval(tick, 30_000);
        return () => window.clearInterval(interval);
    }, []);

    const visibleNav = navItems.filter((item) => {
        if (user.is_admin && item.roles.includes('admin')) return true;
        if (user.is_supervisor && item.roles.includes('supervisor')) return true;
        return false;
    });

    const roleBadge = user.is_admin
        ? 'bg-purple-500/20 text-purple-200'
        : user.is_supervisor
          ? 'bg-blue-500/20 text-blue-200'
          : 'bg-emerald-500/20 text-emerald-200';

    const requestSignOut = async (e) => {
        e.preventDefault();

        if (await confirmSignOut()) {
            router.post('/logout');
        }
    };

    const Sidebar = () => (
        <>
            <div className="border-b border-white/10 px-5 py-6">
                <div className="flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10">
                        <svg className="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                            <path d="M12 3 5.5 5.4v5.2c0 4.1 2.6 7.8 6.5 9.4 3.9-1.6 6.5-5.3 6.5-9.4V5.4L12 3Z" />
                        </svg>
                    </div>
                    <div>
                        <p className="text-sm font-extrabold uppercase tracking-[.18em]">APO Guard</p>
                        <p className="text-[10px] font-semibold uppercase tracking-[.28em] text-white/45">Monitoring System</p>
                    </div>
                </div>
                <div className={`mt-4 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide ${roleBadge}`}>
                    <span className="h-1.5 w-1.5 rounded-full bg-current opacity-70" />
                    {user.role_label === 'Security Guard' ? 'Guard' : user.role_label}
                </div>
            </div>

            <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                <p className="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.2em] text-white/35">Navigation</p>
                {visibleNav.map((item) => {
                    const active = isActive(pathname, item.match);
                    const className = `flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition ${
                        active
                            ? 'bg-white/12 font-semibold text-white'
                            : 'font-medium text-slate-300 hover:bg-white/8 hover:text-white'
                    }`;
                    const content = (
                        <>
                            {icons[item.icon]}
                            <span>{item.label}</span>
                            {item.href === '/notifications' && unreadNotifications > 0 && (
                                <span className="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                    {unreadNotifications}
                                </span>
                            )}
                        </>
                    );

                    if (item.blade) {
                        return (
                            <a key={item.href} href={item.href} onClick={() => setMobileNav(false)} className={className}>
                                {content}
                            </a>
                        );
                    }

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            onClick={() => setMobileNav(false)}
                            className={className}
                        >
                            {content}
                        </Link>
                    );
                })}
            </nav>

            <div className="border-t border-white/10 px-4 py-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm font-bold">
                        {user.initials}
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold">{user.name}</p>
                        <p className="truncate text-[10px] uppercase tracking-wide text-white/45">
                            {user.role_label === 'Security Guard' ? 'Guard' : user.role_label}
                        </p>
                    </div>
                    <button type="button" onClick={requestSignOut} className="rounded-lg p-2 text-white/50 hover:bg-white/10 hover:text-white" aria-label="Sign out">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                            <path d="M15 3h4a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </>
    );

    return (
        <div className="min-h-screen bg-[#edf1f7]">
            <FlashMessages />
            <div className="flex min-h-screen">
                <aside className="hidden w-[250px] shrink-0 flex-col bg-[#162d4a] text-white lg:flex">
                    <Sidebar />
                </aside>

                {mobileNav && (
                    <div className="fixed inset-0 z-40 lg:hidden">
                        <div className="absolute inset-0 bg-black/50" onClick={() => setMobileNav(false)} />
                        <aside className="absolute bottom-0 left-0 top-0 flex w-[250px] flex-col bg-[#162d4a] text-white">
                            <Sidebar />
                        </aside>
                    </div>
                )}

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="border-b border-slate-200 bg-[#f7f9fc] px-4 py-4 sm:px-6">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex min-w-0 items-center gap-3">
                                <button type="button" className="rounded-lg p-2 text-slate-500 hover:bg-slate-200 lg:hidden" onClick={() => setMobileNav(true)}>
                                    <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path d="M4 7h16M4 12h16M4 17h16" />
                                    </svg>
                                </button>
                                <div>
                                    {breadcrumb && (
                                        <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                            {breadcrumb}
                                        </div>
                                    )}
                                    {header && (
                                        <h1 className="mt-1 text-lg font-extrabold uppercase tracking-wide text-slate-900">{header}</h1>
                                    )}
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-3 sm:gap-4">
                                <span className="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-emerald-600">
                                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                                    System Online
                                </span>
                                <span className="hidden text-[11px] font-semibold uppercase tracking-wide text-slate-500 md:inline">
                                    {clockLabel}
                                </span>
                                <Link
                                    href="/notifications"
                                    className="relative rounded-lg p-2 text-slate-400 transition hover:bg-white hover:text-slate-700"
                                    aria-label="Notifications"
                                >
                                    <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
                                    </svg>
                                    {unreadNotifications > 0 && (
                                        <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-[#f7f9fc]" />
                                    )}
                                </Link>
                                <a
                                    href="/profile"
                                    className="rounded-lg p-2 text-slate-400 transition hover:bg-white hover:text-slate-700"
                                    aria-label="Profile settings"
                                >
                                    <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
                                        <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z" />
                                    </svg>
                                </a>
                                <button
                                    type="button"
                                    onClick={requestSignOut}
                                    className="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-[#fff5f5] px-4 py-2 text-sm font-bold text-red-600 transition hover:bg-red-50"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path d="M15 3h4a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3" />
                                    </svg>
                                    Sign Out
                                </button>
                            </div>
                        </div>
                    </header>
                    <main className="flex-1 p-4 sm:p-6">{children}</main>
                </div>
            </div>
            <HelpButton />
        </div>
    );
}
