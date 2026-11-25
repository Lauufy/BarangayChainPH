@props([
    'target' => 'document',
    'targetBack' => 'document_back',
    'uploadTarget' => 'id-uploads',
])

<section id="id-scanner" data-target="#{{ $target }}" data-target-back="#{{ $targetBack }}"
    data-upload-target="{{ $uploadTarget }}"
    class="border-(--gray-200) bg-(--white) dark:border-(--gray-700) dark:bg-(--gray-800) mt-4 rounded-md border p-4">
    <div role="tablist" aria-label="ID photo method"
        class="bg-(--gray-100) dark:bg-(--gray-900) inline-flex rounded-lg p-1 text-sm font-semibold">
        <button type="button" role="tab" data-scan-camera aria-selected="true"
            class="rounded-md px-4 py-2 shadow-sm transition">Scan photo</button>
        <button type="button" role="tab" data-scan-upload aria-selected="false"
            class="rounded-md px-4 py-2 transition">Upload photos</button>
    </div>

    <div data-scan-camera-panel class="mt-4 hidden">
        <div data-scan-live>
            <div class="relative mx-auto w-full max-w-md overflow-hidden rounded-md bg-black">
                <video data-scan-video playsinline muted autoplay class="block w-full"></video>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div data-scan-guide
                        class="w-[86%] rounded-lg border-2 border-white/90 shadow-[0_0_0_999px_rgba(0,0,0,.45)] transition-colors duration-150"
                        style="aspect-ratio:1.586/1"></div>
                </div>
            </div>
        </div>
        <p class="text-(--gray-700) dark:text-(--gray-200) mt-3 text-center text-sm font-semibold">
            <span data-scan-stage-label>Taking photo of:</span>
            <span data-scan-side-label>Front of ID</span>
        </p>
        <p data-scan-instruction class="text-(--gray-600) dark:text-(--gray-300) mt-2 text-center text-xs"
            role="status" aria-live="polite">Fit the whole ID inside the frame.</p>
        <p data-scan-error class="text-(--red-600) mt-2 hidden text-center text-xs" role="alert"></p>
        <div data-scan-review-panel class="mt-3 hidden">
            <img data-scan-review-image alt="Captured front of ID"
                class="mx-auto max-h-[60vh] w-full max-w-md rounded-md bg-black object-contain">
            <div class="mt-3 flex justify-center gap-2">
                <button type="button" data-scan-retake
                    class="border-(--gray-300) text-(--gray-700) dark:text-(--gray-200) rounded-md border px-4 py-2 text-sm">Retake</button>
                <button type="button" data-scan-next
                    class="bg-(--gray-900) text-(--white) rounded-md px-4 py-2 text-sm font-semibold">Next</button>
            </div>
        </div>
        <div class="mt-3 flex justify-center gap-2">
            <button type="button" data-scan-capture disabled
                class="bg-(--gray-900) text-(--white) rounded-md px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50">Capture
                front</button>
            <button type="button" data-scan-cancel
                class="border-(--gray-300) text-(--gray-700) dark:text-(--gray-200) rounded-md border px-4 py-2 text-sm">Cancel</button>
        </div>
    </div>
</section>

