@extends('layouts.guest')

@section('title', 'Link Your Google Account')

@section('content')
    <div class="w-full min-w-0">
        <header class="mb-5">
            <h1 class="text-2xl font-bold leading-tight tracking-tight text-(--gray-900) dark:text-(--gray-50) sm:text-3xl">
                Link your Google account
            </h1>

            <p class="mt-2 text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                An account for <strong>{{ $email }}</strong> already exists.
                @if ($hasPassword)
                    Enter its password to confirm it is yours and link it with Google.
                @else
                    Enter the verification code sent to <strong>{{ $otpTarget }}</strong> to confirm it is yours and link it with Google.
                @endif
            </p>
        </header>

        <x-feedbacks.form-errors />

        <form action="{{ route('google.link.confirm.post') }}" method="POST" class="space-y-4">
            @csrf

            @if ($hasPassword)
                <x-form.input
                    light
                    name="password"
                    label="Account password"
                    type="password"
                    maxlength="64"
                    required
                    autofocus
                    autocomplete="current-password"
                    placeholder="Enter your account password"
                />
            @else
                <x-form.input
                    light
                    name="otp"
                    label="Verification code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    placeholder="Enter the 6-digit code"
                />
            @endif

            <x-form.button type="submit" color="blue" class="w-full" loading-text="Linking account...">
                CONFIRM AND LINK GOOGLE
            </x-form.button>
        </form>

        <div class="mt-4 flex items-center justify-between text-sm">
            <a href="{{ route('password.request') }}"
               class="text-(--gray-600) underline hover:text-(--gray-900) dark:text-(--gray-300) dark:hover:text-(--gray-50)">
                Forgot your password?
            </a>

            <form action="{{ route('google.link.cancel') }}" method="POST">
                @csrf
                <button type="submit"
                        class="text-(--gray-600) underline hover:text-(--gray-900) dark:text-(--gray-300) dark:hover:text-(--gray-50)">
                    Cancel
                </button>
            </form>
        </div>
    </div>
@endsection