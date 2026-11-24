import { getLiveCaptureStatus, mapValidationResult } from './id-validation-result.js';

export { getLiveCaptureStatus, mapValidationResult };

const MAX_SOURCE_DIMENSION = 2400;
const MAX_ANALYSIS_WIDTH = 640;
const MAX_ANALYSIS_HEIGHT = 900;
const MORPHOLOGY_RADIUS = 1;
const CARD_AREA_MIN = 0.2;
const FRAMING_AREA_MIN = 0.6;
const FRAMING_AREA_MAX = 0.9;
const WHOLE_FRAME_AREA_MIN = 0.98;
const CARD_ASPECT_MIN = 1.4;
const CARD_ASPECT_MAX = 1.7;
const MAX_TILT_DEGREES = 15;
const MIN_BRIGHTNESS = 55;
const MAX_BRIGHTNESS = 225;
const MIN_LIGHTING_SAMPLE_RATIO = 0.5;
const DARK_PIXEL_LIMIT = 35;
const DARK_PIXEL_RATIO_LIMIT = 0.25;
const GLARE_PIXEL_LIMIT = 250;
const GLARE_RATIO_LIMIT = 0.12;
const MIN_SHARPNESS = 80;
const MIN_EDGE_MAGNITUDE = 52;
const MIN_EDGE_PIXELS = 120;
const MIN_FRAME_EDGE_RATIO = 0.01;
const MIN_OBSTRUCTION_DARK_RATIO = 0.18;
const OBSTRUCTION_DARK_LIMIT = 28;
const OBSTRUCTION_INSET_RATIO = 0.2;
const FACE_DETECTION_CONFIDENCE = 0.6;
const VISION = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14';
const FACE_MODEL =
    'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite';
const CHECK_NAMES = [
    'DOCUMENT_PRESENT',
    'FACE_VISIBLE',
    'CORRECT_SIDE',
    'POSITION_AND_FRAMING',
    'ALIGNMENT',
    'LIGHTING',
    'GLARE',
    'SHARPNESS',
    'OBSTRUCTIONS',
    'READABILITY',
    'NOT_EXPIRED',
    'ORIGINAL_CAPTURE',
    'NO_VISIBLE_EDITING',
];

const IS_DEV = Boolean(import.meta.env?.DEV);
let detectorPromise;

function reviewChecks(reason = 'This check needs staff review.') {
    return Object.fromEntries(CHECK_NAMES.map((name) => [name, { result: 'REVIEW', reason }]));
}

function createResult(checks, userMessage, debug = null) {
    const mapped = mapValidationResult(checks);
    const result = {
        ...mapped,
        document_type_detected: null,
        side_detected: 'unknown',
        checks,
        user_message: userMessage,
    };

    if (IS_DEV && debug) {
        result.debug = debug;
    }

    return result;
}

async function readImage(file) {
    if (typeof createImageBitmap === 'function') {
        try {
            return await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch {
            try {
                return await createImageBitmap(file);
            } catch {
                throw new Error('This image could not be opened. Choose a readable JPG or PNG file.');
            }
        }
    }

    return new Promise((resolve, reject) => {
        const image = new Image();
        const url = URL.createObjectURL(file);
        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('This image could not be opened. Choose a readable JPG or PNG file.'));
        };
        image.src = url;
    });
}

function makeCanvas(width, height) {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    if (!canvas.getContext('2d')) {
        throw new Error('Image processing is unavailable in this browser.');
    }

    return canvas;
}

function normalizeImage(image) {
    const scale = Math.min(1, MAX_SOURCE_DIMENSION / Math.max(image.width, image.height));
    const canvas = makeCanvas(
        Math.max(1, Math.round(image.width * scale)),
        Math.max(1, Math.round(image.height * scale)),
    );
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
    image.close?.();

    return canvas;
}

function resizeForAnalysis(canvas) {
    const scale = Math.min(1, MAX_ANALYSIS_WIDTH / canvas.width, MAX_ANALYSIS_HEIGHT / canvas.height);
    const resized = makeCanvas(
        Math.max(1, Math.round(canvas.width * scale)),
        Math.max(1, Math.round(canvas.height * scale)),
    );
    resized.getContext('2d').drawImage(canvas, 0, 0, resized.width, resized.height);

    return resized;
}

