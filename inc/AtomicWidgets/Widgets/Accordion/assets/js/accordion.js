import { register } from '@elementor/frontend-handlers';

// Open-state registry keyed by item id (data-id). Survives Elementor editor
// re-renders: when a user clicks an item the editor also selects it, which
// re-renders the element from the twig and wipes the `.active` class — the item
// would "collapse instantly". We remember open ids here and re-apply them after
// any re-render via the MutationObserver below.
const openItems = new Set();

const itemId = (item) => item.getAttribute('data-id') || item.id || '';

// Move the two injected Div_Blocks (Header, Content) out of the hidden
// injector into their slots. This used to live in an inline <script> in the
// item twig, but inline scripts don't run when Elementor compiles the twig
// client-side in the editor — and that inline script also blocked the editor's
// twig compiler from emitting the static markup (the .aae-accordion-header
// <button> was missing in the editor). Running distribution from the enqueued
// bundle fixes both: the template stays pure static markup, and distribution
// works in the editor and on the frontend.
const isComposedChild = (child) =>
    child.classList.contains('elementor-element') ||
    child.classList.contains('e-con') ||
    child.classList.contains('e-widget') ||
    child.hasAttribute('data-element_type');

const distributeChildren = (item) => {
    if (!item || item.dataset.aaeDistributed === 'true') return;

    const headerContent = item.querySelector('.aae-header-content');
    const contentArea = item.querySelector('.aae-accordion-content');
    if (!headerContent || !contentArea) return;

    // Server (twig) renders place the composed Header/Content Div_Blocks
    // inside the hidden .aae-children-injector, exactly where
    // {{ children_placeholder }} sits in the twig. Elementor's OWN editor
    // view does NOT respect that position at all — it mounts child element
    // views directly onto the item's root node instead (bypassing the
    // injector entirely), which left the header title and content text
    // inheriting the page's default typography instead of ours, since they
    // never made it into `.aae-header-content` / `.aae-accordion-content`.
    // isComposedChild() already excludes every one of our own structural
    // wrappers (title-wrapper, content-wrapper, the injector itself) and
    // Elementor's own `.elementor-element-overlay` — none of them carry an
    // `elementor-element`/`e-con`/`e-widget` class or `data-element_type`.
    const injector = item.querySelector(':scope > .aae-children-injector');
    let children = injector ? Array.from(injector.children).filter(isComposedChild) : [];
    if (children.length === 0) {
        children = Array.from(item.children).filter(isComposedChild);
    }
    if (children.length === 0) return;

    // children[0] = Header Div_Block, children[1] = Content Div_Block
    children.forEach((child, index) => {
        if (index === 0) {
            headerContent.appendChild(child);
        } else {
            contentArea.appendChild(child);
        }
    });

    item.dataset.aaeDistributed = 'true';
};

// Base-style class names, mirroring define_base_styles()'s keys in
// class-aae-a-accordion-item.php ({element_type}-{key}).
const ITEM_TYPE = 'e-aae-a-accordion-item';
const HEADER_CLASS = ITEM_TYPE + '-header_element';
const ICON_CLASS = ITEM_TYPE + '-header_icon';

// An icon slot is an Atomic_Svg element. `e-svg-base` is that element's OWN
// base-style class, rendered by Elementor's twig rather than seeded into the
// `classes` prop, so unlike our hook classes it cannot be unapplied.
const isIconEl = (el) =>
    el.classList.contains('e-svg-base') || !!el.querySelector(':scope > svg');

// Re-assert the DOM hooks the widget's CSS keys on.
//
// The Header/Content div blocks, the title and the two icons are all
// ELEMENTOR-owned element types (Div_Block, Atomic_Heading, Atomic_Svg), so
// their twigs belong to Elementor and we cannot render our hook classes from
// them the way the item's own twig does — define_default_children() has to
// seed them into the `classes` prop instead. The editing panel resolves every
// entry in that prop against the style repository, reports ours under "Some
// classes are missing" (they are behaviour hooks, not styles), and its ✕
// calls unapplyClasses() on exactly those ids.
//
// One click therefore stripped the classes this widget runs on. The close
// icon lost `aae-header-icon-close`, its `display: none` rule stopped
// matching, and BOTH chevrons rendered at once — the reported bug. The header
// row also lost its layout classes, and the title lost the colour fence that
// keeps it readable on a focused header (see accordion.scss).
//
// Role is recovered from STRUCTURE, never from the classes being restored:
// `.aae-header-content` / `.aae-accordion-content` come from the item's own
// twig, and the parts are identified by Elementor's own base-style classes
// plus the twig's fixed child order (title, open icon, close icon). Classes
// are then put back, and each icon additionally gets a
// `data-aae-accordion-icon` attribute — nothing in the panel can reach an
// attribute, so the visibility rules keyed on it survive a ✕ even before this
// restoration has run.
// Write only on a real change, so a steady-state pass produces ZERO mutation
// records. That is what lets the observer below watch `class` without the
// re-tagging feeding itself an endless loop.
const setAttr = (el, name, value) => {
    if (el.getAttribute(name) !== value) el.setAttribute(name, value);
};

