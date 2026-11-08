@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-(--blue-600) dark:text-(--blue-300)">
                    Community Services Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-(--gray-900) dark:text-(--gray-50)">
                    Reset your password
                </h1>

                <p class="mt-2 text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                    {{ $isSmsReset
                        ? 'Your mobile number is verified. Create a new password for your BarangayChain PH account.'
                        : 'Create a new password for your BarangayChain PH account.' }}
                </p>
            </header>

            <x-feedbacks.form-errors />

            <div class="rounded-md border border-(--gray-200) bg-(--white) p-5 shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800) sm:p-6">
                <form method="POST"
                    action="{{ route($isSmsReset ? 'password.sms.update' : 'password.update') }}"
                    class="space-y-4">
                    @csrf

                    @unless ($isSmsReset)
                        <input type="hidden" name="token" value="{{ $token }}">

                        <x-form.input light name="email" label="Email Address" type="email" :value="$email" required
                            autocomplete="email" placeholder="Enter your registered email" />
                    @endunless

                    <x-form.input light name="password" label="New Password" type="password" data-password-strength
                        maxlength="16" required autocomplete="new-password" placeholder="Enter your new password">
                        <x-feedbacks.password-requirement />
                    </x-form.input>

                    <x-form.input light name="password_confirmation" label="Confirm New Password" type="password"
                        data-password-confirmation maxlength="16" required autocomplete="new-password"
                        placeholder="Re-enter your new password" />

                    <x-form.button type="submit" color="blue" class="w-full" loading-text="Updating password...">
                        RESET PASSWORD
                    </x-form.button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-(--gray-500) dark:text-(--gray-400)">
                For your security, choose a password you haven't used before.
            </p>
        </section>
    </main>
@endsection