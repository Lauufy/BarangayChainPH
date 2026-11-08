@props([
    'name',
    'id' => null,
    'label',
    'type' => 'text',
    'value' => null,
    'options' => [],
    'hint' => null,
    'raised' => false, // true = label always floated, placeholder always visible
    'light' => false, // true = follows the theme toggle (white in light mode, dark in dark mode)
                      // false = always the dark navy style (login, register, etc.)
    'useOld' => true, // false = always show $value, ignore old input
])

@php
    $fieldId = $id ?? $name;
    $current = $useOld ? old($name, $value) : $value;

    // ------------------------------------------------------------------
    // Colors. Uses the theme tokens (resources/css/theme.css).
    // $light = adaptive: white in light mode, dark in dark mode (the toggle changes it)
    // else   = always dark navy
    // (full class names are written out so Tailwind can find them)
    // ------------------------------------------------------------------
    $fieldColors = $light
        ? 'border-(--gray-300) bg-(--white) text-(--gray-900) hover:border-(--gray-400) focus:border-(--blue-600) focus:ring-[color-mix(in_srgb,var(--blue-600)_20%,transparent)] dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-50) dark:hover:border-(--gray-500) dark:focus:border-(--green-400) dark:focus:ring-[color-mix(in_srgb,var(--green-400)_15%,transparent)]'
        : 'border-white/10 bg-(--blue-900) text-(--white) hover:border-white/20 focus:border-(--green-400) focus:ring-[color-mix(in_srgb,var(--green-400)_10%,transparent)]';

    // Paint over the browser's autofill background, keep text and caret readable
    $autofill = $light
        ? '[&:-webkit-autofill]:shadow-[inset_0_0_0_1000px_var(--white)] [&:-webkit-autofill]:[-webkit-text-fill-color:var(--gray-900)] [&:-webkit-autofill]:caret-(--gray-900) dark:[&:-webkit-autofill]:shadow-[inset_0_0_0_1000px_var(--gray-800)] dark:[&:-webkit-autofill]:[-webkit-text-fill-color:var(--gray-50)] dark:[&:-webkit-autofill]:caret-(--white)'
        : '[&:-webkit-autofill]:shadow-[inset_0_0_0_1000px_var(--blue-900)] [&:-webkit-autofill]:[-webkit-text-fill-color:var(--white)] [&:-webkit-autofill]:caret-(--white)';

    $muted = $light
        ? 'text-(--gray-500) dark:text-(--gray-400)'
        : 'text-[color-mix(in_srgb,var(--blue-100)_60%,transparent)]';

    $labelFocus = $light
        ? 'peer-focus:text-(--blue-600) dark:peer-focus:text-(--green-400)'
        : 'peer-focus:text-(--green-400)';

    $optionBg = $light
        ? '[&>option]:bg-(--white) [&>option]:text-(--gray-900) dark:[&>option]:bg-(--gray-800) dark:[&>option]:text-(--gray-50)'
        : '[&>option]:bg-(--blue-900)';

    $toggleColors = $light
        ? 'hover:text-(--blue-600) focus-visible:ring-(--blue-600) dark:hover:text-(--green-400) dark:focus-visible:ring-(--green-400)'
        : 'hover:text-(--green-400) focus-visible:ring-(--green-400)';

    // ------------------------------------------------------------------
    // Placeholder: hidden until focus, unless "raised"
    // ------------------------------------------------------------------
    $placeholderColor = $light
        ? 'placeholder:text-(--gray-400) dark:placeholder:text-(--gray-500)'
        : 'placeholder:text-[color-mix(in_srgb,var(--blue-100)_40%,transparent)]';

    $placeholderOnFocus = $light
        ? 'focus:placeholder:text-(--gray-400) dark:focus:placeholder:text-(--gray-500)'
        : 'focus:placeholder:text-[color-mix(in_srgb,var(--blue-100)_40%,transparent)]';

    $placeholder = $raised ? $placeholderColor : "placeholder:text-transparent $placeholderOnFocus";

    // ------------------------------------------------------------------
    // Field classes shared by input, select and textarea
    // ------------------------------------------------------------------
    $readOnly = in_array($type, ['select', 'file'])
        ? ''
        : 'read-only:cursor-not-allowed read-only:opacity-60';

    $base = implode(' ', [
        'peer validation-input block w-full rounded-md border pb-1.5 pl-3.5 pt-5',
        'text-base outline-none transition focus:ring-2 sm:text-sm',
        'disabled:opacity-60',
        $readOnly,
        $fieldColors,
        $autofill,
    ]);

    // ------------------------------------------------------------------
    // Floating label
    // ------------------------------------------------------------------
    $labelPosition = match (true) {
        $raised => 'top-2.5 text-[10px]',
        $type === 'textarea' => 'top-4',
        default => 'top-1/2 -translate-y-1/2',
    };

    // Label moves up and shrinks on focus, autofill, or when the field has a value
    $labelFloated = implode(' ', [
        'peer-focus:top-2.5 peer-focus:translate-y-0 peer-focus:text-[10px]',
        'peer-autofill:top-2.5 peer-autofill:translate-y-0 peer-autofill:text-[10px]',
        'peer-not-placeholder-shown:top-2.5 peer-not-placeholder-shown:translate-y-0 peer-not-placeholder-shown:text-[10px]',
    ]);

    $labelClasses = implode(' ', [
        'pointer-events-none absolute left-3.5',
        'text-xs font-semibold uppercase tracking-wider transition-all duration-200',
        $muted,
        $labelFocus,
        $labelPosition,
        $labelFloated,
    ]);
@endphp

<div>
    <div class="relative">

        @if ($type === 'textarea')
            <textarea id="{{ $fieldId }}" name="{{ $name }}"
                {{ $attributes->class([$base, $placeholder, 'min-h-[7rem] resize-y pr-3.5']) }}>{{ $current }}</textarea>
        @elseif ($type === 'select')
            {{-- options: ['value' => 'Label'] --}}
            <select id="{{ $fieldId }}" name="{{ $name }}"
                {{ $attributes->except('placeholder')->class([$base, $optionBg, 'h-14 appearance-none pr-10']) }}>
                <option value="">{{ $attributes->get('placeholder', 'Select an option') }}</option>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>

            <svg class="{{ $muted }} pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        @else
            {{-- text, email, tel, number, password, date... --}}
            <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}"
                @if ($type !== 'password') value="{{ $current }}" @endif
                {{ $attributes->class([$base, $placeholder, 'h-14', $type === 'password' ? 'pr-11' : 'pr-3.5']) }}>
        @endif

        <label for="{{ $fieldId }}" class="{{ $labelClasses }}">
            {{ $label }}
        </label>

        {{-- Show / hide password button --}}
        @if ($type === 'password')
            <button type="button" data-password-toggle="{{ $name }}" aria-label="Show password"
                class="{{ $muted }} {{ $toggleColors }} absolute inset-y-0 right-0 flex items-center rounded-r-md px-3.5 transition focus-visible:outline-none focus-visible:ring-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                    aria-hidden="true">
                    <g data-password-eye>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </g>
                </svg>
            </button>
        @endif
    </div>

    {{-- Error message (filled by JS on blur, hidden while typing) --}}
    <p data-error-for="{{ $name }}" class="mt-1 hidden text-xs text-(--red-500)"></p>

    @if ($hint)
        <p class="{{ $muted }} mt-1 text-xs">{{ $hint }}</p>
    @endif

    {{ $slot }}
</div>