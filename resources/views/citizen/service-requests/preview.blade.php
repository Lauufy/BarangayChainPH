{{-- 4: Preview (the paper stays white with dark text in both modes, like the printed document) --}}
<div data-panel="4" class="hidden space-y-3">
    {{ $head('Preview of your document', 'This is how your ' . strtolower($service['title']) . ' will look. Go back if you need to change anything.') }}

    <section class="{{ $card }}">
        <div class="mx-auto max-w-3xl rounded-md border border-(--gray-200) bg-(--white) p-6 text-(--gray-900) shadow-sm dark:border-(--gray-700) sm:p-10">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-wide">Republic of the Philippines</p>
                <p class="text-sm">Barangay San Luis, Antipolo City, Rizal</p>
                <h3 class="mt-8 text-xl font-bold uppercase tracking-wide sm:text-2xl">{{ $service['title'] }}</h3>
            </div>

            <div class="mt-10 space-y-6 text-sm leading-7 text-(--gray-700)">
                <p>To whom it may concern:</p>
                <p>
                    This is to certify that <strong>{{ $pick('full name') ?? ($pick('name') ?? '—') }}</strong>, {{ $age ?? '—' }} years old,
                    and a resident of <strong>{{ $pick('address') ?? '—' }}</strong>, is requesting a {{ $service['title'] }}
                    for the following purpose:
                </p>
                <p id="preview-purpose" class="min-h-20 border-b border-(--gray-400) pb-2 font-semibold"></p>
                <p>This certification is issued upon the request of the interested party for whatever legal purpose it may serve.</p>
            </div>

            <div class="mt-16 text-center text-sm">
                <p class="font-semibold">Barangay Official</p>
                <p class="mt-10 border-t border-(--gray-400) pt-2">Authorized signature</p>
            </div>
        </div>
    </section>
</div>