function imageMetrics(gray, width, height) {
    const pixelCount = gray.length;
    let totalBrightness = 0;
    let lightingBrightness = 0;
    let lightingPixels = 0;
    let darkPixels = 0;
    let glarePixels = 0;

    for (const value of gray) {
        totalBrightness += value;
        if (value <= GLARE_PIXEL_LIMIT) {
            lightingBrightness += value;
            lightingPixels++;
        }
        if (value < DARK_PIXEL_LIMIT) darkPixels++;
        if (value >= GLARE_PIXEL_LIMIT) glarePixels++;
    }

    let laplacianTotal = 0;
    let laplacianSquaredTotal = 0;
    let laplacianCount = 0;
    for (let y = 1; y < height - 1; y++) {
        for (let x = 1; x < width - 1; x++) {
            const index = y * width + x;
            const laplacian = 4 * gray[index]
                - gray[index - 1]
                - gray[index + 1]
                - gray[index - width]
                - gray[index + width];
            laplacianTotal += laplacian;
            laplacianSquaredTotal += laplacian * laplacian;
            laplacianCount++;
        }
    }

    const meanLaplacian = laplacianTotal / Math.max(1, laplacianCount);
    const sharpness = laplacianSquaredTotal / Math.max(1, laplacianCount) - meanLaplacian ** 2;

    return {
        brightness: lightingPixels > pixelCount * MIN_LIGHTING_SAMPLE_RATIO
            ? lightingBrightness / lightingPixels
            : totalBrightness / pixelCount,
        darkRatio: darkPixels / pixelCount,
        glareRatio: glarePixels / pixelCount,
        sharpness,
    };
}

function grayscale(imageData) {
    const gray = new Uint8Array(imageData.width * imageData.height);

    for (let index = 0; index < gray.length; index++) {
        const pixelIndex = index * 4;
        gray[index] = Math.round(
            imageData.data[pixelIndex] * 0.299
            + imageData.data[pixelIndex + 1] * 0.587
            + imageData.data[pixelIndex + 2] * 0.114,
        );
    }

    return gray;
}

function findEdges(gray, width, height) {
    const edges = new Uint8Array(gray.length);

    for (let y = 1; y < height - 1; y++) {
        for (let x = 1; x < width - 1; x++) {
            const index = y * width + x;
            const upperLeft = gray[index - width - 1];
            const upper = gray[index - width];
            const upperRight = gray[index - width + 1];
            const left = gray[index - 1];
            const right = gray[index + 1];
            const lowerLeft = gray[index + width - 1];
            const lower = gray[index + width];
            const lowerRight = gray[index + width + 1];
            const horizontal = -upperLeft - 2 * left - lowerLeft + upperRight + 2 * right + lowerRight;
            const vertical = -upperLeft - 2 * upper - upperRight + lowerLeft + 2 * lower + lowerRight;

            if ((Math.abs(horizontal) + Math.abs(vertical)) / 4 >= MIN_EDGE_MAGNITUDE) {
                edges[index] = 1;
            }
        }
    }

    return edges;
}

function closeEdges(edges, width, height) {
    const dilated = new Uint8Array(edges.length);
    const closed = new Uint8Array(edges.length);

    for (let y = MORPHOLOGY_RADIUS; y < height - MORPHOLOGY_RADIUS; y++) {
        for (let x = MORPHOLOGY_RADIUS; x < width - MORPHOLOGY_RADIUS; x++) {
            const index = y * width + x;
            for (let offsetY = -MORPHOLOGY_RADIUS; offsetY <= MORPHOLOGY_RADIUS && !dilated[index]; offsetY++) {
                for (let offsetX = -MORPHOLOGY_RADIUS; offsetX <= MORPHOLOGY_RADIUS; offsetX++) {
                    if (edges[index + offsetY * width + offsetX]) {
                        dilated[index] = 1;
                        break;
                    }
                }
            }
        }
    }

    for (let y = MORPHOLOGY_RADIUS; y < height - MORPHOLOGY_RADIUS; y++) {
        for (let x = MORPHOLOGY_RADIUS; x < width - MORPHOLOGY_RADIUS; x++) {
            const index = y * width + x;
            let filled = true;
            for (let offsetY = -MORPHOLOGY_RADIUS; offsetY <= MORPHOLOGY_RADIUS && filled; offsetY++) {
                for (let offsetX = -MORPHOLOGY_RADIUS; offsetX <= MORPHOLOGY_RADIUS; offsetX++) {
                    if (!dilated[index + offsetY * width + offsetX]) {
                        filled = false;
                        break;
                    }
                }
            }
            closed[index] = Number(filled);
        }
    }

    return closed;
}

