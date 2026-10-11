@extends('layouts.guest')

@section('title', 'Verify Email')

@section('content')

    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-400">
                    Citizen Portal
                </p>

                <h1 class="text-3xl font-bold tracking-tight text-white">
                    Forgot password?
                </h1>

                <p class="mt-2 text-sm leading-5 text-blue-100/65">
                    Enter your registered email address. We'll send you a secure link to reset your password.
                </p>
            </header>

            <x-feedbacks.form-errors />

            {{-- Request reset link --}}
            <div class="rounded-lg border border-white/10 bg-[#09172D] p-5 sm:p-6">

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email"
                            class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-blue-100">
                            Email Address
                        </label>

                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                            placeholder="Enter your registered email" required autofocus
                            class="w-full rounded-lg border border-white/10 bg-[#0B2347] px-3.5 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/30 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
                    </div>

                    <button type="submit" data-loading data-loading-text="Sending..."
                        class="flex w-full items-center justify-center gap-3 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold uppercase tracking-[0.12em] text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18] disabled:cursor-not-allowed disabled:opacity-70">
                        Send Reset Link
                    </button>
                </form>

            </div>

            <p class="mt-6 text-center text-xs leading-5 text-blue-100/40">
                Reset links expire after a limited time.
                Check your spam or junk folder if needed.
            </p>

        </section>
    </main>

@endsection
