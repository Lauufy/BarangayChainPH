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
        $btn =
            'inline-flex items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 sm:py-2';
    @endphp

    <div class="w-full px-4 sm:px-6 pt-10 sm:pt-15 pb-6 rounded-md bg-(--white) ring-1 ring-(--gray-200) dark:bg-(--gray-800) dark:ring-(--gray-700)">
        @if ($errors->any())
            <div role="alert"
                class="border-(--red-600) bg-(--white) text-(--red-600) dark:bg-(--gray-800) rounded-md border p-3 text-sm">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                @if ($service['requirements'])
                    <p class="mt-2">For security, your browser clears chosen files. Please choose them again.</p>
                @endif
            </div>
        @endif

        {{-- Heading card (the only part with padding + background) --}}
        <div class="p-4  text-center ">
            <h1 class="text-(--blue-900) dark:text-(--blue-300) text-2xl font-bold uppercase tracking-wide sm:text-3xl">
                {{ $service['title'] }}
            </h1>
            <p class="{{ $sub }} mx-auto mt-2 max-w-3xl">
               Review your verified details, complete the required fields, and attach your documents before submitting.
            </p>
        </div>

        <div class="p-4">
            <x-form.stepper id="apply-stepper" :steps="$steps" :current="$startStep" :title="$service['title']" />
        </div>

        <form id="apply-form" data-validate data-validation-steps method="POST" action="{{ route('citizen.' . $slug . '.store') }}"
            enctype="multipart/form-data" novalidate>
            @csrf

            <div data-panel="1" class="space-y-4">
                @include('citizen.service-requests.app-info', ['prefix' => 'profile_'])
                @include('citizen.service-requests.app-details')
                @if ($service['requirements'])
                    @include('citizen.service-requests.documents')
                @endif
            </div>

            <section data-panel="2" class="hidden space-y-3" aria-labelledby="document-preview-heading">
                <h2 id="document-preview-heading" class="text-base font-bold text-(--blue-900) dark:text-(--blue-300)">
                    Preview your document</h2>
                @include('citizen.service-requests.preview')
            </section>

            <div data-panel="{{ count($steps) }}" class="hidden">
                @include('citizen.service-requests.review')
            </div>
        </form>

        {{-- Actions (mobile: fixed bottom bar). CHANGED: added id="actions-bar" so the script can hide it during the privacy notice --}}
        <div id="actions-bar" class="{{ $line }} fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t bg-(--white) p-3 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] dark:bg-(--gray-800) sm:static sm:z-auto sm:justify-end sm:border-0 sm:bg-transparent sm:p-0 sm:pt-2 sm:shadow-none dark:sm:bg-transparent">
            <button type="button" id="back-button"
                class="{{ $btn }} border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) focus-visible:ring-(--blue-300) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) flex-1 border sm:flex-none">Back</button>

            <div id="next-wrap" class="flex-2 sm:flex-none">
                <button type="button" id="next-button"
                    class="{{ $btn }} bg-(--blue-600) text-(--white) hover:bg-(--blue-700) focus-visible:ring-(--blue-600) w-full sm:w-auto sm:min-w-36">Next
                    <span class="ml-1.5" aria-hidden="true">›</span></button>
            </div>

            <div id="submit-wrap" class="hidden flex-2 sm:flex-none">
                <button type="submit" form="apply-form" id="submit-button"
                    class="{{ $btn }} bg-(--green-700) text-(--white) hover:bg-(--green-600) focus-visible:ring-(--green-500) w-full disabled:opacity-60 sm:w-auto sm:min-w-36">Submit</button>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-md border border-(--blue-200) bg-(--blue-50) px-4 py-3 text-sm text-(--gray-800) dark:border-(--blue-800) dark:bg-[color-mix(in_srgb,var(--blue-900)_40%,transparent)] dark:text-(--gray-200)">
        Need to correct your profile? Update it through your
        <a href="{{ route('citizen.profile') }}" class="font-semibold text-(--blue-700) underline hover:text-(--blue-800) dark:text-(--blue-300) dark:hover:text-(--blue-200)">verified profile page</a>
        before applying.
    </div>

    <script>
        (() => {
            const $ = (id) => document.getElementById(id);
            const all = (sel) => document.querySelectorAll(sel);
            const form = $('apply-form');
            const LAST = {{ count($steps) }};
            const purposeTemplate = @json($service['purpose_template']);
            let step = 1;

            // Privacy notice gate on the last step.
            // Stays accepted once accepted, including after a validation error (old value).
            const privacy = $('agree_privacy');
            const syncPrivacy = () => {
                const ok = privacy.value === '1';
                $('privacy-gate').classList.toggle('hidden', ok);
                $('review-content').classList.toggle('hidden', !ok);
                form.querySelector('[name="agree_truth"]').required = ok;
                $('submit-wrap').classList.toggle('hidden', !(step === LAST && ok));

                // CHANGED: the notice has its own Cancel, so hide the bottom bar until it is accepted
                $('actions-bar').hidden = !ok;
            };
            $('privacy-accept').addEventListener('click', () => {
                privacy.value = '1';
                syncPrivacy();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            $('privacy-cancel').addEventListener('click', () => goTo(LAST - 1));

            // Value of an application field ("Others" uses the description box)
            const answer = (key) => {
                const field = $('f-' + key);
                if (field.disabled) return '';
                if (field.type === 'tel' && field.value) return '+63' + field.value;
                return field.dataset.other && field.value === 'Others' ? $('f-' + key + '_other').value : field
                    .value;
            };

            // Show "Please describe" only when "Others" is picked
            all('[data-other]').forEach((select) => {
                const sync = () => {
                    const on = select.value === 'Others';
                    $('other-' + select.dataset.other).classList.toggle('hidden', !on);
                    const description = $('f-' + select.dataset.other + '_other');
                    description.required = on;
                    description.disabled = !on;
                };
                select.addEventListener('change', sync);
                sync();
            });

            all('[data-depends-on]').forEach((wrapper) => {
                const source = $('f-' + wrapper.dataset.dependsOn);
                const field = wrapper.querySelector('input');
                const sync = () => {
                    field.disabled = source.value !== wrapper.dataset.when;
                    wrapper.hidden = field.disabled;
                };
                source.addEventListener('change', sync);
                sync();
            });
            all('[data-required-for-renewal]').forEach((file) => {
                const source = $('f-application_type');
                const sync = () => file.required = source.value === 'Renewal';
                source.addEventListener('change', sync);
                sync();
            });

            const preview = $('certificate-preview');
            function updatePreview() {
                if (!preview) {
                    $('preview-purpose').textContent = (PURPOSE && answer(PURPOSE)) || '—';
                    return;
                }

                const page = preview.contentDocument;
                if (!page?.querySelector('main')) return;
                page.querySelectorAll('u, .line').forEach((element) => {
                    const key = element.dataset.field ?? element.textContent.match(/^\[\[(\w+)\]\]$/)?.[1];
                    if (!key) return;
                    element.dataset.field = key;
                    const value = key === 'other_purpose'
                        ? ($('f-purpose').value === 'Others' ? answer('purpose') : '')
                        : answer(key);
                    element.textContent = value || '________';
                });
                const business = $('f-application_type');
                const choice = business?.value ?? $('f-purpose')?.value;
                page.querySelectorAll('[data-choice]').forEach((mark) => {
                    const selected = choice === mark.dataset.choice;
                    mark.textContent = selected ? 'X' : '';
                });
            }
            preview?.addEventListener('load', updatePreview);

            function goTo(next) {
                step = next;
                all('[data-panel]').forEach((p) => p.classList.toggle('hidden', Number(p.dataset.panel) !== step));
                Stepper.set('apply-stepper', step);
                $('next-wrap').classList.toggle('hidden', step === LAST);

                // CHANGED: bar is visible on every step; on the last step syncPrivacy decides
                $('actions-bar').hidden = false;
                $('submit-wrap').classList.add('hidden');
                if (step === LAST) syncPrivacy();

                if (step === 2) updatePreview();
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
                const panel = form.querySelector(`[data-panel="${step}"]`);
                if (FormValidation.validatePanel(panel)) goTo(step + 1);
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

            // Submit only on the last step after the privacy notice is accepted;
            // jump back to any invalid hidden field
            form.addEventListener('submit', (e) => {
                if (step !== LAST || privacy.value !== '1') {
                    e.preventDefault();
                    return;
                }
                for (const panel of form.querySelectorAll('[data-panel]')) {
                    if (!FormValidation.validatePanel(panel)) {
                        e.preventDefault();
                        goTo(Number(panel.dataset.panel));
                        panel.querySelector('[aria-invalid="true"]')?.focus();
                        return;
                    }
                }
                $('submit-button').disabled = true;
            });

            goTo({{ $startStep }});
        })();
    </script>
@endsection
@section('hide_footer', true)