const checkLabels = {
    DOCUMENT_PRESENT: 'ID card detected',
    FACE_VISIBLE: 'Face visible on the ID',
    CORRECT_SIDE: 'Correct side',
    POSITION_AND_FRAMING: 'Position and framing',
    ALIGNMENT: 'Alignment',
    LIGHTING: 'Lighting',
    GLARE: 'Glare',
    SHARPNESS: 'Sharpness',
    OBSTRUCTIONS: 'Obstructions',
    READABILITY: 'Readability',
    NOT_EXPIRED: 'Expiry date',
    ORIGINAL_CAPTURE: 'Original capture',
    NO_VISIBLE_EDITING: 'Visible editing',
};

const statusPresentation = {
    PASS: {
        icon: '✓',
        label: 'Pass',
        classes: 'text-(--green-700) dark:text-(--green-300)',
    },
    FAIL: {
        icon: '×',
        label: 'Fail',
        classes: 'text-(--red-600) dark:text-(--red-400)',
    },
    REVIEW: {
        icon: '!',
        label: 'Needs staff review',
        classes: 'text-(--amber-700) dark:text-(--amber-300)',
    },
};

export function mapValidationResult(checks) {
    const rows = Object.entries(checks).map(([key, check]) => {
        const presentation = statusPresentation[check.result];
        if (!presentation) {
            throw new TypeError(`Unsupported ID validation result: ${check.result}`);
        }

        return {
            key,
            checkLabel: checkLabels[key] || key.replaceAll('_', ' ').toLowerCase(),
            result: check.result,
            reason: check.reason,
            icon: presentation.icon,
            statusLabel: presentation.label,
            classes: presentation.classes,
        };
    });
    const hasFailure = rows.some((row) => row.result === 'FAIL');
    const requiresManualReview = rows.some((row) => row.result === 'REVIEW');

    return {
        rows,
        overall: hasFailure ? 'FAIL' : 'PASS',
        canContinue: !hasFailure,
        requiresManualReview,
    };
}

export function getLiveCaptureStatus(result, consecutiveGoodFrames, requiredGoodFrames = 3) {
    if (result.overall === 'FAIL') {
        return {
            state: 'invalid',
            canCapture: false,
            message: result.user_message,
        };
    }

    if (consecutiveGoodFrames < requiredGoodFrames) {
        return {
            state: 'checking',
            canCapture: false,
            message: `Hold steady while we check the ID (${consecutiveGoodFrames}/${requiredGoodFrames}).`,
        };
    }

    return {
        state: 'valid',
        canCapture: true,
        message: 'Perfect position. Capturing automatically.',
    };
}
