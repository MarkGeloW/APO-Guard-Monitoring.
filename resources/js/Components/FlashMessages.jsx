import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function FlashMessages() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            // Could integrate toast library later
        }
    }, [flash]);

    if (!flash?.success && !flash?.error) return null;

    return (
        <div className="fixed right-4 top-4 z-50 space-y-2">
            {flash.success && (
                <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-lg">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 shadow-lg">
                    {flash.error}
                </div>
            )}
        </div>
    );
}
