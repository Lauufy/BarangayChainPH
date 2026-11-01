{{-- Reusable checkbox. Put the label text (and any links) between the tags --}}
@props(['name', 'value' => '1'])

<div>
    <label class="flex cursor-pointer items-start gap-2.5">
        <span class="relative mt-0.5 flex h-4 w-4 shrink-0">
            <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="{{ $value }}"
                @checked(old($name))
                {{ $attributes->class(['peer h-4 w-4 cursor-pointer appearance-none rounded-sm border-1 border-black bg-[#ffff] transition checked:border-emerald-600 checked:bg-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400']) }}>

            {{-- Check mark --}}
            <svg class="pointer-events-none absolute inset-0 m-auto h-3 w-3 text-white opacity-0 transition peer-checked:opacity-100"
                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 8.5l3 3 6-6.5" />
            </svg>
        </span>

        <span class="text-sm leading-5 text-[#000135]">{{ $slot }}</span>
    </label>

    {{-- Error message for this checkbox --}}
    @error($name)
        <p class="mt-2 text-xs font-medium text-red-400">{{ $message }}</p>
    @enderror
</div>