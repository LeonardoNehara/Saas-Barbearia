import './admin';

const loginForm = document.querySelector('[data-login-form]');

if (loginForm) {
    const password = loginForm.querySelector('#password');
    const toggle = loginForm.querySelector('[data-password-toggle]');
    const submit = loginForm.querySelector('[type="submit"]');
    const label = loginForm.querySelector('[data-submit-label]');
    let submitting = false;

    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-label', visible ? 'Esconder senha' : 'Mostrar senha');
        toggle.setAttribute('aria-pressed', String(visible));
    });

    loginForm.addEventListener('submit', (event) => {
        if (submitting) {
            event.preventDefault();
            return;
        }

        submitting = true;
        submit.disabled = true;
        loginForm.setAttribute('aria-busy', 'true');
        label.textContent = 'Entrando…';
    });

    window.addEventListener('pageshow', () => {
        submitting = false;
        submit.disabled = false;
        loginForm.removeAttribute('aria-busy');
        label.textContent = 'Entrar no sistema';
        password.type = 'password';
        toggle.setAttribute('aria-label', 'Mostrar senha');
        toggle.setAttribute('aria-pressed', 'false');
    });
}
