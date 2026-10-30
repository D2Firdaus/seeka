import flatpickr from 'flatpickr';

let datePicker;

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'get') {
        return;
    }

    event.preventDefault();

    const params = new URLSearchParams();

    for (const [name, value] of new FormData(form).entries()) {
        if (String(value).trim() !== '') {
            params.append(name, value);
        }
    }

    const url = new URL(form.action);

    url.search = params.toString();

    window.location.assign(url.toString());
});

document.addEventListener('DOMContentLoaded', () => {
    const rangeInput = document.getElementById('date-range');
    if (!rangeInput) {
        return;
    }

    const fromInput = document.querySelector('input[name="date_from"]');
    const toInput = document.querySelector('input[name="date_to"]');

    datePicker = flatpickr(rangeInput, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        defaultDate: [fromInput?.value, toInput?.value].filter(Boolean),
        locale: { rangeSeparator: ' s/d ' },
        onChange: (selectedDates) => {
            if (selectedDates.length === 2) {
                fromInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                toInput.value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
            } else {
                fromInput.value = '';
                toInput.value = '';
            }
        },
    });
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-reset]');
    if (!button) {
        return;
    }

    const target = button.dataset.reset;
    const form = button.closest('form');
    if (!form) {
        return;
    }

    if (target === 'category' || target === 'all') {
        const select = form.querySelector('select[name="category"]');
        if (select) select.selectedIndex = 0;
    }

    if (target === 'location' || target === 'all') {
        const select = form.querySelector('select[name="location"]');
        if (select) select.selectedIndex = 0;
    }

    if (target === 'date' || target === 'all') {
        datePicker?.clear();
        const from = form.querySelector('input[name="date_from"]');
        const to = form.querySelector('input[name="date_to"]');
        if (from) from.value = '';
        if (to) to.value = '';
    }

    if (target === 'type' || target === 'all') {
        form.querySelectorAll('input[name="type[]"]').forEach((cb) => { cb.checked = false; });
    }

    if (target === 'status' || target === 'all') {
        const select = form.querySelector('select[name="status"]');
        if (select) select.selectedIndex = 0;
    }
});
