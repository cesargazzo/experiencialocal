// Tinku: comportamiento de interfaz que no depende de Livewire.
// Alpine viene incluido con Livewire, así que los widgets interactivos usan x-data.

const io = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach((en) => {
            if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
        });
    }, { threshold: 0.12 })
    : null;

export function observeReveal(root = document) {
    root.querySelectorAll('.reveal:not(.is-in)').forEach((el, k) => {
        el.style.transitionDelay = `${(k % 6) * 60}ms`;
        io ? io.observe(el) : el.classList.add('is-in');
    });
    clearTimeout(observeReveal._t);
    observeReveal._t = setTimeout(() => root.querySelectorAll('.reveal:not(.is-in)').forEach((el) => el.classList.add('is-in')), 2500);
}

function animateCounters() {
    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = Number(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        const run = () => {
            const t0 = performance.now();
            const step = (t) => {
                const p = Math.min(1, (t - t0) / 1200);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('es-AR') + suffix;
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        if (io) { const o = new IntersectionObserver((es) => { if (es[0].isIntersecting) { run(); o.disconnect(); } }); o.observe(el); } else run();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    observeReveal();
    animateCounters();
    document.querySelector('.nav-toggle')?.addEventListener('click', () => document.querySelector('.nav')?.classList.toggle('is-open'));
});

document.addEventListener('livewire:navigated', () => observeReveal());
