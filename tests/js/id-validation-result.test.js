import assert from 'node:assert/strict';
import test from 'node:test';
import { getLiveCaptureStatus, mapValidationResult } from '../../resources/js/identity/id-validation-result.js';

test('PASS results show a pass icon and allow continuing without manual review', () => {
    const result = mapValidationResult({
        SHARPNESS: { result: 'PASS', reason: 'Image edges are sufficiently sharp.' },
    });

    assert.equal(result.rows[0].icon, '✓');
    assert.equal(result.canContinue, true);
    assert.equal(result.requiresManualReview, false);
});

test('FAIL results show a fail icon and block continuing', () => {
    const result = mapValidationResult({
        ALIGNMENT: { result: 'FAIL', reason: 'The card is tilted more than about 15 degrees.' },
    });

    assert.equal(result.rows[0].icon, '×');
    assert.equal(result.overall, 'FAIL');
    assert.equal(result.canContinue, false);
});

test('REVIEW results allow continuing and require manual review', () => {
    const result = mapValidationResult({
        NO_VISIBLE_EDITING: { result: 'REVIEW', reason: 'This check needs staff review.' },
    });

    assert.equal(result.rows[0].checkLabel, 'Visible editing');
    assert.equal(result.rows[0].statusLabel, 'Needs staff review');
    assert.equal(result.rows[0].icon, '!');
    assert.equal(result.canContinue, true);
    assert.equal(result.requiresManualReview, true);
});

test('live capture stays disabled until enough passing frames are checked', () => {
    const result = getLiveCaptureStatus({
        overall: 'PASS',
        user_message: 'The image quality looks good.',
    }, 2);

    assert.equal(result.state, 'checking');
    assert.equal(result.canCapture, false);
    assert.match(result.message, /Hold steady/);
});

test('live capture signals automatic capture after consecutive passing frames', () => {
    const result = getLiveCaptureStatus({
        overall: 'PASS',
        user_message: 'The image quality looks good.',
    }, 3);

    assert.equal(result.state, 'valid');
    assert.equal(result.canCapture, true);
    assert.equal(result.message, 'Perfect position. Capturing automatically.');
});

test('live capture remains disabled and shows the fix for a failed frame', () => {
    const result = getLiveCaptureStatus({
        overall: 'FAIL',
        user_message: 'Straighten the ID before trying again.',
    }, 3);

    assert.equal(result.state, 'invalid');
    assert.equal(result.canCapture, false);
    assert.equal(result.message, 'Straighten the ID before trying again.');
});
