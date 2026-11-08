// Sync card title heights per grid row
export default function syncCardTitles() {
    const cards = document.querySelectorAll('.item-card');
    if (!cards.length) return;

    const getTop = el => el.getBoundingClientRect().top;

    // Group cards by top offset (5px bucket)
    const rows = {};
    cards.forEach(card => {
        const top = Math.round(getTop(card) / 5) * 5;
        if (!rows[top]) rows[top] = [];
        rows[top].push(card);
    });

    Object.values(rows).forEach(rowCards => {
        // Reset heights
        rowCards.forEach(c => {
            const t = c.querySelector('.item-card-title');
            if (t) t.style.height = 'auto';
        });

        const maxHeight = Math.max(
            ...rowCards.map(c => {
                const t = c.querySelector('.item-card-title');
                return t ? t.offsetHeight : 0;
            })
        );

        rowCards.forEach(c => {
            const t = c.querySelector('.item-card-title');
            if (t) t.style.height = `${maxHeight}px`;
        });
    });
}

// Debounce helper
function debounce(fn, delay) {
    let timer;
    return () => {
        clearTimeout(timer);
        timer = setTimeout(fn, delay);
    };
}

// Run on load and on resize
document.addEventListener('DOMContentLoaded', syncCardTitles);
window.addEventListener('resize', debounce(syncCardTitles, 200));
