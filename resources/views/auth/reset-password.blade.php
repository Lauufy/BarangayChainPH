@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-400">
                    Citizen Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-white">
                    Reset your password
                </h1>

                <p class="mt-2 text-sm leading-6 text-blue-100/70">
                    Create a new password for your BarangayChain PH account.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            {{-- Reset password form --}}
            <div class="rounded-md border border-white/10 bg-[#09172D] p-5 sm:p-6">
                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    {{-- Email (filled from the reset link) --}}
                    <x-form.input name="email" label="Email Address" type="email" :value="$email" required
                        autocomplete="email" placeholder="Enter your registered email" />

                    {{-- New password + rules checklist --}}
                    <x-form.input name="password" label="New Password" type="password" data-password-strength maxlength="16" required
                        autocomplete="new-password" placeholder="Enter your new password">
                        <x-feedbacks.password-requirement />
                    </x-form.input>

                    {{-- Confirm password --}}
                    <x-form.input name="password_confirmation" label="Confirm New Password" type="password" data-password-confirmation maxlength="16"
                        required autocomplete="new-password" placeholder="Re-enter your new password" />

                    {{-- Submit --}}
                    <x-form.button type="submit" class="w-full" loading-text="Updating password...">
                        RESET PASSWORD
                    </x-form.button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-blue-100/50">
                For your security, choose a password you haven't used before.
            </p>

        </section>
    </main>
@endsection
