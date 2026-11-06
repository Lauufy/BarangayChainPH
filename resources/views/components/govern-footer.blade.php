@php
    // Shared footer styles (kept identical in citizen-footer.blade.php)
    // Light mode: blue-900 + white. Dark mode: green glass + white + light emerald accents.
    $heading = 'border-l-2 border-white/60 pl-3 text-sm font-bold text-white dark:border-emerald-300';
    $link = 'inline-block transition duration-200 hover:translate-x-0.5 hover:text-blue-200 dark:hover:text-emerald-300';
    $contactLink =
        'text-blue-50 underline-offset-4 transition-colors hover:text-blue-200 hover:underline dark:text-white dark:hover:text-emerald-300';
    $term = 'text-xs text-blue-200/60 dark:text-white/50';
    $facebookLink =
        'font-semibold text-white underline underline-offset-4 decoration-white/60 transition-colors hover:text-blue-200 dark:decoration-emerald-300/60 dark:hover:text-emerald-300';
    $bottomRow =
        'mt-12 flex flex-col gap-2 border-t border-white/15 pt-6 text-xs text-blue-200/60 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:text-white/50';
    $legal = 'transition-colors hover:text-blue-200 dark:hover:text-emerald-300';

    $links = [
        [url('/') . '#services', 'Services'],
        [url('/') . '#announcements', 'Announcements'],
        [url('/') . '#about', 'About'],
        [route('login'), 'Log in'],
        [route('register'), 'Create account'],
    ];
@endphp

<footer
    class="border-t-4 border-white/30 bg-blue-900/90 text-white backdrop-blur-2xl dark:border-emerald-300/60 dark:bg-[#152a1c]/50">
    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">

        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">

            {{-- Brand --}}
            <div>
                <span
                    class="block whitespace-nowrap font-['Barlow_Condensed'] text-4xl font-bold uppercase tracking-[0.06em] text-white">
                    BarangayChain <span class="text-blue-200 dark:text-emerald-300">PH</span>
                </span>
                <span class="mt-1 block text-xs font-medium text-blue-200/70 dark:text-white/60">
                    Barangay San Luis, Antipolo City, Rizal
                </span>

                <p class="mt-5 max-w-xs text-sm leading-6 text-blue-100/75 dark:text-white/70">
                    The official online portal of Barangay San Luis, for public information and
                    barangay services.
                </p>
            </div>

            {{-- Quick links --}}
            <nav aria-label="Footer">
                <h3 class="{{ $heading }}">Explore</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-blue-100/80 dark:text-white/75">
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
                        <dd class="mt-0.5 text-blue-50 dark:text-white">Barangay San Luis, Antipolo City, Rizal</dd>
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
                        <dt class="font-semibold text-blue-50 dark:text-white">Monday to Friday</dt>
                        <dd class="mt-0.5 text-blue-100/75 dark:text-white/70">8:00 AM to 5:00 PM</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-blue-50 dark:text-white">Saturday and Sunday</dt>
                        <dd class="mt-0.5 text-blue-100/75 dark:text-white/70">Closed</dd>
                    </div>
                </dl>
            </div>

        </div>

        {{-- Legal links and copyright --}}
        <div class="{{ $bottomRow }}">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <a href="{{ route('privacy') }}" class="{{ $legal }}">Privacy Notice</a>
                <a href="{{ route('terms') }}" class="border-l border-white/20 pl-4 {{ $legal }}">Terms of
                    Service</a>
                <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="border-l border-white/20 pl-4 {{ $legal }}">Back to top <svg class="inline-block h-4 w-4 align-[-2px]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
            </div>

            <p>© {{ now()->year }} Barangay San Luis. All rights reserved.</p>
        </div>

    </div>
</footer>