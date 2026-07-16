/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "@elementor/frontend-handlers":
/*!***************************************************!*\
  !*** external ["elementorV2","frontendHandlers"] ***!
  \***************************************************/
/***/ (function(module) {

module.exports = elementorV2.frontendHandlers;

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
!function() {
/*!********************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Accordion/assets/js/accordion.js ***!
  \********************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @elementor/frontend-handlers */ "@elementor/frontend-handlers");
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__);


// Open-state registry keyed by item id (data-id). Survives Elementor editor
// re-renders: when a user clicks an item the editor also selects it, which
// re-renders the element from the twig and wipes the `.active` class — the item
// would "collapse instantly". We remember open ids here and re-apply them after
// any re-render via the MutationObserver below.
const openItems = new Set();
const itemId = item => item.getAttribute('data-id') || item.id || '';

// True when running inside the Elementor editor preview. Elementor toggles
// between `elementor-editor-active` and `elementor-editor-preview` on the
// preview body (e.g. when selecting an element), so accept either; also fall
// back to detecting the editor's preview iframe.
const isEditor = node => {
  const doc = node?.ownerDocument || document;
  const win = doc.defaultView || window;
  const body = doc.body;
  if (body && (body.classList.contains('elementor-editor-active') || body.classList.contains('elementor-editor-preview'))) {
    return true;
  }
  try {
    return win !== win.parent && !!win.parent.elementor;
  } catch (e) {
    return false;
  }
};

// Move the two injected Div_Blocks (Header, Content) out of the hidden
// injector into their slots. This used to live in an inline <script> in the
// item twig, but inline scripts don't run when Elementor compiles the twig
// client-side in the editor — and that inline script also blocked the editor's
// twig compiler from emitting the static markup (the .aae-accordion-header
// <button> was missing in the editor). Running distribution from the enqueued
// bundle fixes both: the template stays pure static markup, and distribution
// works in the editor and on the frontend.
const distributeChildren = item => {
  if (!item || item.dataset.aaeDistributed === 'true') return;
  const injector = item.querySelector(':scope > .aae-children-injector');
  if (!injector) return;
  const headerContent = item.querySelector('.aae-header-content');
  const contentArea = item.querySelector('.aae-accordion-content');
  if (!headerContent || !contentArea) return;
  const children = Array.from(injector.children).filter(child => child.classList.contains('elementor-element') || child.classList.contains('e-con') || child.classList.contains('e-widget') || child.hasAttribute('data-element_type'));
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
const distributeAll = container => {
  container.querySelectorAll('.aae-a-accordion-item').forEach(distributeChildren);
};

// Measure the wrapper's natural (fully-expanded) pixel height reliably — even
// in the editor where scrollHeight is flaky mid-render. We temporarily disable
// the transition and un-clip max-height, read the layout height, then restore
// the previous inline values. This forces a synchronous reflow so the reading
// reflects the real content rather than a stale/partial layout.
const measureNaturalHeight = wrapper => {
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
    const onOpenEnd = e => {
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
const applyDefaultState = container => {
  if (container.dataset.aaeStateApplied === 'true') return;
  container.dataset.aaeStateApplied = 'true';
  const items = container.querySelectorAll('.aae-a-accordion-item');
  if (!items.length) return;
  const defaultState = container.dataset.defaultState || 'first';
  items.forEach((item, index) => {
    const startsActive = item.classList.contains('active') || defaultState === 'first' && index === 0;
    // No animation for the initial state — items render in their resting
    // open/closed position without a visible slide.
    setItemActive(item, defaultState === 'none' ? false : startsActive, false);
  });
};

// Re-apply remembered open state to every item in a container. Cheap and
// idempotent — safe to call on every mutation.
const restoreState = container => {
  container.querySelectorAll('.aae-a-accordion-item').forEach(item => {
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
const observeContainer = container => {
  if (container.__aaeStateObserved) return;
  container.__aaeStateObserved = true;
  const win = container.ownerDocument.defaultView || window;
  const observer = new win.MutationObserver(() => {
    // A re-render rebuilds items from the twig and empties the slots, so
    // re-distribute before restoring open state.
    distributeAll(container);
    restoreState(container);
  });
  observer.observe(container, {
    childList: true,
    subtree: true
  });
};
const toggleItem = item => {
  const accordion = item.closest('.aae-a-accordion');
  const header = item.querySelector('.aae-accordion-header');
  if (!header) return;
  const maxItemsExpanded = accordion ? accordion.dataset.maxItemsExpanded || 'one' : 'one';
  const isActive = item.classList.contains('active');

  // Animate user toggles in both the editor and the frontend. Height is
  // measured via measureNaturalHeight() so it's reliable even in the editor.
  const animate = true;

  // Close siblings when only one item may stay open.
  if (maxItemsExpanded === 'one' && !isActive && accordion) {
    accordion.querySelectorAll('.aae-a-accordion-item.active').forEach(other => {
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
const installDelegatedToggle = doc => {
  if (!doc || doc.__aaeAccordionDelegated) return;
  doc.__aaeAccordionDelegated = true;
  doc.addEventListener('click', e => {
    if (!e.target.closest) return;
    const item = e.target.closest('.aae-a-accordion-item');
    if (!item) return;
    if (isEditor(item)) {
      // In the editor, toggle ONLY on the bare header element area.
      if (!e.target.closest('.aae-header-element')) return;

      // …but ignore clicks that land on the inner child widgets
      // (title paragraph, open/close icons) so those stay selectable
      // and editable instead of toggling the item.
      if (e.target.closest('.aae-header-title-element') || e.target.closest('.aae-header-icon-element')) {
        return;
      }
    } else {
      // On the frontend, toggle on clicks anywhere in the item EXCEPT
      // inside the content area — otherwise interacting with the open
      // content would collapse it.
      if (e.target.closest('.aae-accordion-content-wrapper')) return;
    }
    e.preventDefault();
    toggleItem(item);
  }, true // capture phase
  );
};
const initAccordion = container => {
  if (!container) return;
  installDelegatedToggle(container.ownerDocument);
  distributeAll(container);
  applyDefaultState(container);
  observeContainer(container);
};
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: 'e-aae-a-accordion',
  id: 'aae-a-accordion-handler',
  callback: ({
    element
  }) => {
    const container = element.classList.contains('aae-a-accordion') ? element : element.querySelector('.aae-a-accordion');
    initAccordion(container);
  }
});

// Fallback bootstrap for the editor preview, where the frontend-handler
// callback may not fire. Initialise existing accordions and watch for ones
// added later (idempotent — guarded per element/document).
const bootstrap = doc => {
  if (!doc) return;
  installDelegatedToggle(doc);
  doc.querySelectorAll('.aae-a-accordion').forEach(initAccordion);
  if (doc.__aaeAccordionBootstrapped) return;
  doc.__aaeAccordionBootstrapped = true;
  const win = doc.defaultView || window;
  const docObserver = new win.MutationObserver(() => {
    doc.querySelectorAll('.aae-a-accordion').forEach(initAccordion);
  });
  docObserver.observe(doc.documentElement || doc.body, {
    childList: true,
    subtree: true
  });
};
bootstrap(document);

/* ------------------------------------------------------------------ *
 * Editor bridge control surface (window.AAEAccordion)
 *
 * The editor bridge (atomic-editor.js) suppresses Elementor's re-render for
 * accordion settings and instead patches the preview DOM in place. These
 * helpers let it re-seed / toggle live state without a re-render.
 * ------------------------------------------------------------------ */

const findItemById = id => {
  if (!id) return null;
  return document.querySelector('.aae-a-accordion-item[data-id="' + id + '"]');
};

// Re-seed open/closed state from the parent's `default_state` (used when the
// parent setting changes). `applyDefaultState` guards with
// `data-aae-state-applied`, so clear it first to force a fresh seed.
const reseedDefaultState = container => {
  if (!container) return;
  delete container.dataset.aaeStateApplied;
  // Forget remembered open state for THIS accordion's items only, so other
  // accordions on the page keep their state.
  container.querySelectorAll('.aae-a-accordion-item').forEach(item => {
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
  const maxItemsExpanded = accordion ? accordion.dataset.maxItemsExpanded || 'one' : 'one';
  if (active && maxItemsExpanded === 'one' && accordion) {
    accordion.querySelectorAll('.aae-a-accordion-item.active').forEach(other => {
      if (other !== item) setItemActive(other, false, true);
    });
  }
  setItemActive(item, !!active, true);
};

// Published on the preview iframe's window so the editor bridge can reach it.
window.AAEAccordion = window.AAEAccordion || {};
window.AAEAccordion.applyDefaultState = reseedDefaultState;
window.AAEAccordion.setItemActive = setItemActiveById;
}();
/******/ })()
;
//# sourceMappingURL=accordion.js.map