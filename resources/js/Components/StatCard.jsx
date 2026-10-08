export default function StatCard({ label, value, sub, hint, hintClass = 'text-slate-500', valueClass = 'text-slate-900', icon, iconBg }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-[11px] font-bold uppercase tracking-[.18em] text-slate-400">{label}</p>
                    <p className={`mt-2 text-4xl font-extrabold ${valueClass}`}>{value}</p>
                    {sub && <p className="mt-1 text-xs text-slate-500">{sub}</p>}
                    {hint && <p className={`mt-2 text-xs font-semibold ${hintClass}`}>{hint}</p>}
                </div>
                {icon && <div className={`flex h-11 w-11 items-center justify-center rounded-2xl ${iconBg}`}>{icon}</div>}
            </div>
        </div>
    );
}
