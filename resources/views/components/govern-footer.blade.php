<footer class="border-t border-white/10 bg-(--blue-850) text-(--off-white)">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-5 py-8 sm:px-8 lg:flex-row">

        {{-- Logo + divider + links and copyright --}}
        <div class="flex flex-col items-center gap-6 text-center sm:flex-row sm:text-left">
            <a href="{{ url('/') }}"
                class="flex shrink-0 items-center rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--celadon-500)">
                <img src="{{ asset('img/branding/brgychainlogo.png') }}" alt="BarangayChain PH Logo" height="30"
                    class="h-7 w-auto shrink-0 object-contain brightness-0 invert sm:h-10"
                    onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                <span hidden class="text-xl font-bold uppercase tracking-wide text-(--off-white)">BarangayChain
                    PH</span>
            </a>

            <span class="hidden h-16 border-l border-white/15 sm:block" aria-hidden="true"></span>

            <div class="flex flex-col items-center gap-3 sm:items-start">

                <div
                    class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs text-(--off-white)/60 sm:justify-start">
                    <p>&copy; {{ now()->year }} Barangay San Luis. All rights reserved.</p>

                    <span class="h-3 border-l border-white/20" aria-hidden="true"></span>

                    <a href="{{ route('privacy') }}"
                        class="rounded-sm transition-colors hover:text-(--celadon-500) focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--celadon-500)">Privacy
                        Notice</a>

                    <a href="{{ route('terms') }}"
                        class="rounded-sm transition-colors hover:text-(--celadon-500) focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--celadon-500)">Terms
                        of Service</a>

                    <button type="button"
                        onclick="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
                        class="cursor-pointer rounded-sm transition-colors hover:text-(--celadon-500) focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--celadon-500)">
                        Back to top
                        <svg class="inline-block h-4 w-4 align-[-2px]" fill="none" stroke="currentColor"
                            stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="flex flex-col items-center gap-3 text-sm lg:items-end">

            <a href="mailto:barangaychain@gmail.com"
                class="inline-flex items-center gap-2 rounded-sm text-(--off-white)/75 transition-colors hover:text-(--celadon-500) focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--celadon-500)">
                <svg class="h-5 w-5 text-(--celadon-500)" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                barangaychain@gmail.com
            </a>
        </div>
    </div>
</footer>