function componentRegions(edges, width, height) {
    const visited = new Uint8Array(edges.length);
    const queue = new Int32Array(edges.length);
    const regions = [];

    for (let start = 0; start < edges.length; start++) {
        if (!edges[start] || visited[start]) continue;

        let head = 0;
        let tail = 0;
        let minX = width;
        let minY = height;
        let maxX = -1;
        let maxY = -1;
        let sumX = 0;
        let sumY = 0;
        let sumXX = 0;
        let sumYY = 0;
        let sumXY = 0;
        queue[tail++] = start;
        visited[start] = 1;

        while (head < tail) {
            const index = queue[head++];
            const x = index % width;
            const y = Math.floor(index / width);
            minX = Math.min(minX, x);
            minY = Math.min(minY, y);
            maxX = Math.max(maxX, x);
            maxY = Math.max(maxY, y);
            sumX += x;
            sumY += y;
            sumXX += x * x;
            sumYY += y * y;
            sumXY += x * y;

            for (let offsetY = -1; offsetY <= 1; offsetY++) {
                for (let offsetX = -1; offsetX <= 1; offsetX++) {
                    if (offsetX === 0 && offsetY === 0) continue;
                    const neighborX = x + offsetX;
                    const neighborY = y + offsetY;
                    if (neighborX < 0 || neighborY < 0 || neighborX >= width || neighborY >= height) continue;
                    const neighbor = neighborY * width + neighborX;
                    if (edges[neighbor] && !visited[neighbor]) {
                        visited[neighbor] = 1;
                        queue[tail++] = neighbor;
                    }
                }
            }
        }

        if (tail < MIN_EDGE_PIXELS) continue;

        const centerX = sumX / tail;
        const centerY = sumY / tail;
        const covarianceX = sumXX / tail - centerX ** 2;
        const covarianceY = sumYY / tail - centerY ** 2;
        const covarianceXY = sumXY / tail - centerX * centerY;
        const angle = 0.5 * Math.atan2(2 * covarianceXY, covarianceX - covarianceY);
        const cosine = Math.cos(angle);
        const sine = Math.sin(angle);
        let minU = Infinity;
        let maxU = -Infinity;
        let minV = Infinity;
        let maxV = -Infinity;

        for (let point = 0; point < tail; point++) {
            const index = queue[point];
            const x = index % width;
            const y = Math.floor(index / width);
            const u = x * cosine + y * sine;
            const v = -x * sine + y * cosine;
            minU = Math.min(minU, u);
            maxU = Math.max(maxU, u);
            minV = Math.min(minV, v);
            maxV = Math.max(maxV, v);
        }

        const axisWidth = maxX - minX + 1;
        const axisHeight = maxY - minY + 1;
        const rotatedWidth = maxU - minU + 1;
        const rotatedHeight = maxV - minV + 1;
        const angleDegrees = Math.abs(angle * 180 / Math.PI) % 90;
        const tilt = Math.min(angleDegrees, 90 - angleDegrees);

        regions.push({
            edgePixels: tail,
            x: minX,
            y: minY,
            width: axisWidth,
            height: axisHeight,
            axisAreaRatio: axisWidth * axisHeight / edges.length,
            axisAspectRatio: Math.max(axisWidth, axisHeight) / Math.max(1, Math.min(axisWidth, axisHeight)),
            rotatedAreaRatio: rotatedWidth * rotatedHeight / edges.length,
            rotatedAspectRatio: Math.max(rotatedWidth, rotatedHeight) / Math.max(1, Math.min(rotatedWidth, rotatedHeight)),
            tilt,
        });
    }

    return regions.sort((first, second) => second.edgePixels - first.edgePixels);
}

