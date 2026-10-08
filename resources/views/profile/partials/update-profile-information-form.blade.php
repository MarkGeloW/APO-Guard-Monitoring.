<section>
    <div class="mb-5 flex items-center gap-3 border-b border-slate-100 pb-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-bold text-slate-900">Profile Information</h2>
            <p class="text-xs text-slate-500">Update your display name and email address.</p>
        </div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="{{ $labelClass }}">Full Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" class="{{ $inputClass }}">
            @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="{{ $labelClass }}">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="{{ $inputClass }}">
            @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2.5 text-xs text-amber-800">
                    Your email address is unverified.
                    <button form="send-verification" class="font-bold underline hover:text-amber-950">
                        Resend verification email
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1.5 font-semibold text-emerald-700">A new verification link has been sent.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600">
                Save Profile
            </button>

            @if (session('status') === 'profile-updated')
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
