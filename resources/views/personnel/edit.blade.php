@php
    $statusLabels = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'on_leave' => 'On leave',
    ];
@endphp

<x-admin-layout>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Edit Personnel</span>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Edit Security Personnel</h2>
        <p class="mt-1 text-sm text-slate-500">Update guard profile details and linked mobile login account.</p>
    </div>

    <div
        class="grid grid-cols-1 gap-6 xl:grid-cols-5"
        x-data="{
            firstName: {{ Js::from(old('first_name', $personnel->first_name)) }},
            lastName: {{ Js::from(old('last_name', $personnel->last_name)) }},
            badge: {{ Js::from(old('badge_number', $personnel->badge_number)) }},
            email: {{ Js::from(old('email', $personnel->email ?? '')) }},
            phone: {{ Js::from(old('phone', $personnel->phone ?? '')) }},
            status: {{ Js::from(old('status', $personnel->status)) }},
            statusLabels: {{ Js::from($statusLabels) }},
            get fullName() {
                return [this.firstName, this.lastName].filter(Boolean).join(' ') || 'Guard profile';
            },
            get initials() {
                return ((this.firstName?.[0] || '') + (this.lastName?.[0] || '')).toUpperCase() || '—';
            },
            get statusLabel() { return this.statusLabels[this.status] || this.status }
        }"
    >
        <form method="POST" action="{{ route('personnel.update', $personnel) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-3">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">Personnel Profile</p>
                    <p class="text-xs text-slate-500">Badge, contact, and employment details</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">Badge Number</label>
                    <input type="text" name="badge_number" required class="{{ $inputClass }}" x-model="badge">
                    @error('badge_number')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Status</label>
                    <select name="status" class="{{ $inputClass }}" x-model="status">
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $personnel->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">First Name</label>
                    <input type="text" name="first_name" required class="{{ $inputClass }}" x-model="firstName">
                    @error('first_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Last Name</label>
                    <input type="text" name="last_name" required class="{{ $inputClass }}" x-model="lastName">
                    @error('last_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Email</label>
                    <input type="email" name="email" class="{{ $inputClass }}" x-model="email">
                    @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Phone</label>
                    <input type="text" name="phone" class="{{ $inputClass }}" x-model="phone">
                    @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">Address</label>
                    <input type="text" name="address" value="{{ old('address', $personnel->address) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Date Hired</label>
                    <input type="date" name="date_hired" value="{{ old('date_hired', $personnel->date_hired?->format('Y-m-d')) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Linked Account</label>
                    <select name="user_id" class="{{ $inputClass }}">
                        <option value="">— None —</option>
                        @foreach($guardUsers as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id', $personnel->user_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Links this profile to a guard mobile login.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Emergency Contact</label>
                    <input type="text" name="emergency_contact" value="{{ old('emergency_contact', $personnel->emergency_contact) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Emergency Phone</label>
                    <input type="text" name="emergency_phone" value="{{ old('emergency_phone', $personnel->emergency_phone) }}" class="{{ $inputClass }}">
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">Notes</label>
                    <textarea name="notes" rows="3" class="{{ $inputClass }}" placeholder="Optional internal notes">{{ old('notes', $personnel->notes) }}</textarea>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600">
                    Update Personnel
                </button>
                <a href="{{ route('personnel.show', $personnel) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">View Profile</a>
                <a href="{{ route('personnel.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>

        <aside class="space-y-4 xl:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Guard Preview</p>
                        <p class="text-xs text-slate-500">Live view of profile fields</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-600" x-text="statusLabel"></span>
                </div>

                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#162d4a] text-sm font-bold text-white" x-text="initials"></div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900" x-text="fullName"></p>
                        <p class="truncate text-xs text-slate-500">Badge <span x-text="badge || '—'"></span></p>
                    </div>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Email</dt>
                        <dd class="max-w-[60%] truncate text-right font-semibold text-slate-900" x-text="email || '—'"></dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="font-semibold text-slate-900" x-text="phone || '—'"></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Code</dt>
                        <dd class="font-semibold text-slate-900">{{ $personnel->guardCode() }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>
</x-admin-layout>
