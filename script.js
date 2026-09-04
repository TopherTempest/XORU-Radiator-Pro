const menu = document.querySelector('.menu-toggle');
const nav = document.querySelector('.nav');

if (menu) {
    menu.addEventListener('click', () => {
        const open = nav.classList.toggle('open');
        menu.setAttribute('aria-expanded', open);
    });
}

document.querySelectorAll('.nav a').forEach(a =>
    a.addEventListener('click', () =>
        nav.classList.remove('open')
    )
);

document.querySelectorAll('[data-modal]').forEach(btn =>
    btn.addEventListener('click', () =>
        openModal(btn.dataset.modal)
    )
);

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) {
            closeModal(modal);
        }
    });

    modal.querySelector('.close').addEventListener('click', () =>
        closeModal(modal)
    );
});

function openModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.open').forEach(closeModal);
    }
});

const subscribeForm = document.getElementById('subscribeForm');

subscribeForm.addEventListener('submit', e => {
    e.preventDefault();

    const email = subscribeForm.querySelector('input').value;

    document.getElementById('subscribeMessage').textContent =
        `Thanks! ${email} has been added for maintenance tips.`;

    subscribeForm.reset();
});