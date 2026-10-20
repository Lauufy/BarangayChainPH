@php
    // Shared styles
    $icon = 'h-5 w-5 fill-none stroke-[#17325c] stroke-2 [stroke-linecap:round] [stroke-linejoin:round]';
    $iconCircle = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50';
    $heading = 'text-sm font-bold uppercase tracking-wider text-white';
    $link = 'transition-colors duration-200 hover:text-white hover:underline';
    $label = 'text-xs font-semibold uppercase tracking-wide text-blue-200';
@endphp

<footer class="mt-auto border-t-4 border-emerald-600 bg-[#17325c] text-blue-100">
    <div class="mx-auto max-w-7xl space-y-10 px-4 py-12 sm:px-6 lg:px-8">

        {{-- Main footer --}}
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">

            {{-- Brand --}}
            <div class="sm:col-span-2 lg:col-span-4">
                <a href="{{ route('citizen.dashboard') }}"
                    class="inline-block font-['Barlow_Condensed'] text-3xl font-bold leading-none tracking-wide">
                    <span class="text-white">BARANGAY</span><span class="text-emerald-500">CHAIN PH</span>
                </a>

                <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-blue-200">
                    San Luis | Antipolo City
                </p>

                <p class="mt-4 max-w-sm text-sm leading-6 text-blue-100/80">
                    Official citizen portal of Barangay San Luis, Antipolo City.
                    Access public services and manage your account online.
                </p>

                <a href="https://www.facebook.com/barangay.sanluis.35"
                    target="_blank" rel="noopener noreferrer"
                    class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-[#17325c] transition hover:bg-blue-50">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                    </svg>
                    Visit our Facebook page
                </a>
            </div>

            {{-- Menu --}}
            <div class="lg:col-span-2">
                <h3 class="{{ $heading }}">Quick Links</h3>

                <ul class="mt-4 space-y-3 text-sm text-blue-100/80">
                    @foreach ([
                        'Home' => 'citizen.dashboard',
                        'Services' => 'citizen.services',
                        'Applications' => 'citizen.applications',
                        'Documents' => 'citizen.documents',
                    ] as $name => $route)
                        <li>
                            <a href="{{ route($route) }}" class="{{ $link }}">
                                {{ $name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Services --}}
            <div class="lg:col-span-2">
                <h3 class="{{ $heading }}">Services</h3>

                <ul class="mt-4 space-y-3 text-sm text-blue-100/80">
                    @foreach ([
                        'Barangay Clearance',
                        'Certificate of Residency',
                        'Certificate of Indigency',
                    ] as $service)
                        <li class="flex items-start gap-2">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400"></span>
                            <span>{{ $service }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Contact --}}
            <div class="sm:col-span-2 lg:col-span-4">
                <h3 class="{{ $heading }}">Contact Us</h3>

                <ul class="mt-4 space-y-5 text-sm">
                    <li class="flex items-center gap-3">
                        <span class="{{ $iconCircle }}">
                            <svg class="{{ $icon }}" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>
                            </svg>
                        </span>

                        <div>
                            <p class="{{ $label }}">Phone</p>
                            <a href="tel:+639233232322" class="{{ $link }}">
                                +63 923 323 2322
                            </a>
                        </div>
                    </li>

                    <li class="flex items-center gap-3">
                        <span class="{{ $iconCircle }}">
                            <svg class="{{ $icon }}" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 5h18v14H3zM3 5l9 8 9-8"/>
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <p class="{{ $label }}">Email</p>
                            <a href="mailto:barangaychain@gmail.com"
                                class="{{ $link }} break-all">
                                barangaychain@gmail.com
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Office information and map --}}
        <div class="grid overflow-hidden rounded-xl border border-white/15 bg-white md:grid-cols-[1fr_1.5fr]">

            <div class="space-y-6 p-6">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[#17325c]">
                        Visit Our Office
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Barangay San Luis, Antipolo City
                    </p>
                </div>

                <div class="flex items-start gap-3">
                    <span class="{{ $iconCircle }}">
                        <svg class="{{ $icon }}" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                    </span>

                    <div class="text-sm text-slate-600">
                        <p class="font-semibold text-slate-900">Office Hours</p>
                        <p>Monday to Friday</p>
                        <p>8:00 AM – 5:00 PM</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="{{ $iconCircle }}">
                        <svg class="{{ $icon }}" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                    </span>

                    <a href="https://maps.app.goo.gl/3sjGNZNBfGSFn64w9"
                        target="_blank" rel="noopener noreferrer"
                        class="text-sm leading-6 text-slate-600 transition hover:text-[#17325c] hover:underline">
                        J53X+J7G, Sitio Pinagminahan, Antipolo, 1870 Rizal
                    </a>
                </div>

                <a href="https://maps.app.goo.gl/3sjGNZNBfGSFn64w9"
                    target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#17325c] hover:underline">
                    Get directions
                    <svg class="h-4 w-4 fill-none stroke-current" viewBox="0 0 24 24"
                        stroke-width="2" aria-hidden="true">
                        <path d="M7 17 17 7M7 7h10v10"/>
                    </svg>
                </a>
            </div>

            <iframe
                title="Map of Barangay San Luis, Antipolo City"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City&output=embed"
                class="h-64 w-full border-t border-slate-200 md:h-full md:min-h-72 md:border-l md:border-t-0">
            </iframe>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="border-t border-white/10 bg-[#102748] text-xs text-blue-100/80">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-5 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <a href="{{ route('privacy') }}" class="hover:text-white">
                    Privacy Notice
                </a>

                <a href="{{ route('terms') }}"
                    class="border-l border-white/20 pl-4 hover:text-white">
                    Terms of Service
                </a>

                <button type="button"
                    onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="border-l border-white/20 pl-4 hover:text-white">
                    Back to Top ↑
                </button>
            </div>

            <p>
                © {{ now()->year }} Barangay San Luis, Antipolo City. All rights reserved.
            </p>
        </div>
    </div>
</footer>
