@props(['labels' => false])

@php
    $icons = [
        'light' => 'M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z',
        'dark'  => 'M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z',
    ];
    $svg = 'fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"';
@endphp

<div {{ $attributes->class('inline-flex') }}>
    <div role="group" aria-label="Theme"
        class="{{ $labels ? 'inline-flex' : 'hidden md:inline-flex' }} gap-0.5 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
        @foreach ($icons as $k => $d)
            <button type="button" data-theme="{{ $k }}" aria-pressed="false" title="{{ ucfirst($k) }}"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900 aria-pressed:bg-white aria-pressed:text-slate-900 aria-pressed:shadow-sm dark:text-slate-400 dark:hover:text-white dark:aria-pressed:bg-slate-700 dark:aria-pressed:text-white">
                <svg class="h-4 w-4" {!! $svg !!}><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" /></svg>
                <span @class(['sr-only' => !$labels])>{{ ucfirst($k) }}</span>
            </button>
        @endforeach
    </div>

    @unless ($labels)
        <button type="button" data-theme="toggle" aria-label="Toggle theme"
            class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition hover:bg-slate-200 md:hidden dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
            @foreach ($icons as $k => $d)
                <svg class="h-5 w-5 {{ $k === 'light' ? 'dark:hidden' : 'hidden dark:block' }}" {!! $svg !!}><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" /></svg>
            @endforeach
        </button>
    @endunless
</div>

@once
    <script>
        (() => {
            const r = document.documentElement;
            const sync = () => document.querySelectorAll('button[data-theme]').forEach((b) =>
                b.setAttribute('aria-pressed', b.dataset.theme === (r.classList.contains('dark') ? 'dark' : 'light')));
            const set = (d) => {
                r.classList.toggle('dark', d);
                try { localStorage.theme = d ? 'dark' : 'light'; } catch {}
                sync();
            };

            document.addEventListener('click', (e) => {
                const b = e.target.closest('[data-theme]');
                if (b) set(b.dataset.theme === 'toggle' ? !r.classList.contains('dark') : b.dataset.theme === 'dark');
            });
            addEventListener('storage', (e) => {
                if (e.key !== 'theme') return;
                r.classList.toggle('dark', e.newValue === 'dark');
                sync();
            });
            document.addEventListener('DOMContentLoaded', sync);
            sync();
        })();
    </script>
@endonce