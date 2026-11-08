@extends('layouts.citizen')

@section('title', $service['title'])

@section('content')
    @php
        $card = 'rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200';
        $btn = 'inline-flex items-center justify-center rounded-md px-5 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 sm:py-2';
        $hiddenLabels = ['privacy policy version', 'consent ip'];
    @endphp

    <div class="w-full space-y-4 pb-20 sm:pb-0">

        {{-- Header + actions (mobile: fixed bottom bar) --}}
        <header class="space-y-2 border-b border-gray-200 pb-4">
            <nav class="flex items-center gap-2 text-xs text-gray-500" aria-label="Breadcrumb">
                <a href="{{ route('citizen.services') }}" class="hover:text-blue-700">Services</a>
                <span aria-hidden="true">›</span>
                <span class="font-semibold text-blue-700">{{ $service['title'] }}</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-blue-900 sm:text-3xl">{{ $service['title'] }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Confirm your verified details, complete the application, and review it before submitting.</p>
                </div>

                <div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-2 border-t border-gray-200 bg-white p-3 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] sm:static sm:z-auto sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
                    <button type="button" id="back-button"
                        class="{{ $btn }} flex-1 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus-visible:ring-blue-300 sm:flex-none">Back</button>

                    <div id="next-wrap" class="flex-1 sm:flex-none">
                        <button type="button" id="next-button"
                            class="{{ $btn }} w-full bg-blue-700 text-white hover:bg-blue-800 focus-visible:ring-blue-700 sm:w-auto">
                            Next <span class="ml-1.5" aria-hidden="true">›</span>
                        </button>
                    </div>

                    <div id="submit-wrap" class="hidden flex-1 sm:flex-none">
                        <button type="submit" form="apply-form" id="submit-button"
                            class="{{ $btn }} w-full bg-green-800 text-white hover:bg-green-700 focus-visible:ring-green-600 disabled:opacity-60 sm:w-auto">
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </header>

        {{-- Stepper --}}
        <ol class="{{ $card }} grid w-full grid-cols-4" aria-label="Progress">
            @foreach ($steps as $number => $label)
                <li data-step="{{ $number }}" class="relative flex min-w-0 flex-col items-center text-center">
                    @unless ($loop->first)
                        <span data-line class="absolute right-1/2 top-3.5 h-0.5 w-full"></span>
                    @endunless
                    <span data-dot class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold">{{ $number }}</span>
                    <p data-step-label class="mt-2 px-1 text-[11px] font-semibold text-gray-500 sm:text-xs">{{ $label }}</p>
                </li>
            @endforeach
        </ol>

        @if ($errors->any())
            <div role="alert" class="rounded-md border border-red-600 bg-white p-3 text-sm text-red-600">
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

            {{-- STEP 1: Applicant information --}}
            <div data-panel="1" class="space-y-3">
                <div>
                    <h2 class="text-lg font-bold text-blue-900">Applicant information</h2>
                    <p class="text-sm text-gray-600">These details come from your verified citizen profile.</p>
                </div>

                @foreach ($sections as $title => $items)
                    <section class="{{ $card }}">
                        <h3 class="text-base font-bold text-blue-900">{{ $title }}</h3>
                        <div class="mt-3 grid gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ((array) $items as $label => $value)
                                @continue(in_array(strtolower($label), $hiddenLabels))
                                <div @class(['sm:col-span-2 lg:col-span-3' => str_contains(strtolower($label), 'address')])>
                                    <x-form.input :label="$label" :name="'profile_' . Str::slug($label, '_')" :value="$value"
                                        :use-old="false" light raised readonly />
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-gray-800">
                    Need to correct your profile? Update it through your
                    <a href="{{ route('citizen.profile') }}" class="font-semibold text-blue-700 underline hover:text-blue-800">verified profile page</a>
                    before applying.
                </div>
            </div>

            {{-- STEP 2: Application details --}}
            <div data-panel="2" class="hidden space-y-3">
                <div>
                    <h2 class="text-lg font-bold text-blue-900">Application details</h2>
                    <p class="text-sm text-gray-600">Provide details for the {{ strtolower($service['title']) }} request.</p>
                </div>

                <section class="{{ $card }}">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($fields as $field)
                            <div @class(['sm:col-span-2 lg:col-span-3' => $field['wide']])>
                                <x-form.input :id="'f-' . $field['key']" :name="$field['key']"
                                    :label="$field['label'] . ($field['required'] ? '' : ' (optional)')"
                                    :type="$field['type']" :value="$field['value']"
                                    :options="$field['type'] === 'select' ? array_combine($field['options'], $field['options']) : []"
                                    :required="$field['required']" :data-label="$field['label']"
                                    :data-other="$field['other'] ? $field['key'] : null" data-field light raised />

                                @if ($field['other'])
                                    <div id="other-{{ $field['key'] }}" class="{{ $field['value'] === 'Others' ? '' : 'hidden' }} mt-3">
                                        <x-form.input :id="'f-' . $field['key'] . '_other'" :name="$field['key'] . '_other'"
                                            label="Please describe" :value="$field['otherValue']" maxlength="200" light raised />
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- STEP 3: Supporting documents --}}
            <div data-panel="3" class="hidden space-y-3">
                <div>
                    <h2 class="text-lg font-bold text-blue-900">Supporting documents</h2>
                    <p class="text-sm text-gray-600">Upload clear PDF, JPG, JPEG, or PNG files up to {{ $maxMb }} MB each. All documents below are required.</p>
                </div>

                <section class="{{ $card }}">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($service['requirements'] as $key => $label)
                            <div data-req-wrap>
                                <x-form.input :id="'req-' . $key" :name="'requirements[' . $key . ']'" :label="$label"
                                    type="file" accept=".jpg,.jpeg,.png,.pdf" data-req required light raised />
                                <p data-req-error class="mt-1 text-sm text-red-600" role="alert"></p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- STEP 4: Review (Steps 1 to 3, disabled) --}}
            <div data-panel="4" class="hidden space-y-3">
                <div>
                    <h2 class="text-lg font-bold text-blue-900">Review and confirmation</h2>
                    <p class="text-sm text-gray-600">Check your details and application information before submitting.</p>
                </div>

                @foreach ($sections as $title => $items)
                    <section class="{{ $card }}">
                        <h3 class="text-base font-bold text-blue-900">{{ $title }}</h3>
                        <div class="mt-3 grid gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ((array) $items as $label => $value)
                                @continue(in_array(strtolower($label), $hiddenLabels))
                                <div @class(['sm:col-span-2 lg:col-span-3' => str_contains(strtolower($label), 'address')])>
                                    <x-form.input :label="$label" :name="'review_profile_' . Str::slug($label, '_')" :value="$value"
                                        :use-old="false" light raised disabled />
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <section class="{{ $card }}">
                    <h3 class="text-base font-bold text-blue-900">Application details</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($fields as $field)
                            <div @class(['sm:col-span-2 lg:col-span-3' => $field['wide']])>
                                <x-form.input :id="'rv-f-' . $field['key']" :name="'review_' . $field['key']"
                                    :label="$field['label']" :type="$field['type'] === 'textarea' ? 'textarea' : 'text'"
                                    :data-review="$field['key']" :use-old="false" light raised disabled />
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="{{ $card }}">
                    <h3 class="text-base font-bold text-blue-900">Supporting documents</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($service['requirements'] as $key => $label)
                            <x-form.input :id="'rv-req-' . $key" :name="'review_req_' . $key" :label="$label"
                                :data-review-file="$key" :use-old="false" light raised disabled />
                        @endforeach
                    </div>
                </section>

                <section class="{{ $card }}">
                    <h3 class="text-base font-bold text-blue-900">Confirmation</h3>
                    <div class="mt-3 text-sm text-gray-700">
                        <x-form.checkbox name="agree_truth" required>
                            I confirm that the information and documents I provided are accurate and true.
                        </x-form.checkbox>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">
                        Submission creates an application reference number. After approval, you may be asked to pay the
                        barangay fee before you can get your document.
                    </p>
                </section>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const MAX_SIZE = {{ $maxMb }} * 1024 * 1024;
            const TYPES = ['image/jpeg', 'image/png', 'application/pdf'];
            const backUrl = @json(route('citizen.services'));
            const $ = (id) => document.getElementById(id);
            const all = (sel) => document.querySelectorAll(sel);
            const form = $('apply-form');
            let currentStep = 1;

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
            all('[data-req]').forEach((input) => {
                input.addEventListener('change', () => {
                    const file = input.files[0];
                    let message = '';
                    if (file && !TYPES.includes(file.type)) message = 'Only JPG, PNG, or PDF files are allowed.';
                    else if (file && file.size > MAX_SIZE) message = 'The file must not be larger than {{ $maxMb }} MB.';
                    input.closest('[data-req-wrap]').querySelector('[data-req-error]').textContent = message;
                    if (message) input.value = '';
                });
            });

            // Review: copy answers and file names into the disabled fields
            function fillReview() {
                all('[data-review]').forEach((el) => {
                    const key = el.dataset.review;
                    const source = $('f-' + key);
                    el.value = source.dataset.other && source.value === 'Others'
                        ? $('f-' + key + '_other').value
                        : source.value;
                });

                all('[data-review-file]').forEach((el) => {
                    const file = $('req-' + el.dataset.reviewFile).files[0];
                    el.value = file ? file.name : 'No file';
                });
            }

            // Stepper: green check = done, blue number = current, outline number = upcoming
            function updateStepper(step) {
                all('[data-step]').forEach((item) => {
                    const number = Number(item.dataset.step);
                    const dot = item.querySelector('[data-dot]');
                    const line = item.querySelector('[data-line]');
                    const label = item.querySelector('[data-step-label]');

                    dot.className = 'relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ' + (
                        number < step ? 'bg-green-600 text-white'
                        : number === step ? 'bg-blue-700 text-white ring-4 ring-blue-100'
                        : 'border-2 border-gray-300 bg-white text-gray-500');

                    dot.innerHTML = number < step
                        ? '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>'
                        : number;

                    if (line) line.className = 'absolute right-1/2 top-3.5 h-0.5 w-full ' + (number <= step ? 'bg-green-600' : 'bg-gray-200');

                    label.classList.toggle('text-blue-900', number <= step);
                    label.classList.toggle('text-gray-500', number > step);
                    item.toggleAttribute('aria-current', number === step);
                });
            }

            function goToStep(step) {
                currentStep = step;
                all('[data-panel]').forEach((p) => p.classList.toggle('hidden', Number(p.dataset.panel) !== step));
                updateStepper(step);
                $('next-wrap').classList.toggle('hidden', step === 4);
                $('submit-wrap').classList.toggle('hidden', step !== 4);
                if (step === 4) fillReview();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // Next: only if every field on this step is valid
            $('next-button').addEventListener('click', () => {
                const fields = all(`[data-panel="${currentStep}"] :is(input, select, textarea)`);
                if ([...fields].every((f) => f.reportValidity())) goToStep(currentStep + 1);
            });

            $('back-button').addEventListener('click', () => {
                if (currentStep === 1) window.location.href = backUrl;
                else goToStep(currentStep - 1);
            });

            // Enter goes to the next step instead of submitting
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT' && currentStep < 4) {
                    e.preventDefault();
                    $('next-button').click();
                }
            });

            // Submit only on the last step; jump back to any invalid hidden field
            form.addEventListener('submit', (e) => {
                const invalid = form.querySelector(':invalid');
                if (currentStep !== 4 || invalid) {
                    e.preventDefault();
                    if (invalid) {
                        goToStep(Number(invalid.closest('[data-panel]').dataset.panel));
                        invalid.reportValidity();
                    }
                    return;
                }
                $('submit-button').disabled = true;
            });

            goToStep({{ $startStep }});
        })();
    </script>
@endsection