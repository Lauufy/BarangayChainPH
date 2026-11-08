@extends('layouts.app')

@section('title', 'Enter Verification Code')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-(--blue-600) dark:text-(--blue-300)">
                    Community Services Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-(--gray-900) dark:text-(--gray-50)">
                    Enter your code
                </h1>

                <p class="mt-2 text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                    We sent a 6-digit code to <strong class="text-(--gray-900) dark:text-(--gray-50)">{{ $target }}</strong>.
                    It expires in 10 minutes.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            <div class="rounded-md border border-(--gray-200) bg-(--white) p-5 shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800) sm:p-6">
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
                        <button type="submit" class="font-semibold text-(--blue-700) transition hover:text-(--blue-800) dark:text-(--blue-300) dark:hover:text-(--blue-200)">
                            Resend code
                        </button>
                    </form>

                    <form method="POST" action="{{ route('verification.cancel') }}">
                        @csrf
                        <button type="submit" class="text-(--gray-500) transition hover:text-(--gray-900) dark:text-(--gray-400) dark:hover:text-(--gray-50)">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-(--gray-500) dark:text-(--gray-400)">
                Never share this code with anyone.
            </p>

        </section>
    </main>
@endsection