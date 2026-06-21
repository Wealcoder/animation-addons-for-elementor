import { register } from '@elementor/frontend-handlers';

// Open-state registry keyed by item id (data-id). Survives Elementor editor
// re-renders: when a user clicks an item the editor also selects it, which
// re-renders the element from the twig and wipes the `.active` class — the item
// would "collapse instantly". We remember open ids here and re-apply them after
// any re-render via the MutationObserver below.
const openItems = new Set();

const itemId = (item) => item.getAttribute('data-id') || item.id || '';

const setItemActive = (item, active) => {
    const header = item.querySelector('.aae-accordion-header');
    item.classList.toggle('active', active);
    if (header) header.setAttribute('aria-expanded', String(active));

    const id = itemId(item);
    if (!id) return;
    if (active) {
        openItems.add(id);
    } else {
        openItems.delete(id);
    }
};

// Apply the configured default open/closed state to the items in a container.
// Runs once per container (seeds the openItems registry). User clicks
// afterwards are handled by the delegated listener; re-renders are healed by
// the observer.
const applyDefaultState = (container) => {
    if (container.dataset.aaeStateApplied === 'true') return;
    container.dataset.aaeStateApplied = 'true';

    const items = container.querySelectorAll('.aae-a-accordion-item');
    if (!items.length) return;

    const defaultState = container.dataset.defaultState || 'first';

    items.forEach((item, index) => {
        const startsActive = item.classList.contains('active') ||
            (defaultState === 'first' && index === 0);
        setItemActive(item, defaultState === 'none' ? false : startsActive);
    });
};

// Re-apply remembered open state to every item in a container. Cheap and
// idempotent — safe to call on every mutation.
const restoreState = (container) => {
    container.querySelectorAll('.aae-a-accordion-item').forEach((item) => {
        const id = itemId(item);
        const shouldBeActive = id ? openItems.has(id) : item.classList.contains('active');
        if (item.classList.contains('active') !== shouldBeActive) {
            const header = item.querySelector('.aae-accordion-header');
            item.classList.toggle('active', shouldBeActive);
            if (header) header.setAttribute('aria-expanded', String(shouldBeActive));
        }
    });
};

// Watch the accordion for editor re-renders and restore open state afterwards.
const observeContainer = (container) => {
    if (container.__aaeStateObserved) return;
    container.__aaeStateObserved = true;

    const win = container.ownerDocument.defaultView || window;
    const observer = new win.MutationObserver(() => restoreState(container));
    observer.observe(container, { childList: true, subtree: true });
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
            if (other !== item) setItemActive(other, false);
        });
    }

    setItemActive(item, !isActive);
};

// Delegated click handler. Bound once per document (including the editor
// preview iframe's document), it survives Elementor re-renders and does not
// depend on per-element binding timing. A click anywhere on an accordion item
// (except inside its content area) toggles the item's `active` class — this is
// what makes the toggle work in editor mode.
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
            if (!e.target.closest) return;

            const item = e.target.closest('.aae-a-accordion-item');
            if (!item) return;

            // Toggle on clicks anywhere in the item EXCEPT inside the content
            // area — otherwise interacting with (or editing) the open content
            // would collapse it.
            if (e.target.closest('.aae-accordion-content-wrapper')) return;

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
        observeContainer(container);
    }
});

// Fallback: ensure the delegated toggle is installed on this document even if
// the frontend-handler callback above never runs (e.g. timing inside the
// editor preview iframe). Delegation is idempotent via the document flag.
installDelegatedToggle(document);
