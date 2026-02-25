(function () {

  "use strict";

  function initStarterAnimations(scope) {
    const wrapper = scope[0];
    if (!wrapper) return;
    // avoid repeated initialization for the same wrapper
    if (wrapper.dataset && wrapper.dataset.wcfInitDone) return;
    try { wrapper.dataset.wcfInitDone = '1'; } catch(e) {}
    // debug log only once per wrapper
    try { if (!wrapper.dataset.wcfInitLogged) { console.log('Initializing Starter Animations', wrapper); wrapper.dataset.wcfInitLogged = '1'; } } catch(e) {}

    const hasStarterAnimation = [...wrapper.classList].some(cls =>
      cls.startsWith('wcf-starter-animations-')
    );

    if (!hasStarterAnimation) return;

    /* ==========================================
      CHARACTER TEXT SUPPORT (ADDED BACK)
    ========================================== */

    if ([...wrapper.classList].some(cls =>
      cls.startsWith('wcf-starter-animations-text-char')
    )) {

      let target = wrapper.querySelector('.elementor-widget-container > *');
      if (!target) {
        // fallback to direct first element child (some widgets omit .elementor-widget-container)
        const firstEl = Array.from(wrapper.children).find(c => c.nodeType === 1);
        if (firstEl) target = firstEl;
      }
      if (!target && wrapper.classList.contains('wcf-target-self')) target = wrapper;

      if (target && !target.dataset.charInit) {

        const text = target.textContent;

        if (text && text.trim().length) {

          target.innerHTML = '';

          text.split('').forEach((char, i) => {
            const span = document.createElement('span');
            span.textContent = char;
            span.style.setProperty('--i', i);
            target.appendChild(span);
          });

          target.dataset.charInit = "true";
        }
      }
    }

    /* ==========================================
       WAVE TEXT SUPPORT (ADDED ONLY THIS PART)
    ========================================== */

    if (wrapper.classList.contains('wcf-starter-animations-text-wave')) {

      let target = wrapper.querySelector('.elementor-widget-container > *');
      if (!target) {
        const firstEl = Array.from(wrapper.children).find(c => c.nodeType === 1);
        if (firstEl) target = firstEl;
      }
      if (!target && wrapper.classList.contains('wcf-target-self')) target = wrapper;
      if (target && !target.dataset.waveInit) {

        const text = target.textContent.trim();
        if (text) {
          target.setAttribute('data-text', text);
          target.dataset.waveInit = "true";
        }

      }
    }

    /* ==========================================
       MASK WIPE, TYPEWRITER, BG-CLIP INITIALIZATION
       Ensure these effects initialize when the wrapper
       itself is the target (wcf-target-self)
    ========================================== */

    if (wrapper.classList.contains('wcf-starter-animations-text-mask-wipe')) {
      let target = wrapper.querySelector('.elementor-widget-container > *');
      if (!target) {
        const firstEl = Array.from(wrapper.children).find(c => c.nodeType === 1);
        if (firstEl) target = firstEl;
      }
      if (!target && wrapper.classList.contains('wcf-target-self')) target = wrapper;
      if (target && !target.dataset.maskInit) {
        target.dataset.maskInit = "true";
      }
    }

    if (wrapper.classList.contains('wcf-starter-animations-text-typewriter')) {
      let target = wrapper.querySelector('.elementor-widget-container > *');
      if (!target) {
        const firstEl = Array.from(wrapper.children).find(c => c.nodeType === 1);
        if (firstEl) target = firstEl;
      }
      if (!target && wrapper.classList.contains('wcf-target-self')) target = wrapper;
      if (target && !target.dataset.typeInit) {
        // no heavy DOM changes required; flag as initialized
        target.dataset.typeInit = "true";
      }
    }

    if (wrapper.classList.contains('wcf-starter-animations-text-bg-clip')) {
      let target = wrapper.querySelector('.elementor-widget-container > *');
      if (!target) {
        const firstEl = Array.from(wrapper.children).find(c => c.nodeType === 1);
        if (firstEl) target = firstEl;
      }
      if (!target && wrapper.classList.contains('wcf-target-self')) target = wrapper;
      if (target && !target.dataset.bgClipInit) {
        target.dataset.bgClipInit = "true";
      }
    }

  /* ==========================================
        RESET (editor live change safe)
      ========================================== */

    // Only auto-start animation if element is currently visible in the viewport
    // or when in Elementor editor mode. This prevents off-screen elements from
    // starting and finishing before they enter the viewport.
    function elementIsVisible(el) {
      try {
        var rect = el.getBoundingClientRect();
        var vw = window.innerWidth || document.documentElement.clientWidth;
        var vh = window.innerHeight || document.documentElement.clientHeight;
        return rect.bottom > 0 && rect.right > 0 && rect.left < vw && rect.top < vh;
      } catch (e) { return true; }
    }

    var forceStart = false;
    try {
      // elementorFrontend.isEditMode() indicates editor live preview
      if (window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function') {
        forceStart = !!window.elementorFrontend.isEditMode();
      }
    } catch (e) {}

    if (forceStart || elementIsVisible(wrapper)) {
      wrapper.classList.remove('wcf-animate');
      void wrapper.offsetHeight;
      wrapper.classList.add('wcf-animate');
    }

    /* ==========================================
      OBSERVER (Viewport Play)
    ========================================== */

    if (!wrapper.dataset.observerInit) {

      const observer = new IntersectionObserver((entries, obs) => {

        entries.forEach(entry => {

          if (entry.isIntersecting) {
            entry.target.classList.add('wcf-animate');
            obs.unobserve(entry.target);
          }

        });

      }, { threshold: 0.3 });

      observer.observe(wrapper);
      wrapper.dataset.observerInit = "true";
    }

  }

  window.addEventListener('elementor/frontend/init', function () {

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/global',
      initStarterAnimations
    );

  });

  // expose initializer so replay code (in other IIFE) can ensure per-widget setup
  try { window.wcfInitStarterAnimations = function(scope){ try { initStarterAnimations(scope); } catch(e){} }; } catch(e){}

  // Immediate scan for existing wrappers (useful in editor mode where widgets are already present)
  (function runInitialScan(){
    function setupScanAndObserver(){
      try {
        const all = Array.from(document.querySelectorAll('[class*="wcf-starter-animations-"]'));
        all.forEach(function(w){
          try { initStarterAnimations([w]); } catch(e){}
        });
      } catch(e){}

      // Avoid creating multiple mutation observers
      try {
        if (window._wcf_mo) return;

        const mo = new MutationObserver(function(mutations){
          mutations.forEach(function(m){
            if (m.type === 'childList' && m.addedNodes && m.addedNodes.length) {
              m.addedNodes.forEach(function(n){
                try {
                  if (n.nodeType === 1) {
                    if (n.matches && n.matches('[class*="wcf-starter-animations-"]')) {
                      initStarterAnimations([n]);
                    }
                    const found = n.querySelector && n.querySelectorAll && n.querySelectorAll('[class*="wcf-starter-animations-"]');
                    if (found && found.length) {
                      Array.from(found).forEach(function(f){ initStarterAnimations([f]); });
                    }
                  }
                } catch(e){}
              });
            }
            if (m.type === 'attributes' && m.target) {
              try {
                const t = m.target;
                if (t.matches && t.matches('[class*="wcf-starter-animations-"]')) {
                  initStarterAnimations([t]);
                }
              } catch(e){}
            }
          });
        });

        var observeTarget = document.body || document.documentElement || document;
        mo.observe(observeTarget, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
        window._wcf_mo = mo;
      } catch(e){}
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', setupScanAndObserver, { once:true });
    } else {
      setupScanAndObserver();
    }
  })();

})();

