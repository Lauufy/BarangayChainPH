<dialog id="correction-dialog" aria-labelledby="correction-title"
    class="m-auto w-[calc(100%-2rem)] max-w-xl overflow-hidden rounded-lg bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-sm dark:bg-slate-900 dark:text-slate-100">
    <form method="POST" action="{{ route('admin.permits.resubmission', $application) }}" class="flex max-h-[90dvh] flex-col">
        @csrf
        <header class="border-b border-slate-200 px-4 py-3 sm:px-5 dark:border-slate-700">
            <h2 id="correction-title" class="text-lg font-semibold" tabindex="-1" autofocus>Request Resubmission</h2>
        </header>
        <div class="min-h-0 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
            @if ($errors->hasAny(['flags', 'flags.*', 'notes.*', 'message']))
                <x-feedbacks.form-errors />
            @endif
            <fieldset>
                <legend class="text-sm font-semibold">Which items need correction?</legend>
                <p class="{{ $hintClass }} mb-2">Select each item and tell the citizen what to change.</p>
                @foreach ($correctionFields as $key => $field)
                    <div class="border-b border-slate-200 py-2 dark:border-slate-700">
                        <label class="flex cursor-pointer items-center gap-2 py-1 text-sm">
                            <input type="checkbox" name="flags[]" value="{{ $key }}" aria-controls="note-{{ $key }}"
                                @checked(in_array($key, (array) old('flags', []), true))>
                            {{ $field['label'] }}
                        </label>
                        <div data-note>
                            <label for="note-{{ $key }}" class="text-xs font-semibold">Correction note for {{ $field['label'] }}</label>
                            <textarea id="note-{{ $key }}" name="notes[{{ $key }}]" rows="2" maxlength="500"
                                class="mt-1 w-full rounded-md border border-slate-300 p-2 text-sm dark:bg-slate-800">{{ old("notes.$key") }}</textarea>
                        </div>
                    </div>
                @endforeach
            </fieldset>
            <label class="block text-sm font-semibold">General message (optional)
                <textarea name="message" rows="2" maxlength="1000" class="mt-1 w-full rounded-md border border-slate-300 p-2 font-normal dark:bg-slate-800">{{ old('message') }}</textarea>
            </label>
        </div>
        <footer class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-4 py-3 sm:px-5 dark:border-slate-700">
            <x-form.button type="submit" disabled loadingText="Sending correction request..." class="w-full">Send correction request</x-form.button>
            <button type="button" data-close-dialog class="w-full rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200">Cancel</button>
        </footer>
    </form>
</dialog>
