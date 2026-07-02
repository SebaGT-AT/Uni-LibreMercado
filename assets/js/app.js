document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-add-row]');

    if (!trigger) {
        return;
    }

    const target = document.querySelector(trigger.dataset.addRow);
    const template = document.querySelector(trigger.dataset.template);

    if (target && template) {
        target.insertAdjacentHTML('beforeend', template.innerHTML);
    }
});

document.addEventListener('click', function (event) {
    const remove = event.target.closest('[data-remove-row]');

    if (remove) {
        remove.closest('.dynamic-row')?.remove();
    }
});

document.addEventListener('input', function (event) {
    const search = event.target.closest('[data-catalog-search]');

    if (!search) {
        return;
    }

    const target = document.querySelector(search.dataset.catalogTarget || '');
    const term = search.value.trim().toLowerCase();

    if (!target) {
        return;
    }

    target.querySelectorAll('[data-product-row]').forEach(function (row) {
        const source = (row.dataset.searchable || '').toLowerCase();
        row.classList.toggle('d-none', term !== '' && !source.includes(term));
    });
});
