@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-400">
                    Citizen Portal
                </p>

                <h1 class="text-3xl font-bold tracking-tight text-white">
                    Reset your password
                </h1>

                <p class="mt-2 text-sm leading-5 text-blue-100/65">
                    Create a new password for your BarangayChain PH account.
                </p>
            </header>

            {{-- Validation errors --}}
            <x-feedbacks.form-errors />

            {{-- Reset password form --}}
            <div class="rounded-lg border border-white/10 bg-[#09172D] p-5 sm:p-6">
                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    {{-- Email --}}
                    <div>
                        <label for="email"
                            class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-blue-100">
                            Email Address
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="email"
                            placeholder="Enter your registered email"
                            required
                            class="w-full rounded-lg border border-white/10 bg-[#0B2347] px-3.5 py-3 text-sm text-white outline-none transition placeholder:text-blue-100/30 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
                    </div>

                    {{-- New password --}}
                    <div>
                        <label for="password"
                            class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-blue-100">
                            New Password
                        </label>

                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                maxlength="16"
                                autocomplete="new-password"
                                placeholder="Enter your new password"
                                required
                                class="w-full rounded-lg border border-white/10 bg-[#0B2347] px-3.5 py-3 pr-12 text-sm text-white outline-none transition placeholder:text-blue-100/30 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                            <button
                                type="button"
                                data-password-toggle="password"
                                aria-label="Show password"
                                class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3.5 text-blue-100/50 transition hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">

                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <g data-password-eye>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </g>
                                </svg>
                            </button>
                        </div>

                        <x:feedbacks.password-requirement />
                    </div>

                    {{-- Confirm password --}}
                    <div>
                        <label for="password_confirmation"
                            class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-blue-100">
                            Confirm New Password
                        </label>

                        <div class="relative">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                maxlength="16"
                                autocomplete="new-password"
                                placeholder="Re-enter your new password"
                                required
                                class="w-full rounded-lg border border-white/10 bg-[#0B2347] px-3.5 py-3 pr-12 text-sm text-white outline-none transition placeholder:text-blue-100/30 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                            <button
                                type="button"
                                data-password-toggle="password_confirmation"
                                aria-label="Show password confirmation"
                                class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3.5 text-blue-100/50 transition hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">

                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <g data-password-eye>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </g>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        data-loading
                        data-loading-text="Updating Password..."
                        class="flex w-full items-center justify-center gap-3 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold uppercase tracking-[0.12em] text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18] disabled:cursor-not-allowed disabled:opacity-70">
                        Reset Password
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-blue-100/40">
                For your security, choose a password you haven't used before.
            </p>

        </section>
    </main>
@endsection
