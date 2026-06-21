import { register } from '@elementor/frontend-handlers';

// Apply the configured default open/closed state to the items in a container.
// Runs on initial render only; user clicks afterwards are handled by the
// delegated listener below.
const applyDefaultState = (container) => {
    const items = container.querySelectorAll('.aae-a-accordion-item');
    if (!items.length) return;

    const defaultState = container.dataset.defaultState || 'first';

    items.forEach((item, index) => {
        const header = item.querySelector('.aae-accordion-header');
        if (!header) return;

        const isFirstItem = index === 0;
        if (defaultState === 'first' && isFirstItem && !item.classList.contains('active')) {
            item.classList.add('active');
            header.setAttribute('aria-expanded', 'true');
        } else if (defaultState === 'none' && item.classList.contains('active')) {
            item.classList.remove('active');
            header.setAttribute('aria-expanded', 'false');
        }
    });
};

const toggleItem = (item) => {
    const accordion = item.closest('.aae-a-accordion');
    const header = item.querySelector('.aae-accordion-header');
    if (!header) return;

    const maxItemsExpanded = accordion ? (accordion.dataset.maxItemsExpanded || 'one') : 'one';
    const isActive = item.classList.contains('active');

    // Close siblings when only one item may stay open.
    if (maxItemsExpanded === 'one' && !isActive && accordion) {
        accordion.querySelectorAll('.aae-a-accordion-item.active').forEach((other) => {
            if (other !== item) {
                other.classList.remove('active');
                const otherHeader = other.querySelector('.aae-accordion-header');
                if (otherHeader) otherHeader.setAttribute('aria-expanded', 'false');
            }
        });
    }

    item.classList.toggle('active', !isActive);
    header.setAttribute('aria-expanded', String(!isActive));
};

// Delegated click handler. Bound once per document (including the editor
// preview iframe's document), it survives Elementor re-renders and does not
// depend on per-element binding timing. This is what makes header clicks work
// in editor mode.
//
// Bound on the CAPTURE phase: in the editor preview, Elementor intercepts
// widget clicks (to select the element) and calls stopPropagation, so a
// bubble-phase listener on the document would never fire. Capturing lets us
// run before Elementor's handlers.
const installDelegatedToggle = (doc) => {
    if (!doc || doc.__aaeAccordionDelegated) return;
    doc.__aaeAccordionDelegated = true;

    doc.addEventListener(
        'click',
        (e) => {
            const header = e.target.closest && e.target.closest('.aae-accordion-header');
            if (!header) return;

            const item = header.closest('.aae-a-accordion-item');
            if (!item) return;

            e.preventDefault();
            toggleItem(item);
        },
        true // capture phase
    );
};

register({
    elementType: 'e-aae-a-accordion',
    id: 'aae-a-accordion-handler',
    callback: ({ element }) => {
        const container = element.classList.contains('aae-a-accordion') ? element : element.querySelector('.aae-a-accordion');
        if (!container) return;

        installDelegatedToggle(container.ownerDocument);
        applyDefaultState(container);
    }
});

// Fallback: ensure the delegated toggle is installed on this document even if
// the frontend-handler callback above never runs (e.g. timing inside the
// editor preview iframe). Delegation is idempotent via the document flag.
installDelegatedToggle(document);
