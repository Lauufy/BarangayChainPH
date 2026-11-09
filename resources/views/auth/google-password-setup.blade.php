@extends('layouts.guest')

@section('title', 'Set Your Password')

@section('content')
    <div class="w-full min-w-0">
        <header class="mb-5">
            <h1 class="text-2xl font-bold leading-tight tracking-tight text-(--gray-900) dark:text-(--gray-50) sm:text-3xl">
                Create a password for your account
            </h1>

            <p class="mt-2 text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                Your Google password is not shared with BarangayChain PH. Create a separate password to sign in here.
            </p>
        </header>

        <x-feedbacks.form-errors />

        <form action="{{ route('google.password.setup.store') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <x-form.input
                light
                name="password"
                label="Password"
                type="password"
                data-password-strength
                maxlength="16"
                required
                autofocus
                autocomplete="new-password"
                placeholder="At least 8 characters"
            >
                <x-feedbacks.password-requirement />
            </x-form.input>

            <x-form.input
                light
                name="password_confirmation"
                label="Confirm Password"
                type="password"
                data-password-confirmation
                maxlength="16"
                required
                autocomplete="new-password"
                placeholder="Re-enter your password"
            />

            <x-form.button type="submit" color="blue" class="w-full" loading-text="Saving password...">
                CONTINUE TO ACCOUNT
            </x-form.button>
        </form>
    </div>
@endsection

@section('panel')
    <x-partials.journey-panel :active="1" />
@endsection
