const MUTED = '#94a3b8';
const SUCCESS_TEXT = '#4ade80';

const REQUIREMENTS = [
    { label: '8+ characters', test: (v) => v.length >= 8 },
    { label: 'uppercase', test: (v) => /[A-Z]/.test(v) },
    { label: 'lowercase', test: (v) => /[a-z]/.test(v) },
    { label: 'number', test: (v) => /[0-9]/.test(v) },
    { label: 'symbol', test: (v) => /[^A-Za-z0-9]/.test(v) },
];

function strengthFor(score) {
    if (score <= 1) return { label: 'Bad', width: '25%', color: '#f43f5e' };
    if (score <= 2) return { label: 'Fair', width: '40%', color: '#f59e0b' };
    if (score <= 3) return { label: 'Good', width: '65%', color: '#84cc16' };

    return { label: 'Excellent', width: '100%', color: '#22c55e' };
}

function initPasswordConfirmation(confirmationInput) {
    const form = confirmationInput.closest('form');
    const passwordInput = form?.querySelector('[name="password"]');
    const feedback = document.getElementById(`${confirmationInput.name}-feedback`);

    if (!passwordInput || !feedback) return;

    const update = () => {
        const hasConfirmation = confirmationInput.value.length > 0;
        const isMismatch = hasConfirmation && confirmationInput.value !== passwordInput.value;

        feedback.textContent = isMismatch ? 'Passwords do not match.' : '';
        feedback.classList.toggle('hidden', !isMismatch);

        if (hasConfirmation) {
            confirmationInput.setAttribute('aria-invalid', String(isMismatch));
        } else {
            confirmationInput.removeAttribute('aria-invalid');
        }
    };

    confirmationInput.addEventListener('input', update);
    passwordInput.addEventListener('input', update);
    update();
}

function initPasswordStrength(container) {
    const inputId = container.dataset.passwordTarget;
    const passwordInput = inputId ? document.getElementById(inputId) : null;
    const bar = container.querySelector('[data-password-strength-bar]');
    const strengthText = container.querySelector('[data-password-strength-label]');
    const requirementText = container.querySelector('[data-password-strength-copy]');

    if (!passwordInput || !bar || !strengthText || !requirementText) return;

    const update = () => {
        const password = passwordInput.value;

        if (!password) {
            container.classList.add('hidden');
            bar.style.width = '0%';
            bar.style.backgroundColor = 'transparent';
            strengthText.textContent = '—';
            strengthText.style.color = MUTED;
            requirementText.textContent = 'At least 8 characters with letters and numbers';
            requirementText.style.color = MUTED;
            return;
        }

        container.classList.remove('hidden');

        const missing = REQUIREMENTS.filter((r) => !r.test(password)).map((r) => r.label);
        const strength = strengthFor(REQUIREMENTS.length - missing.length);

        strengthText.textContent = strength.label;
        strengthText.style.color = strength.color;
        bar.style.width = strength.width;
        bar.style.backgroundColor = strength.color;

        if (missing.length === 0) {
            requirementText.textContent = 'All requirements met';
            requirementText.style.color = SUCCESS_TEXT;
        } else {
            requirementText.textContent = `Needs: ${missing.join(', ')}`;
            requirementText.style.color = MUTED;
        }
    };

    passwordInput.addEventListener('input', update);
    update();
}

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('[data-password-confirmation]')
        .forEach(initPasswordConfirmation);

    document
        .querySelectorAll('[data-password-strength-container]')
        .forEach(initPasswordStrength);
});