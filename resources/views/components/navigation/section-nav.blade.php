{{--
    Section navigation card.
    Usage: <x-partials.section-nav :items="[['services', 'Services'], ['help', 'Help']]" />
    Props: items = [section id, label] pairs (no #), label = aria-label, bleed = full-width band
--}}
@props(['items' => [], 'label' => 'Page sections', 'bleed' => false])

@php
    $navLink = 'relative whitespace-nowrap py-3 text-sm font-medium text-slate-600 transition-colors hover:text-blue-900 focus-visible:text-blue-900 aria-[current=true]:font-semibold aria-[current=true]:text-blue-900 after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:origin-left after:scale-x-0 after:bg-emerald-600 after:transition-transform after:duration-200 hover:after:scale-x-100 focus-visible:after:scale-x-100 aria-[current=true]:after:scale-x-100 focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-emerald-600';
@endphp

<nav aria-label="{{ $label }}" data-section-nav
    {{ $attributes->class(['relative left-1/2 w-screen -translate-x-1/2' => $bleed]) }}>
    <div class="mx-auto max-w-7xl px-5 sm:px-8">
        <div class="overflow-x-auto rounded-md border border-slate-400  bg-[#1900ff07]">
            <div class="mx-auto flex w-max gap-5 px-6 py-0.5">
                @foreach ($items as [$id, $text])
                    <a href="#{{ $id }}" data-section-link class="{{ $navLink }}">{{ $text }}</a>
                @endforeach
            </div>
        </div>
    </div>
</nav>

{{-- Added once per page, however many navs are used --}}
@once
    <script>
        document.querySelectorAll('[data-section-nav]').forEach((nav) => {
            const links = [...nav.querySelectorAll('[data-section-link]')];
            const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Smooth scroll
            links.forEach((a) => a.addEventListener('click', (e) => {
                const target = document.querySelector(a.hash);
                if (!target) return;

                e.preventDefault();
                target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
                history.replaceState(null, '', a.hash);
            }));

            // Highlight the link of the section on screen
            if (!('IntersectionObserver' in window)) return;

            const spy = new IntersectionObserver((items) => items.forEach((item) => {
                if (item.isIntersecting) {
                    links.forEach((a) => a.setAttribute('aria-current', a.hash === '#' + item.target.id));
                }
            }), { rootMargin: '-35% 0px -55% 0px' });

            links.forEach((a) => {
                const target = document.querySelector(a.hash);
                if (target) spy.observe(target);
            });
        });
    </script>
@endonce