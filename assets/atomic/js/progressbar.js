/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./inc/AtomicWidgets/Widgets/Progressbar/assets/scss/progressbar.scss":
/*!****************************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Progressbar/assets/scss/progressbar.scss ***!
  \****************************************************************************/
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
/*!************************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Progressbar/assets/js/progressbar.js ***!
  \************************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @elementor/frontend-handlers */ "@elementor/frontend-handlers");
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _scss_progressbar_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../scss/progressbar.scss */ "./inc/AtomicWidgets/Widgets/Progressbar/assets/scss/progressbar.scss");


(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: 'e-aae-a-progressbar',
  id: 'e-aae-a-progressbar-handler',
  callback: ({
    element
  }) => {
    const el = element;
    if (!el) return;
    const type = el.dataset.pbType || 'line';
    const pct = parseFloat(el.dataset.pbPercentage || 50) / 100;
    const color = el.dataset.pbColor || '#7DDED8';
    const trailColor = el.dataset.pbBgColor || '#eee';
    const strokeWidth = parseFloat(el.dataset.pbStrokeWidth || 2);
    const trailWidth = parseFloat(el.dataset.pbTrailWidth || 1);
    const showPct = el.dataset.pbDisplayPercentage === 'true';

    // Dot style: activate spans based on percentage
    if (type === 'dot') {
      const dots = el.querySelectorAll('.dot');
      const active = Math.round(pct * dots.length);
      dots.forEach((dot, i) => dot.classList.toggle('active', i < active));
      return;
    }

    // Line / Circle: delegate to ProgressBar.js (expected to be a global)
    if (typeof ProgressBar === 'undefined') {
      // eslint-disable-next-line no-console
      console.warn('AAE Progressbar: ProgressBar.js library not found.');
      return;
    }
    const container = el.querySelector('.progressbar');
    if (!container) return;

    // Clear any previously-injected SVG so re-runs in the editor don't stack bars.
    container.innerHTML = '';
    const opts = {
      color,
      trailColor,
      strokeWidth,
      trailWidth,
      duration: 1400,
      easing: 'easeInOut'
    };
    if (showPct) {
      opts.text = {
        style: {
          color: 'var(--pb-percentage-color, inherit)',
          position: 'absolute'
        },
        autoStyleContainer: false
      };
      opts.step = (state, bar) => {
        bar.setText(Math.round(bar.value() * 100) + '%');
      };
    }
    const bar = type === 'circle' ? new ProgressBar.Circle(container, opts) : new ProgressBar.Line(container, opts);
    bar.animate(pct);
  }
});
}();
/******/ })()
;
//# sourceMappingURL=progressbar.js.map