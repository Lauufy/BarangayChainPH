@extends('layouts.guest')

@section('title', 'Verify Email')

@section('content')

    <x-navigation.back-nav />

    {{-- Header --}}
    <header class="mb-8">
        <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-emerald-400">
            Citizen Portal
        </p>

        <h1 class="text-4xl font-bold tracking-tight text-white">
            Verify your email
        </h1>

        <p class="mt-3 text-sm leading-6 text-blue-100/65">
            One more step to secure your account.
        </p>
    </header>


    {{-- Email information --}}
    <div class="mb-6 rounded-lg border border-white/10 bg-[#09172D] px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
            Verification email
        </p>

        <p class="mt-1 break-all text-sm font-semibold text-emerald-400">
            {{ $email }}
        </p>
    </div>

    {{-- OTP verification --}}
    <form method="POST" action="{{ route('verification.otp.verify') }}" class="space-y-5">
        @csrf

        <div>
            <label for="otp" class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-300">
                Verification Code
            </label>

            <input id="otp" name="otp" type="text" value="{{ old('otp') }}" maxlength="6" minlength="6"
                inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder="000000" required
                autofocus oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
                class="w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-4 text-center text-2xl font-bold tracking-[0.5em] text-white outline-none transition placeholder:text-blue-100/30 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
        </div>

        <button id="verify-button" type="submit" data-loading data-loading-text="Verifying..." disabled
            class="flex w-full items-center justify-center gap-3 rounded-lg bg-emerald-600 px-4 py-4 text-sm font-bold uppercase tracking-[0.12em] text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-emerald-600">
            Verify Email
        </button>
    </form>

    {{-- Resend OTP --}}
    <form id="resend-form" method="POST" action="{{ route('verification.otp.resend') }}" class="mt-3">
        @csrf

        <button id="resend-button" type="submit"
            class="w-full rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-4 py-4 text-sm font-semibold text-emerald-300 transition hover:bg-emerald-400/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:cursor-not-allowed disabled:opacity-60">
            <span id="resend-text">Resend Verification Code</span>
        </button>
    </form>

    {{-- Cancel verification --}}
    <form method="POST" action="{{ route('verification.cancel') }}" class="mt-3">
        @csrf

        <button type="submit" data-loading data-loading-text="Canceling..."
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/10 bg-[#09172D] px-4 py-4 text-sm font-semibold text-slate-300 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:cursor-not-allowed disabled:opacity-70">
            {{ session('otp_flow') === 'login' ? 'Back to Login' : 'Cancel Verification' }}
        </button>
    </form>

    <p class="mt-8 text-center text-xs leading-5 text-blue-100/40">
        Your verification code is valid for 10 minutes.
        Check your spam or junk folder if needed.
    </p>

    <p class="mt-4 text-center text-xs leading-5 text-blue-100/40">
        &copy; {{ date('Y') }} BarangayChain PH<br>
        Barangay Management and Document Services
    </p>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Verify button: enabled only with 6 digits
            const otpInput = document.getElementById('otp');
            const verifyButton = document.getElementById('verify-button');

            const updateVerifyButton = () => {
                verifyButton.disabled = !/^\d{6}$/.test(otpInput.value);
            };

            otpInput.addEventListener('input', updateVerifyButton);
            window.addEventListener('pageshow', updateVerifyButton);
            updateVerifyButton();

            // Resend cooldown
            const resendForm = document.getElementById('resend-form');
            const resendButton = document.getElementById('resend-button');
            const resendText = document.getElementById('resend-text');

            const cooldownSeconds = 120;
            const storageKey = 'barangaychain_resend_cooldown';
            let cooldownTimer;

            const updateResendButton = () => {
                clearTimeout(cooldownTimer);

                const expiresAt = Number(sessionStorage.getItem(storageKey) || 0);
                const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));

                resendButton.disabled = remaining > 0;
                resendText.textContent = remaining > 0
                    ? `Resend available in ${remaining}s`
                    : 'Resend Verification Code';

                if (remaining > 0) {
                    cooldownTimer = setTimeout(updateResendButton, 1000);
                } else {
                    sessionStorage.removeItem(storageKey);
                }
            };

            resendForm.addEventListener('submit', () => {
                sessionStorage.setItem(
                    storageKey,
                    String(Date.now() + cooldownSeconds * 1000)
                );

                updateResendButton();
            });

            window.addEventListener('pageshow', updateResendButton);
            updateResendButton();
        });
    </script>
@endsection