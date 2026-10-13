@props(['title', 'text' => null])

<header class="border-b border-slate-200 pb-5">
    <h1 class="text-2xl font-bold text-[#17325c] sm:text-3xl">{{ $title }}</h1>
    @if ($text)
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $text }}</p>
    @endif
</header>