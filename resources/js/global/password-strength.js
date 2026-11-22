const MUTED = "#475569";
const SUCCESS_TEXT = "#16a34a";

// Same rules as isStrongPassword() in form-validation.js.
const REQUIREMENTS = [
    { label: "8+ characters", test: (v) => v.length >= 8 },
    { label: "max 16 characters", test: (v) => v.length <= 16 },
    { label: "A-Z", test: (v) => /[A-Z]/.test(v) },
    { label: "a-z", test: (v) => /[a-z]/.test(v) },
    { label: "0-9", test: (v) => /[0-9]/.test(v) },
    { label: "!@#$", test: (v) => /[^A-Za-z0-9]/.test(v) },
];

// Strength levels. "Excellent" is only possible when nothing is missing.
const STRENGTH = {
    bad: { label: "Bad", width: "25%", color: "#f43f5e" },
    fair: { label: "Fair", width: "40%", color: "#f59e0b" },
    good: { label: "Good", width: "65%", color: "#84cc16" },
    excellent: { label: "Excellent", width: "100%", color: "#22c55e" },
};

// Pick the level from how many requirements are missing.
function strengthFor(missingCount) {
    if (missingCount === 0) return STRENGTH.excellent;
    if (missingCount <= 2) return STRENGTH.good; // one missing caps at Good
    if (missingCount === 3) return STRENGTH.fair;

    return STRENGTH.bad;
}

// Show "Passwords do not match." under the confirmation field.
function initPasswordConfirmation(confirmationInput) {
    const form = confirmationInput.closest("form");
    const passwordInput = form?.querySelector('[name="password"]');
    const feedback = document.getElementById(`${confirmationInput.name}-feedback`);

    if (!passwordInput || !feedback) return;

    const update = () => {
        const hasValue = confirmationInput.value.length > 0;
        const isMismatch = hasValue && confirmationInput.value !== passwordInput.value;

        feedback.textContent = isMismatch ? "Passwords do not match." : "";
        feedback.classList.toggle("hidden", !isMismatch);

        if (hasValue) {
            confirmationInput.setAttribute("aria-invalid", String(isMismatch));
        } else {
            confirmationInput.removeAttribute("aria-invalid");
        }
    };

    confirmationInput.addEventListener("input", update);
    passwordInput.addEventListener("input", update);
    update();
}

// Show the strength bar, label and the list of missing requirements.
function initPasswordStrength(container) {
    const passwordInput = document.getElementById(container.dataset.passwordTarget ?? "");
    const bar = container.querySelector("[data-password-strength-bar]");
    const strengthText = container.querySelector("[data-password-strength-label]");
    const requirementText = container.querySelector("[data-password-strength-copy]");

    if (!passwordInput || !bar || !strengthText || !requirementText) return;

    const update = () => {
        const password = passwordInput.value;

        container.classList.toggle("hidden", !password);

        if (!password) return;

        const missing = REQUIREMENTS.filter((r) => !r.test(password)).map((r) => r.label);
        const strength = strengthFor(missing.length);

        strengthText.textContent = strength.label;
        strengthText.style.color = strength.color;

        bar.style.width = strength.width;
        bar.style.backgroundColor = strength.color;

        requirementText.textContent = missing.length
            ? `Needs: ${missing.join(", ")}`
            : "All requirements met";
        requirementText.style.color = missing.length ? MUTED : SUCCESS_TEXT;
    };

    passwordInput.addEventListener("input", update);
    update();
}

function init() {
    document
        .querySelectorAll("[data-password-confirmation]")
        .forEach(initPasswordConfirmation);

    document
        .querySelectorAll("[data-password-strength-container]")
        .forEach(initPasswordStrength);
}

// Run now if the page is already loaded (deferred or module scripts), otherwise wait.
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
} else {
    init();
}