@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'options' => [],
    'hint' => null,
    'raised' => false,
    // Shared look for input, textarea and select
    'fieldClass' => 'peer validation-input block w-full rounded-md border border-white/10 bg-[#0B2347] pb-1.5 pl-3.5 pt-5 text-base text-white outline-none transition hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10 disabled:cursor-not-allowed disabled:opacity-60 sm:text-sm',
])

<div>
    <div class="relative">

        {{-- Textarea --}}
        @if ($type === 'textarea')
            <textarea id="{{ $name }}" name="{{ $name }}"
                {{ $attributes->class([
                    $fieldClass,
                    'min-h-[7rem] resize-y pr-3.5',
                    'placeholder:text-transparent focus:placeholder:text-blue-100/40' => !$raised,
                    'placeholder:text-blue-100/40' => $raised,
                ]) }}>{{ old($name, $value) }}</textarea>

        {{-- Select (pass options as ['value' => 'Label']) --}}
        @elseif ($type === 'select')
            <select id="{{ $name }}" name="{{ $name }}"
                {{ $attributes->except('placeholder')->class([$fieldClass, 'h-14 appearance-none pr-10 [&>option]:bg-[#0B2347]']) }}>
                <option value="">{{ $attributes->get('placeholder', 'Select an option') }}</option>

                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>

            {{-- Dropdown arrow --}}
            <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-100/50"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>

        {{-- Every other type (text, email, tel, number, password, date...) --}}
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
                @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
                {{ $attributes->class([
                    $fieldClass,
                    'h-14',
                    'pr-11' => $type === 'password',
                    'pr-3.5' => $type !== 'password',
                    'placeholder:text-transparent focus:placeholder:text-blue-100/40' => !$raised,
                    'placeholder:text-blue-100/40' => $raised,
                ]) }}>
        @endif

        {{-- Floating label: centered at rest, slides to the top on focus or when filled --}}
        <label for="{{ $name }}"
            class="pointer-events-none absolute left-3.5 text-xs font-semibold uppercase tracking-wider text-blue-100/60 transition-all duration-200
            {{ $raised ? 'top-2.5 text-[10px]' : ($type === 'textarea' ? 'top-4' : 'top-1/2 -translate-y-1/2') }}
            peer-focus:top-2.5 peer-focus:translate-y-0 peer-focus:text-[10px] peer-focus:text-emerald-400
            peer-not-placeholder-shown:top-2.5 peer-not-placeholder-shown:translate-y-0 peer-not-placeholder-shown:text-[10px]">
            {{ $label }}
        </label>

        {{-- Show / hide button: only for password fields --}}
        @if ($type === 'password')
            <button type="button" data-password-toggle="{{ $name }}" aria-label="Show password"
                class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-3.5 text-blue-100/50 transition hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
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

    {{-- helper text --}}
    @if ($hint)
        <p class="mt-1 text-xs text-blue-100/50">{{ $hint }}</p>
    @endif

    {{-- extra content under the field (e.g. password rules) --}}
    {{ $slot }}
</div>