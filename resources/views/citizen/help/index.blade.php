@extends('layouts.help')

@section('title', 'Help Center')

@section('help-intro')
    <h1 class="text-balance text-4xl font-bold tracking-tight sm:text-5xl">Need some help?</h1>
    <search id="help-search" class="relative mx-auto mt-6 block max-w-lg">
        <x-lucide-search aria-hidden="true"
            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-600" />
        <input name="q" value="{{ $search }}" aria-label="Search help articles" type="search"
            placeholder="Search for articles..."
            class="text-(--blue-850) w-full rounded-md bg-white py-3 pl-10 pr-4 text-sm placeholder:text-slate-600">
    </search>
    <div class="mt-4 flex flex-wrap items-center justify-center gap-3 text-xs text-white/90">
        <span>Popular topics</span>
        @foreach (['Clearance', 'Residency', 'QR'] as $topic)
            <a href="{{ route('citizen.help.index', ['q' => $topic]) }}" data-help-topic="{{ $topic }}"
                class="hover:text-(--celadon-500) underline underline-offset-4">{{ $topic }}</a>
        @endforeach
    </div>
@endsection

@section('help-content')
    <div class="mx-auto max-w-4xl space-y-10 sm:space-y-14">
        <section id="help-categories" aria-label="Browse by category">
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($categories as $category)
                    <a href="{{ route('citizen.help.category', $category) }}"
                        class="dark:bg-(--blue-850) group flex flex-col rounded-lg border border-slate-200 bg-white p-6 hover:border-slate-400 focus-visible:outline-2 dark:border-slate-700 dark:hover:border-slate-500">
                        <span class="flex items-center gap-3">
                            <span class="bg-current/5 grid size-8 place-items-center rounded-md">
                                <x-lucide-file-text aria-hidden="true" class="size-4" />
                            </span>
                            <span class="text-lg font-bold">{{ $category->name }}</span>
                        </span>
                        <span
                            class="mt-7 flex-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ match ($category->slug) {
                                'permits-clearances' => 'Apply for a clearance, track a request, or prepare for renewal.',
                                'certificates' => 'Find the right certificate and learn how to request, save, or correct it.',
                                'verification-qr' => 'Scan a QR code, check a permit number, and understand your verification result.',
                                'account-identity' => 'Set up your account, verify your identity, and manage your profile.',
                                default => $category->articles->first()?->summary,
                            } }}</span>
                        <span class="mt-5 flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1 text-sm font-semibold group-hover:underline">Explore
                                articles <x-lucide-arrow-up-right aria-hidden="true" class="size-4" /></span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">
                                {{ $category->articles->count() }}
                                {{ Str::plural('article', $category->articles->count()) }}
                            </span>
                        </span>
                    </a>
                @empty
                    <p class="text-sm">No help categories yet.</p>
                @endforelse
            </div>
        </section>

        <section id="help-popular">
            <h2 class="border-b border-slate-200 pb-4 text-2xl font-bold dark:border-slate-700">Quick links</h2>
            <div class="divide-y divide-slate-200 border-b border-slate-200 dark:divide-slate-700 dark:border-slate-700">
                @foreach ($popular as $article)
                    <details name="help-quick-links" class="group/quick">
                        <summary
                            class="flex cursor-pointer list-none items-center gap-4 px-2 py-5 text-sm font-semibold hover:underline focus-visible:outline-2 [&::-webkit-details-marker]:hidden">
                            <span aria-hidden="true" class="size-1.5 shrink-0 rounded-full bg-current"></span>
                            <span class="flex-1">{{ $article->title }}</span>
                            <x-lucide-plus aria-hidden="true" class="size-4 shrink-0 group-open/quick:hidden" />
                            <x-lucide-minus aria-hidden="true" class="hidden size-4 shrink-0 group-open/quick:block" />
                        </summary>
                        <div class="pl-7.5 pb-6 pr-8 text-slate-700 dark:text-slate-300">
                            @include('citizen.help.partials.article-body')
                        </div>
                    </details>
                @endforeach
            </div>
        </section>

        <section id="help-results" hidden
            class="bg-(--off-white)/95 dark:bg-(--blue-850)/95 rounded-xl border border-slate-200 p-5 dark:border-slate-700">
            <h2 aria-live="polite" class="text-sm font-semibold">Search results</h2>
            <div class="mt-4 grid items-start gap-3 sm:grid-cols-2">
                @foreach ($categories as $category)
                    <div class="contents" data-category="{{ $category->name }}">
                        @foreach ($category->articles as $article)
                            @include('citizen.help.partials.article-row')
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        <nav aria-label="Support tickets" class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('citizen.help.create') }}"
                class="bg-(--blue-700) text-(--off-white) hover:bg-(--blue-800) focus-visible:outline-(--blue-500) dark:bg-(--blue-600) dark:hover:bg-(--blue-500) rounded-md border border-transparent px-4 py-2.5 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2">Open
                a support ticket</a>
            <a href="{{ route('citizen.help.tickets') }}"
                class="bg-(--off-white) text-(--blue-700) hover:bg-(--blue-50) focus-visible:outline-(--blue-500) dark:bg-(--blue-850) dark:text-(--off-white) dark:hover:bg-(--blue-900) rounded-md border border-current px-4 py-2.5 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2">View
                my tickets</a>
        </nav>
    </div>
@endsection
