@props(['action'])
<form data-ticket-composer method="POST" enctype="multipart/form-data" action="{{ $action }}" class="space-y-2">
    @csrf
    <x-tickets.outgoing />
    <div class="flex items-end gap-2">
        <div class="flex min-w-0 flex-1 items-end rounded-xl bg-slate-100 px-3 dark:bg-slate-800">
            <textarea name="body" data-enter-send aria-label="Message" rows="1" maxlength="1000" placeholder="Type your message..."
                class="min-h-11 min-w-0 flex-1 resize-y bg-transparent py-3 text-sm">{{ old('body') }}</textarea>
            <label class="relative grid size-11 shrink-0 cursor-pointer place-items-center rounded-md focus-within:outline-2" title="Attach an image or document">
                <x-lucide-paperclip aria-hidden="true" class="size-5" />
                <input type="file" name="attachment" aria-label="Attach an image or document" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt"
                    class="absolute inset-0 w-full cursor-pointer opacity-0">
            </label>
        </div>
        <button type="submit" aria-label="Send message" class="grid size-11 shrink-0 place-items-center rounded-lg bg-(--blue-600) text-white disabled:opacity-50">
            <x-lucide-send aria-hidden="true" class="size-5" />
        </button>
    </div>
    {{-- Only visible when your script puts a selected file name here --}}
    <p class="text-xs text-slate-500 empty:hidden dark:text-slate-400"><span data-selection></span></p>
    <x-feedbacks.form-errors />
</form>

@once
    <script>
        document.addEventListener('keydown', (e) => {
            const field = e.target.closest?.('textarea[data-enter-send]');
            if (!field) return;

            // Enter sends; Shift+Enter adds a new line; ignore Enter while typing with an IME
            if (e.key !== 'Enter' || e.shiftKey || e.isComposing) return;
            e.preventDefault();

            const form = field.form;
            const submit = form.querySelector('[type="submit"]');
            const hasFile = form.querySelector('input[type="file"]')?.files.length > 0;

            if (submit?.disabled) return; // already sending
            if (!field.value.trim() && !hasFile) return; // nothing to send

            form.requestSubmit(submit); // fires the normal submit event, so your composer JS still runs
        });
    </script>
@endonce