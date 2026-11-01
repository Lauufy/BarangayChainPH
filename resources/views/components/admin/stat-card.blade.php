@props(['label', 'value' => '—', 'note' => null])

<div class="rounded-xl border border-l-4 border-slate-200 border-l-[#17325c] bg-white p-5 shadow-sm">
    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $value }}</p>

    @if ($note)
        <p class="mt-2 text-xs text-slate-500">{{ $note }}</p>
    @endif
</div>