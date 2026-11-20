// Wait this long (ms) after the last keystroke before validating.
const TYPING_DELAY = 600;

// Fields to validate: all inputs except hidden, buttons, checkbox, radio and file, plus select and textarea.
const VALIDATABLE_FIELDS =
    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]), select, textarea';

// Email must end with a domain extension of 2+ letters, e.g. name@gmail.com.
const EMAIL_PATTERN = /^[^\s@]+@[^\s@.]+(\.[^\s@.]+)*\.[A-Za-z]{2,}$/;

// Well-known providers and the only extensions each one should have.
const KNOWN_PROVIDERS = {
    gmail: ["com"],
    yahoo: ["com", "com.ph"],
    outlook: ["com"],
    hotmail: ["com"],
};

// True when the email has a real extension and no typo on a known provider.
function isValidEmail(value) {
    if (!EMAIL_PATTERN.test(value)) return false;

    const domain = value.split("@")[1].toLowerCase();
    const [provider, ...rest] = domain.split(".");
    const extension = rest.join(".");

    // A known provider with the wrong extension (gmail.con, gmail.co) is a typo.
    return !(provider in KNOWN_PROVIDERS) || KNOWN_PROVIDERS[provider].includes(extension);
}

// Password strength rule: 8-16 characters with upper, lower, number and symbol.
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

// True for the confirm password field.
function isConfirmationField(input) {
    return (
        input.name === "password_confirmation" ||
        input.hasAttribute("data-password-confirmation")
    );
}

// Strength applies with data-password-strength, or to the main password field of a form with a confirmation field.
function needsStrengthCheck(input, form) {
    if (input.type !== "password") return false;
    if (input.hasAttribute("data-password-strength")) return true;

    return (
        input.name === "password" &&
        form.querySelector('[name="password_confirmation"]') !== null
    );
}

// Show or clear the red border (aria-invalid) and the message under the field.
function showError(input, form, message) {
    const hint = form.querySelector(`[data-error-for="${input.name}"]`);

    // The main password field keeps the red border but shows no text.
    const hideText = needsStrengthCheck(input, form);

    if (message) {
        input.setAttribute("aria-invalid", "true");
    } else {
        input.removeAttribute("aria-invalid");
    }

    if (hint) {
        hint.textContent = hideText ? "" : message;
        hint.classList.toggle("hidden", hideText || !message);
    }
}

// Return the error message for a field, or '' when it is valid.
// A custom text from the error-message prop (data-error-message) replaces the default.
function getError(input, form) {
    const value = input.value.trim();
    const custom = input.dataset.errorMessage;

    if (input.name === "name") return "";

    if (input.type === "email") {
        return input.validity.valid && isValidEmail(value)
            ? ""
            : custom || "Enter a valid email address, like name@gmail.com.";
    }

    // Must run before the strength check because the confirmation field is also a password input.
    if (isConfirmationField(input)) {
        const password = form.querySelector('[name="password"]');
        return password && input.value === password.value
            ? ""
            : custom || "Passwords do not match.";
    }

    // Weak passwords are flagged with a red border only (see showError).
    if (needsStrengthCheck(input, form)) {
        return isStrongPassword(input.value) ? "" : custom || "Weak password.";
    }

    // Generic validation for all other fields.
    return input.validity.valid ? "" : custom || input.validationMessage;
}

// Validate one field; empty fields stay neutral. Returns true when valid.
function validateInput(input, form) {
    if (input.disabled) return true;

    const message = input.value.trim() ? getError(input, form) : "";

    showError(input, form, message);

    return message === "";
}

// Enable validation for forms marked with data-validate.
document.querySelectorAll("form[data-validate]").forEach((form) => {
    const fields = form.querySelectorAll(VALIDATABLE_FIELDS);
    const password = form.querySelector('[name="password"]');
    const confirmation = form.querySelector('[name="password_confirmation"]');

    fields.forEach((input) => {
        let timer;

        // Clear the old error while typing, then revalidate once the user stops.
        input.addEventListener("input", () => {
            showError(input, form, "");

            clearTimeout(timer);
            timer = setTimeout(() => {
                validateInput(input, form);

                // Recheck the confirmation field when the password changes.
                if (input === password && confirmation && confirmation.value) {
                    validateInput(confirmation, form);
                }
            }, TYPING_DELAY);
        });

        // Selects may not fire input, so also clear and validate on change.
        input.addEventListener("change", () => {
            showError(input, form, "");
            validateInput(input, form);
        });

        // Validate right away after leaving the field.
        input.addEventListener("blur", () => {
            clearTimeout(timer);
            validateInput(input, form);
        });
    });

    // Recheck all fields when the form is submitted.
    form.addEventListener("submit", () => {
        fields.forEach((input) => validateInput(input, form));
    });
});