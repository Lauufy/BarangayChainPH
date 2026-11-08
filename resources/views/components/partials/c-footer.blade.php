@php
    // Same color design as govern-footer.blade.php (theme tokens, light and dark)
    $ring = 'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--blue-300)';
    $focus = 'rounded-sm ' . $ring;
    $line = 'border-(--gray-200) dark:border-(--gray-700)';
    $heading = 'border-l-2 border-(--green-600) pl-3 text-sm font-bold text-(--gray-900) dark:text-(--gray-50)';
    $list = 'mt-4 space-y-2.5 text-sm text-(--gray-700) dark:text-(--gray-300)';
    $link = 'transition-colors hover:text-(--blue-700) dark:hover:text-(--blue-300) ' . $focus;
    $term = 'text-xs text-(--gray-500) dark:text-(--gray-400)';
    $strong = 'font-semibold text-(--gray-900) dark:text-(--gray-50)';
    $body = 'text-(--gray-600) dark:text-(--gray-400)';
    $cta = 'font-semibold text-(--blue-700) underline decoration-(--green-600) decoration-2 underline-offset-4 transition-colors hover:text-(--blue-800) dark:text-(--blue-300) dark:hover:text-(--blue-200) ' . $focus;
    $legal = 'rounded-sm text-(--gray-600) transition-colors hover:text-(--blue-700) dark:text-(--gray-400) dark:hover:text-(--blue-300) ' . $ring;
    $divider = 'h-4 border-l border-(--gray-300) dark:border-(--gray-700)';

    $links = [
        [route('citizen.dashboard'), 'Home'],
        [route('citizen.services'), 'Services'],
        [route('citizen.applications'), 'Applications'],
        [route('citizen.documents'), 'Documents'],
    ];

    $services = ['Barangay Clearance', 'Certificate of Residency', 'Certificate of Indigency'];
    $directions = 'https://maps.app.goo.gl/3sjGNZNBfGSFn64w9';
@endphp

<footer class="{{ $line }} mt-auto border-t">
    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8">

        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">

            {{-- Brand --}}
            <div>
                <a href="{{ route('citizen.dashboard') }}" class="{{ $focus }} inline-block">
                    <span class="block whitespace-nowrap font-['Barlow_Condensed'] text-4xl font-bold uppercase tracking-wide text-(--blue-900) dark:text-(--blue-300)">
                        BarangayChain PH
                    </span>
                    <span class="mt-1 block text-[9px] font-semibold uppercase tracking-[0.14em] text-(--gray-600) dark:text-(--gray-400)">
                        Barangay San Luis | Antipolo City
                    </span>
                </a>

                <p class="{{ $body }} mt-5 max-w-xs text-sm leading-6">
                    Official citizen portal of Barangay San Luis. Access public services and manage your account online.
                </p>
            </div>

            {{-- Quick links --}}
            <nav aria-label="Footer">
                <h3 class="{{ $heading }}">Quick links</h3>
                <ul class="{{ $list }}">
                    @foreach ($links as [$href, $text])
                        <li><a href="{{ $href }}" class="{{ $link }}">{{ $text }}</a></li>
                    @endforeach
                </ul>
            </nav>

            {{-- Services --}}
            <div>
                <h3 class="{{ $heading }}">Services</h3>
                <ul class="{{ $list }}">
                    @foreach ($services as $service)
                        <li>{{ $service }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <h3 class="{{ $heading }}">Contact</h3>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="{{ $term }}">Phone</dt>
                        <dd class="mt-0.5"><a href="tel:+639233232322" class="{{ $link }}">+63 923 323 2322</a></dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Email</dt>
                        <dd class="mt-0.5 break-all"><a href="mailto:barangaychain@gmail.com" class="{{ $link }}">barangaychain@gmail.com</a></dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Facebook</dt>
                        <dd class="mt-0.5">
                            <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank" rel="noopener noreferrer" class="{{ $cta }}">Visit our Facebook page</a>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Office information and map --}}
        <div class="{{ $line }} mt-12 grid overflow-hidden rounded-md border md:grid-cols-[1fr_1.5fr]">

            <div class="p-6">
                <h3 class="{{ $heading }}">Visit our office</h3>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="{{ $strong }}">Monday to Friday</dt>
                        <dd class="{{ $body }} mt-0.5">8:00 AM to 5:00 PM</dd>
                    </div>
                    <div>
                        <dt class="{{ $strong }}">Saturday and Sunday</dt>
                        <dd class="{{ $body }} mt-0.5">Closed</dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Address</dt>
                        <dd class="mt-0.5">
                            <a href="{{ $directions }}" target="_blank" rel="noopener noreferrer" class="{{ $link }}">J53X+J7G, Sitio Pinagminahan, Antipolo, 1870 Rizal</a>
                        </dd>
                    </div>
                </dl>

                <a href="{{ $directions }}" target="_blank" rel="noopener noreferrer" class="{{ $cta }} mt-5 inline-block text-sm">Get directions</a>
            </div>

            <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City&output=embed"
                class="{{ $line }} h-64 w-full border-t bg-(--gray-100) dark:bg-(--gray-800) md:h-full md:min-h-72 md:border-l md:border-t-0"></iframe>
        </div>

        {{-- Legal links and copyright --}}
        <div class="{{ $line }} mt-12 flex flex-col gap-4 border-t pt-6 text-xs md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <a href="{{ route('privacy') }}" class="{{ $legal }}">Privacy Notice</a>
                <span class="{{ $divider }}" aria-hidden="true"></span>
                <a href="{{ route('terms') }}" class="{{ $legal }}">Terms of Service</a>
                <span class="{{ $divider }}" aria-hidden="true"></span>
                <button type="button" class="{{ $legal }}"
                    onclick="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })">
                    Back to top
                    <svg class="inline-block h-4 w-4 align-[-2px]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                </button>
            </div>

            <p class="text-(--gray-500)">&copy; {{ now()->year }} Barangay San Luis. All rights reserved.</p>
        </div>

    </div>
</footer>