<x-admin-layout>
    <x-slot name="header">Alerts & Notifications</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Alerts</span>
    </x-slot>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Alerts & Notifications</h2>
            <p class="mt-1 text-sm text-slate-500">System alerts for attendance, deployment, and GPS location events.</p>
        </div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Mark All Read
                </button>
            </form>
        @endif
    </div>

    <div class="space-y-3">
        @forelse($alerts as $alert)
            @php
                $styles = match($alert['severity']) {
                    'error' => [
                        'card' => 'border-red-100 bg-red-50/70',
                        'icon' => 'bg-red-100 text-red-600',
                        'label' => 'text-red-600',
                    ],
                    'warning' => [
                        'card' => 'border-amber-100 bg-amber-50/70',
                        'icon' => 'bg-amber-100 text-amber-600',
                        'label' => 'text-amber-600',
                    ],
                    'success' => [
                        'card' => 'border-emerald-100 bg-emerald-50/70',
                        'icon' => 'bg-emerald-100 text-emerald-600',
                        'label' => 'text-emerald-600',
                    ],
                    default => [
                        'card' => 'border-blue-100 bg-blue-50/70',
                        'icon' => 'bg-blue-100 text-blue-600',
                        'label' => 'text-blue-600',
                    ],
                };
                $severityLabel = match($alert['severity']) {
                    'error' => 'Error',
                    'warning' => 'Warning',
                    'success' => 'Success',
                    default => 'Info',
                };
                $time = $alert['time'] instanceof \Carbon\Carbon ? $alert['time'] : \Carbon\Carbon::parse($alert['time']);
            @endphp
            <div class="flex items-start gap-4 rounded-2xl border px-5 py-4 shadow-sm transition {{ $styles['card'] }} {{ ($alert['read'] ?? false) ? 'opacity-70' : '' }}">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $styles['icon'] }}">
                    @if($alert['severity'] === 'error')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    @elseif($alert['severity'] === 'warning')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.3 4.7 2.6 18a1 1 0 0 0 .9 1.5h16.9a1 1 0 0 0 .9-1.5L13.7 4.7a1 1 0 0 0-1.8 0Z"/></svg>
                    @elseif($alert['severity'] === 'success')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="m5 13 4 4L19 7"/></svg>
                    @else
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9m14.2 14.2c3.9-3.9 3.9-10.3 0-14.2M8.5 15.5l7-7"/></svg>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-900">{{ $alert['message'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $time->format('h:i A') }} • {{ $time->format('M j, Y') }}</p>
                </div>

                <span class="shrink-0 text-xs font-bold uppercase tracking-wide {{ $styles['label'] }}">
                    {{ $severityLabel }}
                </span>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-12 text-center text-slate-500 shadow-sm">
                No alerts at this time.
            </div>
        @endforelse
    </div>

    <button type="button" class="fixed bottom-6 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-bold text-slate-500 shadow-lg hover:text-slate-800" aria-label="Help">?</button>
</x-admin-layout>
