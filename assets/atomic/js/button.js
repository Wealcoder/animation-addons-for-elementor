/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./inc/AtomicWidgets/Widgets/Button/assets/scss/button.scss":
/*!******************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Button/assets/scss/button.scss ***!
  \******************************************************************/
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
/*!**************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Button/assets/js/button.js ***!
  \**************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @elementor/frontend-handlers */ "@elementor/frontend-handlers");
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _scss_button_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../scss/button.scss */ "./inc/AtomicWidgets/Widgets/Button/assets/scss/button.scss");


const rippleBtn = container => {
  // In V4 atomic the container IS the <a> root element, so check the element
  // itself before falling back to a descendant search (needed for V3 compat).
  const rippleBtn = container.classList.contains("btn-hover") ? container : container.querySelector(".btn-hover");
  if (!rippleBtn) return;
  const moveRipple = e => {
    // Prefer the named ripple element injected by twig; fall back to bare span.
    const span = rippleBtn.querySelector(".aae-ripple-el") || rippleBtn.querySelector("span:first-child");
    if (!span) return;
    const rect = rippleBtn.getBoundingClientRect();
    span.style.left = e.clientX - rect.left + "px";
    span.style.top = e.clientY - rect.top + "px";
  };
  rippleBtn.addEventListener("mouseenter", moveRipple);
  rippleBtn.addEventListener("mouseleave", moveRipple);
};
const groupSwap = container => {
  // const is_l = container.classList.contains("style-5");

  // if (!is_l) return null;

  console.log("inside grupSwat");
  const svgEl = container.querySelector(".e-svg-base");
  if (!svgEl) return null;
  const duplicatedSvg = svgEl.cloneNode(true);
  container.prepend(duplicatedSvg);
  // if (is_l) {
  // } else {
  //   container.append(duplicatedSvg);
  // }
};
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: "e-aae-a-button",
  id: "e-aae-a-button-handler",
  callback: ({
    element
  }) => {
    if (element.classList.contains("btn-hover")) {
      rippleBtn(element);
    } else if (element.classList.contains("aae-btn-pro-group")) {
      groupSwap(element);
    }
  }
});
}();
/******/ })()
;
//# sourceMappingURL=button.js.map