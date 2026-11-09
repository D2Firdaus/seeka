/**
 * Item detail modal — thumbnail gallery switcher.
 *
 * Clicking a thumbnail swaps the main image and highlights
 * the active thumb with a primary ring.
 */
document.addEventListener('click', (event) => {
    const thumb = event.target.closest('[data-thumb-index]');
    if (!thumb) {
        return;
    }

    const gallery = thumb.closest('[data-gallery]');
    if (!gallery) {
        return;
    }

    const mainImage = gallery.querySelector('[data-main-image]');
    if (!mainImage) {
        return;
    }

    // Swap the main image source
    mainImage.src = thumb.dataset.thumbSrc;

    // Update active ring on thumbnails
    gallery.querySelectorAll('[data-thumb-index]').forEach((btn) => {
        btn.classList.remove('ring-primary');
        btn.classList.add('ring-transparent');
    });

    thumb.classList.remove('ring-transparent');
    thumb.classList.add('ring-primary');
});

/**
 * Close detail modal on Escape key.
 */
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    const checked = document.querySelector('.detail-toggle:checked');
    if (checked) {
        checked.checked = false;
    }
});
