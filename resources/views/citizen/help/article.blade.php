@extends('layouts.help')

@section('title', $article->title)

@section('help-content')
    <div class="mx-auto max-w-3xl space-y-6 rounded-xl bg-(--off-white)/95 p-5 sm:p-8 dark:bg-(--blue-850)/95">
        <nav class="text-sm text-slate-500">
            <a href="{{ route('citizen.help.index') }}" class="hover:underline">Help Center</a>
            <span class="mx-1">/</span>
            <a href="{{ route('citizen.help.category', $article->category) }}" class="hover:underline">{{ $article->category->name }}</a>
        </nav>

        <article>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $article->summary }}</p>

            @include('citizen.help.partials.article-body')
        </article>

        <div class="rounded-xl border border-slate-200 p-4 text-sm dark:border-slate-700">
            Didn't find what you need?
            <a href="{{ route('citizen.help.create') }}" class="font-semibold underline">Open a ticket</a>
            and the barangay will reply.
        </div>

        @if ($related->isNotEmpty())
            <section>
                <h2 class="text-sm font-semibold">Related articles</h2>
                <div class="mt-3 space-y-3">
                    @foreach ($related as $item)
                        @include('citizen.help.partials.article-row', ['article' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection