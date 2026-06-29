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


const textFlipSetup = container => {
  const spanEl = container.querySelector(".e-paragraph-base span, :scope > span");
  if (!spanEl) return;
  if (!spanEl.dataset.text) {
    spanEl.dataset.text = spanEl.textContent.trim();
  }
};

// Re-sync the visible clones in a btn-border-divide button from the live originals.
const syncBorderDivideClones = btn => {
  const textWrapper = btn.querySelector(":scope > span.text");
  const iconWrapper = btn.querySelector(":scope > span.icon");
  if (textWrapper) {
    const liveText = btn.querySelector(".elementor-widget-e-paragraph .e-paragraph-base, :scope > .e-paragraph-base");
    const clone = textWrapper.querySelector(".e-paragraph-base");
    if (liveText && clone && liveText.innerHTML !== clone.innerHTML) {
      clone.innerHTML = liveText.innerHTML;
    }
  }
  if (iconWrapper) {
    const liveSvg = btn.querySelector(".elementor-widget-e-svg .e-svg-base, :scope > .e-svg-base");
    if (liveSvg) {
      iconWrapper.querySelectorAll(".e-svg-base").forEach(clone => {
        if (clone.innerHTML !== liveSvg.innerHTML) {
          clone.innerHTML = liveSvg.innerHTML;
        }
      });
    }
  }
};
const borderDivideSwap = container => {
  // Clean up any previous swap wrappers before re-running
  const existingText = container.querySelector(":scope > span.text");
  const existingIcon = container.querySelector(":scope > span.icon");
  if (existingText) existingText.remove();
  if (existingIcon) existingIcon.remove();

  // Find elements from Elementor's display:contents wrappers
  const svgEl = container.querySelector(".elementor-widget-e-svg .e-svg-base, :scope > .e-svg-base");
  if (!svgEl) return null;
  const textEl = container.querySelector(".elementor-widget-e-paragraph .e-paragraph-base, :scope > .e-paragraph-base");
  if (!textEl) return null;

  // Clone the svg for the duplicate
  const duplicatedSvg = svgEl.cloneNode(true);
  duplicatedSvg.setAttribute("data-swap-clone", "true");
  // Remove interaction id from clone to avoid duplicates
  duplicatedSvg.removeAttribute("data-interaction-id");

  // Build wrappers
  const textWrapper = document.createElement("span");
  textWrapper.classList.add("text");
  const iconWrapper = document.createElement("span");
  iconWrapper.classList.add("icon");

  // Append cloned content (don't move originals — they live inside display:contents wrappers)
  const clonedText = textEl.cloneNode(true);
  clonedText.removeAttribute("draggable");
  textWrapper.appendChild(clonedText);
  iconWrapper.appendChild(svgEl.cloneNode(true));
  iconWrapper.appendChild(duplicatedSvg);

  // Prepend both wrappers
  container.prepend(iconWrapper);
  container.prepend(textWrapper);
  container.dataset.borderDivideSwapped = "true";
};
const maskBtn = container => {
  const textEl = container.querySelector(".e-paragraph-base");
  if (!textEl) return;
  container.setAttribute("data-text", textEl.textContent.trim());
};

// When a child atomic widget (e-paragraph or e-svg) is re-rendered by the
// Elementor editor, it replaces its own DOM element. Any MutationObserver
// placed on the old element becomes deaf. Instead, hook into Elementor's
// frontend/element_ready event — fired for every widget render including
// editor live-updates — and re-sync the visible clones from the new DOM.
// We register these hooks once (after elementorFrontend is guaranteed ready)
// using a flag so repeated button initializations don't stack duplicates.
function hookChildReadyOnce() {
  if (window._aaeBorderDivideHooked) return;
  if (!window.elementorFrontend?.hooks) return;
  window._aaeBorderDivideHooked = true;
  const onChildReady = $scope => {
    const btn = $scope?.[0]?.closest?.(".btn-border-divide");
    if (btn) syncBorderDivideClones(btn);
  };
  elementorFrontend.hooks.addAction("frontend/element_ready/e-paragraph", onChildReady);
  elementorFrontend.hooks.addAction("frontend/element_ready/e-svg", onChildReady);
}
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: "e-aae-a-button",
  id: "e-aae-a-button-handler",
  callback: ({
    element
  }) => {
    if (element.classList.contains("btn-border-divide")) {
      borderDivideSwap(element);
      hookChildReadyOnce();
    } else if (element.classList.contains("btn-text-flip")) {
      textFlipSetup(element);
    } else if (element.classList.contains("wcf-btn-mask")) {
      maskBtn(element);
    }
  }
});
}();
/******/ })()
;
//# sourceMappingURL=button.js.map