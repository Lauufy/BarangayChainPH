@php
    // Shared icon style (outline icons in navy)
    $icon = 'mt-0.5 h-4 w-4 shrink-0 fill-none stroke-[#17325c] stroke-2';
    $link = 'hover:text-[#17325c] hover:underline';
@endphp

<footer class="mt-auto">

    {{-- Info columns --}}
    <div class="border-t border-slate-900 bg-[#ffffff] py-5">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.4fr_1.3fr_1.2fr]">

                {{-- Brand --}}
                <div class="sm:col-span-2 lg:col-span-1">
                    <p class="whitespace-nowrap font-['Barlow_Condensed'] text-3xl font-bold leading-none tracking-wide">
                        <span class="text-[#17325c]">BARANGAY</span><span class="text-[#06640d]">CHAIN PH</span>
                    </p>
                    <p class="mt-1 text-sm font-medium text-emerald-900">SAN LUIS | ANTIPOLO CITY</p>
                    <p class="mt-3 border-t-2 border-slate-800 pt-3 text-sm leading-5 text-slate-600">
                        Official citizen portal of Barangay San Luis, Antipolo City. Request services and manage your account online.
                    </p>
                </div>

                {{-- Menu --}}
                <div>
                    <h3 class="font-bold text-slate-900">Menu</h3>
                    <ul class="mt-3 list-disc space-y-1.5 pl-4 text-xs text-slate-600 marker:text-slate-400">
                        @foreach (['Home' => 'citizen.dashboard', 'Services' => 'citizen.services', 'Applications' => 'citizen.applications', 'Documents' => 'citizen.documents'] as $label => $route)
                            <li><a href="{{ route($route) }}" class="{{ $link }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Services --}}
                <div>
                    <h3 class="font-bold text-slate-900">Services</h3>
                    <ul class="mt-3 list-disc space-y-1.5 pl-4 text-xs text-slate-600 marker:text-slate-400">
                        <li>Barangay clearance</li>
                        <li>Certificate of residency</li>
                        <li>Certificate of indigency</li>
                    </ul>
                </div>

                {{-- Contact --}}
                <div>
                    <h3 class="font-bold text-slate-900">Contact</h3>
                    <ul class="mt-3 space-y-3 text-xs text-slate-600">
                        <li class="flex gap-2">
                            <svg class="{{ $icon }}" viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" /></svg>
                            <a href="tel:+639233232322" class="{{ $link }}">+63 923 323 2322</a>
                        </li>
                        <li class="flex gap-2">
                            <svg class="{{ $icon }}" viewBox="0 0 24 24"><path d="M3 5h18v14H3zM3 5l9 8 9-8" /></svg>
                            <a href="mailto:barangaychain@gmail.com" class="break-all {{ $link }}">barangaychain@gmail.com</a>
                        </li>
                        <li class="flex gap-2">
                            <svg class="{{ $icon }}" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>
                            <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank" rel="noopener noreferrer" class="{{ $link }}">Visit our Facebook page</a>
                        </li>
                    </ul>
                </div>

                {{-- Visit us --}}
                <div>
                    <h3 class="font-bold text-slate-900">Visit us</h3>
                    <ul class="mt-3 space-y-3 text-xs text-slate-600">
                        <li class="flex gap-2">
                            <svg class="{{ $icon }}" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                            <div>
                                <p class="font-bold text-slate-900">Monday to Friday</p>
                                <p>8:00 AM to 5:00 PM</p>
                            </div>
                        </li>
                        <li class="flex gap-2">
                            <svg class="{{ $icon }}" viewBox="0 0 24 24"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z" /><circle cx="12" cy="10" r="2.5" /></svg>
                            <a href="https://maps.app.goo.gl/3sjGNZNBfGSFn64w9" target="_blank" rel="noopener noreferrer" class="{{ $link }}">
                                J53X+J7G, Sitio Pinagminahan, Antipolo, 1870 Rizal
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Map --}}
                <iframe title="Map of Barangay San Luis" loading="lazy"
                    src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City&output=embed"
                    class="h-44 w-full rounded-md border border-slate-200 sm:col-span-2 lg:col-span-1 lg:h-full"></iframe>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="bg-[#17325c] text-xs text-blue-100">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-4 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <div class="flex gap-4">
                <a href="{{ route('privacy') }}" class="hover:text-white">Privacy notice</a>
                <a href="{{ route('terms') }}" class="border-l border-red-500 pl-4 hover:text-white">Terms of service</a>
            </div>
            <p>© {{ now()->year }} Barangay San Luis, Antipolo City. All rights reserved.</p>
        </div>
    </div>
</footer>