@once
    <script @if (app()->bound('csp-nonce')) nonce="{{ app('csp-nonce') }}" @endif>
        (() => {
            const initialize = () => {
                const root = document.getElementById('id-scanner');
                if (!root || root.dataset.initialized) return;
                root.dataset.initialized = 'true';

                const frontInput = document.querySelector(root.dataset.target);
                const backInput = document.querySelector(root.dataset.targetBack);
                const uploadPanel = document.getElementById(root.dataset.uploadTarget);
                const $ = (selector) => root.querySelector(selector);
                if (!frontInput || !backInput || !uploadPanel) return;

                const el = {
                    upload: $('[data-scan-upload]'),
                    cameraTab: $('[data-scan-camera]'),
                    cameraPanel: $('[data-scan-camera-panel]'),
                    video: $('[data-scan-video]'),
                    guide: $('[data-scan-guide]'),
                    livePanel: $('[data-scan-live]'),
                    stageLabel: $('[data-scan-stage-label]'),
                    sideLabel: $('[data-scan-side-label]'),
                    reviewPanel: $('[data-scan-review-panel]'),
                    reviewImage: $('[data-scan-review-image]'),
                    instruction: $('[data-scan-instruction]'),
                    error: $('[data-scan-error]'),
                    capture: $('[data-scan-capture]'),
                    cancel: $('[data-scan-cancel]'),
                    retake: $('[data-scan-retake]'),
                    next: $('[data-scan-next]'),
                };
                const maxWidth = 1600;
                const goodFramesRequired = 3;
                const checkInterval = 300;
                const guideColors = {
                    idle: 'border-white/90',
                    checking: 'border-(--amber-500)',
                    invalid: 'border-(--red-600)',
                    valid: 'border-(--green-500)',
                };
                let stream = null;
                let side = 'front';
                let session = 0;
                let validator = null;
                let frameRequest = null;
                let analysisBusy = false;
                let liveCheckFailed = false;
                let goodFrames = 0;
                let lastCheckAt = 0;
                let capturedFrontFile = null;
                let pendingFile = null;
                let pendingPreviewUrl = null;

                function show(element, visible) {
                    element.classList.toggle('hidden', !visible);
                }

                function setStatus(message) {
                    el.instruction.textContent = message;
                }

                function setGuideState(state) {
                    Object.values(guideColors).forEach((color) => el.guide.classList.remove(color));
                    el.guide.classList.add(guideColors[state]);
                }

                function setError(message) {
                    el.error.textContent = message || '';
                    show(el.error, Boolean(message));
                }

                function setMethod(useCamera) {
                    const active = 'rounded-md bg-(--white) px-4 py-2 text-(--gray-900) shadow-sm transition dark:bg-(--gray-700) dark:text-(--gray-50)';
                    const inactive = 'rounded-md px-4 py-2 text-(--gray-600) transition hover:text-(--gray-900) dark:text-(--gray-300) dark:hover:text-(--gray-50)';
                    el.upload.className = useCamera ? inactive : active;
                    el.cameraTab.className = useCamera ? active : inactive;
                    el.upload.setAttribute('aria-selected', String(!useCamera));
                    el.cameraTab.setAttribute('aria-selected', String(useCamera));
                    show(uploadPanel, !useCamera);
                    show(el.cameraPanel, useCamera);
                }

                function setSide(nextSide) {
                    side = nextSide;
                    el.capture.textContent = `Capture ${side}`;
                    el.stageLabel.textContent = 'Taking photo of:';
                    el.sideLabel.textContent = nextSide === 'front' ? 'Front of ID' : 'Back of ID';
                    setStatus(side === 'front'
                        ? 'Place the front of your ID inside the frame.'
                        : 'Turn your ID over and fit the back inside the frame.');
                }

                function stopCamera() {
                    pauseCamera();
                    capturedFrontFile = null;
                }

                function pauseCamera() {
                    session++;
                    if (frameRequest !== null) {
                        cancelAnimationFrame(frameRequest);
                        frameRequest = null;
                    }
                    if (stream) {
                        stream.getTracks().forEach((track) => track.stop());
                        stream = null;
                    }
                    el.video.srcObject = null;
                    el.capture.disabled = true;
                    validator = null;
                    analysisBusy = false;
                    liveCheckFailed = false;
                    goodFrames = 0;
                    setGuideState('idle');
                }

                function clearReview() {
                    if (pendingPreviewUrl) {
                        URL.revokeObjectURL(pendingPreviewUrl);
                        pendingPreviewUrl = null;
                    }
                    pendingFile = null;
                    el.reviewImage.removeAttribute('src');
                    show(el.reviewPanel, false);
                    show(el.livePanel, true);
                    show(el.capture, true);
                }

                function close() {
                    clearReview();
                    stopCamera();
                    setMethod(false);
                    setError('');
                }

                function cropRect() {
                    const videoRect = el.video.getBoundingClientRect();
                    const guideRect = el.guide.getBoundingClientRect();
                    const scaleX = el.video.videoWidth / videoRect.width;
                    const scaleY = el.video.videoHeight / videoRect.height;
                    const x = Math.max(0, Math.round((guideRect.left - videoRect.left) * scaleX));
                    const y = Math.max(0, Math.round((guideRect.top - videoRect.top) * scaleY));

                    return {
                        x,
                        y,
                        width: Math.min(el.video.videoWidth - x, Math.round(guideRect.width * scaleX)),
                        height: Math.min(el.video.videoHeight - y, Math.round(guideRect.height * scaleY)),
                    };
                }

                function drawCrop(maximumWidth = maxWidth) {
                    const crop = cropRect();
                    const scale = Math.min(1, maximumWidth / crop.width);
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(crop.width * scale);
                    canvas.height = Math.round(crop.height * scale);
                    canvas.getContext('2d').drawImage(
                        el.video,
                        crop.x,
                        crop.y,
                        crop.width,
                        crop.height,
                        0,
                        0,
                        canvas.width,
                        canvas.height,
                    );
                    return canvas;
                }

                function setFile(input, file) {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }

                async function capture() {
                    if (el.capture.disabled || !el.video.videoWidth || !validator) return;
                    const currentSession = session;
                    el.capture.disabled = true;
                    analysisBusy = true;

                    try {
                        const finalCheck = await validator.validateIdCanvas(drawCrop(800), side);
                        if (currentSession !== session) return;
                        if (finalCheck.overall === 'FAIL') {
                            goodFrames = 0;
                            const status = validator.getLiveCaptureStatus(finalCheck, 0, goodFramesRequired);
                            setGuideState(status.state);
                            setStatus(status.message);
                            return;
                        }

                        const canvas = drawCrop();
                        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.88));
                        if (currentSession !== session) return;
                        if (!blob) throw new Error('The camera frame could not be saved.');

                        const file = new File([blob], `id-${side}-${Date.now()}.jpg`, { type: 'image/jpeg' });
                        pendingFile = file;
                        pendingPreviewUrl = URL.createObjectURL(file);
                        el.reviewImage.src = pendingPreviewUrl;
                        el.reviewImage.alt = `Captured ${side} of ID`;
                        pauseCamera();
                        el.stageLabel.textContent = 'Review this photo:';
                        setStatus('Check that the photo is clear and readable. Retake it or continue.');
                        show(el.livePanel, false);
                        show(el.reviewPanel, true);
                        show(el.capture, false);
                    } catch (error) {
                        if (currentSession === session) {
                            setGuideState('invalid');
                            setError('Could not check or capture this side. Upload a photo instead.');
                        }
                    } finally {
                        if (currentSession === session) {
                            analysisBusy = false;
                            if (stream && !liveCheckFailed) {
                                scheduleFrameCheck(currentSession);
                            }
                        }
                    }
                }

                async function applyFrameResult(result) {
                    if (result.overall === 'FAIL') {
                        goodFrames = 0;
                    } else {
                        goodFrames++;
                    }

                    const status = validator.getLiveCaptureStatus(result, goodFrames, goodFramesRequired);
                    setGuideState(status.state);
                    setStatus(status.message);
                    el.capture.disabled = !status.canCapture;
                    if (status.canCapture) {
                        setError('');
                        await capture();
                    }
                }

                function scheduleFrameCheck(currentSession) {
                    if (frameRequest !== null || liveCheckFailed) return;

                    frameRequest = requestAnimationFrame(async () => {
                        frameRequest = null;
                        if (currentSession !== session || !stream || liveCheckFailed) return;

                        if (el.video.readyState < 2 || !el.video.videoWidth || analysisBusy
                            || performance.now() - lastCheckAt < checkInterval) {
                            scheduleFrameCheck(currentSession);
                            return;
                        }

                        analysisBusy = true;
                        lastCheckAt = performance.now();
                        try {
                            const result = await validator.validateIdCanvas(drawCrop(800), side);
                            if (currentSession === session) await applyFrameResult(result);
                        } catch (error) {
                            if (currentSession === session) {
                                liveCheckFailed = true;
                                el.capture.disabled = true;
                                setGuideState('invalid');
                                setStatus('Live checking is unavailable. Use Upload photos instead.');
                                setError('We could not check the camera frame on this device.');
                            }
                        } finally {
                            if (currentSession === session) {
                                analysisBusy = false;
                                if (stream && !liveCheckFailed) {
                                    scheduleFrameCheck(currentSession);
                                }
                            }
                        }
                    });
                }

                async function openCamera() {
                    const currentSession = session;
                    setError('');

                    if (!navigator.mediaDevices?.getUserMedia) {
                        setError('Camera access is unavailable in this browser. Choose Upload photos instead.');
                        setGuideState('invalid');
                        return;
                    }

                    setStatus('Opening camera...');
                    setGuideState('checking');
                    el.capture.disabled = true;
                    let camera;
                    try {
                        camera = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'environment' } },
                            audio: false,
                        });
                        if (currentSession !== session) {
                            camera.getTracks().forEach((track) => track.stop());
                            return;
                        }

                        stream = camera;
                        el.video.srcObject = camera;
                        await el.video.play();
                        if (currentSession !== session) return;
                    } catch (error) {
                        if (currentSession !== session) return;
                        pauseCamera();
                        setError('Camera unavailable. Allow camera access, or upload both photos instead.');
                        setGuideState('invalid');
                        return;
                    }

                    try {
                        if (typeof window.idImageValidatorReady !== 'function') {
                            throw new Error('The on-device ID check is unavailable.');
                        }
                        validator = await window.idImageValidatorReady();
                        if (currentSession !== session) return;

                        goodFrames = 0;
                        lastCheckAt = 0;
                        liveCheckFailed = false;
                        setError('');
                        setSide(side);
                        scheduleFrameCheck(currentSession);
                    } catch (error) {
                        if (currentSession !== session) return;
                        liveCheckFailed = true;
                        el.capture.disabled = true;
                        setGuideState('invalid');
                        setStatus('Live checking is unavailable. Use Upload photos instead.');
                        setError('The on-device ID check could not be loaded.');
                    }
                }

                async function startCamera() {
                    clearReview();
                    stopCamera();
                    setError('');
                    const hasFront = frontInput.files.length > 0
                        || Boolean(frontInput.closest('[data-upload]')?.dataset.savedPreview);
                    const hasBack = backInput.files.length > 0
                        || Boolean(backInput.closest('[data-upload]')?.dataset.savedPreview);
                    side = hasFront && !hasBack ? 'back' : 'front';
                    setSide(side);
                    setMethod(true);
                    await openCamera();
                }

                async function retake() {
                    clearReview();
                    setSide(side);
                    await openCamera();
                }

                function next() {
                    if (!pendingFile) return;

                    if (side === 'front') {
                        capturedFrontFile = pendingFile;
                        clearReview();
                        setSide('back');
                        openCamera();
                        return;
                    }

                    if (capturedFrontFile) {
                        setFile(frontInput, capturedFrontFile);
                    }
                    setFile(backInput, pendingFile);
                    clearReview();
                    stopCamera();
                    setMethod(false);
                    setError('');
                }

                el.upload.addEventListener('click', close);
                el.cameraTab.addEventListener('click', startCamera);
                el.capture.addEventListener('click', capture);
                el.cancel.addEventListener('click', close);
                el.retake.addEventListener('click', retake);
                el.next.addEventListener('click', next);

                const step = root.closest('[data-panel="2"]');
                if (step) {
                    const syncStep = () => {
                        if (step.classList.contains('hidden')) {
                            close();
                        } else if (![frontInput, backInput].every(input =>
                            input.files.length || input.closest('[data-upload]')?.dataset.savedPreview)) {
                            startCamera();
                        } else {
                            setMethod(false);
                        }
                    };
                    syncStep();
                    new MutationObserver(syncStep).observe(step, { attributes: true, attributeFilter: ['class'] });
                }

                window.addEventListener('pagehide', close);
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) close();
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialize, { once: true });
            } else {
                initialize();
            }
        })();
    </script>
@endonce
