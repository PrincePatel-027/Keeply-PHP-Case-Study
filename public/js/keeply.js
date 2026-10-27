document.querySelectorAll('[data-print-preview]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});
