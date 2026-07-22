/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./inc/AtomicWidgets/Widgets/ToggleSwitcher/assets/scss/toggle-switcher.scss":
/*!***********************************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/ToggleSwitcher/assets/scss/toggle-switcher.scss ***!
  \***********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


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
/*!*******************************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/ToggleSwitcher/assets/js/toggle-switcher.js ***!
  \*******************************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _scss_toggle_switcher_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../scss/toggle-switcher.scss */ "./inc/AtomicWidgets/Widgets/ToggleSwitcher/assets/scss/toggle-switcher.scss");


// Persists active state across editor re-initializations, keyed by
// switcher data-id — mirrors ToggleSwitcherMain's toggleState map.
const tsState = new Map();
function findSwitcher(el) {
  return el.closest('.aae-a-toggle-switcher');
}
function applyTsState(wrapper, checked) {
  const id = wrapper.dataset.id || '';
  if (id) tsState.set(id, checked);
  const panes = wrapper.querySelectorAll('.aae-ts-pane');
  panes.forEach((pane, i) => {
    const show = checked ? i === 1 : i === 0;
    pane.classList.toggle('show', show);
    // Inline !important so Elementor's editor CSS (display:flex on .e-con
    // containers) cannot override the hidden state.
    if (show) {
      pane.style.removeProperty('display');
    } else {
      pane.style.setProperty('display', 'none', 'important');
    }
  });
  const before = wrapper.querySelector('.aae-ts-label-before');
  const after = wrapper.querySelector('.aae-ts-label-after');
  // `active` drives legacy Switch/Label-Highlight preset CSS; `e--selected`
  // is Elementor's own class-based Style-panel state (Style_States::SELECTED)
  // — the default Tab widget's "active tab" look (accent color + underline)
  // is a real Style-panel state keyed off this class, not raw CSS. Both are
  // toggled together so either mechanism works no matter which preset (or
  // the plain default) supplied the tabs.
  before?.classList.toggle('active', !checked);
  before?.classList.toggle('e--selected', !checked);
  before?.setAttribute('aria-selected', String(!checked));
  after?.classList.toggle('active', checked);
  after?.classList.toggle('e--selected', checked);
  after?.setAttribute('aria-selected', String(checked));
  wrapper.querySelectorAll('.aae-ts-switch').forEach(el => el.classList.toggle('active', checked));
}
function syncInitialState(wrapper) {
  var _tsState$get;
  const panes = wrapper.querySelectorAll('.aae-ts-pane');
  if (!panes.length) return false;
  const id = wrapper.dataset.id || '';
  const saved = id ? (_tsState$get = tsState.get(id)) !== null && _tsState$get !== void 0 ? _tsState$get : false : false;
  applyTsState(wrapper, saved);
  return true;
}
function initAllSwitchers() {
  document.querySelectorAll('.aae-a-toggle-switcher').forEach(syncInitialState);
}

// Capture phase fires before any ancestor/Elementor click handler that may
// call preventDefault(). Delegation on document means newly-inserted labels,
// switches, or panes (editor mounts atomic widgets asynchronously) work
// without any per-element rebinding.
document.addEventListener('click', e => {
  const trigger = e.target.closest('.aae-ts-label-before, .aae-ts-label-after, .aae-ts-switch');
  if (!trigger) return;
  const wrapper = findSwitcher(trigger);
  if (!wrapper) return;
  e.preventDefault();
  if (trigger.classList.contains('aae-ts-label-before')) {
    applyTsState(wrapper, false);
  } else if (trigger.classList.contains('aae-ts-label-after')) {
    applyTsState(wrapper, true);
  } else {
    var _tsState$get2;
    const id = wrapper.dataset.id || '';
    const current = id ? (_tsState$get2 = tsState.get(id)) !== null && _tsState$get2 !== void 0 ? _tsState$get2 : false : false;
    applyTsState(wrapper, !current);
  }
}, true);

// Run once for whatever is already in the DOM…
initAllSwitchers();

// …and again whenever the DOM changes. Elementor's editor preview mounts
// atomic widgets asynchronously (they may not exist yet on first run above),
// and can also replace a widget's markup on selection/setting changes,
// wiping the pane's inline display override. Disconnect while mutating so
// this observer doesn't react to its own changes.
const tsObserver = new MutationObserver(() => {
  tsObserver.disconnect();
  initAllSwitchers();
  tsObserver.observe(document.body, {
    childList: true,
    subtree: true
  });
});
tsObserver.observe(document.body, {
  childList: true,
  subtree: true
});
}();
/******/ })()
;
//# sourceMappingURL=toggle-switcher.js.map