{{--
    Email | Mobile number tabs. Only the picked tab is sent to the server
    (the hidden tab's input is disabled), and "mode" says which one it was.

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
    $tab = 'flex min-w-0 flex-1 items-center justify-center rounded px-3 py-2.5 text-sm font-semibold transition';
@endphp

<div data-contact-tabs data-mode="{{ $mode }}" class="space-y-4">
    <input type="hidden" name="mode" value="{{ $mode }}">

    {{-- Tabs --}}
    <div class="flex rounded-md border border-[#DDE3F3] bg-[#E6ECFA] p-1">
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
    <div data-panel="phone" @if ($mode !== 'phone') hidden @endif>
        <label for="phone" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
            Mobile number
        </label>

        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center gap-1.5 text-sm text-slate-500">
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
                class="block h-14 w-full rounded-md border border-slate-300 bg-white pl-20 pr-3.5 text-base text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-[#17325c] focus:ring-2 focus:ring-[#17325c]/20 disabled:opacity-60 sm:text-sm"
            >
        </div>
    </div>
</div>

@once
    <script>
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
                tab.classList.toggle('bg-white', on);
                tab.classList.toggle('shadow-sm', on);
                tab.classList.toggle('text-[#0F1B3D]', on);
                tab.classList.toggle('text-[#5A6788]', !on);
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
