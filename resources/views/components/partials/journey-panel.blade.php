@props(['mode' => request()->is('register*') ? 'register' : 'login', 'active' => 1])

<aside
    class="relative isolate flex min-h-dvh w-full min-w-0 flex-col justify-end overflow-hidden bg-[#050d1c] p-6 pb-12 text-white sm:p-10 sm:pb-14 lg:sticky lg:top-0 lg:self-start lg:p-14">
    {{-- Background --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
        <div
            class="bg-linear-to-br absolute -right-1/4 -top-10 h-[70%] w-3/4 from-sky-400/40 via-blue-700/20 to-transparent [clip-path:polygon(35%_0,100%_0,100%_100%,0_70%)]">
        </div>

        <div
            class="bg-linear-to-tr absolute -left-10 top-1/4 h-2/3 w-3/4 from-emerald-400/20 to-transparent [clip-path:polygon(0_20%,100%_0,60%_100%,0_100%)]">
        </div>

        <div class="bg-linear-to-t absolute inset-0 from-[#050d1c] via-[#050d1c]/70 to-transparent"></div>
    </div>

    {{-- Content --}}
    <div class="relative mx-auto w-full max-w-2xl pb-4 text-center">

        @if ($mode === 'register')
            {{-- Register --}}
            <h2
                class="font-heading text-balance text-3xl font-bold leading-tight tracking-tight sm:text-4xl xl:text-6xl">
                Get Started with Barangay Services
            </h2>

            <p class="mx-auto mt-5 max-w-md text-sm leading-7 text-white/60 sm:text-base">
                Register to access barangay services online. Please provide information exactly as it appears on your
                valid ID.
            </p>

            <ol class="mx-auto mt-8 grid max-w-xl gap-5 text-left sm:mt-10 sm:grid-cols-3 sm:gap-4">
                @foreach (['Register your account', 'Verify your email address', 'Access barangay services'] as $step)
                    <li @if ($loop->iteration === $active) aria-current="step" @endif>
                        <div
                            class="{{ $loop->iteration <= $active ? 'bg-emerald-400' : 'bg-white/15' }} h-0.5 rounded-full">
                        </div>

                        <p
                            class="{{ $loop->iteration === $active ? 'text-emerald-300' : 'text-white/40' }} mt-3 text-[11px] font-medium uppercase tracking-widest">
                            Step {{ $loop->iteration }}
                        </p>

                        <p
                            class="{{ $loop->iteration === $active ? 'font-semibold text-white' : 'text-white/60' }} mt-1 text-sm">
                            {{ $step }}
                        </p>
                    </li>
                @endforeach
            </ol>
        @else
            {{-- Login --}}
            <h2
                class="font-heading text-balance text-3xl font-bold leading-tight tracking-tight sm:text-4xl xl:text-6xl">
                Secure Citizen Sign In
            </h2>

            <p class="mx-auto mt-5 max-w-md text-sm leading-7 text-white/60 sm:text-base">
                Authorized access to barangay services, requests, and official records.
            </p>

            <ul class="mx-auto mt-8 grid max-w-xl gap-5 text-left sm:mt-10 sm:grid-cols-3 sm:gap-4">
                @foreach (['Request barangay documents', 'Track your applications', 'Receive official announcements'] as $service)
                    <li>
                        <div class="h-0.5 rounded-full bg-emerald-400"></div>
                        <p class="mt-3 text-sm text-white/60">{{ $service }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</aside>
