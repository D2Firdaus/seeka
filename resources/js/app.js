import flatpickr from 'flatpickr';

import './card-title-sync.js';
import './item-detail-gallery.js';


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
    const button = event.target.closest('[data-toggle-password]');
    if (!button) {
        return;
    }

    const input = document.getElementById(button.dataset.togglePassword);
    if (!input) {
        return;
    }

    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    button.setAttribute('aria-label', showing ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
    button.setAttribute('aria-pressed', showing ? 'false' : 'true');
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

const MAX_PHOTOS = 3;
const MAX_PHOTO_BYTES = 5 * 1024 * 1024;
const PHOTO_TYPES = ['image/jpeg', 'image/png'];

const photoFiles = [];
const photoUrls = [];

function renderPhotoPreviews() {
    const container = document.getElementById('photo-previews');
    const template = document.getElementById('photo-preview-template');
    const input = document.getElementById('photos');

    if (!container || !template || !(input instanceof HTMLInputElement)) {
        return;
    }

    photoUrls.forEach((url) => URL.revokeObjectURL(url));
    photoUrls.length = 0;
    container.replaceChildren();

    photoFiles.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        photoUrls.push(url);

        const node = template.content.firstElementChild.cloneNode(true);
        const image = node.querySelector('img');
        image.src = url;
        image.alt = file.name;
        node.querySelector('[data-photo-name]').textContent = file.name;
        node.querySelector('[data-photo-remove]').dataset.photoRemove = String(index);
        container.appendChild(node);
    });

    container.classList.toggle('hidden', photoFiles.length === 0);

    const count = document.getElementById('photo-count');
    if (count) {
        count.textContent = photoFiles.length > 0 ? `${photoFiles.length}/${MAX_PHOTOS}` : '';
    }

    const full = photoFiles.length >= MAX_PHOTOS;
    const label = document.getElementById('photo-picker-label');
    if (label) {
        label.classList.toggle('pointer-events-none', full);
        label.classList.toggle('opacity-50', full);
    }
}

function syncPhotoInput() {
    const input = document.getElementById('photos');
    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    const transfer = new DataTransfer();
    photoFiles.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
}

document.addEventListener('change', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || input.id !== 'photos') {
        return;
    }

    const error = document.getElementById('photo-error');
    const messages = [];

    for (const file of input.files) {
        if (!PHOTO_TYPES.includes(file.type)) {
            messages.push(`"${file.name}" harus JPG atau PNG.`);
            continue;
        }

        if (file.size > MAX_PHOTO_BYTES) {
            messages.push(`"${file.name}" melebihi 5 MB.`);
            continue;
        }

        const duplicate = photoFiles.some((existing) =>
            existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified);

        if (duplicate) {
            continue;
        }

        if (photoFiles.length >= MAX_PHOTOS) {
            messages.push(`Maksimal ${MAX_PHOTOS} foto.`);
            break;
        }

        photoFiles.push(file);
    }

    if (error) {
        error.textContent = messages.join(' ');
        error.classList.toggle('hidden', messages.length === 0);
    }

    syncPhotoInput();
    renderPhotoPreviews();
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-photo-remove]');

    if (!button) {
        return;
    }

    const index = Number(button.dataset.photoRemove);

    if (!Number.isInteger(index) || index < 0 || index >= photoFiles.length) {
        return;
    }

    photoFiles.splice(index, 1);
    syncPhotoInput();
    renderPhotoPreviews();
});

let proofPhotoUrl = null;

function clearProofPhoto() {
    const input = document.getElementById('proof_image');
    const container = document.getElementById('proof-photo-preview');
    const img = document.getElementById('proof-preview-img');
    const name = document.getElementById('proof-preview-name');
    const error = document.getElementById('proof-photo-error');

    if (proofPhotoUrl) {
        URL.revokeObjectURL(proofPhotoUrl);
        proofPhotoUrl = null;
    }

    if (input instanceof HTMLInputElement) {
        input.value = '';
    }

    if (img instanceof HTMLImageElement) {
        img.src = '';
    }

    if (name) {
        name.textContent = '';
    }

    if (container) {
        container.classList.add('hidden');
    }

    if (error) {
        error.textContent = '';
        error.classList.add('hidden');
    }
}

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.id !== 'proof_image') {
        return;
    }

    const file = input.files?.[0];
    const error = document.getElementById('proof-photo-error');
    const container = document.getElementById('proof-photo-preview');
    const img = document.getElementById('proof-preview-img');
    const name = document.getElementById('proof-preview-name');

    if (!file) {
        clearProofPhoto();
        return;
    }

    if (!PHOTO_TYPES.includes(file.type)) {
        clearProofPhoto();
        if (error) {
            error.textContent = `"${file.name}" harus JPG atau PNG.`;
            error.classList.remove('hidden');
        }
        return;
    }

    if (file.size > MAX_PHOTO_BYTES) {
        clearProofPhoto();
        if (error) {
            error.textContent = `"${file.name}" melebihi 5 MB.`;
            error.classList.remove('hidden');
        }
        return;
    }

    if (error) {
        error.textContent = '';
        error.classList.add('hidden');
    }

    if (proofPhotoUrl) {
        URL.revokeObjectURL(proofPhotoUrl);
    }

    proofPhotoUrl = URL.createObjectURL(file);

    if (img instanceof HTMLImageElement) {
        img.src = proofPhotoUrl;
        img.alt = file.name;
    }

    if (name) {
        name.textContent = file.name;
    }

    if (container) {
        container.classList.remove('hidden');
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('#proof-photo-remove');
    if (button) {
        clearProofPhoto();
    }
});

