<x-admin-layout>
    <x-slot name="header">User Management</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">User Management</span>
    </x-slot>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">User Management</h2>
            <p class="mt-1 text-sm text-slate-500">Manage system accounts, roles, and access permissions.</p>
        </div>
        <a
            href="{{ route('users.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600"
        >
            <span class="text-lg leading-none">+</span>
            Create Account
        </a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Total Accounts</p>
                    <p class="mt-2 text-4xl font-extrabold text-slate-900">{{ $stats['total'] }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Administrators</p>
                    <p class="mt-2 text-4xl font-extrabold text-purple-600">{{ $stats['admins'] }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3 5.5 5.4v5.2c0 4.1 2.6 7.8 6.5 9.4 3.9-1.6 6.5-5.3 6.5-9.4V5.4L12 3Z"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Supervisors</p>
                    <p class="mt-2 text-4xl font-extrabold text-blue-600">{{ $stats['supervisors'] }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 14a4 4 0 0 0 4-4V7a4 4 0 1 0-8 0v3a4 4 0 0 0 4 4Zm-7 6a7 7 0 0 1 14 0"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Guards</p>
                    <p class="mt-2 text-4xl font-extrabold text-emerald-600">{{ $stats['guards'] }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="mb-6">
        <div class="relative">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search by name, username or role..."
                class="w-full rounded-2xl border-0 bg-white py-4 pl-11 pr-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
            >
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">
                    <tr>
                        <th class="px-5 py-4 text-left">User ID</th>
                        <th class="px-5 py-4 text-left">Full Name</th>
                        <th class="px-5 py-4 text-left">Username</th>
                        <th class="px-5 py-4 text-left">Role</th>
                        <th class="px-5 py-4 text-left">Status</th>
                        <th class="px-5 py-4 text-left">Joined</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-500">{{ $user->userCode() }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-extrabold text-white {{ $user->avatarClasses() }}">
                                        {{ $user->initials() }}
                                    </div>
                                    <span class="font-bold text-slate-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-600">{{ $user->username() }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide ring-1 {{ $user->roleBadgeClasses() }}">
                                    {{ $user->roleTableLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($user->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-600 ring-1 ring-emerald-100">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->joinedLabel() }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a
                                        href="{{ route('users.edit', $user) }}"
                                        class="inline-flex items-center rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-600 transition hover:bg-blue-50"
                                    >
                                        Edit
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('users.toggle-status', $user) }}" method="POST" data-swal-confirm="{{ $user->is_active ? 'This user will no longer be able to sign in.' : 'This user will be able to sign in again.' }}" data-swal-title="{{ $user->is_active ? 'Deactivate user?' : 'Activate user?' }}" data-swal-confirm-text="{{ $user->is_active ? 'Deactivate' : 'Activate' }}" data-swal-confirm-color="#d97706">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-bold text-amber-600 transition hover:bg-amber-50"
                                            >
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" data-swal-confirm="This user account will be permanently removed." data-swal-title="Delete user?" data-swal-type="delete">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-50"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                No users found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <button type="button" class="fixed bottom-6 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-bold text-slate-500 shadow-lg hover:text-slate-800" aria-label="Help">?</button>
</x-admin-layout>
