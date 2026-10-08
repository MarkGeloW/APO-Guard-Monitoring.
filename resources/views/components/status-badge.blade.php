@props(['status'])

@php
    $classes = match($status) {
        'active', 'present', 'scheduled' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'late' => 'bg-amber-100 text-amber-800',
        'pending' => 'bg-slate-100 text-slate-700',
        'absent', 'cancelled', 'inactive' => 'bg-red-100 text-red-800',
        'on_leave' => 'bg-purple-100 text-purple-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {$classes}"]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
