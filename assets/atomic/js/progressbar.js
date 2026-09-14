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
/*!************************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Progressbar/assets/js/progressbar.js ***!
  \************************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @elementor/frontend-handlers */ "@elementor/frontend-handlers");
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__);

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
function easeInOutQuad(t) {
  return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
}
function animateWidth(el, toPercent, duration) {
  el.style.transition = 'none';
  el.style.width = '0%';
  const startTime = performance.now();
  (function tick(now) {
    const progress = Math.min((now - startTime) / duration, 1);
    el.style.width = easeInOutQuad(progress) * toPercent + '%';
    if (progress < 1) requestAnimationFrame(tick);
  })(performance.now());
}

/** Run the actual reveal — called once the progress bar scrolls into view. */
function playProgressBar(el) {
  const pct = parseFloat(el.dataset.pbPercentage || 50) / 100;
  const showPct = el.dataset.pbDisplayPercentage === 'true';
  const pctEl = showPct ? el.querySelector('.aae-pb-pct') : null;

  // ── Circle ───────────────────────────────────────────────────────────
  const path = el.querySelector('.aae-progressbar-path');
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
  const dots = el.querySelectorAll('.aae-progressbar-dot');
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
  const fill = el.querySelector('.aae-progressbar-fill');
  if (!fill) return;
  animateWidth(fill, pct * 100, DURATION);
  if (pctEl) animateCounter(pctEl, Math.round(pct * 100), DURATION);
}
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: 'e-aae-a-progressbar',
  id: 'e-aae-a-progressbar-handler',
  callback: ({
    element
  }) => {
    const el = element;
    if (!el) return;

    // Fire the reveal once the bar actually enters the viewport — by
    // scrolling down to it, or because it's already in view on a fresh
    // page load/refresh (IntersectionObserver reports that immediately
    // on observe(), so both cases are covered by the same code path).
    if (typeof IntersectionObserver === 'undefined') {
      playProgressBar(el);
      return;
    }
    const observer = new IntersectionObserver((entries, obs) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          playProgressBar(el);
          obs.disconnect();
        }
      }
    }, {
      threshold: 0.3
    });
    observer.observe(el);
    return () => observer.disconnect();
  }
});
}();
/******/ })()
;
//# sourceMappingURL=progressbar.js.map