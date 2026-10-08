@php
    $defaultRole = old('role', $roles[0]->value ?? 'admin');
@endphp

<x-admin-layout>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m12 8a8 8 0 1 0-16 0"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Add User</span>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Add System User</h2>
        <p class="mt-1 text-sm text-slate-500">Create an administrator, supervisor, or guard login account.</p>
    </div>

    <div
        class="grid grid-cols-1 gap-6 xl:grid-cols-5"
        x-data="{
            name: {{ Js::from(old('name', '')) }},
            email: {{ Js::from(old('email', '')) }},
            phone: {{ Js::from(old('phone', '')) }},
            role: {{ Js::from($defaultRole) }},
            active: true,
            roleLabels: {{ Js::from(collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])) }},
            get initials() {
                const parts = (this.name || '').trim().split(/\s+/).filter(Boolean);
                if (!parts.length) return 'SY';
                return (parts[0][0] + (parts[1]?.[0] || parts[0][1] || '')).toUpperCase();
            },
            get roleLabel() { return this.roleLabels[this.role] || this.role }
        }"
    >
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-3">
            @csrf

            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m12 8a8 8 0 1 0-16 0"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">Account Details</p>
                    <p class="text-xs text-slate-500">Login credentials and role access</p>
                </div>
            </div>

            <div>
                <label class="{{ $labelClass }}">Full Name</label>
                <input type="text" name="name" required class="{{ $inputClass }}" x-model="name" value="{{ old('name') }}">
                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Email Address</label>
                <input type="email" name="email" required class="{{ $inputClass }}" x-model="email" value="{{ old('email') }}">
                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Phone</label>
                <input type="text" name="phone" class="{{ $inputClass }}" x-model="phone" value="{{ old('phone') }}" placeholder="Optional">
                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Role</label>
                <select name="role" required class="{{ $inputClass }}" x-model="role">
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">Password</label>
                    <input type="password" name="password" required class="{{ $inputClass }}">
                    @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Confirm Password</label>
                    <input type="password" name="password_confirmation" required class="{{ $inputClass }}">
                </div>
            </div>

            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" x-model="active">
                Active account
            </label>

            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600">
                    Create User
                </button>
                <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>

        <aside class="space-y-4 xl:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Account Preview</p>
                        <p class="text-xs text-slate-500">Updates as you fill the form</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-600" x-text="roleLabel"></span>
                </div>

                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#162d4a] text-sm font-bold text-white" x-text="initials"></div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900" x-text="name || 'New user'"></p>
                        <p class="truncate text-xs text-slate-500" x-text="email || 'email@example.com'"></p>
                    </div>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="font-semibold text-slate-900" x-text="phone || '—'"></dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Role</dt>
                        <dd class="font-semibold text-slate-900" x-text="roleLabel"></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Status</dt>
                        <dd class="font-semibold" :class="active ? 'text-emerald-600' : 'text-slate-500'" x-text="active ? 'Active' : 'Inactive'"></dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-slate-50 p-5">
                <p class="text-sm font-bold text-slate-900">Role access</p>
                <ul class="mt-3 space-y-2 text-xs leading-relaxed text-slate-600">
                    <li><span class="font-semibold text-slate-800">Administrator</span> — full system and user management</li>
                    <li><span class="font-semibold text-slate-800">Supervisor</span> — deployments, monitoring, reports</li>
                    <li><span class="font-semibold text-slate-800">Security Guard</span> — mobile check-in and alerts</li>
                </ul>
            </div>
        </aside>
    </div>
</x-admin-layout>
