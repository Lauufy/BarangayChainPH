{{--
    Email | Mobile number tabs. Only the picked tab is sent to the server
    (the hidden tab's input is disabled), and "mode" says which one it was.

    Mobile numbers use the Philippine format: +63 9XX XXX XXXX. The user types
    only the 10 digits after +63 (e.g. 9171234567). A leading 0 or 63, spaces
    and dashes are cleaned up automatically (09171234567 -> 9171234567).

    Usage:
    <x-form.contact-tabs email-name="email" email-label="Email Address"
        email-error-message="Please use a valid email." />
--}}
@props([
    'emailName' => 'email',
    'emailLabel' => 'Email Address',
    'emailType' => 'email',
    'emailPlaceholder' => 'you@example.com',
    'emailAutocomplete' => 'email',
    'emailErrorMessage' => null, // custom text for an invalid email
    'phoneErrorMessage' => 'Enter a valid PH mobile number, like 9171234567.', // custom text for an invalid mobile number
])

@php
    $mode = old('mode', 'email') === 'phone' ? 'phone' : 'email';
    $tab = 'flex min-w-0 flex-1 items-center justify-center rounded px-3 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-600) dark:focus-visible:ring-(--green-400)';
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
            :error-message="$emailErrorMessage"
            required
        />
    </div>

    {{-- Mobile number tab: the user types only the 10 digits after +63 --}}
    <div data-panel="phone" @if ($mode !== 'phone') hidden @endif>
        <x-form.input
            light
            name="phone"
            label="Mobile number"
            type="tel"
            prefix="+63"
            :value="preg_replace('/^(\+?63|0)/', '', (string) old('phone'))"
            :use-old="false"
            :error-message="$phoneErrorMessage"
            inputmode="numeric"
            pattern="9[0-9]{9}"
            autocomplete="tel-national"
            placeholder="9171234567"
            required
        />
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

        // Clean a typed or pasted PH mobile number down to the 10 digits after +63.
        function cleanPhoneNumber(value) {
            let digits = value.replace(/\D/g, '');

            if (digits.startsWith('63') && digits.length > 10) digits = digits.slice(2); // +63 917...
            if (digits.startsWith('0')) digits = digits.slice(1); // 0917...

            return digits.slice(0, 10);
        }

        document.addEventListener('click', event => {
            const tab = event.target.closest('[data-tab]');
            if (tab) setContactMode(tab.closest('[data-contact-tabs]'), tab.dataset.tab);
        });

        document.addEventListener('input', event => {
            const field = event.target;

            if (field.matches('[data-panel="phone"] input[name="phone"]')) {
                const cleaned = cleanPhoneNumber(field.value);
                if (field.value !== cleaned) field.value = cleaned;
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-contact-tabs]').forEach(box => setContactMode(box, box.dataset.mode));
        });
    </script>
@endonce