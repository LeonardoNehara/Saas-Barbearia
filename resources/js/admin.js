document.addEventListener('error', (event) => {
    if (event.target instanceof HTMLImageElement && event.target.matches('[data-profissional-foto]')) {
        event.target.hidden = true;
    }
}, true);

document.querySelectorAll('[data-profissional-foto]').forEach((photo) => {
    if (photo.complete && !photo.naturalWidth) photo.hidden = true;
});

const menu = document.querySelector('#mobile-menu');
const menuButtons = document.querySelectorAll('[data-open-menu]');
let activeMenuButton;

if (menu && menuButtons.length) {
    menuButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeMenuButton = button;
            menu.showModal();
            menuButtons.forEach((menuButton) => menuButton.setAttribute('aria-expanded', 'true'));
            document.body.style.overflow = 'hidden';
        });
    });
    menu.querySelector('[data-close-menu]').addEventListener('click', () => menu.close());
    menu.addEventListener('click', (event) => {
        if (event.target === menu && event.clientX >= menu.getBoundingClientRect().right) {
            menu.close();
        }
    });
    menu.addEventListener('close', () => {
        menuButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
        document.body.style.overflow = '';
        activeMenuButton?.focus();
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches && menu.open && !menu.hasAttribute('data-desktop-menu')) menu.close();
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
