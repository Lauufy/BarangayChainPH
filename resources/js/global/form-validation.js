// Wait this long (ms) after the last keystroke before validating.
const TYPING_DELAY = 600;

// Fields to validate: all inputs except hidden, buttons, checkbox, radio and file, plus select and textarea.
const VALIDATABLE_FIELDS =
    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]), select, textarea';

// Basic email format: something@domain.extension (extension is 2+ letters).
const EMAIL_PATTERN = /^[^\s@]+@[^\s@.]+(\.[^\s@.]+)*\.[A-Za-z]{2,}$/;

// Public email providers and the only extensions each one accepts.
// Domain must be exactly provider + extension, e.g. gmail.com, yahoo.com.ph.
const EMAIL_PROVIDERS = {
    gmail: ["com"],
    yahoo: ["com", "com.ph"],
    outlook: ["com"],
    hotmail: ["com"],
};

// Government extensions. Any subdomain under them is accepted (dswd.gov.ph, agency.gov, department.gov.uk).
const GOVERNMENT_EXTENSIONS = ["gov", "gov.ph", "gov.uk", "gov.au", "gov.sg"];

// Default messages.
const MESSAGES = {
    email: "Please enter a valid email address (e.g., juan@gmail.com)",
    mismatch: "Passwords do not match.",
    weak: "Weak password.",
};

// True when the domain is exactly a known provider with one of its extensions.
function isProviderDomain(domain) {
    return Object.entries(EMAIL_PROVIDERS).some(([provider, extensions]) =>
        extensions.some((extension) => domain === `${provider}.${extension}`)
    );
}

// True when the domain is a government extension or any subdomain under it.
function isGovernmentDomain(domain) {
    return GOVERNMENT_EXTENSIONS.some(
        (extension) => domain === extension || domain.endsWith(`.${extension}`)
    );
}

// True when the email is well-formed and its domain is an allowed provider or a government domain.
function isValidEmail(value) {
    if (!EMAIL_PATTERN.test(value)) return false;

    const domain = value.split("@")[1].toLowerCase();

    return isProviderDomain(domain) || isGovernmentDomain(domain);
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
    const hint = form.querySelector(`[data-error-for="${CSS.escape(input.name)}"]`);

    // The main password field keeps the red border but shows no text.
    const hideText = needsStrengthCheck(input, form);

    if (message) {
        input.setAttribute("aria-invalid", "true");
    } else {
        input.removeAttribute("aria-invalid");
    }

    if (hint) {
        if (message && !hideText) {
            hint.id ||= input.id + "-error";
            input.setAttribute("aria-errormessage", hint.id);
        } else {
            input.removeAttribute("aria-errormessage");
        }
        hint.textContent = hideText ? "" : message;
        // Upload errors use empty:hidden so the upload component can also show file errors.
        if (!hint.hasAttribute("data-error")) hint.classList.toggle("hidden", hideText || !message);
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
            : custom || MESSAGES.email;
    }

    // Must run before the strength check because the confirmation field is also a password input.
    if (isConfirmationField(input)) {
        const password = form.querySelector('[name="password"]');
        return password && input.value === password.value
            ? ""
            : custom || MESSAGES.mismatch;
    }

    // Weak passwords are flagged with a red border only (see showError).
    if (needsStrengthCheck(input, form)) {
        return isStrongPassword(input.value) ? "" : custom || MESSAGES.weak;
    }

    // Generic validation for all other fields.
    return input.validity.valid ? "" : custom || input.validationMessage;
}

// Validate one field; empty fields stay neutral. Returns true when valid.
function validateInput(input, form, checkRequired = false) {
    if (!input.willValidate) return true;

    const empty = !input.value.trim();
    let message = "";
    if (checkRequired && (input.validity.badInput || (input.required && empty))) {
        message = input.dataset.errorMessage || input.validationMessage || "Complete this field.";
    } else if (!empty) {
        message = getError(input, form);
    }

    showError(input, form, message);

    return message === "";
}

// The wizard checks a whole panel before focusing its first invalid field.
window.FormValidation = {
    validatePanel(panel) {
        let firstInvalid = null;
        for (const input of panel.querySelectorAll("input, select, textarea")) {
            if (!validateInput(input, input.form, true)) firstInvalid ??= input;
        }
        firstInvalid?.focus();
        return !firstInvalid;
    },
};

// Enable validation for forms marked with data-validate.
document.querySelectorAll("form[data-validate]").forEach((form) => {
    const wizard = form.hasAttribute("data-validation-steps");
    const fields = form.querySelectorAll(wizard ? "input, select, textarea" : VALIDATABLE_FIELDS);
    const password = form.querySelector('[name="password"]');
    const confirmation = form.querySelector('[name="password_confirmation"]');

    // Validate a field, and recheck the confirmation when the password changes.
    const validate = (input) => {
        validateInput(input, form, wizard);

        if (input === password && confirmation && confirmation.value) {
            validateInput(confirmation, form);
        }
    };

    fields.forEach((input) => {
        let timer;

        // Clear the old error while typing, then revalidate once the user stops.
        input.addEventListener("input", () => {
            showError(input, form, "");

            clearTimeout(timer);
            timer = setTimeout(() => validate(input), TYPING_DELAY);
        });

        // Selects may not fire input, so also validate on change.
        input.addEventListener("change", () => validate(input));

        // Validate right away after leaving the field.
        input.addEventListener("blur", () => {
            clearTimeout(timer);
            validate(input);
        });
    });

    // Recheck all fields when the form is submitted.
    if (wizard) return;
    form.addEventListener("submit", () => {
        fields.forEach((input) => validateInput(input, form));
    });
});