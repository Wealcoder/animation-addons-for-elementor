import { register } from '@elementor/frontend-handlers';

// Open-state registry keyed by item id (data-id). Survives Elementor editor
// re-renders: when a user clicks an item the editor also selects it, which
// re-renders the element from the twig and wipes the `.active` class — the item
// would "collapse instantly". We remember open ids here and re-apply them after
// any re-render via the MutationObserver below.
const openItems = new Set();

const itemId = (item) => item.getAttribute('data-id') || item.id || '';

// Measure the wrapper's natural (fully-expanded) pixel height reliably — even
// in the editor where scrollHeight is flaky mid-render. We temporarily disable
// the transition and un-clip max-height, read the layout height, then restore
// the previous inline values. This forces a synchronous reflow so the reading
// reflects the real content rather than a stale/partial layout.
const measureNaturalHeight = (wrapper) => {
    const prevTransition = wrapper.style.transition;
    const prevMaxHeight = wrapper.style.maxHeight;

    wrapper.style.transition = 'none';
    wrapper.style.maxHeight = 'none';
    const h = wrapper.scrollHeight;

    wrapper.style.maxHeight = prevMaxHeight;
    void wrapper.offsetHeight; // flush so the restore doesn't animate
    wrapper.style.transition = prevTransition;
    return h;
};

// Smoothly animate a content wrapper's height. The CSS keeps it at
// `max-height: 0; overflow: hidden` collapsed and transitions max-height; here
// we drive the explicit px target so the open/close animates, then settle an
// open wrapper to `max-height: none` so its content can reflow freely. This
// runs the same in the editor and on the frontend.
//
// `animate=false` jumps straight to the end state with no transition — used
// when applying the initial/default state and when healing editor re-renders,
// so those don't visibly slide.
const setWrapperHeight = (wrapper, open, animate = true) => {
    if (!wrapper) return;
    const win = wrapper.ownerDocument.defaultView || window;

    if (!animate) {
        wrapper.style.transition = 'none';
        wrapper.style.maxHeight = open ? 'none' : '0px';
        // Flush, then restore the transition for subsequent user toggles.
        void wrapper.offsetHeight;
        wrapper.style.transition = '';
        return;
    }

    if (open) {
        const target = measureNaturalHeight(wrapper);
        // Ensure we animate from the collapsed value, then to the measured one.
        wrapper.style.maxHeight = '0px';
        void wrapper.offsetHeight;
        win.requestAnimationFrame(() => {
            wrapper.style.maxHeight = target + 'px';
        });

        const onOpenEnd = (e) => {
            if (e.target !== wrapper || e.propertyName !== 'max-height') return;
            wrapper.removeEventListener('transitionend', onOpenEnd);
            // Only settle to `none` if still open (user may have re-toggled).
            if (wrapper.closest('.aae-a-accordion-item')?.classList.contains('active')) {
                wrapper.style.maxHeight = 'none';
            }
        };
        wrapper.addEventListener('transitionend', onOpenEnd);
    } else {
        // From `none` we must first pin the current px height, then collapse,
        // otherwise there is no value to animate from.
        if (wrapper.style.maxHeight === 'none' || wrapper.style.maxHeight === '') {
            wrapper.style.maxHeight = measureNaturalHeight(wrapper) + 'px';
        }
        void wrapper.offsetHeight; // force reflow so the next change animates
        win.requestAnimationFrame(() => {
            wrapper.style.maxHeight = '0px';
        });
    }
};

const setItemActive = (item, active, animate = true) => {
    const header = item.querySelector('.aae-accordion-header');
    const wrapper = item.querySelector('.aae-accordion-content-wrapper');
    item.classList.toggle('active', active);
    if (header) header.setAttribute('aria-expanded', String(active));
    setWrapperHeight(wrapper, active, animate);

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
        // No animation for the initial state — items render in their resting
        // open/closed position without a visible slide.
        setItemActive(item, defaultState === 'none' ? false : startsActive, false);
    });
};

// Re-apply remembered open state to every item in a container. Cheap and
// idempotent — safe to call on every mutation.
const restoreState = (container) => {
    container.querySelectorAll('.aae-a-accordion-item').forEach((item) => {
        const id = itemId(item);
        const shouldBeActive = id ? openItems.has(id) : item.classList.contains('active');
        const wrapper = item.querySelector('.aae-accordion-content-wrapper');

        if (item.classList.contains('active') !== shouldBeActive) {
            const header = item.querySelector('.aae-accordion-header');
            item.classList.toggle('active', shouldBeActive);
            if (header) header.setAttribute('aria-expanded', String(shouldBeActive));
            // Re-render heal — snap to the correct height with no slide.
            setWrapperHeight(wrapper, shouldBeActive, false);
        } else if (wrapper) {
            // A re-render resets inline max-height; re-assert the resting value
            // for open items so their content stays visible.
            const current = wrapper.style.maxHeight;
            if (shouldBeActive && (current === '' || current === '0px')) {
                setWrapperHeight(wrapper, true, false);
            }
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

    // Animate user toggles in both the editor and the frontend. Height is
    // measured via measureNaturalHeight() so it's reliable even in the editor.
    const animate = true;

    // Close siblings when only one item may stay open.
    if (maxItemsExpanded === 'one' && !isActive && accordion) {
        accordion.querySelectorAll('.aae-a-accordion-item.active').forEach((other) => {
            if (other !== item) setItemActive(other, false, animate);
        });
    }

    setItemActive(item, !isActive, animate);
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
