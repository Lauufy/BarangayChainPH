@extends('layouts.help')

@section('title', 'Submit a Help Request')

@section('help-intro')
    <p class="text-sm text-white/90">Need some help?</p>
    <h1 class="mt-1 text-balance text-4xl font-bold tracking-tight sm:text-5xl">Submit a Help Request</h1>
    <p class="mt-3 text-xs text-white/90">Please select the type of request and explain your purpose below.</p>
@endsection

@section('help-content')
    @php
        [$firstName, $lastName] = array_pad(preg_split('/\s+/', trim(auth()->user()->name), 2), 2, '');
    @endphp

    <form method="POST" action="{{ route('citizen.help.store') }}"
        class="border-(--gray-200) bg-(--white)/30 dark:border-(--blue-870) dark:bg-(--blue-860) mx-auto max-w-xl space-y-4 rounded-lg border px-4 py-6 shadow-sm sm:p-5 dark:shadow-none">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input light name="first_name" label="First name" :value="$firstName" autocomplete="given-name"
                maxlength="100" readonly required />
            <x-form.input light name="last_name" label="Last name" :value="$lastName" autocomplete="family-name"
                maxlength="100" readonly required />
            <div class="sm:col-span-2">
                <x-form.input light name="email" type="email" label="Email address" :value="auth()->user()->email"
                    autocomplete="email" maxlength="254" readonly required />
            </div>
            <div class="sm:col-span-2">
                <x-form.input light name="service" type="select" label="Type of reason"
                    placeholder="Select a reason..." :options="array_combine($topics, $topics)" required />
            </div>
            <div class="sm:col-span-2">
                <x-form.input light name="body" type="textarea" label="Purpose / Explain" rows="5"
                    maxlength="1000" placeholder="Explain what you need help with..." required />
            </div>
        </div>

        <x-feedbacks.form-errors />

        <x-form.button type="submit" color="blue" loadingText="Sending..." class="w-full">
            Send Message
        </x-form.button>
    </form>
@endsection