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


/* AAE Atomic Menu — vanilla JS. CSS handles transitions for desktop;
   mobile sub-menu uses Web Animations API for guaranteed visible effect. */

const DROPDOWN_EFFECT_KEYFRAMES = {
  slide: [{
    opacity: 0,
    transform: 'translateY(-18px) scale(0.95)'
  }, {
    opacity: 1,
    transform: 'translateY(0) scale(1)'
  }],
  fade: [{
    opacity: 0
  }, {
    opacity: 1
  }],
  'slide-fade': [{
    opacity: 0,
    transform: 'translateY(-40px)'
  }, {
    opacity: 1,
    transform: 'translateY(0)'
  }],
  scale: [{
    opacity: 0,
    transform: 'scale(0.55)'
  }, {
    opacity: 1,
    transform: 'scale(1)'
  }],
  zoom: [{
    opacity: 0,
    transform: 'scale(0.2)'
  }, {
    opacity: 1,
    transform: 'scale(1)'
  }],
  flip: [{
    opacity: 0,
    transform: 'perspective(800px) rotateX(-90deg)'
  }, {
    opacity: 1,
    transform: 'perspective(800px) rotateX(0deg)'
  }]
};

/* Drawer effects — applied as inline styles on the root + .aae-a-menu-nav.
   from/to drive the CSS transition via the --aae-drawer-*-transform custom
   props; navStyle handles per-effect anchor / dimensions (slide-top is full
   width pinned to top, fade/zoom-in are centered, etc.). */
const DRAWER_EFFECTS = {
  'slide-left': {
    from: 'translateX(-100%)',
    to: 'translateX(0)'
  },
  'slide-right': {
    from: 'translateX(100%)',
    to: 'translateX(0)',
    navStyle: {
      left: 'auto',
      right: '0',
      boxShadow: '-4px 0 32px rgba(0,0,0,0.18)'
    }
  },
  // `top` clears the WP admin bar, matching the CSS default in menu.scss. This
  // inline value would otherwise override it and re-hide the drawer header
  // behind the bar for logged-in users on this effect only.
  'slide-top': {
    from: 'translateY(-100%)',
    to: 'translateY(0)',
    navStyle: {
      left: '0',
      right: '0',
      top: 'var(--wp-admin--admin-bar--height, 0px)',
      bottom: 'auto',
      width: '100%',
      maxWidth: '100vw',
      height: 'auto',
      maxHeight: '85vh',
      boxShadow: '0 4px 32px rgba(0,0,0,0.18)'
    }
  },
  'slide-bottom': {
    from: 'translateY(100%)',
    to: 'translateY(0)',
    navStyle: {
      left: '0',
      right: '0',
      top: 'auto',
      bottom: '0',
      width: '100%',
      maxWidth: '100vw',
      height: 'auto',
      maxHeight: '85vh',
      boxShadow: '0 -4px 32px rgba(0,0,0,0.18)'
    }
  },
  fade: {
    from: 'none',
    to: 'none',
    navStyle: {
      left: '50%',
      right: 'auto',
      top: '50%',
      bottom: 'auto',
      width: 'min(var(--aae-drawer-w),92vw)',
      height: 'min(80vh,600px)',
      margin: 'calc(-1 * min(80vh,600px) / 2) 0 0 calc(-1 * min(var(--aae-drawer-w),92vw) / 2)',
      borderRadius: 'var(--aae-dd-r)',
      transformOrigin: 'center center'
    }
  },
  scale: {
    from: 'scale(0.7)',
    to: 'scale(1)',
    navStyle: {
      transformOrigin: 'left center'
    }
  },
  'zoom-in': {
    from: 'scale(0)',
    to: 'scale(1)',
    navStyle: {
      left: '50%',
      right: 'auto',
      top: '50%',
      bottom: 'auto',
      width: 'min(var(--aae-drawer-w),92vw)',
      height: 'min(80vh,600px)',
      margin: 'calc(-1 * min(80vh,600px) / 2) 0 0 calc(-1 * min(var(--aae-drawer-w),92vw) / 2)',
      borderRadius: 'var(--aae-dd-r)',
      transformOrigin: 'center center'
    }
  },
  flip: {
    from: 'perspective(1200px) rotateY(-90deg)',
    to: 'perspective(1200px) rotateY(0deg)',
    navStyle: {
      transformOrigin: 'left center'
    }
  }
};

