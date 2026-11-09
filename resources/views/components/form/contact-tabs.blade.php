{{--
    Email | Mobile number tabs. Only the picked tab is sent to the server
    (the hidden tab's input is disabled), and "mode" says which one it was.
    Colors follow the site theme (light / dark).

    Usage:
    <x-form.contact-tabs email-name="email" email-label="Email Address" />
--}}
@props([
    'emailName' => 'email',
    'emailLabel' => 'Email Address',
    'emailType' => 'email',
    'emailPlaceholder' => 'you@example.com',
    'emailAutocomplete' => 'email',
])

@php
    $mode = old('mode', 'email') === 'phone' ? 'phone' : 'email';
    $tab = 'flex min-w-0 flex-1 items-center justify-center rounded px-3 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-600) dark:focus-visible:ring-(--green-400)';
    $muted = 'text-(--gray-500) dark:text-(--gray-400)';

    // Paint over the browser's blue autofill background, keep text and caret readable (same as x-form.input)
    $autofill = '[&:-webkit-autofill]:shadow-[inset_0_0_0_1000px_var(--white)] [&:-webkit-autofill]:[-webkit-text-fill-color:var(--gray-900)] [&:-webkit-autofill]:caret-(--gray-900) dark:[&:-webkit-autofill]:shadow-[inset_0_0_0_1000px_var(--gray-800)] dark:[&:-webkit-autofill]:[-webkit-text-fill-color:var(--gray-50)] dark:[&:-webkit-autofill]:caret-(--white)';
@endphp

<div data-contact-tabs data-mode="{{ $mode }}" class="space-y-4">
    <input type="hidden" name="mode" value="{{ $mode }}">

    {{-- Tabs --}}
    <div class="flex rounded-md border border-(--gray-200) bg-(--blue-50) p-1 dark:border-(--gray-700) dark:bg-(--gray-900)">
        <button type="button" data-tab="email" class="{{ $tab }}">Email</button>
        <button type="button" data-tab="phone" class="{{ $tab }}">Mobile number</button>
    </div>

    {{-- Email tab --}}
    <div data-panel="email" @if ($mode !== 'email') hidden @endif>
        <x-form.input
            light
            :name="$emailName"
            :label="$emailLabel"
            :type="$emailType"
            :autocomplete="$emailAutocomplete"
            :placeholder="$emailPlaceholder"
            required
        />
    </div>

    {{-- Mobile number tab: the user types only the part after +63 --}}
    <div data-panel="phone" class="group" @if ($mode !== 'phone') hidden @endif>
        <label for="phone" class="{{ $muted }} mb-1.5 block text-xs font-semibold uppercase tracking-wider transition-colors group-focus-within:text-(--blue-600) dark:group-focus-within:text-(--green-400)">
            Mobile number
        </label>

        <div class="relative">
            <span class="{{ $muted }} pointer-events-none absolute inset-y-0 left-3.5 flex items-center gap-1.5 text-sm">
                <span class="text-[10px] font-bold">PH</span> +63
            </span>

            <input
                id="phone"
                name="phone"
                type="tel"
                inputmode="numeric"
                maxlength="11"
                pattern="0?9[0-9]{9}"
                required
                autocomplete="tel-national"
                placeholder="9171234567"
                value="{{ preg_replace('/^(\+?63|0)/', '', (string) old('phone')) }}"
                class="{{ $autofill }} block h-14 w-full rounded-md border border-(--gray-300) bg-(--white) pl-20 pr-3.5 text-base text-(--gray-900) outline-none transition placeholder:text-(--gray-400) hover:border-(--gray-400) focus:border-(--blue-600) focus:ring-2 focus:ring-[color-mix(in_srgb,var(--blue-600)_20%,transparent)] disabled:opacity-60 dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-50) dark:placeholder:text-(--gray-500) dark:hover:border-(--gray-500) dark:focus:border-(--green-400) dark:focus:ring-[color-mix(in_srgb,var(--green-400)_15%,transparent)] sm:text-sm"
            >
        </div>
    </div>
</div>

@once
    <script>
        // Tab colors (full class names so Tailwind can find them)
        const TAB_ON = ['bg-(--white)', 'shadow-sm', 'text-(--gray-900)', 'dark:bg-(--gray-700)', 'dark:text-(--gray-50)'];
        const TAB_OFF = ['text-(--gray-500)', 'dark:text-(--gray-400)'];

        function setContactMode(box, mode) {
            box.querySelector('[name=mode]').value = mode;

            box.querySelectorAll('[data-panel]').forEach(panel => {
                const on = panel.dataset.panel === mode;
                panel.hidden = !on;
                // A disabled input is not sent and not validated, so the hidden tab never blocks the form.
                panel.querySelectorAll('input').forEach(input => input.disabled = !on);
            });

            box.querySelectorAll('[data-tab]').forEach(tab => {
                const on = tab.dataset.tab === mode;
                tab.classList.remove(...TAB_ON, ...TAB_OFF);
                tab.classList.add(...(on ? TAB_ON : TAB_OFF));
            });
        }

        document.addEventListener('click', event => {
            const tab = event.target.closest('[data-tab]');
            if (tab) setContactMode(tab.closest('[data-contact-tabs]'), tab.dataset.tab);
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-contact-tabs]').forEach(box => setContactMode(box, box.dataset.mode));
        });
    </script>
@endonce