@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
    @php
        $link =
            'font-semibold text-(--blue-700) transition hover:text-(--blue-800) dark:text-(--blue-300) dark:hover:text-(--blue-200)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
    @endphp

    <div class="w-full min-w-0">

        {{-- Header --}}
        <header class="mb-5">
            <h1 class="text-(--gray-900) dark:text-(--gray-50) text-2xl font-bold leading-tight tracking-tight sm:text-3xl">
                Sign In to Continue
            </h1>

            <p class="text-(--gray-600) dark:text-(--gray-300) mt-2 text-sm leading-6">
                Log in to access your account and barangay services.
            </p>
        </header>

        {{-- Errors --}}
        <x-feedbacks.form-errors />

        {{-- Login form (data-validate = check fields after the user stops typing) --}}
        <form action="{{ route('login.store') }}" method="POST" class="space-y-4" data-validate data-login-form>
            @csrf

            {{-- Email / Employee ID tab  or  Mobile number tab --}}
            <x-form.contact-tabs email-name="identifier" email-label="Email Address or Employee ID" email-type="text"
                email-autocomplete="username" email-placeholder="Enter your email or employee ID" />

            {{-- Password --}}
            <x-form.input light name="password" label="Password" type="password" maxlength="30" required
                autocomplete="current-password" placeholder="Enter your password" />

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
        <p class="text-(--gray-600) dark:text-(--gray-300) mt-5 text-center text-sm">
            Don't have an account?
            <a href="{{ route('register') }}"
                class="text-(--gray-900) hover:text-(--blue-700) dark:text-(--gray-50) dark:hover:text-(--blue-300) font-semibold transition">
                Create an account
            </a>
        </p>

        {{-- OR divider --}}
        <div class="{{ $muted }} my-4 flex items-center gap-3 text-sm">
            <div class="bg-(--gray-200) dark:bg-(--gray-700) h-px min-w-0 flex-1"></div>
            <span class="shrink-0">or</span>
            <div class="bg-(--gray-200) dark:bg-(--gray-700) h-px min-w-0 flex-1"></div>
        </div>

        {{-- Google login --}}
        <a href="{{ route('google.redirect') }}"
            class="border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) flex w-full min-w-0 items-center justify-center gap-2 rounded-md border px-3 py-3 text-sm font-medium transition sm:px-4">
            <img src="{{ asset('img/branding/g-logo.png') }}" alt="" width="20" height="20" class="h-5 w-5"
                aria-hidden="true">
            <span>Continue with Google</span>
        </a>

        {{-- Security note --}}
        <p class="{{ $muted }} mt-3 text-center text-xs leading-5">
            Keep your password private. Never share it with anyone
        </p>

    </div>

    {{-- Lockout countdown: disables the login button for 5 minutes after 3 wrong attempts --}}
    <script>
        (function() {
            const form = document.querySelector('[data-login-form]');
            const btn = form?.querySelector('button[type="submit"]');
            if (!btn) return;

            const KEY = 'login_lockout_until';
            const original = btn.innerHTML;

            @if (session('lockout_seconds'))
                try {
                    localStorage.setItem(KEY, Date.now() + {{ (int) session('lockout_seconds') }} * 1000);
                } catch (e) {}
            @endif

            let until = 0;
            try {
                until = parseInt(localStorage.getItem(KEY) || '0', 10);
            } catch (e) {}
            if (until <= Date.now()) return;

            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');

            const tick = () => {
                const left = Math.ceil((until - Date.now()) / 1000);

                if (left <= 0) {
                    clearInterval(timer);
                    try {
                        localStorage.removeItem(KEY);
                    } catch (e) {}
                    btn.disabled = false;
                    btn.classList.remove('opacity-60', 'cursor-not-allowed');
                    btn.innerHTML = original;
                    return;
                }

                const m = String(Math.floor(left / 60)).padStart(2, '0');
                const s = String(left % 60).padStart(2, '0');
                btn.textContent = `Try again in ${m}:${s}`;
            };

            tick();
            const timer = setInterval(tick, 1000);
        })();
    </script>
@endsection

@section('panel')
    <x-partials.journey-panel :active="0" />
@endsection