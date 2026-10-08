<x-guest-layout>
    <main
        class="min-h-screen bg-slate-100"
        x-data="{
            showPassword: false,
            fillDemo(email) {
                this.$refs.email.value = email;
                this.$refs.password.value = 'password';
                this.$refs.email.dispatchEvent(new Event('input'));
                this.$refs.password.dispatchEvent(new Event('input'));
            }
        }"
    >
        <div class="grid min-h-screen lg:grid-cols-[1.55fr_1fr]">
            <section class="relative hidden overflow-hidden bg-[#183b63] px-10 py-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute inset-0 opacity-25">
                    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.08)_1px,transparent_1px)] bg-[size:44px_44px]"></div>
                    <div class="absolute left-1/2 top-0 h-[620px] w-[620px] -translate-x-1/4 -translate-y-1/4 rounded-full border border-white/10"></div>
                    <div class="absolute left-1/2 top-1/2 h-[340px] w-[340px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/10"></div>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/12 shadow-lg ring-1 ring-white/15">
                            <svg class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 3 5.5 5.4v5.2c0 4.1 2.6 7.8 6.5 9.4 3.9-1.6 6.5-5.3 6.5-9.4V5.4L12 3Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="tracking-[.28em] text-xl font-extrabold uppercase">APO Guard</p>
                            <p class="mt-1 text-[10px] font-semibold uppercase tracking-[.38em] text-white/45">Monitoring System</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 max-w-3xl pb-12">
                    <h1 class="max-w-2xl text-5xl font-extrabold leading-tight tracking-tight xl:text-6xl">
                        Security Personnel Deployment & Monitoring System
                    </h1>
                    <p class="mt-6 max-w-xl text-sm leading-7 text-blue-100/85">
                        A centralized web-based platform for Apo Security Agency - manage guard deployments,
                        monitor attendance, and track real-time locations with integrated GIS mapping.
                    </p>

                    <div class="mt-10 grid max-w-lg grid-cols-3 gap-8">
                        <div>
                            <p class="text-sm font-bold">GIS Mapping</p>
                            <p class="mt-1 text-xs text-blue-100/65">Real-time location</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold">GPS Verified</p>
                            <p class="mt-1 text-xs text-blue-100/65">Attendance tracking</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold">Role-Based</p>
                            <p class="mt-1 text-xs text-blue-100/65">Secure access</p>
                        </div>
                    </div>
                </div>

                <p class="relative z-10 text-[10px] font-semibold uppercase tracking-[.28em] text-white/25">
                    &copy; 2026 APO Security Agency - Kidapawan City, North Cotabato
                </p>
            </section>

            <section class="flex min-h-screen items-center justify-center bg-slate-100 px-5 py-8 sm:px-8">
                <div class="w-full max-w-[420px]">
                    <div class="mb-10 lg:hidden">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#183b63] text-white">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 3 5.5 5.4v5.2c0 4.1 2.6 7.8 6.5 9.4 3.9-1.6 6.5-5.3 6.5-9.4V5.4L12 3Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg font-extrabold uppercase tracking-[.22em] text-slate-900">APO Guard</p>
                                <p class="text-[10px] font-semibold uppercase tracking-[.28em] text-slate-400">Monitoring System</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950">Sign In</h2>
                        <p class="mt-2 text-sm text-slate-500">Enter your credentials to access the system</p>
                    </div>

                    <x-auth-session-status class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="text-[11px] font-extrabold uppercase tracking-[.22em] text-slate-700">Username</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M20 21a8 8 0 0 0-16 0" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                </span>
                                <input
                                    x-ref="email"
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="e.g. admin@aposecurity.com"
                                    class="block w-full rounded-2xl border-0 bg-white px-11 py-4 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
                                >
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <label for="password" class="text-[11px] font-extrabold uppercase tracking-[.22em] text-slate-700">Password</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <rect width="16" height="11" x="4" y="11" rx="2" />
                                        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                                    </svg>
                                </span>
                                <input
                                    x-ref="password"
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter password"
                                    class="block w-full rounded-2xl border-0 bg-white px-11 py-4 pr-12 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
                                >
                                <button type="button" @click="showPassword = ! showPassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-600" aria-label="Toggle password visibility">
                                    <svg x-show="!showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg x-cloak x-show="showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                        <path d="m3 3 18 18" />
                                        <path d="M10.6 10.6a3 3 0 0 0 3.8 3.8" />
                                        <path d="M9.9 4.2A10.6 10.6 0 0 1 12 4c6.5 0 10 8 10 8a17.8 17.8 0 0 1-3.2 4.3" />
                                        <path d="M6.6 6.6C3.6 8.6 2 12 2 12s3.5 6 10 6c1.2 0 2.3-.2 3.3-.6" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <button type="submit" class="w-full rounded-2xl bg-blue-400 px-5 py-4 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                            Sign In
                        </button>
                    </form>

                    <div class="my-8 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <p class="text-[10px] font-bold uppercase tracking-[.18em] text-slate-400">Demo Accounts</p>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <div class="space-y-3">
                        <button type="button" @click="fillDemo('admin@aposecurity.com')" class="group flex w-full items-center justify-between rounded-2xl bg-white p-4 text-left shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 3 6 5.3v5.1c0 3.8 2.4 7.2 6 8.7 3.6-1.5 6-4.9 6-8.7V5.3L12 3Z" />
                                    </svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-800">Administrator</span>
                                    <span class="block text-xs text-slate-400">admin@aposecurity.com / password</span>
                                </span>
                            </span>
                            <span class="rounded-full bg-purple-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-purple-500">Administrator</span>
                        </button>

                        <button type="button" @click="fillDemo('supervisor@aposecurity.com')" class="group flex w-full items-center justify-between rounded-2xl bg-white p-4 text-left shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 3 6 5.3v5.1c0 3.8 2.4 7.2 6 8.7 3.6-1.5 6-4.9 6-8.7V5.3L12 3Z" />
                                    </svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-800">Supervisor</span>
                                    <span class="block text-xs text-slate-400">supervisor@aposecurity.com / password</span>
                                </span>
                            </span>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-500">Supervisor</span>
                        </button>
                    </div>

                    <p class="mt-6 text-center text-xs text-slate-400">
                        Click a demo account to autofill credentials, then sign in. Guards use the mobile app.
                    </p>
                </div>
            </section>
        </div>
    </main>
</x-guest-layout>
