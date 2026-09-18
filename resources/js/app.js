import './admin';

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    const targetId = toggle.dataset.passwordTarget
        ?? toggle.getAttribute('aria-controls');

    const password = document.getElementById(targetId);

    if (!password) {
        return;
    }

    toggle.hidden = false;

    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';

        password.type = visible ? 'text' : 'password';

        toggle.setAttribute(
            'aria-label',
            visible ? 'Esconder senha' : 'Mostrar senha'
        );

        toggle.setAttribute(
            'aria-pressed',
            String(visible)
        );
    });
});

if (document.querySelector('[data-agenda]')) {
    import('./agenda').catch(() => {
        document.querySelector('[data-agenda-message]').textContent = 'Não foi possível iniciar a agenda. Atualize a página para tentar novamente.';
    });
}

const loginForm = document.querySelector('[data-login-form]');

if (loginForm) {
    const password = loginForm.querySelector('#password');
    const toggle = loginForm.querySelector('[data-password-toggle]');
    const submit = loginForm.querySelector('[type="submit"]');
    const label = loginForm.querySelector('[data-submit-label]');
    let submitting = false;

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