const ensureHooks = (item) => {
    // Header-side parts are everything outside the content region. Scoping by
    // the CONTENT wrapper rather than by the header slot is deliberate: both
    // wrappers come from the item's own twig, but in the editor Elementor
    // mounts child views onto the item root and only distributeChildren() moves
    // them into `.aae-header-content` — so keying off the header slot made this
    // whole function bail (slot empty) at exactly the moment a re-render had
    // just dropped the hooks. Working from "not in the content region" tags the
    // icons whether or not distribution has happened yet.
    const contentWrap = item.querySelector('.aae-accordion-content-wrapper');
    const headerSide = (el) => !(contentWrap && contentWrap.contains(el));

    const all = Array.from(item.querySelectorAll('*'));
    const icons = all.filter((el) => isIconEl(el) && headerSide(el));

    // Prefer whichever hook class survived; fall back to document order, which
    // define_default_children() fixes as title, open icon, close icon. Only
    // guess when there are exactly the two icons we seeded — a builder who
    // added their own SVG to the header must not have it silently re-roled.
    let open = icons.find((i) => i.classList.contains('aae-header-icon-open'));
    let close = icons.find((i) => i.classList.contains('aae-header-icon-close'));
    if (icons.length === 2) {
        if (!open) open = icons.find((i) => i !== close);
        if (!close) close = icons.find((i) => i !== open);
    }

    icons.forEach((i) => {
        i.classList.add('aae-header-icon-element', ICON_CLASS);
        // The chevrons duplicate the state the button already announces through
        // aria-expanded, so letting a screen reader announce them again is noise.
        setAttr(i, 'aria-hidden', 'true');
    });

    if (open && close && open !== close) {
        open.classList.add('aae-header-icon-open');
        close.classList.add('aae-header-icon-close');
        setAttr(open, 'data-aae-accordion-icon', 'open');
        setAttr(close, 'data-aae-accordion-icon', 'close');
    }

    // The Header div block is the icons' parent; fall back to the slot for a
    // header that has no icons at all.
    const headerSlot = item.querySelector('.aae-header-content');
    const headerDiv = (open && open.parentElement) ||
        (icons[0] && icons[0].parentElement) ||
        (headerSlot && headerSlot.firstElementChild);

    if (headerDiv && headerSide(headerDiv) && headerDiv !== headerSlot) {
        headerDiv.classList.add('aae-header-element', HEADER_CLASS);

        const title = Array.from(headerDiv.children).find((k) => !isIconEl(k));
        if (title) title.classList.add('aae-header-title-element');
    }

    const contentSlot = item.querySelector('.aae-accordion-content');
    const contentDiv = contentSlot && contentSlot.firstElementChild;
    if (contentDiv) contentDiv.classList.add('aae-content-element');
};

// Accessible name safety net for the header <button>.
//
// The item twig has always rendered `data-aae-fallback-label`, with a comment
// saying accordion.js promotes it to aria-label when the button would otherwise
// be nameless — but nothing here ever read it, so the guard did not exist. A
// header holding only an icon, or one whose title child was deleted, is a
// <button> with no accessible name at all (WCAG 4.1.2), and it silently takes
// the panel's `aria-labelledby` down with it, leaving the region unnamed too.
//
// Only ever applied when the button is REALLY empty: a visible title must never
// be overridden by a different spoken name (WCAG 2.5.3, Label in Name). It is
// also withdrawn again if a title comes back, so the marker records that the
// label is ours to remove.
const ensureButtonLabel = (item) => {
    const btn = item.querySelector('.aae-accordion-header');
    if (!btn) return;

    const fallback = (btn.getAttribute('data-aae-fallback-label') || '').trim();
    const named = btn.textContent.trim().length > 0 ||
        btn.hasAttribute('aria-labelledby');

    if (named || !fallback) {
        if (btn.getAttribute('data-aae-labelled') === 'fallback') {
            btn.removeAttribute('aria-label');
            btn.removeAttribute('data-aae-labelled');
        }
        return;
    }

    setAttr(btn, 'aria-label', fallback);
    setAttr(btn, 'data-aae-labelled', 'fallback');
};

