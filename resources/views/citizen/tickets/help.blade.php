@extends('layouts.citizen')

@section('title', 'Help Center')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <header>
            <h1 class="text-3xl font-bold">Help Center</h1>
            <p class="mt-1 text-sm">Contact the barangay directly about a question or concern.</p>
        </header>

        <form method="POST" enctype="multipart/form-data" action="{{ route('citizen.help.store') }}"
            class="space-y-4 rounded-lg border border-slate-200 p-5 dark:border-slate-700">
            @csrf
            <h2 class="text-lg font-semibold">Open a ticket</h2>
            <label class="block">
                <span class="text-sm font-medium">Message</span>
                <textarea name="body" maxlength="1000" rows="3" placeholder="Write a message or attach a file"
                    class="mt-1 block w-full rounded-md border bg-transparent p-2">{{ old('body') }}</textarea>
            </label>
            <x-tickets.attachment-input />
            <x-feedbacks.form-errors />
            <button class="rounded-md bg-(--blue-600) px-4 py-2 text-sm font-semibold text-white">Send ticket</button>
        </form>

        <section>
            <h2 class="text-lg font-semibold">Your help tickets</h2>
            <ul class="mt-2 divide-y divide-slate-200 dark:divide-slate-700">
                @forelse ($tickets as $ticket)
                    <li>
                        <a href="{{ route('citizen.help.show', $ticket) }}" class="block py-3 hover:underline">
                            <span class="font-semibold">Help ticket #{{ $ticket->id }}</span>
                            <span class="ml-2 text-xs capitalize">{{ $ticket->status }}</span>
                            <span class="block truncate text-sm">{{ $ticket->latestMessage?->body ?: $ticket->latestMessage?->attachment_name }}</span>
                        </a>
                    </li>
                @empty
                    <li class="py-3 text-sm">You have no help tickets yet.</li>
                @endforelse
            </ul>
            {{ $tickets->links() }}
        </section>
    </div>
@endsection
