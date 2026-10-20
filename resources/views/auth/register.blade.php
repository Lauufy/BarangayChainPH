@extends('layouts.guest')

@section('title', 'Create Account')

@section('content')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <div class="w-full min-w-0">

        {{-- Header --}}
        <header class="mb-5">
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#00229b]">
                Citizen Portal
            </p>

            <h1 class="text-2xl font-bold leading-tight tracking-tight text-[#0F1B3D] sm:text-3xl">
                Create an Account
            </h1>

            <p class="mt-2 text-sm leading-6 text-[#020746]">
                Register to access BarangayChain PH services.
            </p>
        </header>

        {{-- Log In / Sign Up tabs --}}
        <div class="mb-5 flex rounded-md border border-[#DDE3F3] bg-[#E6ECFA] p-1">
            <a href="{{ route('login') }}"
                class="flex min-w-0 flex-1 items-center justify-center rounded px-3 py-2.5 text-sm font-semibold text-[#5A6788] transition hover:text-[#0F1B3D]">
                Log In
            </a>

            <a href="{{ route('register') }}"
                aria-current="page"
                class="flex min-w-0 flex-1 items-center justify-center rounded bg-white px-3 py-2.5 text-sm font-semibold text-[#0F1B3D] shadow-sm">
                Sign Up
            </a>
        </div>

        {{-- Errors --}}
        <x-feedbacks.form-errors />

        {{-- Registration form --}}
        <form action="{{ route('register.store') }}" method="POST" class="space-y-4" data-validate>
            @csrf

            {{-- Full name + Phone --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input
                    light
                    name="name"
                    label="Full Name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Enter your full name"
                />

                <x-form.input
                    light
                    name="phone"
                    label="Phone Number"
                    type="tel"
                    inputmode="numeric"
                    pattern="[0-9]{11}"
                    maxlength="11"
                    minlength="11"
                    required
                    autocomplete="tel"
                    placeholder="Enter your 11-digit phone number"
                />
            </div>

            {{-- Email --}}
            <x-form.input
                light
                name="email"
                label="Email Address"
                type="email"
                required
                data-blur
                data-error="Please enter a valid email address."
                autocomplete="email"
                placeholder="you@example.com"
            />

            {{-- Password + Confirm Password --}}
            <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
                <x-form.input
                    light
                    name="password"
                    label="Password"
                    type="password"
                    data-password-strength
                    maxlength="16"
                    required
                    autocomplete="new-password"
                    placeholder="At least 8 characters"
                >
                    <x-feedbacks.password-requirement />
                </x-form.input>

                <x-form.input
                    light
                    name="password_confirmation"
                    label="Confirm Password"
                    type="password"
                    data-password-confirmation
                    maxlength="16"
                    required
                    autocomplete="new-password"
                    placeholder="Re-enter your password"
                />
            </div>

            {{-- reCAPTCHA --}}
            <div class="min-w-0">
                <div class="max-w-full overflow-x-auto">
                    <div
                        class="g-recaptcha"
                        data-theme="light"
                        data-sitekey="{{ config('services.recaptcha.site_key') }}"
                    ></div>
                </div>

                @error('g-recaptcha-response')
                    <p class="mt-2 text-xs font-medium text-[#CE1126]">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Terms --}}
            <x-form.checkbox light name="terms" required>
                I agree to the
                <a href="{{ route('terms') }}" target="_blank" rel="noopener"
                    class="font-semibold text-[#1D3FBB] transition hover:text-[#16318F]">
                    Terms of Service
                </a>
                and
                <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                    class="font-semibold text-[#1b5f21] transition hover:text-[#168f16]">
                    Privacy Policy
                </a>.
            </x-form.checkbox>

            {{-- Submit --}}
            <x-form.button type="submit" class="w-full" loading-text="Signing up...">
                CREATE ACCOUNT
            </x-form.button>
        </form>

        {{-- Login link --}}
        <p class="mt-5 text-center text-sm text-[#3B4868]">
            Already have an account?
            <a href="{{ route('login') }}"
                class="font-semibold text-[#0F1B3D] transition hover:text-[#1D3FBB]">
                Sign in
            </a>
        </p>

        {{-- Verification notice --}}
        <p class="mt-3 text-center text-xs leading-5 text-[#5A6788]">
            Your account requires email verification before you can access
            BarangayChain PH services.
        </p>

    </div>
@endsection

@section('panel')
    <x-partials.journey-panel :active="1" />
@endsection