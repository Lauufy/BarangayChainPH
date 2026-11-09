@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
    <div class="flex items-center justify-center">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-(--gray-900) dark:text-(--gray-50)">
                    Forgot password?
                </h1>

                <p class="mt-2 text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                    Use your email to get a reset link, or your verified mobile number to get a code by SMS.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            {{-- Request reset link or code --}}
            <div class="rounded-md border border-(--gray-200) bg-(--white) p-3 shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800) sm:p-4">
                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    <x-form.contact-tabs
                        email-name="email"
                        email-label="Email Address"
                        email-placeholder="Enter your registered email"
                    />

                    {{-- Submit --}}
                    <x-form.button type="submit" color="blue" class="w-full" loading-text="Sending...">
                        Send Reset Link or Code
                    </x-form.button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-(--gray-500) dark:text-(--gray-400)">
                Reset links and codes expire after a limited time.
                Check your spam or junk folder if needed.
            </p>

        </section>
    </div>
@endsection