const distributeAll = (container) => {
    container.querySelectorAll('.aae-a-accordion-item').forEach((item) => {
        distributeChildren(item);
        // NOT gated by `aaeDistributed`: the panel can strip a class at any
        // point after distribution, so this has to run on every pass. Adding
        // classes and attributes does not re-trigger the observer, which
        // watches `childList` only.
        ensureHooks(item);
        ensureButtonLabel(item);
    });
};

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
// The editor canvas must stay fully clickable: `inert` blocks pointer events and
// focus, so applying it there would stop a builder selecting anything inside a
// collapsed item's content from the Structure panel.
const isEditor = (doc) =>
    !!(doc && doc.body && doc.body.classList.contains('elementor-editor-active'));

const prefersReducedMotion = (el) => {
    const win = el.ownerDocument.defaultView || window;
    return !!(win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches);
};

// A collapsed panel is hidden with `max-height: 0; overflow: hidden` so it can
// animate — and content hidden THAT way is still in the tab order and still
// reachable by a screen reader. `display: none` and `visibility: hidden` remove
// it automatically; a clipped box does not. So a keyboard user tabs into links
// and buttons they cannot see, inside a panel they never opened. `inert` is the
// attribute that closes exactly this gap without affecting rendering, so the
// animation is unchanged.
const setPanelInert = (wrapper, open) => {
    if (!wrapper || isEditor(wrapper.ownerDocument)) return;
    if (open) {
        wrapper.removeAttribute('inert');
    } else if (!wrapper.hasAttribute('inert')) {
        wrapper.setAttribute('inert', '');
    }
};

const setWrapperHeight = (wrapper, open, animate = true) => {
    if (!wrapper) return;
    const win = wrapper.ownerDocument.defaultView || window;

    setPanelInert(wrapper, open);

    // Honour prefers-reduced-motion by taking the instant path. Doing it HERE
    // rather than only in CSS is what keeps the end state correct: the animated
    // branch settles an open panel to `max-height: none` from a `transitionend`
    // listener, and with the transition suppressed that event never fires — the
    // panel would stay pinned at its measured pixel height and clip any content
    // that grew later.
    if (!animate || prefersReducedMotion(wrapper)) {
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

    // childList ONLY — `attributes` is deliberately NOT observed here, and the
    // attempt is recorded because it looks like the obvious fix and it locks the
    // tab solid.
    //
    // Watching `class` to catch the panel's ✕ (unapplyClasses) makes the heal
    // observe itself: ensureHooks() writes classes, that delivers new records,
    // which sweep again. Measured — the accordion page stopped loading entirely
    // (server responding in 0.7s, `domcontentloaded` never firing), because a
    // MutationObserver callback is a microtask so the queue never drains and the
    // page simply stops painting with nothing in the console. Neither a
    // `sweeping` flag (cleared synchronously, while the records it caused arrive
    // in a LATER microtask) nor disconnect + takeRecords() around the sweep was
    // enough. Same failure as the Structure-panel eye-toggle hang.
    //
    // It is also unnecessary, which is the real reason it is gone. The ✕ strips
    // CLASSES; the `data-aae-accordion-icon` attribute ensureHooks() already
    // wrote is untouched by it, so the visibility rules keyed on that attribute
    // keep working with no re-run at all. And when the panel re-renders the
    // element instead of patching it, the replacement IS a childList mutation,
    // which this observer already catches. Both paths are covered.
    const OPTS = {
        childList: true,
        subtree: true,
    };

    // DISCONNECT for the duration of the sweep, then DROP whatever it caused
    // before reconnecting. A plain `sweeping = true/false` flag does NOT work
    // here and locks the tab solid: the flag is cleared synchronously, while the
    // records the sweep just generated are delivered in a LATER microtask with
    // the flag already false — so the heal observes itself forever. A
    // MutationObserver callback is a microtask, so the queue never drains and
    // the page simply stops painting, with nothing in the console. (Same failure
    // and the same cure as the Structure-panel eye-toggle hang.) takeRecords()
    // is what discards the self-inflicted batch; without it, reconnecting would
    // deliver it anyway.
    const observer = new win.MutationObserver(() => {
        observer.disconnect();
        try {
            // A re-render rebuilds items from the twig and empties the slots, so
            // re-distribute before restoring open state.
            distributeAll(container);
            restoreState(container);
        } finally {
            observer.takeRecords();
            observer.observe(container, OPTS);
        }
    });

    observer.observe(container, OPTS);
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

            // Toggle on a click anywhere on the item — including directly on
            // the title text or the icon — except inside the content area,
            // where interacting with the open content would collapse it. Same
            // rule in the editor and on the frontend: no editor-only "bare
            // header element" carve-out. `preventDefault()` alone (no
            // `stopPropagation()`) still lets the click bubble to Elementor's
            // own selection handler afterwards, so the title/icon widgets stay
            // selectable/editable in the builder even though the same click
            // also toggles the item.
            if (e.target.closest('.aae-accordion-content-wrapper')) return;

            e.preventDefault();
            toggleItem(item);
        },
        true // capture phase
    );
};

