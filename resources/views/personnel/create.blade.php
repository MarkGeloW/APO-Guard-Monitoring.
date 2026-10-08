<x-admin-layout>
    <x-slot name="header">Add Security Personnel</x-slot>

    <form method="POST" action="{{ route('personnel.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="{{ $labelClass }}">Badge Number</label><input type="text" name="badge_number" value="{{ old('badge_number') }}" required class="{{ $inputClass }}"></div>
            <div><label class="{{ $labelClass }}">Status</label>
                <select name="status" class="{{ $inputClass }}">
                    @foreach(['active','inactive','on_leave'] as $s)<option value="{{ $s }}">{{ str_replace('_',' ',ucfirst($s)) }}</option>@endforeach
                </select>
            </div>
            <div><label class="{{ $labelClass }}">First Name</label><input type="text" name="first_name" value="{{ old('first_name') }}" required class="{{ $inputClass }}"></div>
            <div><label class="{{ $labelClass }}">Last Name</label><input type="text" name="last_name" value="{{ old('last_name') }}" required class="{{ $inputClass }}"></div>
            <div><label class="{{ $labelClass }}">Email</label><input type="email" name="email" value="{{ old('email') }}" class="{{ $inputClass }}"></div>
            <div><label class="{{ $labelClass }}">Phone</label><input type="text" name="phone" value="{{ old('phone') }}" class="{{ $inputClass }}"></div>
            <div class="md:col-span-2"><label class="{{ $labelClass }}">Address</label><input type="text" name="address" value="{{ old('address') }}" class="{{ $inputClass }}"></div>
            <div><label class="{{ $labelClass }}">Date Hired</label><input type="date" name="date_hired" value="{{ old('date_hired') }}" class="{{ $inputClass }}"></div>
            <div><label class="{{ $labelClass }}">Emergency Contact</label><input type="text" name="emergency_contact" value="{{ old('emergency_contact') }}" class="{{ $inputClass }}"></div>
            <div><label class="{{ $labelClass }}">Emergency Phone</label><input type="text" name="emergency_phone" value="{{ old('emergency_phone') }}" class="{{ $inputClass }}"></div>
        </div>
        <div><label class="{{ $labelClass }}">Notes</label><textarea name="notes" rows="3" class="{{ $inputClass }}">{{ old('notes') }}</textarea></div>

        <div class="border-t border-slate-200 pt-4 space-y-3">
            <p class="font-medium text-slate-900">System Account (Optional)</p>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="create_account" value="1"> Create new guard login account</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="{{ $labelClass }}">Account Email</label><input type="email" name="account_email" value="{{ old('account_email') }}" class="{{ $inputClass }}"></div>
                <div><label class="{{ $labelClass }}">Account Password</label><input type="password" name="account_password" class="{{ $inputClass }}"></div>
            </div>
            <div><label class="{{ $labelClass }}">Or link existing guard account</label>
                <select name="user_id" class="{{ $inputClass }}">
                    <option value="">— None —</option>
                    @foreach($guardUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>@endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="{{ $btnPrimary }}">Save Personnel</button>
            <a href="{{ route('personnel.index') }}" class="{{ $btnSecondary }}">Cancel</a>
        </div>
    </form>
</x-admin-layout>