function isCardRegion(areaRatio, aspectRatio) {
    return areaRatio >= CARD_AREA_MIN
        && aspectRatio >= CARD_ASPECT_MIN
        && aspectRatio <= CARD_ASPECT_MAX;
}

function detectCard(gray, width, height, source) {
    const edges = closeEdges(findEdges(gray, width, height), width, height);
    const regions = componentRegions(edges, width, height);

    for (const region of regions) {
        if (isCardRegion(region.axisAreaRatio, region.axisAspectRatio)) {
            return {
                ...region,
                areaRatio: region.axisAreaRatio,
                aspectRatio: region.axisAspectRatio,
                detectionMethod: 'largest region bounding rectangle',
            };
        }
    }

    for (const region of regions) {
        if (isCardRegion(region.rotatedAreaRatio, region.rotatedAspectRatio)) {
            return {
                ...region,
                areaRatio: region.rotatedAreaRatio,
                aspectRatio: region.rotatedAspectRatio,
                detectionMethod: 'rotated bounding rectangle',
            };
        }
    }

    const frameAspectRatio = Math.max(width, height) / Math.max(1, Math.min(width, height));
    const edgeRatio = edges.reduce((total, value) => total + value, 0) / edges.length;
    if (frameAspectRatio >= CARD_ASPECT_MIN
        && frameAspectRatio <= CARD_ASPECT_MAX
        && (source === 'camera' || edgeRatio >= MIN_FRAME_EDGE_RATIO)) {
        return {
            x: 0,
            y: 0,
            width,
            height,
            areaRatio: 1,
            aspectRatio: frameAspectRatio,
            tilt: 0,
            detectionMethod: source === 'camera' ? 'camera guide-frame region' : 'whole image',
        };
    }

    return null;
}

function getFaceDetector() {
    if (!detectorPromise) {
        detectorPromise = import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/vision_bundle.mjs')
            .then((vision) => vision.FilesetResolver.forVisionTasks(`${VISION}/wasm`)
                .then((files) => vision.FaceDetector.createFromOptions(files, {
                    baseOptions: { modelAssetPath: FACE_MODEL },
                    runningMode: 'IMAGE',
                    minDetectionConfidence: FACE_DETECTION_CONFIDENCE,
                })))
            .catch((error) => {
                detectorPromise = null;
                throw error;
            });
    }

    return detectorPromise;
}

function cropToCard(canvas, card) {
    if (!card) return canvas;
    const crop = makeCanvas(card.width, card.height);
    crop.getContext('2d').drawImage(
        canvas,
        card.x,
        card.y,
        card.width,
        card.height,
        0,
        0,
        card.width,
        card.height,
    );

    return crop;
}

async function faceCheck(canvas, card, imageQualityFailed) {
    try {
        const detector = await getFaceDetector();
        const detections = detector.detect(cropToCard(canvas, card)).detections || [];

        if (detections.length === 1) {
            return { result: 'PASS', reason: 'Exactly one face was detected on the ID.' };
        }

        if (imageQualityFailed) {
            return { result: 'REVIEW', reason: 'Image quality may affect face detection; staff will review it.' };
        }

        return {
            result: 'FAIL',
            reason: detections.length === 0
                ? 'No face was detected on the ID.'
                : 'More than one face was detected on the ID.',
        };
    } catch {
        return { result: 'REVIEW', reason: 'Face detection is unavailable on this device; staff will review it.' };
    }
}

function obstructionCheck(gray, width, card, imageQualityFailed) {
    if (!card || imageQualityFailed) {
        return { result: 'REVIEW', reason: 'Obstructions could not be assessed reliably in this image.' };
    }

    const left = Math.max(0, Math.round(card.x + card.width * OBSTRUCTION_INSET_RATIO));
    const right = Math.min(width, Math.round(card.x + card.width * (1 - OBSTRUCTION_INSET_RATIO)));
    const top = Math.max(0, Math.round(card.y + card.height * OBSTRUCTION_INSET_RATIO));
    const bottom = Math.min(gray.length / width, Math.round(card.y + card.height * (1 - OBSTRUCTION_INSET_RATIO)));
    const regionWidth = Math.max(1, right - left);
    const regionHeight = Math.max(1, bottom - top);
    let darkPixels = 0;

    for (let y = top; y < bottom; y++) {
        for (let x = left; x < right; x++) {
            if (gray[y * width + x] < OBSTRUCTION_DARK_LIMIT) darkPixels++;
        }
    }

    const darkRatio = darkPixels / (regionWidth * regionHeight);
    return darkRatio > MIN_OBSTRUCTION_DARK_RATIO
        ? { result: 'FAIL', reason: 'A large dark area may be covering the ID.' }
        : { result: 'PASS', reason: 'No large dark obstruction was detected.' };
}

