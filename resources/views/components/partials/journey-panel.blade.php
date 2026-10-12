{{-- Side panel for login and sign up. Use: <x-auth.journey-panel :active="1" /> --}}
@props([
    'badge' => 'Join BarangayChain PH',
    'title' => 'Start your journey',
    'subtitle' => 'Follow these simple steps to set up your account.',
    'active' => 1, // current step (0 = none)
    'steps' => ['Register your account', 'Verify your email address', 'Access barangay services'],
    'footer' => 'BarangayChain PH · Citizen Portal',
])

<aside
    class="sticky top-0 flex h-screen flex-col justify-center overflow-hidden bg-linear-to-br from-[#0B2347] to-[#071428] p-12 text-white xl:p-20">

    {{-- Background glows --}}
    <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-sky-500/25 blur-3xl"></div>

    <div class="relative max-w-lg">

        {{-- Badge, title, text --}}
        <span
            class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3.5 py-1.5 text-xs font-semibold text-emerald-300">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            {{ $badge }}
        </span>

        <h2 class="mt-6 text-5xl font-bold leading-tight tracking-tight">{{ $title }}</h2>

        <p class="mt-4 text-base leading-7 text-white/70">{{ $subtitle }}</p>

        {{-- Steps timeline --}}
        <ol class="mt-12">
            @foreach ($steps as $step)
                <li class="relative flex gap-4 pb-8 last:pb-0">

                    {{-- Line down to the next step --}}
                    @unless ($loop->last)
                        <span class="absolute bottom-0 left-4 top-8 w-px bg-white/15"></span>
                    @endunless

                    {{-- Step circle: done = check, current = white, upcoming = outlined --}}
                    @if ($loop->iteration < $active)
                        <span
                            class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-400 text-[#071428]">
                            <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor"
                                stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 8.5l3 3 6-6.5" />
                            </svg>
                        </span>
                    @elseif ($loop->iteration === $active)
                        <span
                            class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-slate-900 ring-4 ring-white/20">
                            {{ $loop->iteration }}
                        </span>
                    @else
                        <span
                            class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/25 text-sm font-semibold text-white/60">
                            {{ $loop->iteration }}
                        </span>
                    @endif

                    {{-- Step text --}}
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-white/50">
                            Step {{ $loop->iteration }}
                        </p>
                        <p
                            class="mt-0.5 text-base {{ $loop->iteration === $active ? 'font-semibold text-white' : 'font-medium text-white/70' }}">
                            {{ $step }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Footer --}}
    <p class="absolute bottom-8 left-12 text-xs text-white/40 xl:left-20">{{ $footer }}</p>
</aside>