@props(['inputId' => 'password'])

<div id="{{ $inputId }}Requirements"
    data-password-strength-container
    data-password-target="{{ $inputId }}"
    class="mt-2">

    {{-- Password Strength Bar --}}
    <div class="h-1 w-full overflow-hidden rounded-full bg-slate-700">
        <div id="{{ $inputId }}StrengthBar"
            data-password-strength-bar
            class="h-full rounded-full transition-all duration-300 ease-in-out"
            style="width: 0%; background-color: transparent;">
        </div>
    </div>

    {{-- Password Requirements and Strength Label --}}
    <div class="mb-1 flex items-center justify-between gap-3">
        <p id="{{ $inputId }}RequirementText"
            data-password-strength-copy
            class="text-[10px] leading-relaxed text-slate-100">
            Use 8-16 characters with uppercase, lowercase, a number, and a symbol.
        </p>

        <span id="{{ $inputId }}StrengthText"
            data-password-strength-label
            class="shrink-0 text-[11px] font-semibold text-slate-400">
            —
        </span>
    </div>

    <ul class="grid grid-cols-2 gap-x-3 gap-y-1 text-[10px] text-slate-400" aria-label="Password requirements">
        <li data-password-requirement="length">8-16 characters</li>
        <li data-password-requirement="upper">Uppercase letter</li>
        <li data-password-requirement="lower">Lowercase letter</li>
        <li data-password-requirement="number">Number</li>
        <li data-password-requirement="special">Symbol</li>
    </ul>
</div>
