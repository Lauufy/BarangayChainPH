@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')

    {{-- Header --}}
    <header class="mb-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#00229b]">
            Citizen Portal
        </p>

        <h1 class="font-['Inter',sans-serif] text-3xl font-bold leading-tight tracking-tight text-[#0F1B3D]">
            Sign In to Continue
        </h1>

        <p class="mt-2 text-sm font-semibold leading-6 text-[#020746]">
            Log in to access your account and barangay services.
        </p>
    </header>

    {{-- Log In / Sign Up tabs --}}
    <div class="mb-5 flex rounded-md border border-[#DDE3F3] bg-[#E6ECFA] p-1">
        <a href="{{ route('login') }}" aria-current="page"
            class="flex flex-1 items-center justify-center rounded bg-white px-3 py-2.5 text-sm font-semibold text-[#0F1B3D] shadow-sm">
            Log In
        </a>

        <a href="{{ route('register') }}"
            class="flex flex-1 items-center justify-center rounded px-3 py-2.5 text-sm font-semibold text-[#5A6788] transition hover:text-[#0F1B3D]">
            Sign Up
        </a>
    </div>

    {{-- Errors --}}
    <x-feedbacks.form-errors />

    {{-- Login form --}}
    <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
        @csrf

        {{-- Email or Employee ID --}}
        <x-form.input light name="identifier" label="Email Address or Employee ID" required autofocus autocomplete="username"
            placeholder="Enter your email or employee ID" />

        {{-- Password --}}
        <x-form.input light name="password" label="Password" type="password" maxlength="30" required
            autocomplete="current-password" placeholder="Password" />

        {{-- Remember me / Forgot password --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-form.checkbox name="remember">Remember me</x-form.checkbox>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                    class="text-sm font-semibold text-[#1D3FBB] transition hover:text-[#16318F]">
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <x-form.button type="submit" class="w-full" loading-text="Logging in...">
            LOGIN
        </x-form.button>
    </form>

    <p class="mt-6 text-center text-sm text-[#3B4868]">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-[#0F1B3D] transition hover:text-[#1D3FBB]">
            Create an account
        </a>
    </p>

    <p class="mt-3 text-center text-xs leading-5 text-[#5A6788]">
        Your account information is protected.
        Never share your password with anyone.
    </p>

@section('panel')
    <x-partials.journey-panel badge="Citizen Portal" title="Welcome back"
        subtitle="Log in to continue using barangay services." :active="0" />
@endsection

@endsection