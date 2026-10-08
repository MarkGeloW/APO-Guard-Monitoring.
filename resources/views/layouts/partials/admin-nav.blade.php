@php
    $navClass = fn (bool $active) => $active
        ? 'flex items-center gap-3 rounded-xl bg-white/12 px-3 py-2.5 text-sm font-semibold text-white'
        : 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/8 hover:text-white';
@endphp

<a href="{{ route('dashboard') }}" class="{{ $navClass(request()->routeIs('dashboard')) }}">
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
    <span>Dashboard</span>
</a>

@if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
    <a href="{{ route('deployments.index') }}" class="{{ $navClass(request()->routeIs('deployments.*')) }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/></svg>
        <span>Deployment</span>
    </a>
    <a href="{{ route('locations.index') }}" class="{{ $navClass(request()->routeIs('locations.*')) }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
        <span>Locations</span>
    </a>
    <a href="{{ route('personnel.index') }}" class="{{ $navClass(request()->routeIs('personnel.*')) }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m12 8a8 8 0 1 0-16 0"/></svg>
        <span>Personnel</span>
    </a>
    <a href="{{ route('monitoring.index') }}" class="{{ $navClass(request()->routeIs('monitoring.*')) }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
        <span>Live Map</span>
    </a>
    <a href="{{ route('attendance.index') }}" class="{{ $navClass(request()->routeIs('attendance.*')) }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        <span>Attendance</span>
    </a>
    <a href="{{ route('reports.index') }}" class="{{ $navClass(request()->routeIs('reports.*')) }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 17v-6m3 6V7m3 10v-4M5 21h14a1 1 0 0 0 1-1V6l-4-4H5a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1Z"/></svg>
        <span>Reports</span>
    </a>
@endif

@if(auth()->user()->isAdmin())
    <a href="{{ route('users.index') }}" class="{{ $navClass(request()->routeIs('users.*')) }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
        <span>User Management</span>
    </a>
@endif

<a href="{{ route('notifications.index') }}" class="{{ $navClass(request()->routeIs('notifications.*')) }}">
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
    <span>Alerts</span>
    @if(($unreadNotifications ?? 0) > 0)
        <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold">{{ $unreadNotifications }}</span>
    @endif
</a>
