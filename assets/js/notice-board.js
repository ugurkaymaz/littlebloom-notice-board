/* LittleBloom Notice Board — independent trigger handling (v0.1.1). */
(function () {
    'use strict';

    function init() {
        const board = document.getElementById('lb-notice-board-627');
        if (!board || board.dataset.lbnbReady === 'true') return;

        // Explicit shortcode controls always win over similarly named menu links.
        // Only scan a theme header as a compatibility fallback for LittleBloom.
        const explicitTriggers = Array.from(document.querySelectorAll('.lbnb-trigger'));
        let triggers = explicitTriggers;
        if (!triggers.length) {
            const isLittleBloomTheme = Array.from(document.body.classList).some(function (name) {
                return name.indexOf('wp-theme-littlebloom') === 0;
            });
            if (isLittleBloomTheme) {
                const candidates = Array.from(document.querySelectorAll(
                    'header a, header button, .wp-block-template-part a, .wp-block-template-part button'
                ));
                triggers = candidates.filter(function (el) {
                    const label = (el.getAttribute('aria-label') || el.textContent || '')
                        .replace(/\s+/g, ' ').trim().toLowerCase();
                    return label.includes('announcements') && label.length < 70;
                }).slice(0, 1);
            }
        }
        if (!triggers.length) return;

        const closeButton = board.querySelector('.lb-notice-board-627__close');
        let activeTrigger = null;
        let open = false;

        function updateExpanded(expanded) {
            triggers.forEach(function (el) {
                el.setAttribute('aria-expanded', el === activeTrigger && expanded ? 'true' : 'false');
            });
        }

        function positionBoard() {
            if (!open || !activeTrigger) return;
            const rect = activeTrigger.getBoundingClientRect();
            const viewportWidth = document.documentElement.clientWidth;
            const space = 12;
            const width = Math.max(0, Math.min(560, viewportWidth - 2 * space));
            const left = Math.max(space, Math.min(rect.left, viewportWidth - width - space));
            const top = Math.max(space, Math.min(rect.bottom + 10, window.innerHeight - 170));
            board.style.width = width + 'px';
            board.style.left = left + 'px';
            board.style.top = top + 'px';
            board.style.maxHeight = 'calc(100dvh - ' + Math.ceil(top + space) + 'px)';
        }

        function closeBoard(restoreFocus) {
            if (!open) return;
            open = false;
            board.hidden = true;
            updateExpanded(false);
            if (restoreFocus && activeTrigger) activeTrigger.focus();
        }

        function openBoard(trigger) {
            activeTrigger = trigger;
            open = true;
            board.hidden = false;
            updateExpanded(true);
            positionBoard();
            board.focus({ preventScroll: true });
        }

        triggers.forEach(function (trigger) {
            trigger.setAttribute('aria-controls', board.id);
            trigger.setAttribute('aria-expanded', 'false');
            if (trigger.tagName === 'A') trigger.setAttribute('role', 'button');
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (open && activeTrigger === trigger) closeBoard(true);
                else openBoard(trigger);
            }, true);
            if (trigger.tagName === 'A') {
                trigger.addEventListener('keydown', function (event) {
                    if (event.key === ' ') {
                        event.preventDefault();
                        trigger.click();
                    }
                });
            }
        });

        if (closeButton) {
            closeButton.addEventListener('click', function () { closeBoard(true); });
        }
        document.addEventListener('pointerdown', function (event) {
            if (!open || board.contains(event.target)) return;
            if (!triggers.some(function (el) { return el.contains(event.target); })) closeBoard(false);
        });
        document.addEventListener('keydown', function (event) {
            if (open && event.key === 'Escape') {
                event.preventDefault();
                closeBoard(true);
            }
        });
        window.addEventListener('resize', positionBoard, { passive: true });
        window.addEventListener('scroll', positionBoard, { passive: true });
        board.dataset.lbnbReady = 'true';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
