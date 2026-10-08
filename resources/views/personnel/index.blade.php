<x-admin-layout>
    <x-slot name="header">Security Personnel</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Security Personnel</span>
    </x-slot>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <p class="text-sm text-slate-500">Manage guard profiles, ranks, zones, and post assignments.</p>
        <a href="{{ route('personnel.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600">
            <span class="text-lg leading-none">+</span>
            Add Personnel
        </a>
    </div>

    <form method="GET" class="mb-6">
        <div class="relative">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by name or rank..."
                class="w-full rounded-2xl border-0 bg-white py-4 pl-11 pr-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-400"
            >
        </div>
    </form>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($personnel as $person)
            @php
                $statusClasses = $person->isProbation()
                    ? 'bg-amber-50 text-amber-600 ring-amber-100'
                    : ($person->status === 'active'
                        ? 'bg-emerald-50 text-emerald-600 ring-emerald-100'
                        : 'bg-slate-100 text-slate-600 ring-slate-200');
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-sm font-extrabold text-white shadow-md shadow-blue-300/30">
                            {{ $person->initials() }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-bold text-slate-900">{{ $person->fullName() }}</p>
                            <p class="text-sm text-slate-500">{{ $person->rank() }}</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $statusClasses }}">
                        {{ $person->displayStatusLabel() }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-3 border-y border-slate-100 py-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Zone</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $person->zoneLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Joined</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $person->joinedLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Posts</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $person->postsCount() }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <a href="{{ route('personnel.show', $person) }}" class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                        View Profile
                    </a>
                    <a href="{{ route('deployments.create', ['security_personnel_id' => $person->id]) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-600 transition hover:bg-blue-100">
                        Assign Post
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <p class="text-slate-500">No security personnel found.</p>
                <a href="{{ route('personnel.create') }}" class="mt-4 inline-flex items-center text-sm font-semibold text-blue-500 hover:text-blue-600">+ Add your first guard</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $personnel->links() }}</div>

    <button type="button" class="fixed bottom-6 right-6 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-bold text-slate-500 shadow-lg hover:text-slate-800" aria-label="Help">
        ?
    </button>
</x-admin-layout>
