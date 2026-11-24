@props([
    'name',
    'id' => null,
    'label',
    'type' => 'text', // text | email | password | number | tel | url | datetime-local | select | textarea
    'value' => null,
    'options' => [], // select only: ['value' => 'Label']
    'hint' => null,
    'errorMessage' => null, // custom text shown when the field fails client-side validation
    'raised' => false, // true = label always floated, placeholder always visible
    'light' => false, // true = follows the theme toggle (white in light mode, dark in dark mode)
                      // false = always the dark navy style (login, register, etc.)
    'useOld' => true, // false = always show $value, ignore old input
    'prefix' => null, // text inside the field before the value (e.g. +63), shows while the label is floated
])

@php
    // ------------------------------------------------------------------
    // Type handling
    // ------------------------------------------------------------------
    $supportedTypes = [
        'text', 'email', 'password', 'number', 'tel', 'url',
        'datetime-local', 'select', 'textarea',
        'date', 'time', // extras, remove if not needed
    ];
    $type = in_array($type, $supportedTypes, true) ? $type : 'text';

    // Types where the browser always paints something inside the field (mask / selected option),
    // so the label must stay floated and the placeholder trick can't be used.
    $alwaysFloated = in_array($type, ['select', 'datetime-local', 'date', 'time'], true);
    $isDateLike = in_array($type, ['datetime-local', 'date', 'time'], true);

    // Prefix only makes sense on plain single-line inputs
    $prefix = in_array($type, ['text', 'tel', 'number', 'url', 'email'], true) ? $prefix : null;

    // Phone with a country prefix: digits only, leading 0 stripped (09171234567 -> 9171234567)
    $isPrefixedPhone = $type === 'tel' && $prefix;

    $fieldId = $id ?? $name;
    $current = $useOld ? old($name, $value) : $value;

    // datetime-local needs "Y-m-d\TH:i"; convert Carbon/DateTime values or "Y-m-d H:i:s" strings
    if ($type === 'datetime-local' && filled($current) && !($useOld && old($name) !== null)) {
        try {
            $current = \Illuminate\Support\Carbon::parse($current)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            // leave as is
        }
    }

    $required = $attributes->has('required'); // shows the red asterisk
    $invalid = $errors->has($name); // Laravel validation error

    // ------------------------------------------------------------------
    // Colors
    // ------------------------------------------------------------------
    $fieldColors = $light
        ? 'border-(--gray-300) bg-(--white) text-(--gray-900) hover:border-(--gray-400) focus:border-(--blue-600) focus:ring-[color-mix(in_srgb,var(--blue-600)_20%,transparent)] dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-50) dark:hover:border-(--gray-500) dark:focus:border-(--green-400) dark:focus:ring-[color-mix(in_srgb,var(--green-400)_15%,transparent)]'
        : 'border-white/10 bg-(--blue-900) text-(--white) hover:border-white/20 focus:border-(--green-400) focus:ring-[color-mix(in_srgb,var(--green-400)_10%,transparent)]';

    // Red border and ring when the field is invalid (aria-invalid="true")
    $errorColors = 'aria-invalid:border-(--red-500) aria-invalid:hover:border-(--red-500) aria-invalid:focus:border-(--red-500) aria-invalid:focus:ring-[color-mix(in_srgb,var(--red-500)_20%,transparent)]';

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

    // Native pickers (calendar / clock icon, popup) follow the field's theme
    $colorScheme = $light
        ? '[color-scheme:light] dark:[color-scheme:dark]'
        : '[color-scheme:dark]';

    // ------------------------------------------------------------------
    // Placeholder: hidden until focus or error, unless "raised"
    // ------------------------------------------------------------------
    $placeholderColor = $light
        ? 'placeholder:text-(--gray-400) dark:placeholder:text-(--gray-500)'
        : 'placeholder:text-[color-mix(in_srgb,var(--blue-100)_40%,transparent)]';

    $placeholderOnFocus = $light
        ? 'focus:placeholder:text-(--gray-400) dark:focus:placeholder:text-(--gray-500)'
        : 'focus:placeholder:text-[color-mix(in_srgb,var(--blue-100)_40%,transparent)]';

    $placeholderOnError = $light
        ? 'aria-invalid:placeholder:text-(--gray-400) dark:aria-invalid:placeholder:text-(--gray-500)'
        : 'aria-invalid:placeholder:text-[color-mix(in_srgb,var(--blue-100)_40%,transparent)]';

    $placeholder = $raised
        ? $placeholderColor
        : "placeholder:text-transparent $placeholderOnFocus $placeholderOnError";

    // ------------------------------------------------------------------
    // Field classes shared by input, select and textarea
    // ------------------------------------------------------------------
    $readOnly = $type === 'select'
        ? ''
        : 'read-only:cursor-not-allowed read-only:opacity-60';

    $typeSpecific = match (true) {
        // hide the number spinners, they clash with the floating label layout
        $type === 'number' => '[appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none',
        $isDateLike => "$colorScheme min-w-0 appearance-none",
        $type === 'select' => $colorScheme,
        default => '',
    };

    $base = implode(' ', [
        'peer validation-input block w-full rounded-md border pb-1.5 pt-5',
        'pl-3.5', // with a prefix, the inline style in $defaults overrides this
        'text-base outline-none transition focus:ring-2 sm:text-sm',
        'disabled:opacity-60',
        $readOnly,
        $typeSpecific,
        $fieldColors,
        $errorColors,
        $autofill,
    ]);

    // ------------------------------------------------------------------
    // Default attributes (anything passed to the component overrides these)
    // ------------------------------------------------------------------
    $defaults = [
        'aria-invalid' => $invalid ? 'true' : null,
        'data-error-message' => $errorMessage,
        // " " keeps :placeholder-shown working so the floating label knows when the field is empty
        'placeholder' => $alwaysFloated ? null : ' ',
        'inputmode' => match (true) {
            $isPrefixedPhone => 'numeric',
            $type === 'tel' => 'tel',
            $type === 'email' => 'email',
            $type === 'url' => 'url',
            default => null,
        },
        'autocomplete' => match ($type) {
            'email' => 'email',
            'tel' => 'tel',
            'url' => 'url',
            'password' => 'current-password',
            default => null,
        },
        // Left padding follows the prefix length: 0.875rem (pl-3.5) + prefix width + 0.625rem gap
        'style' => $prefix
            ? 'padding-left: calc(0.875rem + ' . mb_strlen($prefix) . 'ch + 0.625rem)'
            : null,
        // Prefixed phone: keep digits only and drop leading zeros
        'oninput' => $isPrefixedPhone
            ? "this.value = this.value.replace(/\\D/g, '').replace(/^0+/, '')"
            : null,
    ];

    // ------------------------------------------------------------------
    // Floating label
    // ------------------------------------------------------------------
    $labelPosition = match (true) {
        $raised || $alwaysFloated => 'top-2.5 text-[10px]',
        $type === 'textarea' => 'top-4',
        default => 'top-1/2 -translate-y-1/2',
    };

    // Label moves up and shrinks on focus, autofill, error, or when the field has a value
    $labelFloated = implode(' ', [
        'peer-focus:top-2.5 peer-focus:translate-y-0 peer-focus:text-[10px]',
        'peer-autofill:top-2.5 peer-autofill:translate-y-0 peer-autofill:text-[10px]',
        'peer-not-placeholder-shown:top-2.5 peer-not-placeholder-shown:translate-y-0 peer-not-placeholder-shown:text-[10px]',
        'peer-aria-invalid:top-2.5 peer-aria-invalid:translate-y-0 peer-aria-invalid:text-[10px]',
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
                {{ $attributes->class([$base, $placeholder, 'min-h-[7rem] resize-y pr-3.5'])->merge($defaults) }}>{{ $current }}</textarea>
        @elseif ($type === 'select')
            {{-- options: ['value' => 'Label'] --}}
            <select id="{{ $fieldId }}" name="{{ $name }}"
                {{ $attributes->except('placeholder')->class([$base, $optionBg, 'h-14 appearance-none pr-10'])->merge($defaults) }}>
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
            {{-- text, email, password, number, tel, url, datetime-local (+ date, time) --}}
            <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}"
                @if ($type !== 'password') value="{{ $current }}" @endif
                {{ $attributes->class([$base, $isDateLike ? '' : $placeholder, 'h-14', $type === 'password' ? 'pr-11' : 'pr-3.5'])->merge($defaults) }}>

            @if ($prefix)
                <span class="{{ $muted }} pointer-events-none absolute bottom-1.5 left-3.5 top-5 items-center text-base sm:text-sm {{ $raised ? 'flex' : 'hidden peer-focus:flex peer-not-placeholder-shown:flex peer-aria-invalid:flex' }}">{{ $prefix }}</span>
            @endif
        @endif

        <label for="{{ $fieldId }}" class="{{ $labelClasses }}">
            {{ $label }}@if ($required)<span class="ml-0.5 text-(--red-500)">*</span>@endif
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

    {{-- Error message: Laravel errors show on load, JS fills this on blur --}}
    <p data-error-for="{{ $name }}" class="{{ $invalid ? '' : 'hidden' }} mt-1 text-xs text-(--red-500)">{{ $errors->first($name) }}</p>

    @if ($hint)
        <p class="{{ $muted }} mt-1 text-xs">{{ $hint }}</p>
    @endif

    {{ $slot }}
</div>