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

export default function Create({ guardUsers = [], linkedGuardCount = 0 }) {
    const { data, setData, post, processing, errors } = useForm({
        license_number: '',
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
        user_id: guardUsers[0]?.id ? String(guardUsers[0].id) : '',
        id_pictures: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post('/personnel', { forceFormData: true });
    };

    const hasGuardAccounts = guardUsers.length > 0;
    const allGuardsAlreadyLinked = !hasGuardAccounts && linkedGuardCount > 0;

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <div className="mb-6">
                <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                    Add Security Personnel
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                    Only needed for older Guard logins that were created without a personnel profile. New Security Guard
                    accounts from User Management are added to Personnel automatically.
                </p>
            </div>

            {allGuardsAlreadyLinked && (
                <div className="mb-6 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900">
                    <p className="font-bold">All Guard accounts are already in Personnel</p>
                    <p className="mt-1">
                        Creating a Security Guard in User Management also creates their personnel record. Open the
                        Personnel list to view or edit them, or create a new Security Guard account if you need another
                        person.
                    </p>
                    <div className="mt-3 flex flex-wrap gap-3">
                        <Link
                            href="/personnel"
                            className="inline-flex text-sm font-bold text-blue-950 underline underline-offset-2"
                        >
                            View Personnel list →
                        </Link>
                        <a
                            href="/users/create"
                            className="inline-flex text-sm font-bold text-blue-950 underline underline-offset-2"
                        >
                            Create Security Guard account →
                        </a>
                    </div>
                </div>
            )}

            {!hasGuardAccounts && !allGuardsAlreadyLinked && (
                <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                    <p className="font-bold">No available guard accounts</p>
                    <p className="mt-1">
                        Create a Security Guard account in User Management first (not Supervisor). That also adds them
                        to Personnel.
                    </p>
                    <a
                        href="/users/create"
                        className="mt-3 inline-flex text-sm font-bold text-amber-900 underline underline-offset-2"
                    >
                        Go to User Management → Create Account
                    </a>
                </div>
            )}

            <form
                onSubmit={submit}
                className="max-w-3xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div>
                    <label className={labelClass}>Login Account (required)</label>
                    <select
                        value={data.user_id}
                        onChange={(e) => setData('user_id', e.target.value)}
                        required
                        disabled={!hasGuardAccounts}
                        className={inputClass}
                    >
                        <option value="">Select a User Management account…</option>
                        {guardUsers.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name} ({user.email})
                            </option>
                        ))}
                    </select>
                    {errors.user_id && <p className="mt-1 text-xs text-red-500">{errors.user_id}</p>}
                    <p className="mt-1 text-xs text-slate-500">
                        Only Guard accounts that are not yet linked to personnel are listed.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label className={labelClass}>Guard License Number</label>
                        <input
                            type="text"
                            value={data.license_number}
                            onChange={(e) => setData('license_number', e.target.value)}
                            required
                            disabled={!hasGuardAccounts}
                            className={inputClass}
                            placeholder="e.g. LIC-2026-001"
                        />
                        {errors.license_number && <p className="mt-1 text-xs text-red-500">{errors.license_number}</p>}
                    </div>
                    <div>
                        <label className={labelClass}>Status</label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            disabled={!hasGuardAccounts}
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
                            disabled={!hasGuardAccounts}
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
                            disabled={!hasGuardAccounts}
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
                            disabled={!hasGuardAccounts}
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
                            disabled={!hasGuardAccounts}
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
                            disabled={!hasGuardAccounts}
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
                            disabled={!hasGuardAccounts}
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
                            disabled={!hasGuardAccounts}
                            className={inputClass}
                        />
                    </div>
                    <div>
                        <label className={labelClass}>Emergency Phone</label>
                        <input
                            type="text"
                            value={data.emergency_phone}
                            onChange={(e) => setData('emergency_phone', e.target.value)}
                            disabled={!hasGuardAccounts}
                            className={inputClass}
                        />
                    </div>
                </div>

                <div>
                    <label className={labelClass}>Valid ID Picture (required)</label>
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        disabled={!hasGuardAccounts}
                        required
                        onChange={(e) => setData('id_pictures', Array.from(e.target.files || []))}
                        className={inputClass}
                    />
                    <p className="mt-1 text-xs text-slate-500">
                        Upload at least one clear photo of a valid ID. You may select more than one.
                    </p>
                    {errors.id_pictures && <p className="mt-1 text-xs text-red-500">{errors.id_pictures}</p>}
                    {errors['id_pictures.0'] && (
                        <p className="mt-1 text-xs text-red-500">{errors['id_pictures.0']}</p>
                    )}
                </div>

                <div>
                    <label className={labelClass}>Notes</label>
                    <textarea
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        disabled={!hasGuardAccounts}
                        className={inputClass}
                    />
                </div>

                <div className="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Face registration happens in the APO Guard mobile app after this profile is linked. The same enrolled
                    face is required for Face In and Face Out.
                </div>

                <div className="flex flex-wrap gap-3 pt-2">
                    <button
                        type="submit"
                        disabled={processing || !hasGuardAccounts}
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
