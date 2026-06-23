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

/***/ }),

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
/*!*******************************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/ToggleSwitcher/assets/js/toggle-switcher.js ***!
  \*******************************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @elementor/frontend-handlers */ "@elementor/frontend-handlers");
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _scss_toggle_switcher_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../scss/toggle-switcher.scss */ "./inc/AtomicWidgets/Widgets/ToggleSwitcher/assets/scss/toggle-switcher.scss");

// Persists toggle state across editor re-initializations, keyed by element data-id.
const toggleState = new Map();
const initToggleSwitcher = (container, signal) => {
  const input = container.querySelector('input[type="checkbox"]');
  const beforeLabel = container.querySelector('.before_label');
  const afterLabel = container.querySelector('.after_label');
  const switchLabel = container.querySelector('label.switcher');
  if (!input) return;
  const elementId = container.dataset.id || '';

  // Re-queries panes on every call so the function works regardless of when
  // child panes appear in the DOM (editor renders children asynchronously).
  const applyState = checked => {
    if (elementId) toggleState.set(elementId, checked);
    input.checked = checked;
    const panes = container.querySelectorAll('.aae-a-toggle-pane');
    const labels = container.querySelectorAll('.before_label, .after_label');
    panes.forEach((pane, i) => {
      const show = checked ? i === 1 : i === 0;
      pane.classList.toggle('show', show);
      // Use inline !important so Elementor's editor CSS (display:flex on
      // .e-con containers) cannot override the hidden state.
      if (show) {
        pane.style.removeProperty('display');
      } else {
        pane.style.setProperty('display', 'none', 'important');
      }
    });
    labels.forEach((label, i) => label.classList.toggle('active', checked ? i === 1 : i === 0));
  };

  // Capture phase fires before any ancestor/Elementor click handler that may
  // call preventDefault() and break the native label→checkbox link.
  // e.preventDefault() here stops the browser from also toggling the checkbox
  // via the `for` attribute, preventing a double-toggle.
  const captureOpts = signal ? {
    signal,
    capture: true
  } : {
    capture: true
  };
  beforeLabel?.addEventListener('click', e => {
    e.preventDefault();
    applyState(false);
  }, captureOpts);
  afterLabel?.addEventListener('click', e => {
    e.preventDefault();
    applyState(true);
  }, captureOpts);
  switchLabel?.addEventListener('click', e => {
    e.preventDefault();
    applyState(!input.checked);
  }, captureOpts);

  // Show the correct pane on init. Restores the last-known state so that
  // editor re-initializations (on settings change, etc.) don't reset the
  // visible pane back to the first one.
  const syncInitialState = () => {
    var _toggleState$get;
    const panes = container.querySelectorAll('.aae-a-toggle-pane');
    if (!panes.length) return false;
    const saved = elementId ? (_toggleState$get = toggleState.get(elementId)) !== null && _toggleState$get !== void 0 ? _toggleState$get : false : false;
    applyState(saved);
    return true;
  };
  if (!syncInitialState()) {
    const observer = new MutationObserver(() => {
      if (syncInitialState()) observer.disconnect();
    });
    observer.observe(container, {
      childList: true,
      subtree: true
    });
    if (signal) {
      signal.addEventListener('abort', () => observer.disconnect(), {
        once: true
      });
    }
  }
};
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: 'e-aae-a-toggle-switcher',
  id: 'aae-a-toggle-switcher-handler',
  callback: ({
    element,
    signal
  }) => {
    const container = element.classList.contains('aae-a-toggle-switcher') ? element : element.querySelector('.aae-a-toggle-switcher');
    if (container) initToggleSwitcher(container, signal);
  }
});
}();
/******/ })()
;
//# sourceMappingURL=toggle-switcher.js.map
