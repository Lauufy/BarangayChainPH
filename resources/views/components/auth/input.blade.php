@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'autocomplete' => null,
    'required' => true,
])

<div class="space-y-2">
    <label
        for="{{ $name }}"
        class="block text-sm font-medium text-slate-200"
    >
        {{ $label }}
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name) }}"
        placeholder="{{ $placeholder }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @required($required)
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        @class([
            'w-full rounded-xl border bg-[#071326] px-4 py-3 text-sm text-white',
            'border-red-500 focus:border-red-500' => $errors->has($name),
            'border-slate-700 focus:border-emerald-500' => !$errors->has($name),
            'placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20',
        ])
    >

    @error($name)
        <p class="text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>