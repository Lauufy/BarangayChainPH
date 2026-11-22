@extends('layouts.admin')

@section('title', 'Website Inquiry')

@section('content')
    <div class="space-y-5">
        <a href="{{ route('admin.tickets.index', ['communications_tab' => 'inquiries']) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">
            <span aria-hidden="true">←</span> Back to Communications Management
        </a>

        <header>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ $contactMessage->service }}
            </p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $contactMessage->subject }}</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                From {{ $contactMessage->first_name }} {{ $contactMessage->last_name }}
                · <a class="underline underline-offset-2" href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a>
                · {{ $contactMessage->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
            </p>
        </header>

        <section class="rounded-md border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Original message</h2>
            <p class="mt-3 whitespace-pre-line wrap-break-word text-sm leading-6 text-slate-700 dark:text-slate-300">
                {{ $contactMessage->body }}
            </p>
        </section>

        <section class="space-y-3" aria-label="Replies">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Replies</h2>
            @forelse ($contactMessage->replies as $reply)
                <article class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ $reply->user?->name ?? 'Former staff member' }}
                        </p>
                        <time class="text-xs text-slate-500 dark:text-slate-400"
                            datetime="{{ $reply->created_at->toIso8601String() }}">
                            Emailed {{ $reply->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </time>
                    </div>
                    <p class="mt-2 whitespace-pre-line wrap-break-word text-sm leading-6 text-slate-700 dark:text-slate-300">
                        {{ $reply->body }}
                    </p>
                </article>
            @empty
                <p class="rounded-md border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    No replies have been sent.
                </p>
            @endforelse
        </section>

        <form method="POST" action="{{ route('admin.contact-messages.reply', $contactMessage) }}"
            class="space-y-3 rounded-md border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            @csrf
            <div>
                <label for="body" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                    Reply by email to {{ $contactMessage->email }}
                </label>
                <textarea id="body" name="body" rows="5" maxlength="5000" required
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    placeholder="Write your reply...">{{ old('body') }}</textarea>
                @error('body')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <x-feedbacks.form-errors />

            <button type="submit"
                class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Send reply by email
            </button>
        </form>
    </div>
@endsection
