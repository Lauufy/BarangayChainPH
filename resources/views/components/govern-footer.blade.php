@php
    // Shared footer styles (kept identical in citizen-footer.blade.php)
    $heading = 'border-l-2 border-green-600 pl-3 text-sm font-bold text-white';
    $link = 'transition hover:text-white hover:underline';
    $contactLink = 'text-blue-50 transition hover:underline';
    $term = 'text-xs text-blue-200/60';
    $facebookLink = 'font-semibold text-white underline underline-offset-4 decoration-green-600 hover:decoration-white';
    $bottomRow =
        'mt-12 flex flex-col gap-2 border-t border-white/15 pt-6 text-xs text-blue-200/60 sm:flex-row sm:items-center sm:justify-between';

    $links = [
        [url('/') . '#services', 'Services'],
        [url('/') . '#announcements', 'Announcements'],
        [url('/') . '#about', 'About'],
        [route('login'), 'Log in'],
        [route('register'), 'Create account'],
    ];
@endphp

<footer class="border-t-4 border-green-700 bg-blue-950 text-white">
    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8">

        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">

            {{-- Brand --}}
            <div>
                <span
                    class="block whitespace-nowrap font-['Barlow_Condensed'] text-4xl font-bold uppercase tracking-[0.06em] text-[#006aff]">
                    BarangayChain <span class="text-[#5fd47a]">PH</span>
                </span>
                </span>
                <span class="mt-1 block text-xs font-medium text-blue-200/70">
                    Barangay San Luis, Antipolo City, Rizal
                </span>

                <p class="mt-5 max-w-xs text-sm leading-6 text-blue-100/75">
                    The official online portal of Barangay San Luis, for public information and
                    barangay services.
                </p>
            </div>

            {{-- Quick links --}}
            <nav aria-label="Footer">
                <h3 class="{{ $heading }}">Explore</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-blue-100/80">
                    @foreach ($links as [$href, $text])
                        <li><a href="{{ $href }}" class="{{ $link }}">{{ $text }}</a></li>
                    @endforeach
                </ul>
            </nav>

            {{-- Contact --}}
            <div>
                <h3 class="{{ $heading }}">Contact</h3>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="{{ $term }}">Address</dt>
                        <dd class="mt-0.5 text-blue-50">Barangay San Luis, Antipolo City, Rizal</dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Phone</dt>
                        <dd class="mt-0.5">
                            <a href="tel:+639233232322" class="{{ $contactLink }}">+63 923 323 2322</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $term }}">Email</dt>
                        <dd class="mt-0.5 break-all">
                            <a href="mailto:barangaychain@gmail.com"
                                class="{{ $contactLink }}">barangaychain@gmail.com</a>
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

            {{-- Office hours --}}
            <div>
                <h3 class="{{ $heading }}">Office hours</h3>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="font-semibold text-blue-50">Monday to Friday</dt>
                        <dd class="mt-0.5 text-blue-100/75">8:00 AM to 5:00 PM</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-blue-50">Saturday and Sunday</dt>
                        <dd class="mt-0.5 text-blue-100/75">Closed</dd>
                    </div>
                </dl>
            </div>

        </div>

        {{-- Legal links and copyright --}}
        <div class="{{ $bottomRow }}">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <a href="{{ route('privacy') }}" class="hover:text-white">Privacy Notice</a>
                <a href="{{ route('terms') }}" class="border-l border-white/20 pl-4 hover:text-white">Terms of
                    Service</a>
                <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="border-l border-white/20 pl-4 hover:text-white">Back to top ↑</button>
            </div>

            <p>© {{ now()->year }} Barangay San Luis. All rights reserved.</p>
        </div>

    </div>
</footer>
