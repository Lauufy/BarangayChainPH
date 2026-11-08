@extends('layouts.citizen')

@section('title', $service['title'])

@section('content')
    @php

        // Styles (theme tokens, follow the light / dark toggle). The step files use these too.
        $card =
            'rounded-lg bg-(--white) p-4 shadow-sm ring-1 ring-(--gray-200) dark:bg-(--gray-800) dark:ring-(--gray-700)';
        $h3 = 'text-base font-bold text-(--blue-900) dark:text-(--blue-300)';
        $sub = 'text-sm text-(--gray-600) dark:text-(--gray-300)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
        $line = 'border-(--gray-200) dark:border-(--gray-700)';
        $grid = 'mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3';
        $span = 'sm:col-span-2 lg:col-span-3';
        $btn =
            'inline-flex items-center justify-center rounded-md px-5 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 sm:py-2';
        $head = fn($title, $text) => new \Illuminate\Support\HtmlString(
            '<div><h2 class="text-(--blue-900) dark:text-(--blue-300) text-lg font-bold">' .
                e($title) .
                '</h2><p class="' .
                $sub .
                '">' .
                e($text) .
                '</p></div>',
        );

        // Profile cards without the hidden labels, and the values shown on the preview
        $hide = ['privacy policy version', 'consent ip'];
        $profile = collect($sections)->map(
            fn($items) => collect((array) $items)->reject(fn($v, $l) => in_array(strtolower($l), $hide)),
        );
        $flat = $profile->flatMap(fn($items) => $items->mapWithKeys(fn($v, $l) => [strtolower($l) => $v]));
        $pick = fn($word) => $flat->first(fn($v, $l) => str_contains($l, $word));
        $age =
            $flat->get('age') ??
            (($born = $pick('birth')) ? rescue(fn() => \Carbon\Carbon::parse($born)->age, null, false) : null);
        $purpose =
            collect($fields)->first(fn($f) => str_contains(strtolower($f['key'] . ' ' . $f['label']), 'purpose'))[
                'key'
            ] ?? null;

        // Steps: 1 Applicant, 2 Details, 3 Documents, 4 Preview, 5 Review
        $given = array_values($steps);
        $stepList = [$given[0], $given[1], $given[2], 'Preview', $given[3] ?? 'Review'];
    @endphp

    <div class="w-full space-y-4 pb-20 sm:pb-0">

        {{-- Header + actions (mobile: fixed bottom bar) --}}
        <header class="{{ $line }} space-y-2 border-b pb-4">
            <nav class="{{ $muted }} flex items-center gap-2 text-xs" aria-label="Breadcrumb">
                <a href="{{ route('citizen.services') }}"
                    class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Services</a>
                <span aria-hidden="true">›</span>
                <span class="text-(--blue-700) dark:text-(--blue-300) font-semibold">{{ $service['title'] }}</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-(--blue-900) dark:text-(--blue-300) text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ $service['title'] }}</h1>
                    <p class="{{ $sub }} mt-1">Confirm your verified details, complete the application, and review
                        it before submitting.</p>
                </div>

                <div
                    class="{{ $line }} bg-(--white) dark:bg-(--gray-900) fixed inset-x-0 bottom-0 z-30 flex items-center gap-2 border-t p-3 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] sm:static sm:z-auto sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none dark:sm:bg-transparent">
                    <button type="button" id="back-button"
                        class="{{ $btn }} border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) focus-visible:ring-(--blue-300) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) flex-1 border sm:flex-none">Back</button>

                    <div id="next-wrap" class="flex-1 sm:flex-none">
                        <button type="button" id="next-button"
                            class="{{ $btn }} bg-(--blue-600) text-(--white) hover:bg-(--blue-700) focus-visible:ring-(--blue-600) w-full sm:w-auto">Next
                            <span class="ml-1.5" aria-hidden="true">›</span></button>
                    </div>

                    <div id="submit-wrap" class="hidden flex-1 sm:flex-none">
                        <button type="submit" form="apply-form" id="submit-button"
                            class="{{ $btn }} bg-(--green-700) text-(--white) hover:bg-(--green-600) focus-visible:ring-(--green-500) w-full disabled:opacity-60 sm:w-auto">Submit</button>
                    </div>
                </div>
            </div>
        </header>

        <x-form.stepper id="apply-stepper" :steps="$stepList" :current="$startStep >= 4 ? 5 : $startStep" />

        @if ($errors->any())
            <div role="alert"
                class="border-(--red-600) bg-(--white) text-(--red-600) dark:bg-(--gray-800) rounded-md border p-3 text-sm">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                <p class="mt-2">For security, your browser clears chosen files. Please choose them again.</p>
            </div>
        @endif

        <form id="apply-form" method="POST" action="{{ route('citizen.' . $slug . '.store') }}"
            enctype="multipart/form-data" novalidate>
            @csrf

            @include('citizen.service-requests.app-info')
            @include('citizen.service-requests.app-details')
            @include('citizen.service-requests.documents')
            @include('citizen.service-requests.preview')
            @include('citizen.service-requests.review')
        </form>
    </div>

    <script>
        (() => {
            const $ = (id) => document.getElementById(id);
            const all = (sel) => document.querySelectorAll(sel);
            const form = $('apply-form');
            const LAST = 5;
            const MAX = {{ $maxMb }} * 1024 * 1024;
            const PURPOSE = @json($purpose);
            let step = 1;

            // Value of an application field ("Others" uses the description box)
            const answer = (key) => {
                const field = $('f-' + key);
                return field.dataset.other && field.value === 'Others' ? $('f-' + key + '_other').value : field
                    .value;
            };

            // Show "Please describe" only when "Others" is picked
            all('[data-other]').forEach((select) => {
                const sync = () => {
                    const on = select.value === 'Others';
                    $('other-' + select.dataset.other).classList.toggle('hidden', !on);
                    $('f-' + select.dataset.other + '_other').required = on;
                };
                select.addEventListener('change', sync);
                sync();
            });

            // Check file type and size when a file is chosen
            all('[data-req]').forEach((input) => input.addEventListener('change', () => {
                const file = input.files[0];
                const message = !file ? '' :
                    !/^(image\/(jpeg|png)|application\/pdf)$/.test(file.type) ?
                    'Only JPG, PNG, or PDF files are allowed.' :
                    file.size > MAX ? 'The file must not be larger than {{ $maxMb }} MB.' : '';
                input.closest('[data-req-wrap]').querySelector('[data-req-error]').textContent = message;
                if (message) input.value = '';
            }));

            function goTo(next) {
                step = next;
                all('[data-panel]').forEach((p) => p.classList.toggle('hidden', Number(p.dataset.panel) !== step));
                Stepper.set('apply-stepper', step);
                $('next-wrap').classList.toggle('hidden', step === LAST);
                $('submit-wrap').classList.toggle('hidden', step !== LAST);

                if (step === 4) $('preview-purpose').textContent = (PURPOSE && answer(PURPOSE)) || '—';
                if (step === LAST) {
                    all('[data-review]').forEach((el) => el.value = answer(el.dataset.review));
                    all('[data-review-file]').forEach((el) => el.value = $('req-' + el.dataset.reviewFile).files[0]
                        ?.name || 'No file');
                }
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // Next: only if every field on this step is valid
            $('next-button').addEventListener('click', () => {
                const fields = all(`[data-panel="${step}"] :is(input, select, textarea)`);
                if ([...fields].every((f) => f.reportValidity())) goTo(step + 1);
            });

            $('back-button').addEventListener('click', () => step === 1 ? location.href = @json(route('citizen.services')) :
                goTo(step - 1));

            // Enter goes to the next step instead of submitting
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT' && step < LAST) {
                    e.preventDefault();
                    $('next-button').click();
                }
            });

            // Submit only on the last step; jump back to any invalid hidden field
            form.addEventListener('submit', (e) => {
                const invalid = form.querySelector(':invalid');
                if (step === LAST && !invalid) return void($('submit-button').disabled = true);

                e.preventDefault();
                if (invalid) {
                    goTo(Number(invalid.closest('[data-panel]').dataset.panel));
                    invalid.reportValidity();
                }
            });

            goTo({{ $startStep >= 4 ? 5 : $startStep }});
        })();
    </script>
@endsection
