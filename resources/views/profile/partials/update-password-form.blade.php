<section>
    <div class="mb-5 flex items-center gap-3 border-b border-slate-100 pb-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 1 0-8 0v2"/>
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-bold text-slate-900">Update Password</h2>
            <p class="text-xs text-slate-500">Use a long, unique password to keep this account secure.</p>
        </div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="{{ $labelClass }}">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="{{ $inputClass }}">
            @error('current_password', 'updatePassword')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="update_password_password" class="{{ $labelClass }}">New Password</label>
                <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="{{ $inputClass }}">
                @error('password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="update_password_password_confirmation" class="{{ $labelClass }}">Confirm Password</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $inputClass }}">
                @error('password_confirmation', 'updatePassword')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600">
                Update Password
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2200)"
                    class="text-sm font-semibold text-emerald-600"
                >Saved.</p>
            @endif
        </div>
    </form>
</section>
