document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('dgcpt-ready');
    document.querySelectorAll('[data-dgcpt-chart]').forEach((el) => {
        el.classList.add('dgcpt-chart-mounted');
    });
});
