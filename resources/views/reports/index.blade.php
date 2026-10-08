<x-admin-layout>
    <x-slot name="header">Reports & Records</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 17v-6m3 6V7m3 10v-4M5 21h14a1 1 0 0 0 1-1V6l-4-4H5a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1Z"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Reports & Records</span>
    </x-slot>

    <div x-data="{ showGenerate: false }">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Reports & Records</h2>
                <p class="mt-1 text-sm text-slate-500">Automated deployment, attendance, and performance reports.</p>
            </div>
            <button
                type="button"
                @click="showGenerate = true"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 transition hover:bg-blue-600"
            >
                <span class="text-lg leading-none">+</span>
                Generate Report
            </button>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Total Reports</p>
                        <p class="mt-2 text-4xl font-extrabold text-slate-900">{{ $stats['total'] }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 17v-6m3 6V7m3 10v-4M5 21h14a1 1 0 0 0 1-1V6l-4-4H5a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1Z"/></svg>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">This Month</p>
                        <p class="mt-2 text-4xl font-extrabold text-slate-900">{{ $stats['this_month'] }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 16v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">Generating</p>
                        <p class="mt-2 text-4xl font-extrabold text-amber-500">{{ $stats['generating'] }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 4v5h5M20 20v-5h-5M20 4a9 9 0 0 0-14.7 3.3M4 20a9 9 0 0 0 14.7-3.3"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">
                        <tr>
                            <th class="px-5 py-4 text-left">Report Name</th>
                            <th class="px-5 py-4 text-left">Type</th>
                            <th class="px-5 py-4 text-left">Size</th>
                            <th class="px-5 py-4 text-left">Generated</th>
                            <th class="px-5 py-4 text-left">Status</th>
                            <th class="px-5 py-4 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($reports as $report)
                            @php
                                $statusClasses = match($report['status']) {
                                    'ready' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                                    'generating' => 'bg-amber-50 text-amber-600 ring-amber-100',
                                    default => 'bg-slate-100 text-slate-500 ring-slate-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-4 font-bold text-slate-900">{{ $report['name'] }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $report['type'] }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $report['size'] }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $report['generated_label'] }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide ring-1 {{ $statusClasses }}">
                                        {{ $report['status'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if($report['url'])
                                        <a
                                            href="{{ $report['url'] }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-sm font-bold text-blue-600 hover:text-blue-700"
                                        >
                                            PDF
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/></svg>
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div
            x-show="showGenerate"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="showGenerate = false"
        >
            <div class="absolute inset-0 bg-slate-900/50" @click="showGenerate = false"></div>
            <div class="relative w-full max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-extrabold uppercase tracking-wide text-slate-900">Generate Report</h3>
                        <p class="mt-1 text-sm text-slate-500">Choose a report type to view, print, or save as PDF.</p>
                    </div>
                    <button type="button" @click="showGenerate = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">&times;</button>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach($reportTypes as $type)
                        <a
                            href="{{ $type['route'] }}"
                            target="_blank"
                            class="group rounded-2xl border border-slate-200 p-5 transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"
                            @click="showGenerate = false"
                        >
                            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-500 transition group-hover:bg-blue-500 group-hover:text-white">
                                @if($type['icon'] === 'deployment')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/></svg>
                                @elseif($type['icon'] === 'attendance')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
                                @endif
                            </div>
                            <h4 class="font-bold text-slate-900">{{ $type['title'] }}</h4>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $type['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="fixed bottom-6 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-bold text-slate-500 shadow-lg hover:text-slate-800" aria-label="Help">?</button>
</x-admin-layout>
