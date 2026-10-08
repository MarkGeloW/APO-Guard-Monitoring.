import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const breadcrumb = (
    <>
        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
        </svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span className="text-slate-600">Add Security Personnel</span>
    </>
);

const inputClass =
    'mt-1 w-full rounded-xl border-slate-200 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:ring-blue-400';
const labelClass = 'block text-[11px] font-bold uppercase tracking-wide text-slate-500';

export default function Create({ guardUsers = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        badge_number: '',
        status: 'active',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        address: '',
        date_hired: '',
        emergency_contact: '',
        emergency_phone: '',
        notes: '',
        create_account: false,
        user_id: '',
        account_email: '',
        account_password: '',
        account_password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/personnel');
    };

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-6">
                <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                    Add Security Personnel
                </h2>
                <p className="mt-1 text-sm text-slate-500">Create a new guard profile and optional login account.</p>
            </div>

            <form
                onSubmit={submit}
                className="max-w-3xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label className={labelClass}>Badge Number</label>
                        <input
                            type="text"
                            value={data.badge_number}
                            onChange={(e) => setData('badge_number', e.target.value)}
                            required
                            className={inputClass}
                        />
                        {errors.badge_number && <p className="mt-1 text-xs text-red-500">{errors.badge_number}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>Status</label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className={inputClass}
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on_leave">On leave</option>
                        </select>
                        {errors.status && <p className="mt-1 text-xs text-red-500">{errors.status}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>First Name</label>
                        <input
                            type="text"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            required
                            className={inputClass}
                        />
                        {errors.first_name && <p className="mt-1 text-xs text-red-500">{errors.first_name}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>Last Name</label>
                        <input
                            type="text"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            required
                            className={inputClass}
                        />
                        {errors.last_name && <p className="mt-1 text-xs text-red-500">{errors.last_name}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>Email</label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className={inputClass}
                        />
                        {errors.email && <p className="mt-1 text-xs text-red-500">{errors.email}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>Phone</label>
                        <input
                            type="text"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            className={inputClass}
                        />
                        {errors.phone && <p className="mt-1 text-xs text-red-500">{errors.phone}</p>}
                    </div>
                    <div className="md:col-span-2">
                        <label className={labelClass}>Address</label>
                        <input
                            type="text"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            className={inputClass}
                        />
                        {errors.address && <p className="mt-1 text-xs text-red-500">{errors.address}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>Date Hired</label>
                        <input
                            type="date"
                            value={data.date_hired}
                            onChange={(e) => setData('date_hired', e.target.value)}
                            className={inputClass}
                        />
                        {errors.date_hired && <p className="mt-1 text-xs text-red-500">{errors.date_hired}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>Emergency Contact</label>
                        <input
                            type="text"
                            value={data.emergency_contact}
                            onChange={(e) => setData('emergency_contact', e.target.value)}
                            className={inputClass}
                        />
                    </div>
                    <div>
                        <label className={labelClass}>Emergency Phone</label>
                        <input
                            type="text"
                            value={data.emergency_phone}
                            onChange={(e) => setData('emergency_phone', e.target.value)}
                            className={inputClass}
                        />
                    </div>
                </div>

                <div>
                    <label className={labelClass}>Notes</label>
                    <textarea
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        className={inputClass}
                    />
                </div>

                <div className="space-y-3 border-t border-slate-200 pt-4">
                    <p className="font-bold text-slate-900">System Account (Optional)</p>
                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={data.create_account}
                            onChange={(e) => setData('create_account', e.target.checked)}
                            className="rounded border-slate-300 text-blue-500 focus:ring-blue-400"
                        />
                        Create new guard login account
                    </label>

                    {data.create_account && (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label className={labelClass}>Account Email</label>
                                <input
                                    type="email"
                                    value={data.account_email}
                                    onChange={(e) => setData('account_email', e.target.value)}
                                    className={inputClass}
                                />
                                {errors.account_email && (
                                    <p className="mt-1 text-xs text-red-500">{errors.account_email}</p>
                                )}
                            </div>
                            <div>
                                <label className={labelClass}>Account Password</label>
                                <input
                                    type="password"
                                    value={data.account_password}
                                    onChange={(e) => setData('account_password', e.target.value)}
                                    className={inputClass}
                                />
                                {errors.account_password && (
                                    <p className="mt-1 text-xs text-red-500">{errors.account_password}</p>
                                )}
                            </div>
                            <div>
                                <label className={labelClass}>Confirm Password</label>
                                <input
                                    type="password"
                                    value={data.account_password_confirmation}
                                    onChange={(e) => setData('account_password_confirmation', e.target.value)}
                                    className={inputClass}
                                />
                            </div>
                        </div>
                    )}

                    <div>
                        <label className={labelClass}>Or link existing guard account</label>
                        <select
                            value={data.user_id}
                            onChange={(e) => setData('user_id', e.target.value)}
                            className={inputClass}
                            disabled={data.create_account}
                        >
                            <option value="">— None —</option>
                            {guardUsers.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name} ({user.email})
                                </option>
                            ))}
                        </select>
                        {errors.user_id && <p className="mt-1 text-xs text-red-500">{errors.user_id}</p>}
                    </div>
                </div>

                <div className="flex flex-wrap gap-3 pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600 disabled:opacity-60"
                    >
                        {processing ? 'Saving…' : 'Save Personnel'}
                    </button>
                    <Link
                        href="/personnel"
                        className="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
