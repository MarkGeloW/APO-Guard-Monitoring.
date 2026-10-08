const presets = {
    on_duty: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    late: 'bg-amber-50 text-amber-600 ring-amber-100',
    absent: 'bg-red-50 text-red-600 ring-red-100',
    off_duty: 'bg-slate-100 text-slate-500 ring-slate-200',
    active: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    warning: 'bg-amber-50 text-amber-600 ring-amber-100',
    probation: 'bg-amber-50 text-amber-600 ring-amber-100',
    pending: 'bg-blue-50 text-blue-600 ring-blue-100',
    ready: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    generating: 'bg-amber-50 text-amber-600 ring-amber-100',
    administrator: 'bg-purple-50 text-purple-600 ring-purple-100',
    supervisor: 'bg-blue-50 text-blue-600 ring-blue-100',
    guard: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
};

export default function StatusBadge({ label, tone = 'pending' }) {
    return (
        <span className={`inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide ring-1 ${presets[tone] ?? presets.pending}`}>
            {label}
        </span>
    );
}
