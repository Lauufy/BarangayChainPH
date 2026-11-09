@php($authUser = auth()->user())

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @method('PUT')

    {{-- The hidden 0 makes an unchecked box still submit a value --}}
    <input type="hidden" name="two_factor_enabled" value="0">

    <label class="flex items-start gap-3 text-sm">
        <input type="checkbox" name="two_factor_enabled" value="1"
            @checked(old('two_factor_enabled', $authUser->two_factor_enabled))
            class="mt-1 h-4 w-4 rounded border-slate-300">
        <span class="font-semibold">Require a verification code when I log in</span>
    </label>
    @error('two_factor_enabled')
        <p class="text-sm text-red-600" role="alert">{{ $message }}</p>
    @enderror

    <div>
        <label for="two-factor-password" class="block text-sm font-medium">Current password</label>
        <input id="two-factor-password" type="password" name="current_password" required
            autocomplete="current-password"
            class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 sm:max-w-sm">
        @error('current_password')
            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit"
        class="rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
        Save
    </button>
</form>