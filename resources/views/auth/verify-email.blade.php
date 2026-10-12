@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')

    {{-- Header --}}
    <header class="mb-6">
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-400">
            Citizen Portal
        </p>

        <h1 class="text-3xl font-bold leading-tight tracking-tight text-white">
            Verify your email
        </h1>

        <p class="mt-2 text-sm leading-6 text-blue-100/70">
            One more step to secure your account.
        </p>
    </header>

    {{-- Email information --}}
    <div class="mb-5 rounded-md border border-white/10 bg-[#09172D] px-3.5 py-2.5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
            Verification email
        </p>

        <p class="mt-1 break-all text-sm font-semibold text-emerald-400">
            {{ $email }}
        </p>
    </div>

    {{-- Errors --}}
    <x-feedbacks.form-errors />

    {{-- OTP form --}}
    <form method="POST" action="{{ route('verification.otp.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label for="otp" class="mb-1.5 block text-sm font-medium text-blue-100">
                Verification Code
            </label>

            <input id="otp" name="otp" type="text" value="{{ old('otp') }}" maxlength="6" minlength="6"
                inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder="000000" required autofocus
                class="w-full rounded-md border border-white/10 bg-[#0B2347] px-3.5 py-3 text-center text-xl font-bold tracking-[0.5em] text-white outline-none transition placeholder:text-blue-100/30 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

            @error('otp')
                <p class="mt-1 text-xs font-medium text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Verify email with a loading spinner; initially disabled until validation passes --}}
        <x-form.button id="verify-button" type="submit" loading-text="Verifying..." disabled
            class="w-full gap-3 py-3 tracking-wide shadow-lg shadow-emerald-950/20 focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18] disabled:hover:bg-emerald-600">
            Verify Email
        </x-form.button>
    </form>

    {{-- Resend code --}}
    <form id="resend-form" method="POST" action="{{ route('verification.otp.resend') }}" class="mt-2.5">
        @csrf

        <button id="resend-button" type="submit"
            class="w-full rounded-md border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-300 transition hover:bg-emerald-400/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:cursor-not-allowed disabled:opacity-60">
            <span id="resend-text">Resend Verification Code</span>
        </button>
    </form>

    {{-- Cancel --}}
    <form method="POST" action="{{ route('verification.cancel') }}" class="mt-2.5">
        @csrf

        {{-- Cancel OTP verification or return to login with a loading spinner --}}
        <x-form.button type="submit" variant="secondary" loading-text="Canceling..."
            class="w-full border-white/10 bg-[#09172D] py-3 text-slate-300 transition hover:text-white focus-visible:ring-2 focus-visible:ring-emerald-400">
            {{ session('otp_flow') === 'login' ? 'Back to Login' : 'Cancel Verification' }}
        </x-form.button>
    </form>

    <p class="mt-6 text-center text-xs leading-5 text-blue-100/50">
        Your verification code is valid for 10 minutes.
        Check your spam or junk folder if needed.
    </p>

    <script>
        // ---------- OTP input ----------
        const otp = document.getElementById('otp');
        const verifyButton = document.getElementById('verify-button');

        // Keep digits only (max 6) and enable the button only when there are 6
        function updateCode() {
            otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
            verifyButton.disabled = otp.value.length !== 6;
        }

        otp.addEventListener('input', updateCode);
        window.addEventListener('pageshow', updateCode);
        updateCode();

        // ---------- Resend cooldown (2 minutes) ----------
        const resendForm = document.getElementById('resend-form');
        const resendButton = document.getElementById('resend-button');
        const resendText = document.getElementById('resend-text');
        const storageKey = 'barangaychain_resend_cooldown';
        let timer;

        function updateResend() {
            clearTimeout(timer);

            const secondsLeft = Math.ceil((Number(sessionStorage.getItem(storageKey)) - Date.now()) / 1000);

            if (secondsLeft > 0) {
                resendButton.disabled = true;
                resendText.textContent = `Resend available in ${secondsLeft}s`;
                timer = setTimeout(updateResend, 1000);
            } else {
                resendButton.disabled = false;
                resendText.textContent = 'Resend Verification Code';
            }
        }

        // Start the cooldown when the resend form is submitted
        resendForm.addEventListener('submit', () => {
            sessionStorage.setItem(storageKey, Date.now() + 120 * 1000);
            updateResend();
        });

        updateResend();
        window.addEventListener('pageshow', updateResend);
    </script>
@endsection
