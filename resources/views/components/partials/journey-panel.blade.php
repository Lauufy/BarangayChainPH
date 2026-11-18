@props(['mode' => request()->is('register*') ? 'register' : 'login', 'active' => 1])

@php
    $isRegister = $mode === 'register';

    // Title, caption
    $steps = [
        ['Step 1', 'Register your account'],
        ['Step 2', 'Verify your email address'],
        ['Step 3', 'Access barangay services'],
    ];

    $perks = [
        ['Documents', 'Request barangay documents'],
        ['Applications', 'Track your applications'],
        ['Announcements', 'Receive official announcements'],
    ];

    $items = $isRegister ? $steps : $perks;
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
    class="bg-(--blue-850) text-(--off-white) relative isolate flex min-h-96 w-full min-w-0 flex-col justify-end overflow-hidden p-6 sm:p-10 lg:sticky lg:top-0 lg:order-last lg:min-h-dvh lg:self-start lg:p-12">

    {{-- Background (same blueprint photo as the hero) --}}
    <x-partials.blueprint base="bg-(--celadon-500)" src="{{ asset('img/photos/ynares-hall-front.jpg') }}"
        pos="object-center" class="absolute inset-0 -z-20" />

    {{-- Overlay --}}
    <div aria-hidden="true"
        class="bg-linear-to-t from-(--blue-870) via-(--blue-870)/85 to-(--blue-870)/60 absolute inset-0 -z-10">
    </div>

    {{-- Heading and guidance --}}
    <div class="journey-content relative z-10 mx-auto w-full max-w-2xl text-center">
        <h2 class="font-heading text-balance text-2xl font-bold leading-tight tracking-tight sm:text-3xl">
            {{ $isRegister ? 'Get started with barangay services' : 'Resident Online Services' }}
        </h2>

        <p class="text-(--off-white)/80 mx-auto mt-3 max-w-md text-sm leading-6">
            @if ($isRegister)
                Register to access barangay services online. Please provide information exactly as it appears on
                your valid ID.
            @else
                Sign in to request documents, follow your applications, and read official announcements from the
                barangay.
            @endif
        </p>

        {{-- Stat-style strip: big title, small caption, thin dividers --}}
        <ul role="list"
            class="mt-8 grid grid-cols-1 divide-y divide-white/15 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            @foreach ($items as [$title, $caption])
                @php $isActive = $isRegister && $loop->iteration === $active; @endphp

                <li @if ($isActive) aria-current="step" @endif class="px-4 py-4 sm:py-2">
                    <p
                        class="{{ $isActive ? 'text-(--celadon-500)' : 'text-white' }} text-xl font-bold leading-tight tracking-tight sm:text-2xl">
                        {{ $title }}
                    </p>

                    <p class="text-(--off-white)/70 mt-1.5 text-xs leading-5">
                        {{ $caption }}
                    </p>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
