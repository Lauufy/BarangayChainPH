@extends('layouts.app')

@section('title', 'Two-Step Verification')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-(--blue-600) dark:text-(--blue-300)">
                    Community Services Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-(--gray-900) dark:text-(--gray-50)">
                    Verify it's you
                </h1>

                <p class="mt-2 text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                    Choose where we should send your login code.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            <div class="rounded-md border border-(--gray-200) bg-(--white) p-5 shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800) sm:p-6">
                <form method="POST" action="{{ route('two-factor.send') }}" class="space-y-3">
                    @csrf

                    @foreach ($channels as $channel => $masked)
                        <button type="submit" name="channel" value="{{ $channel }}"
                            class="w-full rounded-md border border-(--gray-200) px-4 py-3 text-left text-sm font-semibold text-(--gray-900) transition hover:bg-(--blue-50) dark:border-(--gray-700) dark:text-(--gray-50) dark:hover:bg-(--gray-700)">
                            {{ $channel === 'sms' ? 'Text message (SMS)' : 'Email' }}
                            <span class="block text-xs font-normal text-(--gray-500) dark:text-(--gray-400)">{{ $masked }}</span>
                        </button>
                    @endforeach
                </form>

                <form method="POST" action="{{ route('verification.cancel') }}" class="mt-4 text-center">
                    @csrf
                    <button type="submit" class="text-sm text-(--gray-500) transition hover:text-(--gray-900) dark:text-(--gray-400) dark:hover:text-(--gray-50)">
                        Cancel
                    </button>
                </form>
            </div>

        </section>
    </main>
@endsection