/* Replay helper + postMessage listener */
(function(){
  'use strict';

  function replayStarterAnimations(root=document, widgetId) {
    function doReplay(wrapper) {
      // ensure element-specific initialization is run (creates char spans, data flags)
      try { if (window.wcfInitStarterAnimations) window.wcfInitStarterAnimations([wrapper]); } catch(e){}
      var disabledChildren = [];

      var isContainer = false;
      try {
        var et = wrapper.getAttribute && (wrapper.getAttribute('data-element_type') || wrapper.getAttribute('data-element-type'));
        if (et && et.toString() === 'container') isContainer = true;
      } catch (e) { /* ignore */ }

      if (isContainer) {
        // Temporarily disable descendant widget animations so container animation takes precedence
        disabledChildren = Array.from(wrapper.querySelectorAll('[class*="wcf-starter-animations-"].wcf-animate'));
        disabledChildren.forEach(function(el){ el.classList.remove('wcf-animate'); });
      }

      // reset + replay on the wrapper: remove and re-add the class to trigger animation
      wrapper.classList.remove('wcf-animate');
      // force reflow to ensure browser registers the class removal
      void wrapper.offsetHeight;
      // re-add class to trigger CSS animation
      wrapper.classList.add('wcf-animate');

      // Character animations are triggered by the wrapper's .wcf-animate class
      // so just rely on the wrapper class removal/addition above

      if (disabledChildren.length) {
        // compute duration + delay from CSS variables or computed styles
        var cs = window.getComputedStyle(wrapper);
        var dur = cs.getPropertyValue('--wcf-duration') || cs.animationDuration || '';
        var delay = cs.getPropertyValue('--wcf-delay') || cs.animationDelay || '';

        function parseTime(v){
          if (!v) return 0;
          v = v.trim();
          if (v.indexOf('ms') !== -1) return parseFloat(v);
          if (v.indexOf('s') !== -1) return parseFloat(v) * 1000;
          // if numeric, assume ms
          var n = parseFloat(v);
          return isNaN(n) ? 0 : n;
        }

        var total = parseTime(dur) + parseTime(delay) + 50;
        setTimeout(function(){
          disabledChildren.forEach(function(el){ el.classList.add('wcf-animate'); });
        }, total);
      }
    }

    if (widgetId) {
      // Try to find the widget root by common elementor identifiers
      var rootEl = null;
      try {
        const selectors = [
          '[data-id="' + widgetId + '"]',
          '[data-element-id="' + widgetId + '"]',
          '.elementor-element-' + widgetId
        ];
        for (var i = 0; i < selectors.length; i++) {
          var sel = selectors[i];
          var found = document.querySelector(sel);
          if (found) { rootEl = found; break; }
        }
      } catch(e) { rootEl = null; }

      // If we found a widget root, only replay wrappers inside that root (or the root itself)
      if (rootEl) {
        var localWrappers = Array.from(rootEl.querySelectorAll('[class*="wcf-starter-animations-"]'));
        if (rootEl.matches && rootEl.matches('[class*="wcf-starter-animations-"]')) localWrappers.unshift(rootEl);
        if (localWrappers.length) {
          localWrappers.forEach(doReplay);
          return;
        }
      }

      // If no DOM root found by id, try matching a single wrapper by widgetText (if provided).
      if (typeof window._wcf_lastWidgetTextForReplay === 'undefined') window._wcf_lastWidgetTextForReplay = null;
      const widgetText = (window._wcf_lastWidgetTextForReplay || null);
      if (widgetText) {
        try {
          const all = Array.from(root.querySelectorAll('[class*="wcf-starter-animations-"]'));
          // find the first wrapper that contains the snippet (limit to first match)
          const match = all.find(wrapper => {
            try {
              const txt = (wrapper.textContent || '').trim().replace(/\s+/g,' ');
              if (!txt) return false;
              return txt.indexOf(widgetText.trim()) !== -1 || widgetText.trim().indexOf(txt) !== -1;
            } catch(e) { return false; }
          });
          if (match) {
            doReplay(match);
            window._wcf_lastWidgetTextForReplay = null;
            return;
          }
        } catch(e){}
      }

      // If widgetId provided but we couldn't find a matching target, do nothing — avoid replaying all.
      return;
    }

    const wrappers = Array.from(root.querySelectorAll('[class*="wcf-starter-animations-"]'));
    wrappers.forEach(doReplay);
  }

  // expose for direct calls from console if needed
  window.wcfReplayStarterAnimations = replayStarterAnimations;

  // listen for editor/preview messages
  window.addEventListener('message', function(ev){
    var data = ev.data;
    try {
      if (typeof data === 'string') data = JSON.parse(data);
    } catch(e) {
      // ignore malformed JSON
    }
    if (data && data.wcfReplay === true) {
      // store widgetText temporarily for the replay matching logic
      try { window._wcf_lastWidgetTextForReplay = data.widgetText || null; } catch(e) { window._wcf_lastWidgetTextForReplay = null; }
      replayStarterAnimations(document, data.widgetId);
    }
  }, false);

})();