const initAccordion = (container) => {
    if (!container) return;
    installDelegatedToggle(container.ownerDocument);
    distributeAll(container);
    applyDefaultState(container);
    observeContainer(container);
};

register({
    elementType: 'e-aae-a-accordion',
    id: 'aae-a-accordion-handler',
    callback: ({ element }) => {
        const container = element.classList.contains('aae-a-accordion') ? element : element.querySelector('.aae-a-accordion');
        initAccordion(container);
    }
});

// Fallback bootstrap for the editor preview, where the frontend-handler
// callback may not fire. Initialise existing accordions and watch for ones
// added later (idempotent — guarded per element/document).
const bootstrap = (doc) => {
    if (!doc) return;
    installDelegatedToggle(doc);
    doc.querySelectorAll('.aae-a-accordion').forEach(initAccordion);

    if (doc.__aaeAccordionBootstrapped) return;
    doc.__aaeAccordionBootstrapped = true;

    const win = doc.defaultView || window;
    const docObserver = new win.MutationObserver(() => {
        doc.querySelectorAll('.aae-a-accordion').forEach(initAccordion);
    });
    docObserver.observe(doc.documentElement || doc.body, { childList: true, subtree: true });
};

bootstrap(document);

/* ------------------------------------------------------------------ *
 * Editor bridge control surface (window.AAEAccordion)
 *
 * The editor bridge (atomic-editor.js) suppresses Elementor's re-render for
 * accordion settings and instead patches the preview DOM in place. These
 * helpers let it re-seed / toggle live state without a re-render.
 * ------------------------------------------------------------------ */

const findItemById = (id) => {
    if (!id) return null;
    return document.querySelector('.aae-a-accordion-item[data-id="' + id + '"]');
};

// Re-seed open/closed state from the parent's `default_state` (used when the
// parent setting changes). `applyDefaultState` guards with
// `data-aae-state-applied`, so clear it first to force a fresh seed.
const reseedDefaultState = (container) => {
    if (!container) return;
    delete container.dataset.aaeStateApplied;
    // Forget remembered open state for THIS accordion's items only, so other
    // accordions on the page keep their state.
    container.querySelectorAll('.aae-a-accordion-item').forEach((item) => {
        const id = itemId(item);
        if (id) openItems.delete(id);
    });
    applyDefaultState(container);
};

// Set a single item active/inactive live (used when the child item's
// `is_active` setting changes). Honours the parent's max_items_expanded so
// turning one on closes siblings when only one may stay open.
const setItemActiveById = (id, active) => {
    const item = findItemById(id);
    if (!item) return;

    const accordion = item.closest('.aae-a-accordion');
    const maxItemsExpanded = accordion ? (accordion.dataset.maxItemsExpanded || 'one') : 'one';

    if (active && maxItemsExpanded === 'one' && accordion) {
        accordion.querySelectorAll('.aae-a-accordion-item.active').forEach((other) => {
            if (other !== item) setItemActive(other, false, true);
        });
    }

    setItemActive(item, !!active, true);
};

// Published on the preview iframe's window so the editor bridge can reach it.
window.AAEAccordion = window.AAEAccordion || {};
window.AAEAccordion.applyDefaultState = reseedDefaultState;
window.AAEAccordion.setItemActive = setItemActiveById;
