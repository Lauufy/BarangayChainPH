@extends('layouts.help')

@section('title', $category->name)

@section('help-content')
    <div class="mx-auto max-w-3xl space-y-6 rounded-xl bg-(--off-white)/95 p-5 sm:p-8 dark:bg-(--blue-850)/95">
        <h2 class="text-xl font-bold">Articles ({{ $articles->count() }})</h2>
        <div class="space-y-3">
            @forelse ($articles as $article)
                @include('citizen.help.partials.article-row', ['article' => $article])
            @empty
                <p class="text-sm text-slate-500">No articles in this category yet.</p>
            @endforelse
        </div>
    </div>
@endsection