{{-- Sidebar link. Use: <x-admin.nav-link href="/admin/users" match="admin/users*"> + icon slot + label --}}
@props(['href', 'match'])

<a href="{{ url($href) }}"
    class="{{ request()->is($match) ? 'bg-[#0B2347] text-emerald-400' : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition">

    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        {{ $icon }}
    </svg>

    <span>{{ $slot }}</span>
</a>