const togglePassword = (button) => {
    const input = document.getElementById(button.dataset.passwordToggle);

    if (!input) {
        return;
    }

    const isHidden = input.type === 'password';
    const showEye = button.querySelector('[data-password-eye-show]');
    const hideEye = button.querySelector('[data-password-eye-hide]');

    input.type = isHidden ? 'text' : 'password';
    button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    button.setAttribute('aria-pressed', String(isHidden));
    showEye?.classList.toggle('hidden', isHidden);
    hideEye?.classList.toggle('hidden', !isHidden);
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');

    if (button) {
        togglePassword(button);
    }
});
