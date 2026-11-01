document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const eye = button.querySelector('[data-password-eye]');

        const show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

        eye.innerHTML = show
            ? `
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587a2 2 0 002.829 2.828" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.02 10.02 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.13" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.228 6.228A10.05 10.05 0 002.458 12C3.732 16.057 7.523 19 12 19a9.97 9.97 0 005.772-1.772" />
            `
            : `
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-3.057-9.542-7z" />
            `;
    });
});