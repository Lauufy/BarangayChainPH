@props(['inputId' => 'password'])

<div id="{{ $inputId }}Requirements"
    data-password-strength-container
    data-password-target="{{ $inputId }}"
    class="mt-2 hidden">

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
            At least 8 characters with letters and numbers
        </p>

        <span id="{{ $inputId }}StrengthText"
            data-password-strength-label
            class="shrink-0 text-[11px] font-semibold text-slate-400">
            —
        </span>
    </div>
</div>
