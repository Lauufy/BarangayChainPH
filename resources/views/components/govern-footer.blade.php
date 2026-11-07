@php
    $home = url('/');

    $footerLinks = [
        [$home . '#about', 'About'],
        [$home . '#services', 'Services'],
        [$home . '#process', 'How it works'],
        [$home . '#verification', 'Verify'],
        [$home . '#faq', 'FAQ'],
    ];

    $ring = 'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--blue-300)';
    $focus = 'rounded-sm ' . $ring;
    $legal = 'rounded-sm text-(--gray-600) transition-colors hover:text-(--blue-700) dark:text-(--gray-400) dark:hover:text-(--blue-300) ' . $ring;
    $btn = 'inline-flex items-center justify-center rounded-md px-5 py-2.5 text-sm font-semibold transition-colors ' . $ring;
@endphp

<footer class="border-t border-(--gray-200) dark:border-(--gray-800)">

    {{-- Upper section --}}
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-5 py-4 sm:px-8 lg:flex-row">

        {{-- Brand --}}
        <a href="{{ $home }}" class="block {{ $focus }}">
            <span class="block leading-tight">
                <span class="block font-['Barlow_Condensed'] text-4xl font-bold uppercase tracking-wide text-(--blue-900) dark:text-(--blue-300) sm:text-4xl">
                    BarangayChain PH
                </span>
                <span class="block text-[9px] font-semibold uppercase tracking-[0.14em] text-(--gray-600) dark:text-(--gray-400)">
                    Barangay San Luis | Antipolo City
                </span>
            </span>
        </a>

        {{-- Footer navigation --}}
        <nav aria-label="Footer navigation"
            class="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm font-medium text-(--gray-700) dark:text-(--gray-300)">
            @foreach ($footerLinks as [$href, $text])
                <a href="{{ $href }}"
                    class="transition-colors hover:text-(--blue-700) dark:hover:text-(--blue-300) {{ $focus }}">
                    {{ $text }}
                </a>
            @endforeach
        </nav>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('login') }}"
                class="{{ $btn }} border border-(--gray-300) text-(--gray-800) hover:bg-(--gray-100) dark:border-(--gray-700) dark:text-white dark:hover:bg-white/10">
                Sign in
            </a>

            <a href="{{ $home }}#contact"
                class="{{ $btn }} bg-(--blue-900) text-white hover:bg-(--blue-700) dark:bg-white dark:text-(--blue-900) dark:hover:bg-blue-50">
                Contact
            </a>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-6 text-xs sm:px-8 md:flex-row md:items-center md:justify-between">

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <a href="{{ route('privacy') }}" class="{{ $legal }} ">
                    Privacy Notice
                </a>

                <span class="h-4 border-l border-(--gray-300) dark:border-(--gray-700)" aria-hidden="true"></span>

                <a href="{{ route('terms') }}" class="{{ $legal }} ">
                    Terms of Service
                </a>

                <span class="h-4 border-l border-(--gray-300) dark:border-(--gray-700)" aria-hidden="true"></span>

                <button type="button"
                    onclick="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
                    class="{{ $legal }}">
                    Back to top
                    <svg class="inline-block h-4 w-4 align-[-2px]" fill="none"
                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                </button>
            </div>

            <p class="text-(--gray-500) dark:text-(--gray-500)">
                &copy; {{ now()->year }} Barangay San Luis. All rights reserved.
            </p>
        </div>
    </div>

</footer>