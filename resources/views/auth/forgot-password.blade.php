@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-400">
                    Citizen Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-white">
                    Forgot password?
                </h1>

                <p class="mt-2 text-sm leading-6 text-blue-100/70">
                    Enter your registered email address. We'll send you a secure link to reset your password.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            {{-- Request reset link --}}
            <div class="rounded-md border border-white/10 bg-[#09172D] p-5 sm:p-6">
                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    {{-- Email --}}
                    <x-form.input name="email" label="Email Address" type="email" required autofocus
                        autocomplete="email" placeholder="Enter your registered email" />

                    {{-- Submit --}}
                    <button type="submit" data-loading data-loading-text="Sending..."
                        class="flex w-full items-center justify-center gap-3 rounded-md bg-emerald-600 px-4 py-3 text-sm font-semibold tracking-wide text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18] disabled:cursor-not-allowed disabled:opacity-70">
                        Send Reset Link
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-blue-100/50">
                Reset links expire after a limited time.
                Check your spam or junk folder if needed.
            </p>

        </section>
    </main>
@endsection