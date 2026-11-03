@extends('layouts.app')

@section('title', 'Enter Verification Code')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#1D3FBB]">
                    Community Services Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-[#0F1B3D]">
                    Enter your code
                </h1>

                <p class="mt-2 text-sm leading-6 text-[#3B4868]">
                    We sent a 6-digit code to <strong>{{ $target }}</strong>.
                    It expires in 10 minutes.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            <div class="rounded-md border border-[#DDE3F3] bg-white p-5 shadow-sm sm:p-6">
                {{-- Code --}}
                <form method="POST" action="{{ route('verification.otp.verify') }}" class="space-y-4">
                    @csrf

                    <x-form.input
                        light
                        name="otp"
                        label="6-digit code"
                        inputmode="numeric"
                        maxlength="6"
                        required
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="000000"
                    />

                    <x-form.button type="submit" color="blue" class="w-full" loading-text="Verifying...">
                        VERIFY
                    </x-form.button>
                </form>

                {{-- Resend / Cancel --}}
                <div class="mt-4 flex items-center justify-between text-sm">
                    <form method="POST" action="{{ route('verification.otp.resend') }}">
                        @csrf
                        <button type="submit" class="font-semibold text-[#1D3FBB] transition hover:text-[#16318F]">
                            Resend code
                        </button>
                    </form>

                    <form method="POST" action="{{ route('verification.cancel') }}">
                        @csrf
                        <button type="submit" class="text-[#5A6788] transition hover:text-[#0F1B3D]">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-[#5A6788]">
                Never share this code with anyone.
            </p>

        </section>
    </main>
@endsection
