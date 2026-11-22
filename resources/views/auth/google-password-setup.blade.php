@extends('layouts.app')

@section('title', 'Set Your Password')

@section('content')
    <div class="w-full min-w-0 text-center">

        {{-- Header --}}
        <header class="mb-5">
            <h1 class="text-(--gray-900) dark:text-(--gray-50) text-2xl font-bold leading-tight tracking-tight sm:text-3xl">
                Create a password for your account
            </h1>

            <p class="text-(--gray-600) dark:text-(--gray-300) mt-2 text-sm leading-6">
                Your Google password is not shared with BarangayChain PH. Create a separate password to sign in here.
            </p>
        </header>

        {{-- Errors --}}
        <x-feedbacks.form-errors />

        {{-- Password form (fields stay left-aligned for readability) --}}
        <form action="{{ route('google.password.setup.store') }}" method="POST" class="space-y-4 text-left"
            data-validate>
            @csrf
            @method('PUT')

            <x-form.input light name="password" label="Password" type="password" data-password-strength
                minlength="8" maxlength="16" required autofocus autocomplete="new-password"
                placeholder="Ex: Juan@2026">
                <x-feedbacks.password-requirement />
            </x-form.input>

            <x-form.input light name="password_confirmation" label="Confirm Password" type="password"
                data-password-confirmation minlength="8" maxlength="16" required autocomplete="new-password"
                placeholder="Re-enter your password" error-message="Passwords do not match." />

            <x-form.button type="submit" color="blue" class="w-full" loading-text="Saving password...">
                CONTINUE TO ACCOUNT
            </x-form.button>
        </form>

        {{-- Cancel --}}
        <form action="{{ route('google.password.cancel') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit"
                class="text-(--gray-600) hover:text-(--gray-900) dark:text-(--gray-300) dark:hover:text-(--gray-50) text-sm underline transition">
                Cancel and return to login
            </button>
        </form>

    </div>
@endsection