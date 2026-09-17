const menu = document.querySelector('#mobile-menu');
const openMenu = document.querySelector('[data-open-menu]');

if (menu && openMenu) {
    openMenu.addEventListener('click', () => {
        menu.showModal();
        openMenu.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    });
    menu.querySelector('[data-close-menu]').addEventListener('click', () => menu.close());
    menu.addEventListener('click', (event) => {
        if (event.target === menu && event.clientX >= menu.getBoundingClientRect().right) {
            menu.close();
        }
    });
    menu.addEventListener('close', () => {
        openMenu.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        openMenu.focus();
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches && menu.open) menu.close();
    });
}

document.querySelectorAll('[data-busy-form]').forEach((form) => {
    let busy = false;
    const button = form.querySelector('[type="submit"]');
    const label = form.querySelector('[data-busy-label]');
    const originalLabel = label?.textContent;

    form.addEventListener('submit', (event) => {
        if (busy || (form.dataset.confirm && !window.confirm(form.dataset.confirm))) {
            event.preventDefault();
            return;
        }
        busy = true;
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        if (label) label.textContent = 'Aguarde…';
    });

    window.addEventListener('pageshow', () => {
        busy = false;
        button.disabled = false;
        form.removeAttribute('aria-busy');
        if (label) label.textContent = originalLabel;
    });
});