// Inline-style keys we may write on the drawer nav per effect — used to
// fully reset before applying a new effect (so switching effects in the
// editor doesn't leave stale inline styles behind).
const DRAWER_NAV_RESET_KEYS = ['left', 'right', 'top', 'bottom', 'width', 'maxWidth', 'height', 'maxHeight', 'margin', 'borderRadius', 'transformOrigin', 'boxShadow'];
const applyDrawerEffect = (root, nav, effectName) => {
  const eff = DRAWER_EFFECTS[effectName] || DRAWER_EFFECTS['slide-left'];
  root.style.setProperty('--aae-drawer-from-transform', eff.from);
  root.style.setProperty('--aae-drawer-to-transform', eff.to);
  DRAWER_NAV_RESET_KEYS.forEach(k => {
    nav.style[k] = '';
  });
  if (eff.navStyle) Object.assign(nav.style, eff.navStyle);
};
const playSubMenuEffect = (subMenu, effectName, durationMs, direction, onFinish) => {
  const done = cancelled => {
    if (typeof onFinish === 'function') onFinish(cancelled === true);
  };
  if (!subMenu || typeof subMenu.animate !== 'function') {
    done();
    return;
  }
  const baseFrames = DROPDOWN_EFFECT_KEYFRAMES[effectName] || DROPDOWN_EFFECT_KEYFRAMES.slide;
  const frames = direction === 'close' ? [baseFrames[1], baseFrames[0]] : baseFrames;
  const easing = direction === 'close' ? 'cubic-bezier(.4, 0, .2, 1)' : 'cubic-bezier(.2, .8, .2, 1)';

  // Cancel any in-flight animation on this sub-menu so the new one starts clean
  if (typeof subMenu.getAnimations === 'function') {
    subMenu.getAnimations().forEach(a => {
      try {
        a.cancel();
      } catch (e) {/* already settled — nothing to cancel */}
    });
  }
  subMenu.style.transformOrigin = 'top center';
  subMenu.style.willChange = 'transform, opacity';

  // Wait one frame so display:none → display:flex has been committed and the
  // element has computed layout before the animation starts.
  requestAnimationFrame(() => {
    try {
      const anim = subMenu.animate(frames, {
        duration: durationMs,
        easing: easing,
        fill: 'both'
      });
      const cleanup = cancelled => {
        subMenu.style.willChange = '';
        done(cancelled);
      };
      if (anim && anim.finished && typeof anim.finished.then === 'function') {
        anim.finished.then(() => cleanup(false), () => cleanup(true));
      } else if (anim && typeof anim.addEventListener === 'function') {
        anim.addEventListener('finish', () => cleanup(false));
        anim.addEventListener('cancel', () => cleanup(true));
      } else {
        done();
      }
    } catch (e) {
      done();
    }
  });
};

/* ---------- Editor canvas: drop the wrapper's `display: contents` ----------
   Elementor core stamps `style="display: contents !important"` on the
   Marionette wrapper of EVERY atomic widget — see the atomic widget view's
   attributes() in editor-canvas.js, commented "Make the wrapper non-existent
   in terms of CSS to mimic the frontend DOM tree". It is there because
   Atomic_Widget_Base::before_render()/after_render() are empty, so on the
   frontend that wrapper does not exist at all and `.aae-a-menu` is a direct
   child of the container.

   Removed here for this widget only, on request, and paired with
   `width: fit-content` so the wrapper hugs the menu instead of stretching.
   Consequence worth knowing: the editor now has one layout box the frontend
   does not, so container flex/`max-width` rules land on the wrapper there.

   JS is the only lever — an inline `!important` outranks any stylesheet
   declaration, including `!important` ones.

   No-op on the frontend: there is no wrapper, so closest() returns null. */
