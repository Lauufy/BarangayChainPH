@extends('layouts.guest')

@section('title', 'Create Account')

@section('content')

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <x-navigation.back-nav />

    {{-- Header --}}
    <header class="mb-8">
        <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-emerald-400">
            Citizen Portal
        </p>

        <h1 class="text-4xl font-bold tracking-tight text-white">
            Create an account
        </h1>

        <p class="mt-3 text-sm leading-6 text-blue-100/65">
            Register to access BarangayChain PH services.
        </p>
    </header>

    {{-- Log In / Sign Up tabs --}}
    <div class="mb-6 flex rounded-lg border border-white/10 bg-[#09172D] p-1">
        <a href="{{ route('login') }}"
            class="flex flex-1 items-center justify-center rounded-md px-4 py-3 text-sm font-medium text-blue-100/60 transition hover:text-white">
            Log In
        </a>

        <a href="{{ route('register') }}" aria-current="page"
            class="flex flex-1 items-center justify-center rounded-md bg-[#172A45] px-4 py-3 text-sm font-semibold text-white shadow-sm">
            Sign Up
        </a>
    </div>

    {{-- Errors --}}
    <x-feedbacks.form-errors />

    {{-- Registration form --}}
    <form action="{{ route('register.store') }}" method="POST" class="space-y-5" data-validate>
        @csrf

        {{-- Full name --}}
        <div>
            <label for="name" class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-300">
                Full Name
            </label>

            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                autocomplete="name" placeholder="Enter your full name" data-validate
                class="validation-input w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-4 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-300">
                Email Address
            </label>

            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                autocomplete="email" placeholder="you@example.com" data-validate
                class="validation-input w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-4 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-300">
                Password
            </label>

            <div class="relative">
                <input id="password" data-password name="password" type="password" maxlength="16" required
                    autocomplete="new-password" placeholder="At least 8 characters"
                    class="validation-input w-full rounded-lg border border-white/10 bg-[#0B2347] py-4 pl-4 pr-12 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                {{-- Show / hide password --}}
                <button type="button" data-password-toggle="password" aria-label="Show password"
                    class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-4 text-blue-100/50 transition hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">

                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8" aria-hidden="true">
                        <g data-password-eye>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </g>
                    </svg>
                </button>
            </div>

            {{-- <x-feedbacks-requirements input-id="password" /> --}}
        </div>

        {{-- Confirm password --}}
        <div>
            <label for="password_confirmation" class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-300">
                Confirm Password
            </label>

            <div class="relative">
                <input id="password_confirmation" data-password-confirmation name="password_confirmation" type="password" maxlength="16" required
                    autocomplete="new-password" placeholder="Re-enter your password"
                    class="validation-input w-full rounded-lg border border-white/10 bg-[#0B2347] py-4 pl-4 pr-12 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                {{-- Show / hide password --}}
                <button type="button" data-password-toggle="password_confirmation" aria-label="Show password"
                    class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-4 text-blue-100/50 transition hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">

                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8" aria-hidden="true">
                        <g data-password-eye>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </g>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Google reCAPTCHA --}}
        <div>
            <div class="overflow-x-auto">
                <div class="g-recaptcha" data-theme="dark"
                    data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            </div>

            @error('g-recaptcha-response')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Terms and privacy checkbox --}}
        <div>
            <label class="flex cursor-pointer items-start gap-2.5">
                <span class="relative flex h-4 w-4 shrink-0 mt-0.5">
                    <input id="terms" type="checkbox" name="terms" value="1" required @checked(old('terms'))
                        class="peer h-4 w-4 cursor-pointer appearance-none rounded border border-white/20 bg-[#0B2347] transition checked:border-emerald-600 checked:bg-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">

                    {{-- Check mark (shows when checked) --}}
                    <svg class="pointer-events-none absolute inset-0 m-auto h-3 w-3 text-white opacity-0 transition peer-checked:opacity-100"
                        viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 8.5l3 3 6-6.5" />
                    </svg>
                </span>

                <span class="text-xs leading-5 text-blue-100/60">
                    I agree to the
                    <a href="{{ route('terms') }}" target="_blank" rel="noopener"
                        class="font-semibold text-emerald-400 transition hover:text-emerald-300">Terms of Service</a>
                    and
                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                        class="font-semibold text-emerald-400 transition hover:text-emerald-300">Privacy Policy</a>.
                </span>
            </label>

            @error('terms')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" data-loading data-loading-text="Creating account..."
            class="flex w-full items-center justify-center gap-3 rounded-lg bg-emerald-600 px-4 py-4 text-sm font-bold uppercase tracking-[0.12em] text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18] disabled:cursor-not-allowed disabled:opacity-70">
            Create Account
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7" />
            </svg>
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-blue-100/60">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-white transition hover:text-emerald-300">
            Sign in
        </a>
    </p>

    <p class="mt-4 text-center text-xs leading-5 text-blue-100/40">
        Your account requires email verification before you can access
        BarangayChain PH services.
    </p>
@endsection