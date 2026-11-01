const STATE_CLASSES = [
    'border-emerald-500',
    'border-red-500',
    'focus:border-emerald-400',
    'focus:border-red-500',
    'focus:ring-emerald-400/10',
    'focus:ring-red-500/10',
];

const VALIDATABLE_FIELDS =
    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]), select, textarea';

function updateInputState(input, valid) {
    input.classList.remove(...STATE_CLASSES);

    // Keep the default appearance for empty or untouched fields.
    if (valid === null || !input.value.trim()) {
        return;
    }

    input.classList.add(
        valid ? 'border-emerald-500' : 'border-red-500',
        valid ? 'focus:border-emerald-400' : 'focus:border-red-500',
        valid ? 'focus:ring-emerald-400/10' : 'focus:ring-red-500/10'
    );
}

/**
 * Registration password strength requirements.
 */
function isStrongPassword(value) {
    return (
        value.length >= 8 &&
        value.length <= 16 &&
        /[A-Z]/.test(value) &&
        /[a-z]/.test(value) &&
        /[0-9]/.test(value) &&
        /[^A-Za-z0-9]/.test(value)
    );
}

function isConfirmationField(input) {
    return (
        input.name === 'password_confirmation' ||
        input.hasAttribute('data-password-confirmation')
    );
}

// Strength applies when explicitly requested, or to the main password
// field of a form that also has a confirmation field (registration).
function needsStrengthCheck(input, form) {
    if (input.type !== 'password') return false;
    if (input.hasAttribute('data-password-strength')) return true;

    return (
        input.name === 'password' &&
        form.querySelector('[name="password_confirmation"]') !== null
    );
}

/**
 * Validate an individual field.
 */
function validateInput(input, form) {
    const value = input.value;

    // Keep empty fields neutral until they contain a value.
    if (!value.trim()) {
        updateInputState(input, null);
        return !input.required;
    }

    let valid;

    if (input.name === 'name') {
        valid = true; // non-empty is already guaranteed above
    } else if (input.type === 'email') {
        valid = input.validity.valid;
    } else if (isConfirmationField(input)) {
        // Must run before the strength check: the confirmation field is
        // also a password input.
        const password = form.querySelector('[name="password"]');
        valid = Boolean(password && value === password.value);
    } else if (needsStrengthCheck(input, form)) {
        valid = isStrongPassword(value);
    } else {
        // Generic validation for other fields and form types.
        valid = input.validity.valid;
    }

    updateInputState(input, valid);

    return valid;
}

/**
 * Enable reusable validation for forms marked with data-validate.
 */
document.querySelectorAll('form[data-validate]').forEach((form) => {
    const fields = form.querySelectorAll(VALIDATABLE_FIELDS);
    const password = form.querySelector('[name="password"]');
    const confirmation = form.querySelector('[name="password_confirmation"]');

    fields.forEach((input) => {
        // Validate while typing.
        input.addEventListener('input', () => {
            validateInput(input, form);

            // Recheck confirmation when the password changes.
            if (input === password && confirmation && confirmation.value) {
                validateInput(confirmation, form);
            }
        });

        // Validate after leaving a field.
        input.addEventListener('blur', () => validateInput(input, form));
    });

    // Recheck all fields when the form is submitted.
    form.addEventListener('submit', () => {
        fields.forEach((input) => validateInput(input, form));
    });
});