document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('form');
    loginForm?.addEventListener('submit', () => {
        const submitButton = loginForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm ms-2';
            submitButton.appendChild(spinner);
        }
    });

    const toggleBtn = document.getElementById('password-toggle-btn');
    toggleBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const input = document.getElementById('password');
        const icon = toggleBtn.querySelector('i');

        if (input && icon) {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
            toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            toggleBtn.setAttribute('aria-pressed', String(isPassword));
        }
    });
});
