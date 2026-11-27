@extends('layouts.citizen')

@section('title', 'Identity Verification')

@section('content')
    @php
        $step = (int) $step;
        $editing = !in_array($status, ['pending', 'verified']);
        $labels = [1 => 'Details', 2 => 'Id Verification', 3 => 'Face verification', 4 => 'Review'];

        // Same design tokens as the service application page.
        // @include passes these variables to every step partial.
        $card =
            'rounded-lg bg-(--white) p-4 shadow-sm ring-1 ring-(--gray-200) dark:bg-(--gray-800) dark:ring-(--gray-700)';
        $h2 = 'text-lg font-bold text-(--blue-900) dark:text-(--blue-300)';
        $h3 = 'text-base font-bold text-(--blue-900) dark:text-(--blue-300)';
        $sub = 'text-sm text-(--gray-600) dark:text-(--gray-300)';
        $ink = 'text-(--gray-900) dark:text-(--gray-50)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
        $line = 'border-(--gray-200) dark:border-(--gray-700)';
        $btn =
            'inline-flex items-center justify-center rounded-md px-5 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 sm:py-2';
        $btnGhost =
            $btn .
            ' flex-1 border border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) focus-visible:ring-(--blue-300) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) sm:flex-none';
        $btnBlue =
            $btn .
            ' w-full bg-(--blue-600) text-(--white) hover:bg-(--blue-700) focus-visible:ring-(--blue-600) sm:w-auto';
        $dropzone =
            'group relative flex min-h-56 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-(--gray-300) bg-(--gray-50) p-4 text-center transition hover:border-(--blue-600) hover:bg-(--gray-100) focus-within:border-(--blue-600) focus-within:ring-2 focus-within:ring-[color-mix(in_srgb,var(--blue-600)_30%,transparent)] dark:border-(--gray-600) dark:bg-(--gray-900) dark:hover:bg-(--gray-700)';
        $iconBox =
            'flex h-12 w-12 items-center justify-center rounded-full bg-(--white) text-(--blue-600) shadow-sm ring-1 ring-(--gray-200) dark:bg-(--gray-800) dark:ring-(--gray-700)';
        $thumb = 'rounded-md bg-(--white) ring-1 ring-(--gray-200) dark:bg-(--gray-800) dark:ring-(--gray-700)';
        $link = 'text-xs font-semibold text-(--blue-700) underline dark:text-(--blue-300)';
    @endphp

    <div @class(['w-full space-y-4', 'pb-20 sm:pb-0' => $editing])>

        {{-- Header + actions (mobile: fixed bottom bar) --}}
        <header class="{{ $line }} space-y-2 border-b pb-4">
            <nav class="{{ $muted }} flex items-center gap-2 text-xs" aria-label="Breadcrumb">
                <a href="{{ route('citizen.dashboard') }}"
                    class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Home</a>
                <span aria-hidden="true">›</span>
                <span class="text-(--blue-700) dark:text-(--blue-300) font-semibold">Identity Verification</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-(--blue-900) dark:text-(--blue-300) text-2xl font-bold tracking-tight sm:text-3xl">
                        Id Verification</h1>
                    <p class="{{ $sub }} mt-1">Verify your identity to request barangay services and edit your
                        profile.</p>
                </div>

                @if ($editing)
                    {{-- id="action-bar" lets the script hide Back/Submit while the privacy notice is showing --}}
                    <div id="action-bar"
                        class="{{ $line }} bg-(--white) dark:bg-(--gray-900) fixed inset-x-0 bottom-0 z-30 flex items-center gap-2 border-t p-3 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] sm:static sm:z-auto sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none dark:sm:bg-transparent">
                        @if ($step === 1)
                            <a href="{{ url('/') }}" class="{{ $btnGhost }}">Cancel</a>
                            <button type="submit" form="details-form"
                                class="{{ $btnBlue }} flex-2 sm:min-w-44 sm:flex-none">Save and continue <span
                                    class="ml-1.5" aria-hidden="true">›</span></button>
                        @else
                            <button type="button" id="back-button" class="{{ $btnGhost }}">Back</button>

                            <div id="next-wrap" class="flex-2 {{ $step === 4 ? 'hidden' : '' }} sm:flex-none">
                                <button type="button" id="next-button" class="{{ $btnBlue }} sm:min-w-36">Next <span
                                        class="ml-1.5" aria-hidden="true">›</span></button>
                            </div>

                            <div id="submit-wrap" class="flex-2 {{ $step === 4 ? '' : 'hidden' }} sm:flex-none">
                                <button type="submit" form="document-form" id="submit-button"
                                    class="{{ $btn }} bg-(--green-700) text-(--white) hover:bg-(--green-600) focus-visible:ring-(--green-500) w-full disabled:opacity-60 sm:w-auto sm:min-w-44">Submit
                                    for verification</button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </header>


        @unless ($editing)
            {{-- Show the submission only --}}
            <section class="{{ $card }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="{{ $h3 }}">Your submission</h2>
                    <span
                        class="{{ $status === 'verified' ? 'bg-[color-mix(in_srgb,var(--green-500)_18%,transparent)] text-(--green-700) dark:text-(--green-300)' : 'bg-[color-mix(in_srgb,var(--amber-500)_20%,transparent)] text-(--amber-500)' }} rounded-full px-3 py-1 text-xs font-semibold">
                        {{ $status === 'verified' ? 'Verified' : 'Pending review' }}
                    </span>
                </div>

                <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="{{ $muted }}">ID type</dt>
                        <dd class="{{ $ink }} mt-1 font-medium">{{ $verification->document_type }}</dd>
                    </div>
                    <div>
                        <dt class="{{ $muted }}">Submitted</dt>
                        <dd class="{{ $ink }} mt-1 font-medium">
                            {{ $verification->submitted_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>

                <div class="{{ $line }} mt-4 flex flex-wrap justify-end gap-3 border-t pt-4 text-sm">
                    <a href="{{ route('citizen.identity-verification.file', ['verification' => $verification, 'type' => 'document']) }}"
                        target="_blank" rel="noopener"
                        class="{{ $btn }} border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) focus-visible:ring-(--blue-300) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) w-full border sm:w-auto">
                        View front of ID
                    </a>

                    @if ($verification->document_back_key)
                        <a href="{{ route('citizen.identity-verification.file', ['verification' => $verification, 'type' => 'document_back']) }}"
                            target="_blank" rel="noopener"
                            class="{{ $btn }} border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) focus-visible:ring-(--blue-300) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) w-full border sm:w-auto">
                            View back of ID
                        </a>
                    @endif
                </div>
            </section>
        @else
            @if ($status === 'rejected' && filled($user->latestIdentityVerification?->rejection_reason))
                <x-partials.citizen-notice type="amber" title="What to correct before submitting again">
                    {{ $user->latestIdentityVerification->rejection_reason }}
                </x-partials.citizen-notice>
            @endif
            {{-- Validation errors --}}
            @if ($errors->any())
                <div role="alert"
                    class="border-(--red-600) bg-(--white) text-(--red-600) dark:bg-(--gray-800) rounded-md border p-3 text-sm">
                    <p class="font-semibold">
                        {{ $errors->has('submission') ? 'Verification could not be submitted' : 'Please fix the following:' }}
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Stepper (resources/views/components/stepper.blade.php) --}}
            <x-form.stepper id="identity-stepper" :steps="array_values($labels)" :current="$step" />

            @if ($step === 1)
                @include('citizen.identity-verifications.steps.details')
            @else
                {{-- STEPS 2 to 4: retain progress in the session until final submission. --}}
                <form id="document-form" method="POST" action="{{ route('citizen.identity-verification.document') }}"
                    enctype="multipart/form-data">
                    @csrf

                    @include('citizen.identity-verifications.steps.id')
                    @include('citizen.identity-verifications.steps.selfie')
                    @include('citizen.identity-verifications.steps.review')
                </form>

                <script @if (app()->bound('csp-nonce')) nonce="{{ app('csp-nonce') }}" @endif>
                    (() => {
                        const backUrl = @json(route('citizen.identity-verification', ['step' => 1]));
                        const MAX_SIZE = 5 * 1024 * 1024; // same as the Laravel rule (max:5120)
                        const VISION = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14';
                        const MODEL =
                            'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite';
                        const GOOD_FRAMES = 6; // about 1 second of good position (6 x 150 ms)

                        const $ = (id) => document.getElementById(id);
                        const all = (sel) => document.querySelectorAll(sel);
                        const form = $('document-form');
                        const idType = form.elements.document_type;
                        const documentInput = $('document');
                        const documentBackInput = $('document_back');
                        const idDrop = documentInput.closest('[data-upload]');
                        const idBackDrop = documentBackInput.closest('[data-upload]');
                        const selfieInput = $('selfie');
                        const selfieDrop = $('selfie-drop');
                        const selfieError = $('selfie-error');
                        const csrfToken = @json(csrf_token());
                        let hasSavedSelfie = @json(filled($draft['selfie_path'] ?? null));
                        let documentPreviewUrl = @json($draftPreviewUrls['document']);
                        let documentSavedName = @json(filled($draft['document_path'] ?? null) ? 'Saved front of ID' : null);
                        let documentBackPreviewUrl = @json($draftPreviewUrls['document_back']);
                        let documentBackSavedName = @json(filled($draft['document_back_key'] ?? null) ? 'Saved back of ID' : null);
                        let selfiePreviewUrl = @json($draftPreviewUrls['selfie']);
                        const tabUpload = $('tab-upload');
                        const tabCamera = $('open-camera');
                        const cameraBox = $('camera-box');
                        const video = $('camera-video');
                        const takeButton = $('take-photo');
                        const faceStatus = $('face-status');
                        const faceGuide = $('face-guide');

                        // Step 4 privacy gate
                        const privacyGate = $('privacy-gate');
                        const reviewContent = $('review-content');
                        const privacyAgree = $('agree_privacy');
                        const actionBar = $('action-bar');

                        let currentStep = @json($step);
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

                                canvas.toBlob((blob) => done(new File([blob], 'photo.jpg', {
                                    type: 'image/jpeg'
                                })), 'image/jpeg', 0.85);
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

                        function renderSavedSelfie() {
                            if (!hasSavedSelfie || !selfiePreviewUrl) return;

                            const img = selfieDrop.querySelector('img');
                            img.src = selfiePreviewUrl;
                            img.classList.remove('hidden');
                            showDrop(selfieDrop, {
                                name: 'Saved selfie'
                            });
                        }

                        // Makes a file input work: shrink, check, preview, drag and drop
                        function setupUpload(input, drop, errorBox, types, typeError, hasSavedFile, renderSavedFile) {
                            const img = drop.querySelector('img');
                            const pdf = drop.querySelector('[data-pdf]');

                            function refresh(error) {
                                errorBox.textContent = error || '';

                                if (error) {
                                    input.value = '';
                                    if (hasSavedFile()) {
                                        renderSavedFile();
                                        return;
                                    }
                                }

                                const file = input.files[0];
                                if (!file && hasSavedFile()) {
                                    renderSavedFile();
                                    return;
                                }

                                const isImage = file && file.type.startsWith('image/');
                                if (isImage) img.src = URL.createObjectURL(file);
                                img.classList.toggle('hidden', !isImage);
                                if (pdf) {
                                    pdf.classList.toggle('hidden', !file || isImage);
                                    if (file && !isImage) pdf.removeAttribute('href');
                                    if (file && !isImage) pdf.textContent = 'PDF file';
                                    else if (file && isImage && hasSavedFile()) pdf.removeAttribute('href');
                                }
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
                                    shrinkImage(file, (small) => {
                                        setFile(input, small);
                                        check(small);
                                    });
                                } else {
                                    check(file);
                                }
                            });

                            const highlight = (on) => drop.classList.toggle('bg-(--gray-100)', on);
                            drop.addEventListener('dragover', (e) => {
                                e.preventDefault();
                                highlight(true);
                            });
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

                        setupUpload(selfieInput, selfieDrop, selfieError,
                            ['image/jpeg', 'image/png'], 'The selfie must be a JPG or PNG file.',
                            () => hasSavedSelfie, renderSavedSelfie);

                        // A chosen file means the selfie was uploaded
                        selfieInput.addEventListener('change', () => {
                            $('selfie-source').value = 'upload';
                        });

                        function updateContinueButton() {
                            const button = $('next-button');
                            if (!button) return;

                            button.firstChild.textContent = currentStep === 2 ? 'Continue ' : 'Next ';
                        }

                        function renderSavedIdPreview(input, previewUrl, displayName, isImage) {
                            const box = input.closest('[data-upload]');
                            const preview = box.querySelector('[data-preview]');
                            box.dataset.savedPreview = previewUrl || '';
                            box.dataset.savedName = displayName || '';
                            box.dataset.savedIsImage = String(isImage);
                            preview.src = isImage ? previewUrl : '';
                            preview.hidden = !isImage;
                            box.querySelector('[data-placeholder]').hidden = isImage;
                            box.querySelector('[data-file]').hidden = !displayName;
                            box.querySelector('[data-name]').textContent = displayName || '';
                            box.querySelector('[data-meta]').textContent = displayName ? 'Saved file' : '';
                            input.value = '';
                        }

                        // ---------- Face check ----------
                        function loadDetector() {
                            if (detector) return Promise.resolve(detector);

                            return import(VISION + '/vision_bundle.mjs')
                                .then((v) => v.FilesetResolver.forVisionTasks(VISION + '/wasm').then((files) =>
                                    v.FaceDetector.createFromOptions(files, {
                                        baseOptions: {
                                            modelAssetPath: MODEL
                                        },
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
                            faceStatus.classList.toggle('text-(--green-600)', ok);
                            faceStatus.classList.toggle('text-(--gray-600)', !ok);
                            faceGuide.classList.toggle('border-(--green-400)', ok);
                            faceGuide.classList.toggle('border-white/70', !ok);
                        }

                        // Returns '' if the face is in position, or a message about what to fix
                        function checkFace(faces) {
                            if (faces.length === 0) return 'No face found. Look at the camera.';
                            if (faces.length > 1) return 'Only one face should be in the picture.';

                            const {
                                boundingBox: box,
                                keypoints
                            } = faces[0];
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

                        function startFaceCheck(session) {
                            clearInterval(faceTimer);
                            goodFrames = 0;
                            setFace(false, 'Loading face check...');

                            loadDetector().then((d) => {
                                if (session !== cameraSession) return;
                                setFace(false, 'Fit your face inside the oval.');

                                faceTimer = setInterval(() => {
                                    if (video.readyState < 2 || !video.videoWidth) return;

                                    const message = checkFace(d.detectForVideo(video, performance.now())
                                    .detections);
                                    goodFrames = message ? 0 : goodFrames + 1;

                                    if (message) setFace(false, message);
                                    else if (goodFrames >= GOOD_FRAMES) setFace(true,
                                        'Good! Hold still and take the photo.');
                                    else setFace(false, 'Hold still...');
                                }, 150);
                            }).catch(() => {
                                if (session !== cameraSession) return;
                                // Detector could not load (offline, blocked). Do not block the user.
                                setFace(true, 'Face check unavailable. Make sure your face is inside the oval.');
                            });
                        }

                        // ---------- Camera ----------
                        function setTab(cameraOn) {
                            const base = 'rounded-md px-4 py-2 transition ';
                            const on = 'bg-(--white) text-(--gray-900) shadow-sm dark:bg-(--gray-700) dark:text-(--gray-50)';
                            const off =
                                'text-(--gray-600) hover:text-(--gray-900) dark:text-(--gray-300) dark:hover:text-(--gray-50)';
                            tabUpload.className = base + (cameraOn ? off : on);
                            tabCamera.className = base + (cameraOn ? on : off);
                            tabUpload.setAttribute('aria-selected', !cameraOn);
                            tabCamera.setAttribute('aria-selected', cameraOn);
                        }

                        // Turn the camera off and show the upload area again
                        let cameraSession = 0;

                        function stopCamera() {
                            cameraSession++;
                            faceReady = false;
                            video.srcObject = null;
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
                        async function openFaceCamera() {
                            stopCamera();
                            const session = cameraSession;
                            selfieError.textContent = '';
                            selfieDrop.classList.add('hidden');
                            cameraBox.classList.remove('hidden');
                            setTab(true);
                            setFace(false, 'Opening camera…');
                            try {
                                const stream = await navigator.mediaDevices.getUserMedia({
                                    video: {
                                        facingMode: 'user'
                                    }
                                });
                                if (session !== cameraSession) {
                                    stream.getTracks().forEach(track => track.stop());
                                    return;
                                }
                                cameraStream = stream;
                                video.srcObject = stream;
                                startFaceCheck(session);
                            } catch {
                                if (session !== cameraSession) return;
                                setFace(false, 'Allow camera access and select Scan face to retry, or choose Upload photo.');
                            }
                        }

                        tabCamera.addEventListener('click', openFaceCamera);
                        window.addEventListener('pagehide', stopCamera);
                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) stopCamera();
                        });

                        // Take a photo (only when the face is in position) and put it in the selfie input
                        takeButton.addEventListener('click', () => {
                            if (!faceReady) return;

                            const canvas = document.createElement('canvas');
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            canvas.getContext('2d').drawImage(video, 0, 0);

                            canvas.toBlob((blob) => {
                                const file = new File([blob], 'selfie.jpg', {
                                    type: 'image/jpeg'
                                });
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
                            $('review-type').textContent = idType.value;

                            [{
                                    input: documentInput,
                                    drop: idDrop,
                                    image: $('review-id-front'),
                                    name: $('review-id-front-empty'),
                                    savedName: documentSavedName
                                },
                                {
                                    input: documentBackInput,
                                    drop: idBackDrop,
                                    image: $('review-id-back'),
                                    name: $('review-id-back-empty'),
                                    savedName: documentBackSavedName
                                },
                            ].forEach(({
                                input,
                                drop,
                                image,
                                name,
                                savedName
                            }) => {
                                const preview = drop.querySelector('img');
                                const showImage = !preview.hidden;
                                const file = input.files[0];
                                image.src = showImage ? preview.src : '';
                                image.classList.toggle('hidden', !showImage);
                                name.textContent = showImage ? '' : file?.name || savedName || '';
                            });

                            $('review-selfie').src = selfieDrop.querySelector('img').src;
                            $('review-selfie').classList.remove('hidden');
                        }

                        // ---------- Privacy gate (step 4) ----------
                        // on = show only the privacy notice (review and the Back/Submit bar are hidden)
                        function showPrivacyGate(on) {
                            privacyGate.classList.toggle('hidden', !on);
                            reviewContent.classList.toggle('hidden', on);
                            if (actionBar) actionBar.style.display = on ? 'none' : '';
                        }

                        // Accept: remember consent, then show the review
                        $('privacy-accept').addEventListener('click', () => {
                            privacyAgree.value = '1';
                            showPrivacyGate(false);
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        });

                        // Cancel: clear consent and go back to the face verification step
                        $('privacy-cancel').addEventListener('click', () => {
                            privacyAgree.value = '';
                            goToStep(3);
                        });

                        // ---------- Steps ----------
                        function updateStepFields(step) {
                            all('[data-panel] input, [data-panel] select').forEach((field) => {
                                field.disabled = Number(field.closest('[data-panel]').dataset.panel) !== step;
                            });
                        }

                        function goToStep(step) {
                            stopCamera();
                            currentStep = step;

                            all('[data-panel]').forEach((panel) => {
                                panel.classList.toggle('hidden', Number(panel.dataset.panel) !== step);
                            });

                            // The animated stepper component moves the line, dot and check for us
                            Stepper.set('identity-stepper', step);
                            updateStepFields(step);

                            // Submit shows ONLY on the last step, Next shows on the others
                            $('next-wrap').classList.toggle('hidden', step === 4);
                            $('submit-wrap').classList.toggle('hidden', step !== 4);
                            updateContinueButton();

                            if (step === 3 && !hasSavedSelfie && !selfieInput.files.length) openFaceCamera();

                            if (step === 4) {
                                // Privacy notice first: the user must accept before seeing the review
                                privacyAgree.value = '';
                                fillReview();
                                showPrivacyGate(true);
                            } else {
                                // Restore the action bar on every other step
                                showPrivacyGate(false);
                            }

                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        }

                        function showStepErrors(step, errors) {
                            const box = $(step === 2 ? 'id-server-errors' : 'selfie-server-errors');
                            const messages = Object.values(errors || {}).flat();
                            box.textContent = messages.length ?
                                messages.join(' ') :
                                'We could not save your progress. Check your connection and try again.';
                            box.classList.remove('hidden');
                            box.scrollIntoView({
                                block: 'center'
                            });
                        }

                        function clearStepErrors(step) {
                            const box = $(step === 2 ? 'id-server-errors' : 'selfie-server-errors');
                            box.textContent = '';
                            box.classList.add('hidden');
                        }

                        async function saveStep(step) {
                            const payload = new FormData();
                            let endpoint;

                            if (step === 2) {
                                endpoint = @json(route('citizen.identity-verification.id'));
                                payload.append('document_type', idType.value);
                                if (documentInput.files[0]) payload.append('document', documentInput.files[0]);
                                if (documentBackInput.files[0]) payload.append('document_back', documentBackInput.files[0]);
                            } else {
                                endpoint = @json(route('citizen.identity-verification.selfie'));
                                payload.append('selfie_source', $('selfie-source').value);
                                if (selfieInput.files[0]) payload.append('selfie', selfieInput.files[0]);
                            }

                            const button = $('next-button');
                            const buttonHtml = button.innerHTML;
                            button.disabled = true;
                            button.textContent = 'Saving...';
                            clearStepErrors(step);

                            try {
                                const response = await fetch(endpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                    body: payload,
                                });
                                const result = await response.json().catch(() => ({}));

                                if (response.status === 422) {
                                    showStepErrors(step, result.errors);
                                    return;
                                }

                                if (!response.ok || !result.ok) {
                                    showStepErrors(step, result.errors || {});
                                    return;
                                }

                                if (step === 2) {
                                    documentPreviewUrl = result.document_preview_url;
                                    documentSavedName = 'Saved front of ID';
                                    renderSavedIdPreview(documentInput, documentPreviewUrl, documentSavedName, !result
                                        .document_is_pdf);
                                    documentInput.removeAttribute('required');
                                    documentBackPreviewUrl = result.document_back_preview_url;
                                    documentBackSavedName = 'Saved back of ID';
                                    renderSavedIdPreview(
                                        documentBackInput,
                                        documentBackPreviewUrl,
                                        documentBackSavedName,
                                        !result.document_back_is_pdf,
                                    );
                                    documentBackInput.removeAttribute('required');
                                } else {
                                    hasSavedSelfie = true;
                                    selfiePreviewUrl = result.selfie_preview_url;
                                    selfieInput.value = '';
                                    selfieInput.removeAttribute('required');
                                    renderSavedSelfie();
                                }

                                goToStep(step + 1);
                            } catch {
                                showStepErrors(step, {});
                            } finally {
                                button.innerHTML = buttonHtml;
                                button.disabled = false;
                                updateContinueButton();
                            }
                        }

                        // Next: check the current step, save it, and only then advance
                        $('next-button').addEventListener('click', () => {
                            const fields = all(
                                `[data-panel="${currentStep}"] input, [data-panel="${currentStep}"] select`);
                            const invalid = Array.from(fields).filter((field) => !field.checkValidity());
                            if (invalid.length) {
                                if (invalid.some(field => !field.getClientRects().length)) {
                                    showStepErrors(currentStep, invalid.map(field => field.dataset.errorMessage || field
                                        .validationMessage));
                                } else {
                                    invalid[0].focus();
                                    invalid[0].closest('[data-upload], section')?.scrollIntoView({
                                        block: 'center'
                                    });
                                }
                            } else {
                                saveStep(currentStep);
                            }
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

                        // Only submit on the last step (after the privacy notice is accepted), and stop double submit
                        form.addEventListener('submit', (e) => {
                            if (currentStep !== 4 || privacyAgree.value !== '1') e.preventDefault();
                            else $('submit-button').disabled = true;
                        });

                        renderSavedSelfie();
                        goToStep(currentStep);
                    })();
                </script>
            @endif
            <script @if (app()->bound('csp-nonce')) nonce="{{ app('csp-nonce') }}" @endif>
                (() => {
                    const form = document.querySelector('#details-form, #document-form');
                    if (form.id === 'details-form') {
                        const dependents = form.querySelectorAll('[data-address-after]');
                        const syncAddress = () => {
                            for (const group of dependents) {
                                const parent = form.elements.namedItem(group.dataset.addressAfter);
                                group.hidden = parent.disabled || !parent.value.trim();
                                group.querySelector('input').disabled = group.hidden;
                            }
                        };
                        form.addEventListener('input', syncAddress);
                        form.addEventListener('change', syncAddress);
                        window.addEventListener('pageshow', syncAddress);
                        syncAddress();
                    }
                    const hintFor = field => field.closest('[data-upload]')?.querySelector('[data-error]') ||
                        form.querySelector(`[data-error-for="${field.name}"]`) ||
                        document.getElementById(field.name + '-error');

                    form.addEventListener('invalid', ({
                        target: field
                    }) => {
                        const hint = hintFor(field);
                        if (!hint) return;
                        hint.id ||= field.id + '-error';
                        hint.textContent = field.dataset.errorMessage || field.validationMessage;
                        hint.classList.remove('hidden');
                        field.setAttribute('aria-invalid', 'true');
                        field.setAttribute('aria-describedby', hint.id);
                    }, true);

                    form.addEventListener('change', ({
                        target: field
                    }) => {
                        if (!field.validity?.valid || !field.hasAttribute('aria-invalid')) return;
                        const hint = hintFor(field);
                        if (hint) hint.textContent = '';
                        field.removeAttribute('aria-invalid');
                    }, true);
                })();
            </script>
        @endunless
    </div>
@endsection
@section('hide_footer', true)
