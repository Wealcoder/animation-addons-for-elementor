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
/*!****************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Counter/assets/js/counter.js ***!
  \****************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @elementor/frontend-handlers */ "@elementor/frontend-handlers");
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__);

const initCounter = element => {
  // Prevent double initialization
  if (element.classList.contains('aae-counter-initialized')) return;
  element.classList.add('aae-counter-initialized');
  const from = parseFloat(element.getAttribute('data-from')) || 0;
  const to = parseFloat(element.getAttribute('data-to')) || 100;
  const durationMs = parseFloat(element.getAttribute('data-duration')) || 2000;
  const durationSec = durationMs / 1000;
  if (typeof window.gsap !== 'undefined' && window.gsap.ScrollTrigger) {
    window.gsap.fromTo(element, {
      innerHTML: from
    }, {
      innerHTML: to,
      duration: durationSec,
      snap: {
        innerHTML: 1
      },
      ease: "power1.out",
      scrollTrigger: {
        trigger: element,
        start: "top 85%",
        // Start when element is 85% down the viewport
        toggleActions: "play none none none"
      }
    });
  } else if (typeof window.gsap !== 'undefined') {
    // Fallback if ScrollTrigger is missing
    window.gsap.fromTo(element, {
      innerHTML: from
    }, {
      innerHTML: to,
      duration: durationSec,
      snap: {
        innerHTML: 1
      },
      ease: "power1.out"
    });
  } else {
    // Fallback if GSAP is not loaded
    element.innerHTML = to;
  }
};
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: 'e-aae-a-counter',
  id: 'e-aae-a-counter-handler',
  callback: ({
    element
  }) => {
    const numberEl = element.querySelector('.aae-a-counter-number');
    if (numberEl) {
      // Remove init class so it safely re-animates when settings change in the editor
      numberEl.classList.remove('aae-counter-initialized');
      initCounter(numberEl);
    }
  }
});

// Fallback for frontend non-Elementor rendering context
}();
/******/ })()
;
//# sourceMappingURL=counter.js.map