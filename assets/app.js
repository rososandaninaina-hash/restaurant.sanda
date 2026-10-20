// Petites interactions côté navigateur. Les vérifications importantes restent côté PHP.
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.querySelector('#order-type');
    const tableChoice = document.querySelector('#table-choice');

    function updateTableChoice() {
        if (!typeSelect || !tableChoice) return;
        const show = typeSelect.value === 'sur_place';
        tableChoice.hidden = !show;
        const select = tableChoice.querySelector('select');
        if (select) select.required = show;
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', updateTableChoice);
        updateTableChoice();
    }

    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.confirm || 'Confirmer cette action ?')) {
                event.preventDefault();
            }
        });
    });
});