const normalizeEditorWrapper = root => {
  const wrapper = root.closest('.elementor-widget-e-aae-a-menu');
  if (!wrapper) return;

  /**
   * The wrapper has to be exactly as wide as `.aae-a-menu` would be with no
   * wrapper at all, and that width is MODE-DEPENDENT — a constant desyncs the
   * editor from the frontend in whichever mode it does not match:
   *
   *   desktop  → fit-content, the width define_base_styles() gives the menu
   *   drawer   → 100%, because the mobile gate re-widens the row so
   *              `justify-content: flex-end` has free space to push the
   *              hamburger to the right edge
   *
   * Pinning it to fit-content collapsed the drawer row to the 40px button, so
   * flex-end had nothing to push against and the burger sat LEFT in the editor
   * while the frontend — which has no wrapper and so takes the full row —
   * showed it RIGHT.
   */
  const syncWidth = () => {
    const isDrawerRow = root.classList.contains('aae-a-menu--mobile') && root.getAttribute('data-hamburger') === 'true';
    const width = isDrawerRow ? '100%' : 'fit-content';
    if (wrapper.style.width !== width) {
      wrapper.style.setProperty('width', width);
    }
  };
  const apply = () => {
    if (wrapper.style.display === 'contents') {
      wrapper.style.removeProperty('display');
    }
    syncWidth();
  };
  apply();

  // attributes() is re-applied on every re-render, and the panel re-renders
  // on each setting change — so one pass is not enough. The guards inside
  // apply() mean our own writes don't re-trigger any work, so observing our
  // own target cannot loop. Bound once per wrapper node.
  if (typeof MutationObserver !== 'undefined' && !wrapper.__aaeUnwrapped) {
    wrapper.__aaeUnwrapped = true;
    new MutationObserver(apply).observe(wrapper, {
      attributes: true,
      attributeFilter: ['style']
    });

    // Watched on the ROOT, not the wrapper: syncMobileClass() toggles
    // `.aae-a-menu--mobile` on every resize — including each device-preview
    // switch — and that flips which of the two widths is correct. Without
    // this the wrapper keeps whichever width was right at first render, so
    // switching to a mobile device in the editor left it at fit-content.
    new MutationObserver(syncWidth).observe(root, {
      attributes: true,
      attributeFilter: ['class', 'data-hamburger']
    });
  }
};

/**
 * Collapse every open item BELOW `item`, leaving `item` itself alone.
 *
 * Closing a row only ever cleared its own `aae-a-menu-item--open`, so a nested
 * chain kept its classes while hidden and the entire previously-expanded tree
 * sprang back on the next open — which is what made a deep drawer look like it
 * never really closed. Re-opening now always starts collapsed.
 *
 * In-flight animations are cancelled too: a descendant mid-open would otherwise
 * keep its WAAPI `fill: both` end state and stay visible after losing the class.
 */
