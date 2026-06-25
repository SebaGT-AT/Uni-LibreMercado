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
