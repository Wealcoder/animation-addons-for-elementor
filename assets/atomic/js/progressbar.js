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



/**
 * Generic marker-class handler for the AAE Progress Bar wrapper — no stored
 * `style` setting to branch on (see class-aae-a-progressbar.php), so unlike
 * ProgressbarMain's handler this one auto-detects the shape from whichever
 * children a preset (or hand-editing) actually supplied:
 *   .progressbar-path  → circle (stroke-dashoffset ring)
 *   .dot                → dot (sequential reveal)
 *   .progressbar-fill  → line (width fill) — the fallback/default look
 */

const DURATION = 1400;
const CIRCLE_RADIUS = 40;
const CIRCLE_CIRCUMFERENCE = 2 * Math.PI * CIRCLE_RADIUS; // ≈ 251.327

/**
 * Animate a numeric counter from 0 to `to` over `duration` ms,
 * writing `value + '%'` into `el` on each frame.
 */
function animateCounter(el, to, duration) {
  const startTime = performance.now();
  (function tick(now) {
    const progress = Math.min((now - startTime) / duration, 1);
    el.textContent = Math.round(progress * to) + '%';
    if (progress < 1) requestAnimationFrame(tick);
  })(performance.now());
}
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: 'e-aae-a-progressbar',
  id: 'e-aae-a-progressbar-handler',
  callback: ({
    element
  }) => {
    const el = element;
    if (!el) return;
    const pct = parseFloat(el.dataset.pbPercentage || 50) / 100;
    const showPct = el.dataset.pbDisplayPercentage === 'true';
    const pctEl = showPct ? el.querySelector('.aae-pb-pct') : null;

    // ── Circle ───────────────────────────────────────────────────────────
    const path = el.querySelector('.progressbar-path');
    if (path) {
      path.style.transition = 'none';
      path.style.strokeDasharray = String(CIRCLE_CIRCUMFERENCE);
      path.style.strokeDashoffset = String(CIRCLE_CIRCUMFERENCE);
      requestAnimationFrame(() => {
        path.style.transition = '';
        requestAnimationFrame(() => {
          path.style.strokeDashoffset = String(CIRCLE_CIRCUMFERENCE * (1 - pct));
        });
      });
      if (pctEl) animateCounter(pctEl, Math.round(pct * 100), DURATION);
      return;
    }

    // ── Dot ──────────────────────────────────────────────────────────────
    // No `.dot.active` CSS rule on purpose: "active" isn't a real pseudo
    // class Elementor's Style tab can target, so instead of hardcoding a
    // fill colour here we borrow whatever native border-color the user
    // already set per dot — the active fill always matches it, and it
    // stays editable from the Style tab like everything else.
    const dots = el.querySelectorAll('.dot');
    if (dots.length) {
      const active = Math.round(pct * dots.length);
      dots.forEach((dot, i) => {
        setTimeout(() => {
          if (i < active) {
            dot.style.backgroundColor = getComputedStyle(dot).borderColor;
            dot.style.opacity = '1';
          } else {
            dot.style.backgroundColor = '';
            dot.style.opacity = '';
          }
        }, i * 150);
      });
      return;
    }

    // ── Line ─────────────────────────────────────────────────────────────
    const fill = el.querySelector('.progressbar-fill');
    if (!fill) return;

    // Elementor's frontend CSS shrinks the span to its natural content width, so
    // width:100% computes to the span's own text width rather than the container.
    // translateX(-50%) centres without depending on any percentage-based width.
    // if ( pctEl ) {
    // 	pctEl.style.position  = 'absolute';
    // 	pctEl.style.top       = '0';
    // 	pctEl.style.left      = '50%';
    // 	pctEl.style.transform = 'translateX(-50%)';
    // }

    fill.style.transition = 'none';
    fill.style.width = '0%';
    requestAnimationFrame(() => {
      fill.style.transition = '';
      requestAnimationFrame(() => {
        fill.style.width = pct * 100 + '%';
      });
    });
    if (pctEl) animateCounter(pctEl, Math.round(pct * 100), DURATION);
  }
});
}();
/******/ })()
;
//# sourceMappingURL=progressbar.js.map