{{--
    OTP input: resources/views/components/form/otp.blade.php

    Use it:
        <x-form.otp name="otp" label="Verification code" :length="6" light />

    - One box per digit. Typing moves to the next box, Backspace goes back, arrows move, paste fills all boxes.
    - A hidden input named after "name" holds the full code, so the form sends it like a normal field.
    - Fires "otp:complete" on the wrapper when every box is filled:
        document.querySelector('[data-otp]').addEventListener('otp:complete', (e) => e.detail.code);
    - Clear it from JavaScript:  OtpInput.clear(element)
--}}
@props([
    'name' => 'otp',
    'id' => null,
    'label' => null,
    'length' => 6,
    'value' => null,
    'hint' => null,
    'numeric' => true, // false = letters and numbers
    'light' => false, // true = follows the theme toggle, false = always dark navy (same as x-form.input)
    'useOld' => true,
    'autofocus' => false, // true = focus the first box when the page opens
    'required' => false, // true = every box must be filled before the form can submit
])

@php
    $fieldId = $id ?? $name;
    $current = (string) ($useOld ? old($name, $value) : $value);
    $chars = array_pad(str_split(substr($current, 0, $length)), $length, '');
    $invalid = $errors->has($name);

    $colors = $light
        ? 'border-(--gray-300) bg-(--gray-50) text-(--gray-900) hover:border-(--gray-400) focus:border-(--blue-600) focus:bg-(--white) focus:ring-[color-mix(in_srgb,var(--blue-600)_25%,transparent)] dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-50) dark:hover:border-(--gray-500) dark:focus:border-(--green-400) dark:focus:bg-(--gray-900) dark:focus:text-(--white) dark:focus:ring-[color-mix(in_srgb,var(--green-400)_30%,transparent)]'
        : 'border-white/10 bg-(--blue-900) text-(--white) hover:border-white/20 focus:border-(--green-400) focus:ring-[color-mix(in_srgb,var(--green-400)_15%,transparent)]';

    $invalidColors = 'border-(--red-500) hover:border-(--red-500) focus:border-(--red-500)';

    $muted = $light
        ? 'text-(--gray-500) dark:text-(--gray-400)'
        : 'text-[color-mix(in_srgb,var(--blue-100)_60%,transparent)]';

    // rounded-md = less rounded than the usual pill-style OTP boxes.
    // Responsive: boxes share the row equally (flex-1, min-w-0), so 4, 6 or 8 boxes always fit,
    // even on a 320px phone. They stop growing at max-w-16 on wide containers.
    // text-xl (20px) is above 16px, so iOS Safari does not zoom in when a box is focused.
    $box = implode(' ', [
        'h-12 min-w-0 max-w-16 flex-1 rounded-md border px-0 text-center text-xl font-semibold caret-current',
        'outline-none transition focus:ring-2 sm:h-14 disabled:opacity-60',
        $invalid ? $invalidColors : '',
        $colors,
    ]);
@endphp

<div {{ $attributes->class('w-full') }}>
    @if ($label)
        <span id="{{ $fieldId }}-label" class="{{ $muted }} mb-2 block text-xs font-semibold uppercase tracking-wider">
            {{ $label }}
        </span>
    @endif

    <div data-otp data-numeric="{{ $numeric ? 1 : 0 }}" role="group"
        @if ($label) aria-labelledby="{{ $fieldId }}-label" @else aria-label="Verification code" @endif
        class="flex w-full gap-1.5 min-[400px]:gap-2 sm:gap-3">

        @foreach ($chars as $i => $char)
            <input type="text" data-otp-box value="{{ $char }}"
                inputmode="{{ $numeric ? 'numeric' : 'text' }}"
                autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                autocapitalize="characters" spellcheck="false"
                aria-label="Digit {{ $i + 1 }} of {{ $length }}"
                @if ($invalid) aria-invalid="true" @endif
                @if ($required) required @endif
                @if ($autofocus && $i === 0) autofocus @endif
                class="{{ $box }}">
        @endforeach

        {{-- The real field the form submits --}}
        <input type="hidden" id="{{ $fieldId }}" name="{{ $name }}" value="{{ implode('', $chars) }}" data-otp-value>
    </div>

    {{-- Error message (server error, or filled by your JS) --}}
    <p data-error-for="{{ $name }}"
        class="{{ $invalid ? '' : 'hidden' }} mt-2 text-xs text-(--red-500)" role="alert">{{ $errors->first($name) }}</p>

    @if ($hint)
        <p class="{{ $muted }} mt-2 text-xs">{{ $hint }}</p>
    @endif
</div>

@once
    <script>
        window.OtpInput = {
            init(root) {
                if (root.dataset.ready) return;
                root.dataset.ready = '1';

                const boxes = [...root.querySelectorAll('[data-otp-box]')];
                const hidden = root.querySelector('[data-otp-value]');
                const numeric = root.dataset.numeric === '1';

                // Keep only allowed characters
                const clean = (text) => numeric ?
                    text.replace(/\D/g, '') :
                    text.replace(/[^A-Za-z0-9]/g, '').toUpperCase();

                const focusBox = (i) => {
                    const box = boxes[Math.max(0, Math.min(boxes.length - 1, i))];
                    box.focus();
                    box.select();
                };

                // Copy the boxes into the hidden field, tell the page when the code is complete
                const sync = () => {
                    hidden.value = boxes.map((box) => box.value).join('');
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));

                    if (hidden.value.length === boxes.length) {
                        root.dispatchEvent(new CustomEvent('otp:complete', {
                            bubbles: true,
                            detail: { code: hidden.value },
                        }));
                    }
                };

                // Put text into the boxes starting at one box (typing, paste, autofill)
                const fill = (start, text) => {
                    const letters = clean(text).split('').slice(0, boxes.length - start);
                    letters.forEach((letter, k) => boxes[start + k].value = letter);
                    focusBox(start + letters.length);
                    sync();
                };

                boxes.forEach((box, i) => {
                    box.addEventListener('input', () => {
                        const text = clean(box.value);
                        box.value = '';
                        if (text) fill(i, text);
                        else sync();
                    });

                    box.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && !box.value && i > 0) {
                            e.preventDefault();
                            boxes[i - 1].value = '';
                            focusBox(i - 1);
                            sync();
                        } else if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            focusBox(i - 1);
                        } else if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            focusBox(i + 1);
                        }
                    });

                    box.addEventListener('paste', (e) => {
                        e.preventDefault();
                        fill(i, (e.clipboardData || window.clipboardData).getData('text'));
                    });

                    box.addEventListener('focus', () => box.select());
                    box.addEventListener('click', () => box.select());
                });
            },

            // Empty every box and focus the first one
            clear(root) {
                const el = typeof root === 'string' ? document.getElementById(root) : root;
                if (!el) return;
                el.querySelectorAll('[data-otp-box]').forEach((box) => box.value = '');
                el.querySelector('[data-otp-value]').value = '';
                el.querySelector('[data-otp-box]').focus();
            },
        };

        const startOtp = () => document.querySelectorAll('[data-otp]').forEach((el) => OtpInput.init(el));
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startOtp);
        else startOtp();
    </script>
@endonce