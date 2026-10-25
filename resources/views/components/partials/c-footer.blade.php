@php
    // Shared footer styles (kept identical in govern-footer.blade.php)
    $heading = 'border-l-2 border-green-600 pl-3 text-sm font-bold text-white';
    $link = 'transition hover:text-white hover:underline';
    $contactLink = 'text-blue-50 transition hover:underline';
    $term = 'text-xs text-blue-200/60';
    $facebookLink = 'font-semibold text-white underline underline-offset-4 decoration-green-600 hover:decoration-white';
    $bottomRow = 'mt-12 flex flex-col gap-2 border-t border-white/15 pt-6 text-xs text-blue-200/60 sm:flex-row sm:items-center sm:justify-between';

    $links = [
        [route('citizen.dashboard'), 'Home'],
        [route('citizen.services'), 'Services'],
        [route('citizen.applications'), 'Applications'],
        [route('citizen.documents'), 'Documents'],
    ];

    $services = ['Barangay Clearance', 'Certificate of Residency', 'Certificate of Indigency'];
    $directions = 'https://maps.app.goo.gl/3sjGNZNBfGSFn64w9';
@endphp

<footer class="mt-auto border-t-4 border-green-700 bg-blue-950 text-white">
    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8">

        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">

            {{-- Brand --}}
            <div>
                <a href="{{ route('citizen.dashboard') }}" class="inline-block">
                    <span
                        class="block whitespace-nowrap font-['Barlow_Condensed'] text-4xl font-bold uppercase tracking-[0.06em] text-[#006aff]">
                        BarangayChain <span class="text-[#5fd47a]">PH</span>
                    </span>
                    <span class="mt-1 block text-xs font-medium text-blue-200/70">
                        Barangay San Luis, Antipolo City, Rizal
                    </span>
                </a>

                <p class="mt-5 max-w-xs text-sm leading-6 text-blue-100/75">
                    Official citizen portal of Barangay San Luis. Access public services and manage your
                    account online.
                </p>
            </div>

            {{-- Quick links --}}
            <nav aria-label="Footer">
                <h3 class="{{ $heading }}">Quick links</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-blue-100/80">
                    @foreach ($links as [$href, $text])
                        <li><a href="{{ $href }}" class="{{ $link }}">{{ $text }}</a></li>
                    @endforeach
                </ul>
            </nav>

            {{-- Services --}}
            <div>
                <h3 class="{{ $heading }}">Services</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-blue-100/80">
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
                        <dd class="mt-0.5">
                            <a href="tel:+639233232322" class="{{ $contactLink }}">+63 923 323 2322</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Email</dt>
                        <dd class="mt-0.5 break-all">
                            <a href="mailto:barangaychain@gmail.com" class="{{ $contactLink }}">barangaychain@gmail.com</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Facebook</dt>
                        <dd class="mt-0.5">
                            <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank"
                                rel="noopener noreferrer" class="{{ $facebookLink }}">
                                Visit our Facebook page
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>

        </div>

        {{-- Office information and map --}}
        <div class="mt-12 grid overflow-hidden rounded-md border border-white/15 md:grid-cols-[1fr_1.5fr]">

            <div class="p-6">
                <h3 class="{{ $heading }}">Visit our office</h3>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="font-semibold text-blue-50">Monday to Friday</dt>
                        <dd class="mt-0.5 text-blue-100/75">8:00 AM to 5:00 PM</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-blue-50">Saturday and Sunday</dt>
                        <dd class="mt-0.5 text-blue-100/75">Closed</dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Address</dt>
                        <dd class="mt-0.5">
                            <a href="{{ $directions }}" target="_blank" rel="noopener noreferrer"
                                class="{{ $contactLink }}">
                                J53X+J7G, Sitio Pinagminahan, Antipolo, 1870 Rizal
                            </a>
                        </dd>
                    </div>
                </dl>

                <a href="{{ $directions }}" target="_blank" rel="noopener noreferrer"
                    class="{{ $facebookLink }} mt-5 inline-block text-sm">
                    Get directions
                </a>
            </div>

            <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City&output=embed"
                class="h-64 w-full border-t border-white/15 md:h-full md:min-h-72 md:border-l md:border-t-0">
            </iframe>
        </div>

        {{-- Legal links and copyright --}}
        <div class="{{ $bottomRow }}">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <a href="{{ route('privacy') }}" class="hover:text-white">Privacy Notice</a>
                <a href="{{ route('terms') }}" class="border-l border-white/20 pl-4 hover:text-white">Terms of Service</a>
                <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="border-l border-white/20 pl-4 hover:text-white">Back to top ↑</button>
            </div>

            <p>© {{ now()->year }} Barangay San Luis. All rights reserved.</p>
        </div>

    </div>
</footer>