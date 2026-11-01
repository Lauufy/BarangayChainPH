@extends('layouts.citizen')

@section('title', 'Identity Verification')

@section('content')
    @php
        $step = (int) $step;
        $labels = [1 => 'Your details', 2 => 'Upload ID', 3 => 'Selfie with ID', 4 => 'Review and submit'];
        $short = [1 => 'Details', 2 => 'ID', 3 => 'Selfie', 4 => 'Review'];
        $card = 'rounded-xl border border-slate-200 bg-white shadow-sm';
        $dropzone = 'group relative flex min-h-56 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center transition hover:border-[#17325c] hover:bg-slate-100 focus-within:border-[#17325c] focus-within:ring-2 focus-within:ring-[#17325c]/30';
        $ghostButton = 'inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#17325c]/40 sm:w-auto';
        $footer = 'flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end sm:rounded-b-xl sm:px-6';
        $iconBox = 'flex h-12 w-12 items-center justify-center rounded-full bg-white text-[#17325c] shadow-sm ring-1 ring-slate-200';
        $title = 'text-xl font-semibold text-slate-900';
        $hint = 'mt-1 max-w-prose text-sm text-slate-600';
    @endphp

    <div class="mx-auto max-w-3xl space-y-5 sm:space-y-6">
        <x-partials.citizen-page-header title="Identity verification"
            text="Verify your identity to request barangay services and edit your profile." />

        {{-- Status message --}}
        @if ($status === 'verified')
            <x-partials.citizen-notice type="green" title="Verified">Your identity is verified. You can now request barangay services.</x-partials.citizen-notice>
        @elseif ($status === 'pending')
            <x-partials.citizen-notice type="amber" title="Pending review">Your application was submitted. Barangay staff will review it. You will be notified of the result.</x-partials.citizen-notice>
        @elseif ($status === 'rejected')
            <x-partials.citizen-notice type="red" title="Application rejected">
                <p>Reason: {{ $verification->rejection_reason }}</p>
                <p>Please fix the problem and submit again below.</p>
            </x-partials.citizen-notice>
        @else
            <x-partials.citizen-notice title="Not verified yet">Complete the four steps below. Barangay staff will review your application.</x-partials.citizen-notice>
        @endif

        @if ($status === 'pending' || $status === 'verified')
            {{-- Show the submission only --}}
            <section class="{{ $card }} overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
                    <h2 class="font-semibold text-slate-900">Your submission</h2>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $status === 'verified' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $status === 'verified' ? 'Verified' : 'Pending review' }}
                    </span>
                </div>

                <dl class="grid gap-5 px-4 py-5 text-sm sm:grid-cols-2 sm:px-6">
                    <div>
                        <dt class="text-slate-500">ID type</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $verification->document_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Submitted</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $verification->submitted_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>

                <div class="border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6">
                    <a href="{{ route('citizen.identity-verification.file', $verification) }}" target="_blank" rel="noopener"
                        class="text-sm font-semibold text-[#17325c] underline-offset-4 hover:underline">View my uploaded ID</a>
                </div>
            </section>
        @else
            {{-- Validation errors --}}
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Progress: one segment per step --}}
            <nav aria-label="Progress" class="{{ $card }} p-4 sm:p-5">
                <p class="text-sm text-slate-500">
                    Step <span id="step-count" class="font-semibold text-slate-900">{{ $step }}</span> of 4
                </p>
                <p id="step-title" class="mt-0.5 text-lg font-semibold text-slate-900">{{ $labels[$step] }}</p>

                <ol class="mt-4 grid grid-cols-4 gap-2">
                    @foreach ($short as $number => $label)
                        <li data-step="{{ $number }}" @if ($number === $step) aria-current="step" @endif>
                            <div data-bar class="h-1.5 rounded-full transition-colors {{ $number <= $step ? 'bg-[#17325c]' : 'bg-slate-200' }}"></div>
                            <span data-text class="mt-2 hidden text-xs sm:block {{ $number === $step ? 'font-semibold text-slate-900' : 'text-slate-500' }}">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
            </nav>

            @if ($step === 1)
                {{-- STEP 1: Personal information --}}
                <form method="POST" action="{{ route('citizen.identity-verification.details') }}" class="{{ $card }}">
                    @csrf

                    <div class="space-y-8 p-4 sm:p-6">
                        <fieldset>
                            <legend class="text-base font-semibold text-slate-900">Personal information</legend>
                            <p class="mt-1 text-sm text-slate-600">Name and email come from your account and can't be changed here.</p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <x-form.input light disabled placeholder=" " name="name" label="Full name" :value="auth()->user()->name" />
                                <x-form.input light disabled placeholder=" " name="email" label="Email" :value="auth()->user()->email" />
                                <x-form.input light placeholder=" " name="date_of_birth" label="Date of birth" type="date" required
                                    :value="$profile?->date_of_birth?->format('Y-m-d')" />
                                <x-form.input light name="gender" label="Gender" type="select" required placeholder="Select"
                                    :options="['Male' => 'Male', 'Female' => 'Female']" :value="$profile?->gender" />
                                <x-form.input light name="civil_status" label="Civil status" type="select" required placeholder="Select"
                                    :options="['Single' => 'Single', 'Married' => 'Married', 'Widowed' => 'Widowed', 'Separated' => 'Separated', 'Divorced' => 'Divorced']"
                                    :value="$profile?->civil_status" />
                                <x-form.input light placeholder=" " name="place_of_birth" label="Place of birth" required :value="$profile?->place_of_birth" />
                                <x-form.input light placeholder=" " name="nationality" label="Nationality" required :value="$profile?->nationality" />
                                <x-form.input light placeholder=" " name="religion" label="Religion (optional)" :value="$profile?->religion" />
                                <x-form.input light placeholder=" " name="occupation" label="Occupation (optional)" :value="$profile?->occupation" />
                            </div>
                        </fieldset>

                        <fieldset class="border-t border-slate-100 pt-8">
                            <legend class="text-base font-semibold text-slate-900">Address and contact</legend>
                            <p class="mt-1 text-sm text-slate-600">Use the address where you live now.</p>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <x-form.input light placeholder=" " name="phone" label="Phone number" type="tel" required :value="$profile?->phone" />
                                <x-form.input light placeholder=" " name="house_number" label="House number (optional)" :value="$profile?->house_number" />
                                <x-form.input light placeholder=" " name="barangay" label="Barangay" required :value="$profile?->barangay" />
                                <x-form.input light placeholder=" " name="city_municipality" label="City / Municipality" required :value="$profile?->city_municipality" />
                                <x-form.input light placeholder=" " name="province" label="Province" required :value="$profile?->province" />
                                <x-form.input light placeholder=" " name="zip_code" label="ZIP code" required :value="$profile?->zip_code" />
                            </div>
                        </fieldset>
                    </div>

                    <div class="{{ $footer }}">
                        <a href="{{ url('/') }}" class="{{ $ghostButton }}">Cancel</a>
                        <x-form.button type="submit" color="#17325c" hoverColor="#10244a" loadingText="Saving..." class="w-full sm:w-auto">
                            Save and continue
                        </x-form.button>
                    </div>
                </form>
            @else
                {{-- STEPS 2 to 4: one form, one panel shown at a time. Everything is sent on the last step. --}}
                <form id="document-form" method="POST" action="{{ route('citizen.identity-verification.document') }}"
                    enctype="multipart/form-data" class="{{ $card }}">
                    @csrf

                    <div class="p-4 sm:p-6">
                        {{-- STEP 2: ID --}}
                        <div data-panel="2">
                            <h2 class="{{ $title }}">Upload your ID</h2>
                            <p class="{{ $hint }}">Use a valid government ID. Your name and photo must be readable.</p>

                            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                                <x-form.input light name="document_type" label="Type of ID" type="select" required placeholder="Select"
                                    :options="array_combine($documentTypes, $documentTypes)" />
                                <x-form.input light placeholder=" " name="id_number" label="ID number" required maxlength="50" />
                            </div>

                            <div class="mt-5">
                                <span class="text-sm font-medium text-slate-700">ID photo or scan</span>

                                <label for="document" id="id-drop" class="{{ $dropzone }} mt-1.5">
                                    <input id="document" name="document" type="file" required accept=".jpg,.jpeg,.png,.pdf" class="sr-only">

                                    <div data-empty class="flex flex-col items-center">
                                        <span class="{{ $iconBox }}">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V5m0 0-4 4m4-4 4 4M5 19h14" />
                                            </svg>
                                        </span>
                                        <span class="mt-3 text-sm font-semibold text-slate-900">Choose a file or drop it here</span>
                                        <span class="mt-1 text-xs text-slate-500">JPG, PNG, or PDF, up to 5 MB. Photos are shrunk automatically.</span>
                                    </div>

                                    <div data-filled class="hidden w-full flex-col items-center">
                                        <img alt="ID preview" class="hidden max-h-48 w-auto max-w-full rounded-md bg-white object-contain ring-1 ring-slate-200">
                                        <span data-pdf class="hidden rounded-md bg-white px-4 py-6 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">PDF file</span>
                                        <span data-name class="mt-3 max-w-full truncate text-sm font-medium text-slate-800"></span>
                                        <span class="mt-0.5 text-xs font-semibold text-[#17325c] underline">Replace file</span>
                                    </div>
                                </label>
                                <p id="file-error" class="mt-1.5 text-sm text-red-600" role="alert"></p>
                            </div>
                        </div>

                        {{-- STEP 3: Selfie (upload or camera with face check) --}}
                        <div data-panel="3" class="hidden">
                            <h2 class="{{ $title }}">Take a selfie with your ID</h2>
                            <p class="{{ $hint }}">Hold your ID next to your face. Use the camera or upload a photo.</p>

                            <ul class="mt-4 space-y-1.5 rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                                <li>✓ Your face and your ID are both clearly visible.</li>
                                <li>✓ The lighting is good. No hat or sunglasses.</li>
                                <li>✓ The ID text is sharp and not covered.</li>
                            </ul>

                            <input type="hidden" name="selfie_source" id="selfie-source" value="upload">

                            <div role="tablist" aria-label="Selfie method" class="mt-5 inline-flex rounded-lg bg-slate-100 p-1 text-sm font-semibold">
                                <button type="button" role="tab" id="tab-upload" aria-selected="true"
                                    class="rounded-md bg-white px-4 py-2 text-slate-900 shadow-sm transition">Upload photo</button>
                                <button type="button" role="tab" id="open-camera" aria-selected="false"
                                    class="rounded-md px-4 py-2 text-slate-600 transition hover:text-slate-900">Use camera</button>
                            </div>

                            <div class="mt-4">
                                <label for="selfie" id="selfie-drop" class="{{ $dropzone }}">
                                    <input id="selfie" name="selfie" type="file" required accept="image/jpeg,image/png" class="sr-only">

                                    <div data-empty class="flex flex-col items-center">
                                        <span class="{{ $iconBox }}">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                                <circle cx="12" cy="9" r="3.5" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0" />
                                            </svg>
                                        </span>
                                        <span class="mt-3 text-sm font-semibold text-slate-900">Choose a selfie or drop it here</span>
                                        <span class="mt-1 text-xs text-slate-500">JPG or PNG. Photos are shrunk automatically.</span>
                                    </div>

                                    <div data-filled class="hidden w-full flex-col items-center">
                                        <img alt="Selfie preview" class="max-h-56 w-auto max-w-full rounded-md bg-white object-contain ring-1 ring-slate-200">
                                        <span data-name class="mt-3 max-w-full truncate text-sm font-medium text-slate-800"></span>
                                        <span class="mt-0.5 text-xs font-semibold text-[#17325c] underline">Replace photo</span>
                                    </div>
                                </label>

                                <div id="camera-box" class="hidden">
                                    <div class="relative mx-auto w-full max-w-md overflow-hidden rounded-lg bg-slate-900">
                                        <video id="camera-video" autoplay playsinline muted class="aspect-4/3 w-full -scale-x-100 object-cover"></video>
                                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                            <div id="face-guide" class="h-3/4 w-2/5 rounded-[50%] border-2 border-dashed border-white/70 transition-colors"></div>
                                        </div>
                                    </div>
                                    <p id="face-status" class="mt-3 text-center text-sm font-medium text-slate-600" role="status" aria-live="polite"></p>
                                    <div class="mt-3 flex justify-center">
                                        <x-form.button type="button" id="take-photo" color="#17325c" hoverColor="#10244a">Take photo</x-form.button>
                                    </div>
                                </div>

                                <p id="selfie-error" class="mt-1.5 text-sm text-red-600" role="alert"></p>
                            </div>
                        </div>

                        {{-- STEP 4: Review and consent --}}
                        <div data-panel="4" class="hidden">
                            <h2 class="{{ $title }}">Review and submit</h2>
                            <p class="{{ $hint }}">Check that everything is correct, then accept the notice below.</p>

                            <div class="mt-5 divide-y divide-slate-100 rounded-lg border border-slate-200">
                                <dl class="grid gap-4 p-4 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-slate-500">Full name</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ auth()->user()->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500">Date of birth</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ $profile?->date_of_birth?->format('M j, Y') ?? '—' }}</dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-slate-500">Address</dt>
                                        <dd class="mt-1 font-medium text-slate-900">
                                            {{ collect([$profile?->house_number, $profile?->barangay, $profile?->city_municipality, $profile?->province, $profile?->zip_code])->filter()->implode(', ') ?: '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500">ID type</dt>
                                        <dd id="review-type" class="mt-1 font-medium text-slate-900"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500">ID number</dt>
                                        <dd id="review-number" class="mt-1 font-medium text-slate-900"></dd>
                                    </div>
                                </dl>

                                <div class="grid grid-cols-2 gap-4 p-4">
                                    <figure>
                                        <div class="flex h-32 items-center justify-center overflow-hidden rounded-md bg-slate-50 ring-1 ring-slate-200">
                                            <img id="review-id" alt="Your ID" class="hidden h-full w-full object-contain">
                                            <span id="review-id-empty" class="px-2 text-center text-xs text-slate-500"></span>
                                        </div>
                                        <figcaption class="mt-2 text-xs text-slate-500">ID</figcaption>
                                    </figure>
                                    <figure>
                                        <div class="flex h-32 items-center justify-center overflow-hidden rounded-md bg-slate-50 ring-1 ring-slate-200">
                                            <img id="review-selfie" alt="Your selfie" class="hidden h-full w-full object-contain">
                                        </div>
                                        <figcaption class="mt-2 text-xs text-slate-500">Selfie with ID</figcaption>
                                    </figure>
                                </div>
                            </div>

                            {{-- Change this text to match your barangay's official privacy notice --}}
                            <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                                <h3 class="font-semibold text-slate-900">Data privacy notice</h3>
                                <p class="mt-1">
                                    We collect your personal details, ID, and selfie only to verify your identity for barangay online services.
                                    They are stored privately and can only be viewed by authorized barangay staff.
                                    They are not shared with other citizens or made public.
                                    Only a coded proof of the decision is kept in the system ledger. Your personal data is never put on it.
                                    We handle your data in line with the Data Privacy Act of 2012 (Republic Act 10173).
                                </p>
                            </div>

                            <div class="mt-5 space-y-3">
                                <x-form.checkbox name="agree_truth" required>
                                    I confirm that the information, ID, and photo I submitted are true and belong to me.
                                </x-form.checkbox>
                                <x-form.checkbox name="agree_privacy" required>
                                    I have read the data privacy notice and I agree to the use of my personal data for identity verification.
                                </x-form.checkbox>
                            </div>
                        </div>
                    </div>

                    <div class="{{ $footer }}">
                        <button type="button" id="back-button" class="{{ $ghostButton }}">Back</button>

                        <div id="next-wrap">
                            <x-form.button type="button" id="next-button" color="#17325c" hoverColor="#10244a" class="w-full sm:w-auto">Next</x-form.button>
                        </div>

                        <div id="submit-wrap" class="hidden">
                            <x-form.button type="submit" id="submit-button" color="#17325c" hoverColor="#10244a" loadingText="Submitting..."
                                class="w-full sm:w-auto">Submit for verification</x-form.button>
                        </div>
                    </div>
                </form>

                <script>
                    const backUrl = @json(route('citizen.identity-verification'));
                    const titles = @json($labels);
                    const MAX_SIZE = 5 * 1024 * 1024; // same as the Laravel rule (max:5120)
                    const VISION = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14';
                    const MODEL = 'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite';
                    const GOOD_FRAMES = 6; // about 1 second of good position (6 x 150 ms)

                    const $ = (id) => document.getElementById(id);
                    const form = $('document-form');
                    const idNumber = form.id_number;
                    const idDrop = $('id-drop');
                    const selfieInput = $('selfie');
                    const selfieDrop = $('selfie-drop');
                    const selfieError = $('selfie-error');
                    const tabUpload = $('tab-upload');
                    const tabCamera = $('open-camera');
                    const cameraBox = $('camera-box');
                    const video = $('camera-video');
                    const takeButton = $('take-photo');
                    const faceStatus = $('face-status');
                    const faceGuide = $('face-guide');

                    let currentStep = 2;
                    let cameraStream = null;
                    let detector = null;
                    let faceTimer = null;
                    let goodFrames = 0;
                    let faceReady = false;

                    // ---------- Photo helpers ----------

                    // Shrink a photo (max 1800px, JPEG) so it uploads easily
                    function shrinkImage(file, done) {
                        const img = new Image();
                        img.onload = () => {
                            const scale = Math.min(1, 1800 / Math.max(img.width, img.height));
                            const canvas = document.createElement('canvas');
                            canvas.width = img.width * scale;
                            canvas.height = img.height * scale;

                            const ctx = canvas.getContext('2d');
                            ctx.fillStyle = '#fff'; // white background for transparent PNGs
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                            canvas.toBlob((blob) => done(new File([blob], 'photo.jpg', { type: 'image/jpeg' })), 'image/jpeg', 0.85);
                        };
                        img.onerror = () => done(file); // cannot read it, keep the original
                        img.src = URL.createObjectURL(file);
                    }

                    function setFile(input, file) {
                        const list = new DataTransfer();
                        list.items.add(file);
                        input.files = list.files;
                    }

                    // Switch a drop area between "empty" and "has a file"
                    function showDrop(drop, file) {
                        const filled = drop.querySelector('[data-filled]');
                        drop.querySelector('[data-empty]').classList.toggle('hidden', !!file);
                        filled.classList.toggle('hidden', !file);
                        filled.classList.toggle('flex', !!file);
                        drop.querySelector('[data-name]').textContent = file ? file.name : '';
                    }

                    // Makes a file input work: shrink, check, preview, drag and drop
                    function setupUpload(input, drop, errorBox, types, typeError) {
                        const img = drop.querySelector('img');
                        const pdf = drop.querySelector('[data-pdf]');

                        function refresh(error) {
                            errorBox.textContent = error || '';
                            if (error) input.value = '';

                            const file = input.files[0];
                            const isImage = file && file.type.startsWith('image/');
                            if (isImage) img.src = URL.createObjectURL(file);
                            img.classList.toggle('hidden', !isImage);
                            if (pdf) pdf.classList.toggle('hidden', !file || isImage);
                            showDrop(drop, file);
                        }

                        function check(file) {
                            if (!types.includes(file.type)) return refresh(typeError);
                            if (file.size > MAX_SIZE) return refresh('The file must not be larger than 5 MB.');
                            refresh();
                        }

                        input.addEventListener('change', () => {
                            const file = input.files[0];
                            if (!file) return refresh();

                            if (file.type.startsWith('image/')) {
                                shrinkImage(file, (small) => { setFile(input, small); check(small); });
                            } else {
                                check(file);
                            }
                        });

                        const highlight = (on) => drop.classList.toggle('bg-slate-100', on);
                        drop.addEventListener('dragover', (e) => { e.preventDefault(); highlight(true); });
                        drop.addEventListener('dragleave', () => highlight(false));
                        drop.addEventListener('drop', (e) => {
                            e.preventDefault();
                            highlight(false);
                            if (e.dataTransfer.files.length) {
                                setFile(input, e.dataTransfer.files[0]);
                                input.dispatchEvent(new Event('change'));
                            }
                        });
                    }

                    setupUpload($('document'), idDrop, $('file-error'),
                        ['image/jpeg', 'image/png', 'application/pdf'], 'The ID must be a JPG, PNG, or PDF file.');
                    setupUpload(selfieInput, selfieDrop, selfieError,
                        ['image/jpeg', 'image/png'], 'The selfie must be a JPG or PNG file.');

                    // A chosen file means the selfie was uploaded
                    selfieInput.addEventListener('change', () => { $('selfie-source').value = 'upload'; });

                    // ---------- ID number ----------
                    function checkIdNumber() {
                        const value = idNumber.value.trim();
                        let message = '';
                        idNumber.value = value;

                        if (value === '') message = 'Please enter your ID number.';
                        else if (value.length < 5) message = 'The ID number is too short. Check it again.';
                        else if (value.length > 50) message = 'The ID number must not be longer than 50 characters.';
                        else if (!/^[A-Za-z0-9][A-Za-z0-9\-\/ ]*$/.test(value)) message = 'The ID number can only have letters, numbers, spaces, dashes, and slashes.';

                        idNumber.setCustomValidity(message); // empty message = valid
                    }
                    idNumber.addEventListener('input', () => idNumber.setCustomValidity(''));

                    // ---------- Face check ----------
                    function loadDetector() {
                        if (detector) return Promise.resolve(detector);

                        return import(VISION + '/vision_bundle.mjs')
                            .then((v) => v.FilesetResolver.forVisionTasks(VISION + '/wasm').then((files) =>
                                v.FaceDetector.createFromOptions(files, {
                                    baseOptions: { modelAssetPath: MODEL },
                                    runningMode: 'VIDEO',
                                    minDetectionConfidence: 0.6,
                                })))
                            .then((d) => (detector = d));
                    }

                    // Enable or disable the Take photo button and show the message
                    function setFace(ok, message) {
                        faceReady = ok;
                        takeButton.disabled = !ok;
                        takeButton.classList.toggle('opacity-50', !ok);
                        takeButton.classList.toggle('cursor-not-allowed', !ok);
                        faceStatus.textContent = message;
                        faceStatus.classList.toggle('text-emerald-600', ok);
                        faceStatus.classList.toggle('text-slate-600', !ok);
                        faceGuide.classList.toggle('border-emerald-400', ok);
                        faceGuide.classList.toggle('border-white/70', !ok);
                    }

                    // Returns '' if the face is in position, or a message about what to fix
                    function checkFace(faces) {
                        if (faces.length === 0) return 'No face found. Look at the camera.';
                        if (faces.length > 1) return 'Only one face should be in the picture.';

                        const { boundingBox: box, keypoints } = faces[0];
                        const vw = video.videoWidth;
                        const vh = video.videoHeight;
                        const x = 1 - (box.originX + box.width / 2) / vw; // flipped: the preview is mirrored
                        const y = (box.originY + box.height / 2) / vh;
                        const size = box.height / vh;

                        if (size < 0.28) return 'Move closer to the camera.';
                        if (size > 0.6) return 'Move a little farther from the camera.';
                        if (x < 0.35) return 'Move to the right.';
                        if (x > 0.65) return 'Move to the left.';
                        if (y < 0.35) return 'Move down a little.';
                        if (y > 0.65) return 'Move up a little.';

                        // keypoints: 0 and 1 are the eyes, 2 is the nose tip
                        const [eyeA, eyeB, nose] = keypoints;
                        let tilt = Math.abs(Math.atan2((eyeB.y - eyeA.y) * vh, (eyeB.x - eyeA.x) * vw) * 180 / Math.PI);
                        if (tilt > 90) tilt = 180 - tilt;
                        if (tilt > 15) return 'Keep your head straight.';

                        const turn = Math.abs(nose.x - (eyeA.x + eyeB.x) / 2) / (Math.abs(eyeB.x - eyeA.x) || 1);
                        if (turn > 0.35) return 'Look straight at the camera.';

                        return '';
                    }

                    function startFaceCheck() {
                        clearInterval(faceTimer);
                        goodFrames = 0;
                        setFace(false, 'Loading face check...');

                        loadDetector().then((d) => {
                            if (!cameraStream) return; // camera was closed while loading
                            setFace(false, 'Fit your face inside the oval.');

                            faceTimer = setInterval(() => {
                                if (video.readyState < 2 || !video.videoWidth) return;

                                const message = checkFace(d.detectForVideo(video, performance.now()).detections);
                                goodFrames = message ? 0 : goodFrames + 1;

                                if (message) setFace(false, message);
                                else if (goodFrames >= GOOD_FRAMES) setFace(true, 'Good! Hold still and take the photo.');
                                else setFace(false, 'Hold still...');
                            }, 150);
                        }).catch(() => {
                            // Detector could not load (offline, blocked). Do not block the user.
                            setFace(true, 'Face check unavailable. Make sure your face is inside the oval.');
                        });
                    }

                    // ---------- Camera ----------
                    function setTab(cameraOn) {
                        const base = 'rounded-md px-4 py-2 transition ';
                        const on = 'bg-white text-slate-900 shadow-sm';
                        const off = 'text-slate-600 hover:text-slate-900';
                        tabUpload.className = base + (cameraOn ? off : on);
                        tabCamera.className = base + (cameraOn ? on : off);
                        tabUpload.setAttribute('aria-selected', !cameraOn);
                        tabCamera.setAttribute('aria-selected', cameraOn);
                    }

                    // Turn the camera off and show the upload area again
                    function stopCamera() {
                        clearInterval(faceTimer);
                        if (cameraStream) {
                            cameraStream.getTracks().forEach((track) => track.stop());
                            cameraStream = null;
                        }
                        cameraBox.classList.add('hidden');
                        selfieDrop.classList.remove('hidden');
                        setTab(false);
                    }

                    tabUpload.addEventListener('click', stopCamera);

                    // Open the camera (needs HTTPS or localhost)
                    tabCamera.addEventListener('click', () => {
                        selfieError.textContent = '';
                        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                            .then((stream) => {
                                cameraStream = stream;
                                video.srcObject = stream;
                                selfieDrop.classList.add('hidden');
                                cameraBox.classList.remove('hidden');
                                setTab(true);
                                startFaceCheck();
                            })
                            .catch(() => { selfieError.textContent = 'Camera not available. Please upload a photo instead.'; });
                    });

                    // Take a photo (only when the face is in position) and put it in the selfie input
                    takeButton.addEventListener('click', () => {
                        if (!faceReady) return;

                        const canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        canvas.getContext('2d').drawImage(video, 0, 0);

                        canvas.toBlob((blob) => {
                            const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                            const img = selfieDrop.querySelector('img');

                            setFile(selfieInput, file);
                            $('selfie-source').value = 'camera';
                            selfieError.textContent = '';
                            img.src = URL.createObjectURL(blob);
                            img.classList.remove('hidden');
                            showDrop(selfieDrop, file);
                            stopCamera();
                        }, 'image/jpeg', 0.85);
                    });

                    // ---------- Review ----------
                    function fillReview() {
                        const idImg = idDrop.querySelector('img');
                        const idFile = $('document').files[0];
                        const showImage = !idImg.classList.contains('hidden');
                        let number = idNumber.value.trim();

                        // Show only the last 4 characters of the ID number
                        if (number.length > 4) number = '•'.repeat(number.length - 4) + number.slice(-4);

                        $('review-type').textContent = form.document_type.value;
                        $('review-number').textContent = number;

                        // A PDF has no picture, so show its file name instead
                        $('review-id').src = showImage ? idImg.src : '';
                        $('review-id').classList.toggle('hidden', !showImage);
                        $('review-id-empty').textContent = showImage || !idFile ? '' : idFile.name;

                        $('review-selfie').src = selfieDrop.querySelector('img').src;
                        $('review-selfie').classList.remove('hidden');
                    }

                    // ---------- Steps ----------
                    function goToStep(step) {
                        stopCamera();
                        currentStep = step;

                        document.querySelectorAll('[data-panel]').forEach((panel) => {
                            panel.classList.toggle('hidden', Number(panel.dataset.panel) !== step);
                        });

                        document.querySelectorAll('[data-step]').forEach((item) => {
                            const number = Number(item.dataset.step);
                            const text = item.querySelector('[data-text]');

                            item.querySelector('[data-bar]').classList.toggle('bg-[#17325c]', number <= step);
                            item.querySelector('[data-bar]').classList.toggle('bg-slate-200', number > step);
                            text.classList.toggle('font-semibold', number === step);
                            text.classList.toggle('text-slate-900', number === step);
                            text.classList.toggle('text-slate-500', number !== step);

                            if (number === step) item.setAttribute('aria-current', 'step');
                            else item.removeAttribute('aria-current');
                        });

                        $('step-count').textContent = step;
                        $('step-title').textContent = titles[step];

                        // Submit shows ONLY on the last step, Next shows on the others
                        $('next-wrap').classList.toggle('hidden', step === 4);
                        $('submit-wrap').classList.toggle('hidden', step !== 4);

                        if (step === 4) fillReview();
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }

                    // Next: check the fields of this step first
                    $('next-button').addEventListener('click', () => {
                        if (currentStep === 2) checkIdNumber();

                        const fields = document.querySelectorAll(`[data-panel="${currentStep}"] input, [data-panel="${currentStep}"] select`);
                        if (Array.from(fields).every((field) => field.reportValidity())) goToStep(currentStep + 1);
                    });

                    // Back: from step 2 go back to the personal information page
                    $('back-button').addEventListener('click', () => {
                        if (currentStep === 2) window.location.href = backUrl;
                        else goToStep(currentStep - 1);
                    });

                    // Pressing Enter goes to the next step instead of submitting
                    form.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' && e.target.tagName === 'INPUT' && currentStep < 4) {
                            e.preventDefault();
                            $('next-button').click();
                        }
                    });

                    // Only submit on the last step, and stop double submit
                    form.addEventListener('submit', (e) => {
                        if (currentStep !== 4) e.preventDefault();
                        else $('submit-button').disabled = true;
                    });
                </script>
            @endif
        @endif
    </div>
@endsection