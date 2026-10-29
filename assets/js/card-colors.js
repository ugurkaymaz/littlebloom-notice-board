/* Nursery Notice Board: front-end colour preview for pushpin controls.
 * The site owner's saved card and pin colours remain unchanged.
 */
(function () {
    'use strict';

    function rgbToHex(value) {
        const match = String(value || '').match(/^rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/i);
        if (!match) return '';
        return '#' + match.slice(1, 4).map(function (part) {
            return Number(part).toString(16).padStart(2, '0');
        }).join('');
    }

    function changeCardColour(event) {
        const button = event.target.closest('.lbnb-card-pin');
        if (!button) return;

        const card = button.closest('.lb-notice-board-627__note, .lb-notice-archive-628__note');
        const container = button.closest('[data-lbnb-palette]');
        if (!card || !container) return;

        let palette;
        try {
            palette = JSON.parse(container.getAttribute('data-lbnb-palette') || '[]');
        } catch (error) {
            return;
        }
        if (!Array.isArray(palette) || palette.length === 0 ||
            !palette.every(function (colour) { return /^#[0-9a-fA-F]{6}$/.test(colour); })) return;

        const current = rgbToHex(window.getComputedStyle(card).backgroundColor);
        let index = palette.findIndex(function (colour) {
            return colour.toLowerCase() === current;
        });
        // On custom colours the first click selects palette item 1.
        index = index < 0 ? 0 : (index + 1) % palette.length;
        card.style.setProperty('--lbnb-card-color', palette[index]);
        event.preventDefault();
    }

    document.addEventListener('click', changeCardColour);
})();
