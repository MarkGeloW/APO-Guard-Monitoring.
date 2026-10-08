<x-admin-layout>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Profile Settings</span>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Profile Settings</h2>
        <p class="mt-1 text-sm text-slate-500">Manage your account details, password, and security preferences.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <div class="space-y-6 xl:col-span-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                @include('profile.partials.update-password-form')
            </div>

            <div class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm sm:p-7">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

        <aside class="space-y-4 xl:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Account Summary</p>
                        <p class="text-xs text-slate-500">Signed-in user details</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide {{ $user->roleBadgeClasses() }}">
                        {{ $user->roleLabel() }}
                    </span>
                </div>

                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#162d4a] text-base font-bold text-white">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Role</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $user->roleLabel() }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $user->phone ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Status</dt>
                        <dd class="text-right font-semibold {{ ($user->is_active ?? true) ? 'text-emerald-600' : 'text-slate-500' }}">
                            {{ ($user->is_active ?? true) ? 'Active' : 'Inactive' }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Member since</dt>
                        <dd class="text-right font-semibold text-slate-900">{{ $user->created_at?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-slate-50 p-5">
                <p class="text-sm font-bold text-slate-900">Security tips</p>
                <ul class="mt-3 space-y-2.5 text-xs leading-relaxed text-slate-600">
                    <li class="flex gap-2">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-[10px] font-bold text-white">1</span>
                        Use a unique password you do not reuse on other systems.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-[10px] font-bold text-white">2</span>
                        Keep your email up to date so recovery and alerts reach you.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-[10px] font-bold text-white">3</span>
                        Sign out on shared devices after finishing your session.
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</x-admin-layout>
