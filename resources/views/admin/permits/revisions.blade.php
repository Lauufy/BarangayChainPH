@if ($application->revisions->isNotEmpty())
    <section class="{{ $cardClass }} space-y-4">
        <h2 class="{{ $titleClass }}">Resubmission history</h2>
        @foreach ($application->revisions as $revision)
            <article class="border-t border-slate-200 pt-4 dark:border-slate-700">
                <h3 class="font-semibold">{{ $revision->submitted_at ? 'Corrections submitted' : 'Awaiting corrections' }}</h3>
                <p class="{{ $hintClass }}">Requested by {{ $revision->reviewer_name }} on {{ $revision->created_at->format('M j, Y g:i A') }}</p>
                @if ($revision->submitted_at)
                    <p class="{{ $hintClass }}">Submitted by {{ $application->user->name }} on {{ $revision->submitted_at->format('M j, Y g:i A') }}</p>
                @endif
                @if ($revision->message)<p class="mt-2 whitespace-pre-line text-sm">{{ $revision->message }}</p>@endif
                <dl class="mt-3 divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach ($revision->items as $item)
                        <div class="py-3 text-sm">
                            <dt class="font-semibold">{{ $item['label'] }}</dt>
                            <dd class="mb-2 whitespace-pre-line">{{ $item['note'] }}</dd>
                            <dd class="grid gap-2 sm:grid-cols-2">
                                <div class="break-words rounded bg-slate-100 p-2 dark:bg-slate-800"><span class="block text-xs font-semibold">Previous</span>{{ $item['old'] ?? 'Not provided' }}
                                    @isset($item['old_hash'])<small class="block">File fingerprint {{ substr($item['old_hash'], 0, 12) }}</small>@endisset
                                </div>
                                @if ($revision->submitted_at)
                                    <div class="break-words rounded bg-emerald-50 p-2 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><span class="block text-xs font-semibold">Resubmitted</span>{{ $item['new'] ?? 'Not provided' }}
                                        @isset($item['new_hash'])<small class="block">File fingerprint {{ substr($item['new_hash'], 0, 12) }}</small>@endisset
                                    </div>
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </article>
        @endforeach
    </section>
@endif
