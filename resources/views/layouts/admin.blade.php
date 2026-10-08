<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'APO Guard System') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-[#edf1f7]" x-data="{ mobileNav: false }">
    <div class="min-h-screen flex">
        <aside class="hidden lg:flex lg:flex-col w-[250px] bg-[#162d4a] text-white shrink-0">
            <div class="px-5 py-6 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3 5.5 5.4v5.2c0 4.1 2.6 7.8 6.5 9.4 3.9-1.6 6.5-5.3 6.5-9.4V5.4L12 3Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold uppercase tracking-[.18em]">APO Guard</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[.28em] text-white/45">Monitoring System</p>
                    </div>
                </div>
                @if(auth()->user()->isAdmin())
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-purple-500/20 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-purple-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-purple-300"></span>
                        Administrator
                    </div>
                @elseif(auth()->user()->isSupervisor())
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-blue-500/20 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-300"></span>
                        Supervisor
                    </div>
                @endif
            </div>

            <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
                @include('layouts.partials.admin-nav')
            </nav>

            <div class="border-t border-white/10 px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[10px] uppercase tracking-wide text-white/45">{{ auth()->user()->roleLabel() }}</p>
                    </div>
                    <button type="button" data-swal-sign-out class="rounded-lg p-2 text-white/50 hover:bg-white/10 hover:text-white" aria-label="Sign out">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M15 3h4a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3"/></svg>
                    </button>
                </div>
            </div>
        </aside>

        <div x-show="mobileNav" x-cloak class="fixed inset-0 z-40 lg:hidden">
            <div class="absolute inset-0 bg-black/50" @click="mobileNav = false"></div>
            <aside class="absolute left-0 top-0 bottom-0 w-[250px] bg-[#162d4a] text-white flex flex-col">
                <div class="px-5 py-5 border-b border-white/10 flex justify-between items-center">
                    <p class="font-semibold text-sm">APO Guard</p>
                    <button type="button" @click="mobileNav = false" class="text-white/50 hover:text-white">&times;</button>
                </div>
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    @include('layouts.partials.admin-nav')
                </nav>
            </aside>
        </div>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="border-b border-slate-200 bg-[#f7f9fc] px-4 sm:px-6 py-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <button type="button" @click="mobileNav = true" class="lg:hidden rounded-lg border border-slate-200 bg-white p-2 text-slate-600" aria-label="Open menu">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0">
                            @isset($breadcrumb)
                                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">
                                    {!! $breadcrumb !!}
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">
                                    <span>APO Security Agency</span>
                                    <span>›</span>
                                    <span class="text-slate-600">{{ $header ?? 'Dashboard' }}</span>
                                </div>
                            @endisset
                            @isset($header)
                                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">{{ $header }}</h1>
                            @endisset
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                        <div class="hidden md:flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-emerald-600">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            System Online
                        </div>
                        <div class="hidden sm:block text-right">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ now()->format('D · M j, Y') }}</p>
                            <p class="text-xs font-semibold text-slate-700">{{ now()->format('h:i A') }}</p>
                        </div>
                        <a href="{{ route('notifications.index') }}" class="relative rounded-xl border border-slate-200 bg-white p-2.5 text-slate-500 hover:text-slate-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
                            @if(($unreadNotifications ?? 0) > 0)
                                <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500"></span>
                            @endif
                        </a>
                        <a href="{{ route('profile.edit') }}" class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-500 hover:text-slate-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7Zm7.5 2.5a7.5 7.5 0 1 0-15 0"/></svg>
                        </a>
                        <button type="button" data-swal-sign-out class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M15 3h4a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3"/></svg>
                            Sign Out
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @include('components.flash-messages')
                {{ $slot }}
            </main>
        </div>
    </div>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>

    @stack('scripts')
    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
