@props([
    'name' => 'password',
    'label' => 'Password',
    'autocomplete' => 'current-password',
    'required' => true,
])

<div class="space-y-2">
    <label
        for="{{ $name }}"
        class="block text-sm font-medium text-slate-200"
    >
        {{ $label }}
    </label>

    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="password"
            autocomplete="{{ $autocomplete }}"
            @required($required)
            aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            @class([
                'w-full rounded-xl border bg-[#071326] px-4 py-3 pr-12 text-sm text-white',
                'border-red-500 focus:border-red-500' => $errors->has($name),
                'border-slate-700 focus:border-emerald-500' => !$errors->has($name),
                'placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20',
            ])
        >

        <button
            type="button"
            onclick="togglePassword('{{ $name }}', this)"
            class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 transition hover:text-emerald-400"
            aria-label="Show password"
        >
            <svg
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.7"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z"
                />
                <circle cx="12" cy="12" r="3" />
            </svg>
        </button>
    </div>

    @error($name)
        <p class="text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>