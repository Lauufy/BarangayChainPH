<div class="space-y-3">

    @if ($slug !== 'certificate-of-residency')
        <iframe id="certificate-preview" title="Draft certificate preview" class="h-[70vh] w-full border bg-white"
            srcdoc="{{ view('permits.san-luis', [
                'type' => $service['type'], 'name' => $userName, 'age' => $age, 'address' => $address,
                'nationality' => $flat->get('nationality'), 'purposeText' => '[[purpose]]', 'details' => [],
            ])->render() }}"></iframe>
    @else
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
    @endif
</div>