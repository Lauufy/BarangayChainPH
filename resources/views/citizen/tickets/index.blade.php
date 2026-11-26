@extends('layouts.help')
@section('title', 'My support tickets')
@section('help-content')
    <div class="mx-auto max-w-3xl">
        <section id="my-tickets" class="rounded-xl border border-slate-200 bg-(--off-white)/95 p-5 dark:border-slate-700 dark:bg-(--blue-850)/95">
            <h2 class="text-lg font-semibold">Your help tickets</h2>
            <ul class="mt-2 divide-y divide-slate-200 dark:divide-slate-700">
                @forelse ($tickets as $ticket)
                    <li>
                        <a href="{{ route('citizen.help.show', $ticket) }}" class="block py-3 hover:underline">
                            <span class="font-semibold">Help ticket #{{ $ticket->id }}</span>
                            <span class="ml-2 text-xs capitalize">{{ $ticket->status }}</span>
                            <span class="block truncate text-sm">{{ $ticket->latestMessage?->body ?: ($ticket->latestMessage?->imageContentType() ? 'Photo' : $ticket->latestMessage?->attachment_name) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="py-3 text-sm">You have no help tickets yet.</li>
                @endforelse
            </ul>
            {{ $tickets->links() }}
        </section>
        <a class="mt-4 inline-block font-semibold underline" href="{{ route('citizen.help.create') }}">Open a support ticket</a>
    </div>
@endsection
