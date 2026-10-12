@extends('layouts.guest')

@section('title', 'Create Account')

@section('content')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    {{-- Header --}}
    <header class="mb-6">
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-400">
            Citizen Portal
        </p>

        <h1 class="text-3xl font-bold leading-tight tracking-tight text-white">
            Create an account
        </h1>

        <p class="mt-2 text-sm leading-6 text-blue-100/70">
            Register to access BarangayChain PH services.
        </p>
    </header>

    {{-- Log In / Sign Up tabs --}}
    <div class="mb-5 flex rounded-md border border-white/10 bg-[#09172D] p-1">
        <a href="{{ route('login') }}"
            class="flex flex-1 items-center justify-center rounded px-3 py-2.5 text-sm font-semibold text-blue-100/60 transition hover:text-white">
            Log In
        </a>

        <a href="{{ route('register') }}" aria-current="page"
            class="flex flex-1 items-center justify-center rounded bg-[#172A45] px-3 py-2.5 text-sm font-semibold text-white shadow-sm">
            Sign Up
        </a>
    </div>

    {{-- Errors --}}
    <x-feedbacks.form-errors />

    {{-- Register form --}}
    <form action="{{ route('register.store') }}" method="POST" class="space-y-4" data-validate>
        @csrf

        {{-- Full name + Phone --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form.input name="name" label="Full Name" required autofocus autocomplete="name"
                placeholder="Enter your full name" data-validate />

            <x-form.input name="phone" label="Phone Number" type="tel" inputmode="tel" required autocomplete="tel"
                placeholder="Enter your phone number" />
        </div>

        {{-- Email --}}
        <x-form.input name="email" label="Email Address" type="email" required autocomplete="email"
            placeholder="you@example.com" data-validate />

        {{-- Password + Confirm password --}}
        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
            <x-form.input name="password" label="Password" type="password" data-password-strength maxlength="16" required
                autocomplete="new-password" placeholder="At least 8 characters">
                {{-- Password rules checklist --}}
                <x-feedbacks.password-requirement />
            </x-form.input>

            <x-form.input name="password_confirmation" label="Confirm Password" type="password" data-password-confirmation
                maxlength="16" required autocomplete="new-password" placeholder="Re-enter your password" />
        </div>

        {{-- reCAPTCHA --}}
        <div>
            <div class="overflow-x-auto">
                <div class="g-recaptcha" data-theme="dark" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            </div>
            @error('g-recaptcha-response')
                <p class="mt-2 text-xs font-medium text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Terms --}}
        <x-form.checkbox name="terms" required>
            I agree to the
            <a href="{{ route('terms') }}" target="_blank" rel="noopener"
                class="font-semibold text-emerald-400 transition hover:text-emerald-300">Terms of Service</a>
            and
            <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                class="font-semibold text-emerald-400 transition hover:text-emerald-300">Privacy Policy</a>.
        </x-form.checkbox>

        {{-- Submit --}}
        <x-form.button type="submit" class="w-full" loading-text="Signing up...">
            CREATE ACCOUNT
        </x-form.button>
    </form>

    <p class="mt-6 text-center text-sm text-blue-100/70">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-white transition hover:text-emerald-300">
            Sign in
        </a>
    </p>

    <p class="mt-3 text-center text-xs leading-5 text-blue-100/50">
        Your account requires email verification before you can access
        BarangayChain PH services.
    </p>

@section('panel')
    <x-partials.journey-panel :active="1" />
@endsection
@endsection
