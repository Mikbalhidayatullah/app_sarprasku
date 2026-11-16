import './bootstrap';

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.hasAttribute('wire:submit')) return;
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
    }
    if (form.dataset.submitting) { event.preventDefault(); return; }
    form.dataset.submitting = '1';
    const button = form.querySelector('button[type="submit"],button:not([type])');
    if (button) { button.disabled = true; button.dataset.originalText = button.textContent; button.textContent = 'Memproses…'; }
});
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting]').forEach((form) => {
        delete form.dataset.submitting;
        form.querySelectorAll('button[data-original-text]').forEach((button) => { button.disabled = false; button.textContent = button.dataset.originalText; });
    });
});
document.querySelector('[data-error-summary]')?.focus();
