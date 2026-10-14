@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
    <main class="flex min-h-[calc(100vh-5rem)] px-4">
        <section class="w-full max-w-md font-['Inter',sans-serif]">

            {{-- Header --}}
            <header class="mb-4">

                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#00229b]">
                    Citizen Portal
                </p>
                <h1 class="font-['Inter',sans-serif] text-3xl font-bold leading-tight tracking-tight text-[#0F1B3D]">
                    Verify your email
                </h1>
                <p class="mt-2 text-sm leading-6 text-[#020746]">
                    Enter the 6-digit code sent to
                    <span class="break-all font-semibold text-slate-800">{{ $email }}</span>.
                </p>
            </header>

            <x-feedbacks.form-errors />

            {{-- Verification Form --}}
            <div class="rounded-sm border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <form method="POST" action="{{ route('verification.otp.verify') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-form.input light id="otp" name="otp" label="Verification Code" type="text"
                            :value="old('otp')" maxlength="6" minlength="6" inputmode="numeric" pattern="[0-9]{6}"
                            autocomplete="one-time-code" placeholder="000000" required autofocus
                            class="text-center text-2xl font-bold tracking-[0.45em]" />

                        @error('otp')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-form.button id="verify-button" type="submit" loading-text="Verifying..." disabled
                        class="w-full justify-center py-3">
                        Verify Email
                    </x-form.button>
                </form>

                {{-- Resend Code --}}
                <form id="resend-form" method="POST" action="{{ route('verification.otp.resend') }}"
                    class="mt-4 border-t border-slate-100 pt-4">
                    @csrf
                    <p class="mb-3 text-center text-sm text-slate-500">Didn't receive the email?</p>
                    <button id="resend-button" type="submit"
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span id="resend-text">Resend verification code</span>
                    </button>
                </form>

                {{-- Cancel --}}
                <form method="POST" action="{{ route('verification.cancel') }}" class="mt-3">
                    @csrf
                    <x-form.button type="submit" variant="danger" loading-text="Please wait..."
                        class="w-full justify-center py-3">
                        {{ session('otp_flow') === 'login' ? 'Back to Login' : 'Cancel Verification' }}
                    </x-form.button>
                </form>
            </div>

            <p class="mt-5 text-center text-xs leading-5 text-slate-500">
                Your verification code expires after 10 minutes. Never share it with anyone.
            </p>
        </section>
    </main>

    <script>
        (() => {
            const otp = document.getElementById('otp');
            const verify = document.getElementById('verify-button');

            function updateCode() {
                if (!otp || !verify) return;
                otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
                verify.disabled = otp.value.length !== 6;
            }

            otp?.addEventListener('input', updateCode);
            window.addEventListener('pageshow', updateCode);
            updateCode();

            const form = document.getElementById('resend-form');
            const button = document.getElementById('resend-button');
            const text = document.getElementById('resend-text');
            const key = 'barangaychain_resend_cooldown';
            let timer;

            function updateResend() {
                clearTimeout(timer);
                const expiry = Number(sessionStorage.getItem(key)) || 0;
                const left = Math.max(0, Math.ceil((expiry - Date.now()) / 1000));

                button.disabled = left > 0;
                text.textContent = left > 0 ?
                    `Resend available in ${left}s` :
                    'Resend verification code';

                if (left > 0) timer = setTimeout(updateResend, 1000);
                else sessionStorage.removeItem(key);
            }

            form.addEventListener('submit', () => {
                sessionStorage.setItem(key, Date.now() + 120000);
                updateResend();
            });

            updateResend();
            window.addEventListener('pageshow', updateResend);
        })();
    </script>
@endsection
