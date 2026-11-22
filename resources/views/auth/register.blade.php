@extends('layouts.guest')

@section('title', 'Create Account')

@section('content')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <div class="w-full min-w-0">

        {{-- Header --}}
        <header class="mb-5">

            <h1 class="text-(--gray-900) dark:text-(--gray-50) text-2xl font-bold leading-tight tracking-tight sm:text-3xl">
                Create an Account
            </h1>

            <p class="text-(--gray-600) dark:text-(--gray-300) mt-2 text-sm leading-6">
                Register to access BarangayChain PH services.
            </p>
        </header>

        {{-- Errors --}}
        <x-feedbacks.form-errors />

        {{-- Registration form --}}
        <form action="{{ route('register.store') }}" method="POST" class="space-y-4" data-validate>
            @csrf

            {{-- Sign up with an email or with a mobile number --}}
            <x-form.contact-tabs email-name="email" email-label="Email Address" email-placeholder="juan@gmail.com" />

            {{-- Full name --}}
            <x-form.input light name="name" label="Full Name" required autofocus autocomplete="name"
                placeholder="Ex: Juan Dela Cruz" />

            {{-- Password + Confirm Password --}}
            <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
                {{-- Weak password text is hidden here, the requirement list below covers it --}}
                <x-form.input light name="password" label="Password" type="password" data-password-strength maxlength="16"
                    required autocomplete="new-password" placeholder="Ex: Juan@2026">
                    <x-feedbacks.password-requirement />
                </x-form.input>

                <x-form.input light name="password_confirmation" label="Confirm Password" type="password"
                    data-password-confirmation maxlength="16" required autocomplete="new-password"
                    placeholder="Re-enter your password" error-message="Passwords do not match." />
            </div>

            {{-- reCAPTCHA (its theme follows the site theme) --}}
            <div class="min-w-0">
                <div class="max-w-full overflow-x-auto">
                    <div id="recaptcha-box" class="g-recaptcha" data-theme="light"
                        data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    <script>
                        document.getElementById('recaptcha-box').dataset.theme =
                            document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                    </script>
                </div>

                @error('g-recaptcha-response')
                    <p class="text-(--red-600) dark:text-(--red-500) mt-2 text-xs font-medium">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Terms --}}
            <x-form.checkbox light name="terms" required>
                I agree to the
                <a href="{{ route('terms') }}" target="_blank" rel="noopener"
                    class="text-(--blue-700) hover:text-(--blue-800) dark:text-(--blue-300) dark:hover:text-(--blue-200) font-semibold transition">
                    Terms of Service
                </a>
                and
                <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                    class="text-(--green-600) hover:text-(--green-700) dark:text-(--green-300) dark:hover:text-(--green-200) font-semibold transition">
                    Privacy Policy
                </a>.
            </x-form.checkbox>

            {{-- Submit --}}
            <x-form.button type="submit" color="blue" class="w-full" loading-text="Signing up...">
                CREATE ACCOUNT
            </x-form.button>
        </form>

        {{-- Login link --}}
        <p class="text-(--gray-600) dark:text-(--gray-300) mt-5 text-center text-sm">
            Already have an account?
            <a href="{{ route('login') }}"
                class="text-(--gray-900) hover:text-(--blue-700) dark:text-(--gray-50) dark:hover:text-(--blue-300) font-semibold transition">
                Sign in
            </a>
        </p>

        {{-- Verification notice --}}
        <p class="text-(--gray-500) dark:text-(--gray-400) mt-3 text-center text-xs leading-5">
            To keep your account secure, we'll send a verification code to your email or mobile number to confirm your
            details during registration
        </p>

    </div>
@endsection

@section('panel')
    <x-partials.journey-panel :active="1" />
@endsection
