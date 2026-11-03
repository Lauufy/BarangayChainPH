@extends('layouts.app')

@section('title', 'Two-Step Verification')

@section('content')
    <main class="flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-md">

            {{-- Header --}}
            <header class="mb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#1D3FBB]">
                    Community Services Portal
                </p>

                <h1 class="text-3xl font-bold leading-tight tracking-tight text-[#0F1B3D]">
                    Verify it's you
                </h1>

                <p class="mt-2 text-sm leading-6 text-[#3B4868]">
                    Choose where we should send your login code.
                </p>
            </header>

            {{-- Errors --}}
            <x-feedbacks.form-errors />

            <div class="rounded-md border border-[#DDE3F3] bg-white p-5 shadow-sm sm:p-6">
                <form method="POST" action="{{ route('two-factor.send') }}" class="space-y-3">
                    @csrf

                    @foreach ($channels as $channel => $masked)
                        <button type="submit" name="channel" value="{{ $channel }}"
                            class="w-full rounded-md border border-[#DDE3F3] px-4 py-3 text-left text-sm font-semibold text-[#0F1B3D] transition hover:bg-[#E6ECFA]">
                            {{ $channel === 'sms' ? 'Text message (SMS)' : 'Email' }}
                            <span class="block text-xs font-normal text-[#5A6788]">{{ $masked }}</span>
                        </button>
                    @endforeach
                </form>

                <form method="POST" action="{{ route('verification.cancel') }}" class="mt-4 text-center">
                    @csrf
                    <button type="submit" class="text-sm text-[#5A6788] transition hover:text-[#0F1B3D]">
                        Cancel
                    </button>
                </form>
            </div>

        </section>
    </main>
@endsection
