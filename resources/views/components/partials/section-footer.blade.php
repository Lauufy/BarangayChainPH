@php
    $ring = 'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--blue-300)';
    $link = 'rounded-sm text-(--gray-600) transition-colors hover:text-(--blue-700) dark:text-(--gray-400) dark:hover:text-(--blue-300) ' . $ring;

    $links = [
        [route('privacy'), 'Privacy Notice', false],
        [route('terms'), 'Terms of Service', false],
        ['mailto:barangaychain@gmail.com', 'barangaychain@gmail.com', false],
        ['https://www.facebook.com/barangay.sanluis.35', 'Facebook', true],
    ];
@endphp

<footer class="mt-auto border-t border-(--gray-200) dark:border-(--gray-700)">
    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-6 text-sm sm:px-8 md:flex-row md:items-center md:justify-between">

        {{-- Logo, name and copyright --}}
        <a href="{{ route('citizen.dashboard') }}" class="flex items-center gap-3 rounded-sm {{ $ring }}">
            <img src="{{ asset('img/branding/barangaychain-lg1.png') }}" alt="BarangayChain PH" loading="lazy"
                class="h-8 w-8 shrink-0 object-contain">
            <span class="min-w-0">
                <span class="mt-1 block text-xs text-(--gray-500) dark:text-(--gray-400)">
                    &copy; {{ now()->year }} Barangay San Luis, Antipolo City. All rights reserved.
                </span>
            </span>
        </a>

        {{-- Important links --}}
        <nav aria-label="Footer" class="flex flex-wrap items-center gap-x-5 gap-y-2">
            @foreach ($links as [$href, $text, $external])
                <a href="{{ $href }}" class="{{ $link }}"
                    @if ($external) target="_blank" rel="noopener noreferrer" @endif>{{ $text }}</a>
            @endforeach
        </nav>

    </div>
</footer>