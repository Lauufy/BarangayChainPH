@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#1D3FBB]">
                    Citizen Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-[#0F1B3D]">
                    Forgot password?
                </h1>

                <p class="mt-2 text-sm leading-6 text-[#3B4868]">
                    Enter your registered email address. We'll send you a secure link to reset your password.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            {{-- Request reset link --}}
            <div class="rounded-md border border-[#DDE3F3] bg-white p-5 shadow-sm sm:p-6">
                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    {{-- Email --}}
                    <x-form.input light name="email" label="Email Address" type="email" required autofocus
                        autocomplete="email" placeholder="Enter your registered email" />

                    {{-- Submit --}}
                    <x-form.button type="submit" color="blue" class="w-full" loading-text="Sending...">
                        Send Reset Link
                    </x-form.button>


                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-[#5A6788]">
                Reset links expire after a limited time.
                Check your spam or junk folder if needed.
            </p>

        </section>
    </main>
@endsection