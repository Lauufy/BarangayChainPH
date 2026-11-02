@props(['mode' => request()->is('register*') ? 'register' : 'login', 'active' => 1])

@php
    $isRegister = $mode === 'register';

    $steps = ['Register your account', 'Verify your email address', 'Access barangay services'];

    // Label, description
    $perks = [
        ['Documents', 'Request barangay documents'],
        ['Applications', 'Track your applications'],
        ['Announcements', 'Receive official announcements'],
    ];

@endphp

@once
    <style>
        /* Text fades up when the page loads */
        .journey-content {
            animation: journey-in .6s ease .1s both;
        }

        @keyframes journey-in {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .journey-content {
                animation: none;
            }
        }
    </style>
@endonce

<aside
    class="relative isolate flex min-h-96 w-full min-w-0 flex-col justify-end gap-12 overflow-hidden bg-blue-950 p-6 text-white sm:p-10 lg:sticky lg:top-0 lg:min-h-dvh lg:self-start lg:order-last lg:p-14">

    {{-- Photo with a navy and emerald color filter so the text stays easy to read --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
        <img src="{{ asset('img/photos/ynares-hall-front.jpg') }}" alt="" width="1214" height="724"
            class="h-full w-full object-cover object-right saturate-50">

        {{-- Navy tint over the whole photo --}}
        <div class="absolute inset-0 bg-blue-950/70 mix-blend-multiply"></div>

        {{-- Soft emerald glow from the top corner --}}
        <div class="bg-linear-to-br absolute inset-0 from-emerald-500/25 via-transparent to-transparent"></div>

        {{-- Darker toward the bottom, where the text sits --}}
        <div class="bg-linear-to-t absolute inset-0 from-blue-950 via-blue-950/75 to-blue-950/30"></div>
    </div>

    {{-- Heading and guidance --}}
    <div class="journey-content mx-auto w-full max-w-xl text-center">
        @if ($isRegister)
            <h2 class="font-heading text-balance text-3xl font-bold leading-tight tracking-tight sm:text-4xl xl:text-5xl">
                Get started with barangay services
            </h2>

            <p class="mx-auto mt-5 max-w-md text-sm leading-7 text-blue-100 sm:text-base">
                Register to access barangay services online. Please provide information exactly as it appears on
                your valid ID.
            </p>

            <ol class="mt-8 grid gap-5 text-left sm:grid-cols-3 sm:gap-4">
                @foreach ($steps as $step)
                    <li @if ($loop->iteration === $active) aria-current="step" @endif>
                        <div
                            class="{{ $loop->iteration <= $active ? 'bg-emerald-400' : 'bg-white/25' }} h-1 rounded-full">
                        </div>

                        <p
                            class="{{ $loop->iteration === $active ? 'text-emerald-300' : 'text-white/60' }} mt-3 text-xs font-semibold">
                            Step {{ $loop->iteration }}
                        </p>

                        <p
                            class="{{ $loop->iteration === $active ? 'font-semibold text-white' : 'text-white/75' }} mt-1 text-sm leading-6">
                            {{ $step }}
                        </p>
                    </li>
                @endforeach
            </ol>
        @else
            <h2 class="font-heading text-balance text-3xl font-bold leading-tight tracking-tight sm:text-4xl xl:text-5xl">
                Resident Online Services
            </h2>

            <p class="mx-auto mt-5 max-w-md text-sm leading-7 text-blue-100 sm:text-base">
                Sign in to request documents, follow your applications, and read official announcements from the
                barangay.
            </p>

            <ul class="mt-8 grid gap-5 text-left sm:grid-cols-3 sm:gap-4">
                @foreach ($perks as [$label, $text])
                    <li>
                        <div class="h-1 rounded-full bg-emerald-400"></div>

                        <p class="mt-3 text-xs font-semibold text-emerald-300">{{ $label }}</p>

                        <p class="mt-1 text-sm font-semibold leading-6 text-white">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</aside>