const closeDescendants = item => {
  item.querySelectorAll('.aae-a-menu-item--open').forEach(descendant => {
    descendant.classList.remove('aae-a-menu-item--open');
    const dArrow = descendant.querySelector(':scope > .aae-a-menu-arrow');
    if (dArrow) dArrow.setAttribute('aria-expanded', 'false');
    const dSub = descendant.querySelector(':scope > .sub-menu');
    if (dSub && typeof dSub.getAnimations === 'function') {
      dSub.getAnimations().forEach(a => {
        try {
          a.cancel();
        } catch (e) {/* already settled — nothing to cancel */}
      });
    }
  });
};
const initMenu = root => {
  normalizeEditorWrapper(root);
  if (root.dataset.aaeMenuInit === '1') return;
  root.dataset.aaeMenuInit = '1';
  const nav = root.querySelector('.aae-a-menu-nav');
  const toggle = root.querySelector('.aae-a-menu-toggle');
  const overlay = root.querySelector('.aae-a-menu-overlay');
  const closeBtn = root.querySelector('.aae-a-menu-close');
  if (!nav) return;

  // NOT `parseInt(...) || 768`: 0 is a legitimate value meaning "never switch
  // to mobile", and it is falsy, so the old form silently replaced it with the
  // 768 default. Only a genuinely unparseable attribute falls back now.
  const parsedBp = parseInt(root.getAttribute('data-breakpoint'), 10);
  const breakpoint = Number.isFinite(parsedBp) ? parsedBp : 768;
  const isHamburger = root.getAttribute('data-hamburger') === 'true';
  const isMobile = () => window.innerWidth <= breakpoint;

  /**
   * "Sub-menus expand INLINE instead of flying out" — true for the mobile drawer
   * AND for Layout = Vertical, which is the same interaction. Mirrors the
   * `$stacked` gate in menu.scss; the two must agree.
   *
   * Both attributes are read LIVE rather than closed over: `isHamburger` above is
   * captured once and goes stale the moment the builder toggles Mobile Hamburger
   * in the editor, and `data-layout` can change the same way.
   */
  const isStacked = () => root.getAttribute('data-layout') === 'vertical' || root.getAttribute('data-hamburger') === 'true' && isMobile();

  /**
   * Mirror isMobile() onto a class on the root.
   *
   * The stylesheet used to switch layouts with hardcoded
   * `@media (max-width: 768px)` / `(min-width: 769px)` blocks, which the
   * Mobile Breakpoint setting could never reach — it only ever fed this JS.
   * So the hamburger and the drawer appeared at 768px no matter what the
   * builder typed, and the field looked broken because it WAS inert for
   * everything visual. A media query cannot read a per-widget value, so the
   * gate is now `.aae-a-menu--mobile` and this is what sets it. One source of
   * truth: the same isMobile() that already drove the behaviour.
   */
  const syncMobileClass = () => {
    // Re-reads the attribute instead of closing over `breakpoint`. initMenu()
    // re-runs on every editor re-render, so a closure would pin the value
    // from whichever run bound the listener and go stale the moment the
    // builder edits the field. Stateless like this, one binding stays
    // correct forever — which is what makes the bind-once guard safe.
    const bp = parseInt(root.getAttribute('data-breakpoint'), 10);
    root.classList.toggle('aae-a-menu--mobile', window.innerWidth <= (Number.isFinite(bp) ? bp : 768));
  };
  syncMobileClass();
  if (!root.__aaeBpBound) {
    root.__aaeBpBound = true;
    window.addEventListener('resize', syncMobileClass);
  }
  let dropdownEffect = root.getAttribute('data-dropdown-effect') || 'slide';
  const transitionMs = parseInt(root.style.getPropertyValue('--aae-menu-transition'), 10) || 250;

  // Apply drawer effect (transform vars + per-effect nav positioning) on init,
  // and keep it in sync when the editor changes the data-drawer-effect attribute.
  applyDrawerEffect(root, nav, root.getAttribute('data-drawer-effect') || 'slide-left');
  if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(() => {
      applyDrawerEffect(root, nav, root.getAttribute('data-drawer-effect') || 'slide-left');
      dropdownEffect = root.getAttribute('data-dropdown-effect') || 'slide';
    }).observe(root, {
      attributes: true,
      attributeFilter: ['data-drawer-effect', 'data-dropdown-effect']
    });
  }

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
        const wasOpen = item.classList.contains('aae-a-menu-item--open');
        const duration = Math.round(transitionMs * 1.4);
        if (wasOpen) {
          // Closing — when stacked, play the reverse effect THEN remove the
          // class so the close is visible. For a flyout, remove immediately
          // (CSS handles that transition).
          if (isStacked()) {
            arrow.setAttribute('aria-expanded', 'false');
            playSubMenuEffect(subMenu, dropdownEffect, duration, 'close', () => {
              item.classList.remove('aae-a-menu-item--open');
              // AFTER the animation, not before: collapsing the subtree
              // first would yank the rows out from under a still-visible
              // panel and the close would play against a collapsing
              // height. By here the sub-menu is display:none, so the
              // reset is invisible.
              closeDescendants(item);
            });
          } else {
            item.classList.remove('aae-a-menu-item--open');
            arrow.setAttribute('aria-expanded', 'false');
            closeDescendants(item);
          }
          return;
        }

        // Opening
        item.classList.add('aae-a-menu-item--open');
        arrow.setAttribute('aria-expanded', 'true');

        // Close siblings at the same level
        if (item.parentElement) {
          Array.from(item.parentElement.children).forEach(sib => {
            if (sib !== item && sib.classList && sib.classList.contains('aae-a-menu-item--open')) {
              const sArrow = sib.querySelector(':scope > .aae-a-menu-arrow');
              const sSub = sib.querySelector(':scope > .sub-menu');
              sib.classList.remove('aae-a-menu-item--open');
              if (sArrow) sArrow.setAttribute('aria-expanded', 'false');
              // Same reset the manual close does — a sibling auto-closed
              // here would otherwise keep its own expanded subtree and
              // restore it wholesale when the visitor returns to it.
              closeDescendants(sib);
              if (isStacked() && sSub) {
                // Sibling closes simultaneously — just cancel its animation;
                // removing the class above hides it via CSS.
                if (typeof sSub.getAnimations === 'function') {
                  sSub.getAnimations().forEach(a => {
                    try {
                      a.cancel();
                    } catch (e) {/* already settled — nothing to cancel */}
                  });
                }
              }
            }
          });
        }

        // Stacked-only: play the picked dropdown effect via Web Animations API.
        // A stacked sub-menu goes display:none → flex, which is not
        // transitionable, so without this it would pop open with no motion —
        // this is what gives Layout = Vertical the Sub-menu Dropdown Effect.
        if (isStacked()) {
          playSubMenuEffect(subMenu, dropdownEffect, duration, 'open');
        }
      });

      /* Open On = Click: the parent LINK toggles instead of navigating.
         Without this, "click" would mean "click the small arrow", which is
         not what the setting says and is a much smaller hit target.
      		   It forwards to arrow.click() rather than repeating the toggle: that
         logic handles siblings, aria-expanded and the mobile effect, and a
         second copy would drift from it. Read live from the attribute so
         switching the setting in the editor takes effect without a rebind,
         and skipped on mobile, where the drawer is arrow-driven and a parent
         link must stay navigable. */
      const link = item.querySelector(':scope > a');
      if (link) {
        link.addEventListener('click', e => {
          if (root.getAttribute('data-dropdown-trigger') !== 'click') return;
          if (isMobile()) return;
          e.preventDefault();
          arrow.click();
        });
      }
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

  /* ---------- Editor preview AJAX fallback ----------
     The menu markup is built by wp_nav_menu() in get_atomic_settings(), which
     is PHP — so it only exists on a server render. The editor canvas renders
     this Twig CLIENT-side, where `settings.rendered_menu` is simply absent and
     the template falls through to `.aae-a-menu-placeholder`. Fetching the real
     markup is the only way the builder ever sees their menu.
  
     The trigger is the PLACEHOLDER, not an edit-mode test. That is deliberate:
     `elementorFrontend.isEditMode()` is not dependable inside the v4 canvas
     (counter.js already pairs it with an `elementor-editor-active` body check
     for the same reason), and gating on it meant one false negative left the
     builder staring at "Menu rendered on frontend / preview." forever. The
     placeholder is the exact, self-scoping signal for "this render has no menu
     HTML in it" — on the frontend get_atomic_settings() always fills
     `rendered_menu`, so the placeholder is never present and this never runs. */
  const placeholder = nav.querySelector('.aae-a-menu-placeholder');
  const body = nav.querySelector('.aae-a-menu-nav-body');
  const slug = nav.getAttribute('data-menu-slug');
  if (placeholder && slug) {
    // AAE_MENU_CFG.ajaxUrl is admin_url('admin-ajax.php'), localized onto
    // this handle in the editor preview. It is FIRST because it is the only
    // entry that is right on every install layout — the root-relative last
    // resort points at the NETWORK MAIN SITE on a subdirectory multisite
    // (a subsite's admin is /<site>/wp-admin/), which is why the editor
    // preview worked on a single site and silently failed on a network.
    const ajaxUrl = window.AAE_MENU_CFG && window.AAE_MENU_CFG.ajaxUrl || window.elementorFrontend && window.elementorFrontend.config && window.elementorFrontend.config.ajaxurl || window.ajaxurl || '/wp-admin/admin-ajax.php';
    fetch(`${ajaxUrl}?action=aae_get_menu_html&menu=${encodeURIComponent(slug)}`, {
      // admin-ajax authenticates by cookie; without this the request is
      // anonymous, the priv-only action never matches and it 400s.
      credentials: 'same-origin'
    }).then(r => r.json()).then(data => {
      if (data && data.success && data.data && body) {
        body.innerHTML = data.data;
        closeAllSubmenus = buildDropdowns();
      }
    }).catch(() => {});
  } else if (placeholder && !slug) {
    // No menu chosen yet. Say so, instead of the frontend/preview line that
    // reads as "this is fine, it'll show later" when in fact nothing will.
    placeholder.textContent = 'Please select a menu';
    closeAllSubmenus = buildDropdowns();
  } else {
    closeAllSubmenus = buildDropdowns();
  }

  /* ---------- Outside-click + Escape closes desktop dropdowns ---------- */
  document.addEventListener('click', e => {
    // Flyouts only. An inline accordion (drawer or Layout = Vertical) must not
    // collapse because the visitor clicked somewhere unrelated on the page.
    if (isStacked()) return;
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
}();
/******/ })()
;
//# sourceMappingURL=menu.js.map