function mainFix(checks, card) {
    if (checks.DOCUMENT_PRESENT.result === 'FAIL') return 'Place a clear photo of the whole ID in the frame.';
    if (checks.POSITION_AND_FRAMING.result === 'FAIL') {
        return card.areaRatio < FRAMING_AREA_MIN
            ? 'Move closer so the ID fills more of the frame.'
            : 'Move back slightly so the ID has space around it.';
    }
    if (checks.ALIGNMENT.result === 'FAIL') return 'Straighten the ID before trying again.';
    if (checks.LIGHTING.result === 'FAIL') return 'Move to a brighter spot with even lighting.';
    if (checks.GLARE.result === 'FAIL') return 'Adjust the angle to move reflections away from the ID.';
    if (checks.SHARPNESS.result === 'FAIL') return 'Hold the camera steady and retake the photo.';
    if (checks.OBSTRUCTIONS.result === 'FAIL') return 'Move anything covering the ID and take another photo.';
    if (checks.FACE_VISIBLE.result === 'FAIL') return 'Use the side of the ID with one clearly visible face.';

    return 'The image quality looks good. Some details still need staff review.';
}

async function analyzeCanvas(sourceCanvas, source, side = 'front') {
    const canvas = resizeForAnalysis(sourceCanvas);
    const context = canvas.getContext('2d', { willReadFrequently: true });
    if (!context) throw new Error('Image processing is unavailable in this browser.');

    let pixels;
    try {
        pixels = context.getImageData(0, 0, canvas.width, canvas.height);
    } catch {
        throw new Error('This image could not be read on your device. Choose another JPG or PNG file.');
    }

    const gray = grayscale(pixels);
    const metrics = imageMetrics(gray, canvas.width, canvas.height);
    const card = detectCard(gray, canvas.width, canvas.height, source);
    const lightingFailed = metrics.brightness < MIN_BRIGHTNESS
        || metrics.brightness > MAX_BRIGHTNESS
        || metrics.darkRatio > DARK_PIXEL_RATIO_LIMIT;
    const glareFailed = metrics.glareRatio > GLARE_RATIO_LIMIT;
    const sharpnessFailed = metrics.sharpness < MIN_SHARPNESS;
    const imageQualityFailed = lightingFailed || glareFailed || sharpnessFailed;
    const checks = reviewChecks();
    checks.DOCUMENT_PRESENT = card
        ? { result: 'PASS', reason: 'A card-shaped ID region was detected.' }
        : imageQualityFailed
            ? { result: 'REVIEW', reason: 'Poor image quality prevented reliable card detection; staff will review it.' }
            : { result: 'FAIL', reason: 'No card-shaped ID region was detected.' };
    checks.POSITION_AND_FRAMING = !card
        ? { result: 'REVIEW', reason: 'No card region was found; staff will review the framing.' }
        : card.areaRatio >= WHOLE_FRAME_AREA_MIN
            ? { result: 'PASS', reason: 'The card fills the frame and is not cropped further.' }
            : card.areaRatio < FRAMING_AREA_MIN
                ? { result: 'FAIL', reason: 'The ID is too small in the frame.' }
                : card.areaRatio > FRAMING_AREA_MAX
                    ? { result: 'FAIL', reason: 'The ID is too close to the frame edges.' }
                    : { result: 'PASS', reason: 'The card fills about 60–90% of the image.' };
    checks.ALIGNMENT = !card
        ? { result: 'REVIEW', reason: 'Alignment could not be assessed without a detected card.' }
        : card.tilt > MAX_TILT_DEGREES
            ? { result: 'FAIL', reason: 'The card is tilted more than about 15 degrees.' }
            : { result: 'PASS', reason: 'The card is within about 15 degrees of level.' };
    checks.LIGHTING = lightingFailed
        ? { result: 'FAIL', reason: 'The image is too dark or overexposed.' }
        : { result: 'PASS', reason: 'Overall brightness is within a readable range.' };
    checks.GLARE = glareFailed
        ? { result: 'FAIL', reason: 'Too many near-white pixels may hide ID details.' }
        : { result: 'PASS', reason: 'Near-white glare is within the acceptable range.' };
    checks.SHARPNESS = lightingFailed
        ? { result: 'REVIEW', reason: 'Lighting prevents a reliable sharpness assessment.' }
        : sharpnessFailed
            ? { result: 'FAIL', reason: 'The image edges look blurred.' }
            : { result: 'PASS', reason: 'Image edges are sufficiently sharp.' };
    checks.OBSTRUCTIONS = obstructionCheck(gray, canvas.width, card, imageQualityFailed);
    checks.FACE_VISIBLE = side === 'back'
        ? { result: 'REVIEW', reason: 'Face detection applies to the front of the ID; staff will review this side.' }
        : await faceCheck(canvas, card, imageQualityFailed);
    checks.CORRECT_SIDE = { result: 'REVIEW', reason: 'Staff must confirm the correct side of the ID.' };
    checks.READABILITY = { result: 'REVIEW', reason: 'Staff must confirm the text is readable.' };
    checks.NOT_EXPIRED = { result: 'REVIEW', reason: 'Staff must confirm the expiry date.' };
    checks.ORIGINAL_CAPTURE = { result: 'REVIEW', reason: 'Staff must confirm the image is an original capture.' };
    checks.NO_VISIBLE_EDITING = { result: 'REVIEW', reason: 'Staff must check the image for visible editing.' };

    const debug = {
        brightness: { value: Number(metrics.brightness.toFixed(2)), min: MIN_BRIGHTNESS, max: MAX_BRIGHTNESS },
        glareRatio: { value: Number(metrics.glareRatio.toFixed(4)), max: GLARE_RATIO_LIMIT },
        blurScore: { value: Number(metrics.sharpness.toFixed(2)), min: MIN_SHARPNESS },
        darkPixelRatio: { value: Number(metrics.darkRatio.toFixed(4)), max: DARK_PIXEL_RATIO_LIMIT },
        cardAreaPercent: {
            value: card ? Number((card.areaRatio * 100).toFixed(2)) : null,
            minDetected: CARD_AREA_MIN * 100,
            framingMin: FRAMING_AREA_MIN * 100,
            framingMax: FRAMING_AREA_MAX * 100,
            wholeFrameMin: WHOLE_FRAME_AREA_MIN * 100,
        },
        aspectRatio: {
            value: card ? Number(card.aspectRatio.toFixed(3)) : null,
            min: CARD_ASPECT_MIN,
            max: CARD_ASPECT_MAX,
        },
        tiltDegrees: {
            value: card ? Number(card.tilt.toFixed(2)) : null,
            max: MAX_TILT_DEGREES,
        },
        detectionMethod: card?.detectionMethod || 'not detected',
    };

    return createResult(checks, mainFix(checks, card), debug);
}

export function validateIdCanvas(canvas, side = 'front') {
    return analyzeCanvas(canvas, 'camera', side);
}

export async function validateIdImage(file) {
    const extension = file.name.toLowerCase().split('.').pop();
    if (file.type === 'application/pdf' || extension === 'pdf') {
        return createResult(
            reviewChecks('PDF content cannot be checked in this browser; staff will review it.'),
            'This PDF was not checked in the browser. Staff will review the document.',
        );
    }

    if (!['image/jpeg', 'image/png'].includes(file.type) && !['jpg', 'jpeg', 'png'].includes(extension)) {
        throw new Error('Choose a JPG, PNG, or PDF file.');
    }

    let image;
    try {
        image = await readImage(file);
    } catch (error) {
        throw new Error(error.message || 'This image could not be opened. Choose a readable JPG or PNG file.');
    }

    return analyzeCanvas(normalizeImage(image), 'upload');
}
