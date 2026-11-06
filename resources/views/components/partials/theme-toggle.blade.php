{{--
    Universal light / dark switch. Drop it anywhere (header, sidebar, dashboard, login page...):
        <x-partials.theme-toggle />
        <x-partials.theme-toggle class="ml-4" />

    - Needs <x-partials.theme-init /> in <head> and `@custom-variant dark (&:where(.dark, .dark *));` in app.css.
    - Any number of switches can be on one page; they stay in sync.
    - The choice is saved in the browser, so it is the same on every page and tab.
--}}
<button type="button" role="switch" aria-checked="false" aria-label="Dark mode" title="Toggle light / dark mode"
    data-theme-toggle
    {{ $attributes->class(['relative inline-flex h-8 w-14 shrink-0 cursor-pointer items-center rounded-full bg-slate-200 transition-colors duration-300 dark:bg-blue-900']) }}>
    <span
        class="absolute left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-white text-amber-500 shadow transition-transform duration-300 dark:translate-x-6 dark:text-blue-700">
        {{-- sun (light mode) --}}
        <svg class="h-4 w-4 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>
        {{-- moon (dark mode) --}}
        <svg class="hidden h-4 w-4 dark:block" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
    </span>
</button>

@once
    <script>
        (() => {
            const root = document.documentElement;
            const sync = () => document.querySelectorAll('[data-theme-toggle]').forEach((b) =>
                b.setAttribute('aria-checked', root.classList.contains('dark')));
            const set = (dark) => {
                root.classList.toggle('dark', dark);
                try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
                sync();
            };

            document.addEventListener('click', (e) => {
                if (e.target.closest('[data-theme-toggle]')) set(!root.classList.contains('dark'));
            });
            // Keep other open tabs in sync
            addEventListener('storage', (e) => {
                if (e.key === 'theme') {
                    root.classList.toggle('dark', e.newValue === 'dark');
                    sync();
                }
            });
            document.addEventListener('DOMContentLoaded', sync);
            sync();
        })();
    </script>
@endonce