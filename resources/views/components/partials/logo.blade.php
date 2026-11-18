<a href="{{ route('citizen.dashboard') }}" class="flex min-w-0 items-center gap-2.5 sm:gap-2">

    {{-- Logo image. If it fails to load, it hides and the text below appears --}}
    <img src="{{ asset('img/branding/brgychainlogo.png') }}"
        alt="BarangayChain PH Logo"
        height="30"
        class="h-7 w-auto shrink-0 object-contain mix-blend-multiply sm:h-10 dark:mix-blend-normal dark:brightness-0 dark:invert"
        onerror="this.hidden = true; this.nextElementSibling.hidden = false;">

    {{-- Fallback text: hidden by default, shown only if the image fails --}}
    <span hidden class="min-w-0 border-l-2 border-(--blue-700) pl-2.5 leading-none sm:pl-2 dark:border-white/60">
        <span class="block whitespace-nowrap font-sans text-xl font-bold uppercase tracking-[0.02em] text-(--blue-800) sm:text-2xl dark:text-white">
            BarangayChain PH
        </span>
        <span class="hidden truncate text-[9px] font-semibold uppercase tracking-[0.14em] text-(--gray-600) sm:block dark:text-(--gray-200)">
            Barangay San Luis | Antipolo City
        </span>
    </span>
</a>