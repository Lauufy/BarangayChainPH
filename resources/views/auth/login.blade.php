@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
    @php
        $link = 'font-semibold text-(--blue-700) transition hover:text-(--blue-800) dark:text-(--blue-300) dark:hover:text-(--blue-200)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
    @endphp

    <div class="w-full min-w-0">

        {{-- Header --}}
        <header class="mb-5">
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-(--blue-600) dark:text-(--blue-300)">
                Community Services Portal
            </p>

            <h1 class="text-2xl font-bold leading-tight tracking-tight text-(--gray-900) dark:text-(--gray-50) sm:text-3xl">
                Sign In to Continue
            </h1>

            <p class="mt-2 text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                Log in to access your account and barangay services.
            </p>
        </header>

        {{-- Errors --}}
        <x-feedbacks.form-errors />

        {{-- Login form --}}
        <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Email / Employee ID tab  or  Mobile number tab --}}
            <x-form.contact-tabs
                email-name="identifier"
                email-label="Email Address or Employee ID"
                email-type="text"
                email-autocomplete="username"
                email-placeholder="Enter your email or employee ID"
            />

            {{-- Password --}}
            <x-form.input
                light
                name="password"
                label="Password"
                type="password"
                maxlength="30"
                required
                autocomplete="current-password"
                placeholder="Password"
            />

            {{-- Remember me / Forgot password --}}
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                <x-form.checkbox light name="remember">
                    Remember me
                </x-form.checkbox>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="{{ $link }} text-sm">
                        Forgot password?
                    </a>
                @endif
            </div>

            {{-- Submit --}}
            <x-form.button type="submit" color="blue" class="w-full" loading-text="Logging in...">
                LOGIN
            </x-form.button>
        </form>

        {{-- Registration link --}}
        <p class="mt-5 text-center text-sm text-(--gray-600) dark:text-(--gray-300)">
            Don't have an account?
            <a href="{{ route('register') }}"
                class="font-semibold text-(--gray-900) transition hover:text-(--blue-700) dark:text-(--gray-50) dark:hover:text-(--blue-300)">
                Create an account
            </a>
        </p>

        {{-- OR divider --}}
        <div class="{{ $muted }} my-4 flex items-center gap-3 text-sm">
            <div class="h-px min-w-0 flex-1 bg-(--gray-200) dark:bg-(--gray-700)"></div>
            <span class="shrink-0">or</span>
            <div class="h-px min-w-0 flex-1 bg-(--gray-200) dark:bg-(--gray-700)"></div>
        </div>

        {{-- Google login --}}
        <a href="{{ route('google.redirect') }}"
            class="flex w-full min-w-0 items-center justify-center gap-2 rounded-md border border-(--gray-300) bg-(--white) px-3 py-3 text-sm font-medium text-(--gray-700) transition hover:bg-(--gray-50) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) sm:px-4">
            <img src="{{ asset('img/branding/g-logo.png') }}" alt="" width="20" height="20" class="h-5 w-5" aria-hidden="true">
            <span>Continue with Google</span>
        </a>

        {{-- Security note --}}
        <p class="{{ $muted }} mt-3 text-center text-xs leading-5">
            Your account information is protected.
            Never share your password with anyone.
        </p>

    </div>
@endsection

@section('panel')
    <x-partials.journey-panel :active="0" />
@endsection