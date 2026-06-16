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
/*!**********************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/Menu/assets/js/menu.js ***!
  \**********************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @elementor/frontend-handlers */ "@elementor/frontend-handlers");
/* harmony import */ var _elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__);


/* AAE Atomic Menu — minimal vanilla JS. CSS does all transitions. */

const initMenu = root => {
  if (root.dataset.aaeMenuInit === '1') return;
  root.dataset.aaeMenuInit = '1';
  const nav = root.querySelector('.aae-a-menu-nav');
  const toggle = root.querySelector('.aae-a-menu-toggle');
  const overlay = root.querySelector('.aae-a-menu-overlay');
  const closeBtn = root.querySelector('.aae-a-menu-close');
  if (!nav) return;
  const breakpoint = parseInt(root.getAttribute('data-breakpoint'), 10) || 768;
  const isHamburger = root.getAttribute('data-hamburger') === 'true';
  const isMobile = () => window.innerWidth <= breakpoint;

  /* ---------- Dropdown arrows + click-to-toggle ---------- */
  const buildDropdowns = () => {
    const list = nav.querySelector('.aae-a-menu-list');
    if (!list) return null;
    list.querySelectorAll('.menu-item-has-children').forEach(item => {
      if (item.querySelector(':scope > .aae-a-menu-arrow')) return;
      const subMenu = item.querySelector(':scope > .sub-menu');
      if (!subMenu) return;
      const arrow = document.createElement('button');
      arrow.type = 'button';
      arrow.className = 'aae-a-menu-arrow';
      arrow.setAttribute('aria-label', 'Toggle submenu');
      arrow.setAttribute('aria-expanded', 'false');
      // Insert BEFORE the sub-menu so DOM order is: link → arrow → sub-menu.
      // (Sub-menu is flex-basis:100% on mobile, so anything after it gets pushed
      // to its own row, which is why arrows were appearing below.)
      item.insertBefore(arrow, subMenu);
      arrow.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        const open = item.classList.toggle('aae-a-menu-item--open');
        arrow.setAttribute('aria-expanded', open ? 'true' : 'false');

        // Close siblings at the same level
        if (open && item.parentElement) {
          Array.from(item.parentElement.children).forEach(sib => {
            if (sib !== item && sib.classList && sib.classList.contains('aae-a-menu-item--open')) {
              sib.classList.remove('aae-a-menu-item--open');
              const sArrow = sib.querySelector(':scope > .aae-a-menu-arrow');
              if (sArrow) sArrow.setAttribute('aria-expanded', 'false');
            }
          });
        }
      });
    });
    return () => {
      list.querySelectorAll('.aae-a-menu-item--open').forEach(el => {
        el.classList.remove('aae-a-menu-item--open');
        const a = el.querySelector(':scope > .aae-a-menu-arrow');
        if (a) a.setAttribute('aria-expanded', 'false');
      });
    };
  };
  let closeAllSubmenus = null;

  /* ---------- Editor preview AJAX fallback ---------- */
  const inEditor = !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
  const placeholder = nav.querySelector('.aae-a-menu-placeholder');
  if (inEditor && placeholder) {
    const slug = nav.getAttribute('data-menu-slug');
    if (slug) {
      const ajaxUrl = window.elementorFrontend && window.elementorFrontend.config && window.elementorFrontend.config.ajaxurl || window.ajaxurl || '/wp-admin/admin-ajax.php';
      fetch(`${ajaxUrl}?action=aae_get_menu_html&menu=${encodeURIComponent(slug)}`).then(r => r.json()).then(data => {
        if (data && data.success && data.data) {
          const body = nav.querySelector('.aae-a-menu-nav-body');
          if (body) body.innerHTML = data.data;
          closeAllSubmenus = buildDropdowns();
        }
      }).catch(() => {});
    }
  } else {
    closeAllSubmenus = buildDropdowns();
  }

  /* ---------- Outside-click + Escape closes desktop dropdowns ---------- */
  document.addEventListener('click', e => {
    if (isMobile()) return;
    if (!root.contains(e.target) && typeof closeAllSubmenus === 'function') {
      closeAllSubmenus();
    }
  });

  /* ---------- Mobile drawer ---------- */
  if (!isHamburger || !toggle) return;
  const openDrawer = () => {
    root.classList.add('aae-a-menu--open');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('aae-a-menu-body-lock');
  };
  const closeDrawer = () => {
    root.classList.remove('aae-a-menu--open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('aae-a-menu-body-lock');
    if (typeof closeAllSubmenus === 'function') closeAllSubmenus();
  };
  toggle.addEventListener('click', e => {
    e.preventDefault();
    if (root.classList.contains('aae-a-menu--open')) closeDrawer();else openDrawer();
  });
  if (closeBtn) closeBtn.addEventListener('click', e => {
    e.preventDefault();
    closeDrawer();
  });
  if (overlay) overlay.addEventListener('click', e => {
    e.preventDefault();
    closeDrawer();
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && root.classList.contains('aae-a-menu--open')) closeDrawer();
  });
  window.addEventListener('resize', () => {
    if (!isMobile() && root.classList.contains('aae-a-menu--open')) closeDrawer();
  });
};
(0,_elementor_frontend_handlers__WEBPACK_IMPORTED_MODULE_0__.register)({
  elementType: 'e-aae-a-menu',
  id: 'aae-a-menu-handler',
  callback: ({
    element
  }) => {
    const root = element.classList.contains('aae-a-menu') ? element : element.querySelector('.aae-a-menu');
    if (root) initMenu(root);
  }
});

/* Fallback: also init on DOMContentLoaded for non-Elementor contexts */
const initAll = () => document.querySelectorAll('.aae-a-menu').forEach(initMenu);
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAll);
} else {
  initAll();
}
}();
/******/ })()
;
//# sourceMappingURL=menu.js.map