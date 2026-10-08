<section>
    <div class="mb-5 flex items-center gap-3 border-b border-red-50 pb-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M3 6h18M8 6V4h8v2m1 0v14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V6h10Z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-bold text-slate-900">Delete Account</h2>
            <p class="text-xs text-slate-500">Permanently remove this account and related access. This cannot be undone.</p>
        </div>
    </div>

    <div class="rounded-xl border border-red-100 bg-red-50/50 px-4 py-3 text-xs leading-relaxed text-red-800">
        Before deleting, make sure you no longer need access to deployments, reports, or alerts tied to this login.
    </div>

    <button
        type="button"
        data-swal-delete-account
        data-action="{{ route('profile.destroy') }}"
        class="mt-5 inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-5 py-3 text-sm font-bold text-red-600 hover:bg-red-50"
    >
        Delete Account
    </button>
</section>
