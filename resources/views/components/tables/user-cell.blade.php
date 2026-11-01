{{-- Profile column, only for user/person tables. Other tables just use a plain <td>. --}}
@props(['name', 'sub' => null, 'avatar' => null])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

<div class="flex items-center gap-3">
    @if ($avatar)
        <img src="{{ $avatar }}" alt="{{ $name }}" class="size-9 shrink-0 rounded-full border border-slate-200 object-cover">
    @else
        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#17325c]/10 text-xs font-semibold text-[#17325c]">{{ $initials }}</span>
    @endif
    <div class="min-w-0">
        <p class="truncate font-medium text-slate-900">{{ $name }}</p>
        @if ($sub)
            <p class="truncate text-xs text-slate-500">{{ $sub }}</p>
        @endif
    </div>
</div>