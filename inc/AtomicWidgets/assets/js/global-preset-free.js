/**
 * AAE Global Preset — Free button interactions.
 *
 * Build target: ../../../../../assets/atomic/js/global-preset-free.js
 * Styles live in ../scss/global-preset-free.scss (extracted by webpack).
 *
 * Covers: ripple hover span, Text-flip, Border-divide.
 */
import '../scss/global-preset-free.scss';

// ==================================================================================
// ================================== ripple (without gsap) =========================
// ==================================================================================

(function () {

  return null;

  function getRippleSpan(btn) {
    return btn.querySelector(".ripple-btn-span");
  }

  function handleEnter(e) {
    const btn = e.target.closest(".aae-editor-ripple-btn");
    if (!btn) return;
    const span = getRippleSpan(btn);
    if (!span) return;

    const rect = btn.getBoundingClientRect();
    const size = Math.sqrt(rect.width ** 2 + rect.height ** 2) * 2;

    span.style.top = e.clientY - rect.top + "px";
    span.style.left = e.clientX - rect.left + "px";
    span.style.width = size + "px";
    span.style.height = size + "px";
  }

  function handleLeave(e) {
    const btn = e.target.closest(".aae-editor-ripple-btn");
    if (!btn) return;
    const span = getRippleSpan(btn);
    if (!span) return;

    const rect = btn.getBoundingClientRect();
    span.style.top = e.clientY - rect.top + "px";
    span.style.left = e.clientX - rect.left + "px";
    span.style.width = "0";
    span.style.height = "0";
  }

  // capture: true lets delegation work for non-bubbling events
  document.addEventListener("mouseenter", handleEnter, true);
  document.addEventListener("mouseleave", handleLeave, true);
})();


// ==================================================================================
// ================================== ripple (using gsap) ============================
// ==================================================================================

/*

(function () {
  const cache = new WeakMap(); // container -> { span, xTo, yTo, scaleObj, scaleTween }

  function applyTransform(span, x, y, scale) {
    span.style.transform = `translate(-50%, -50%) scale(${scale})`;
  }

  function getRipple(container) {
    const span =
      container.querySelector('.ripple-btn-span') ||
      container.querySelector('span:first-child');
    if (!span || typeof gsap === 'undefined') return null;

    const cached = cache.get(container);
    if (cached && cached.span === span) return cached;

    Object.assign(span.style, {
      position: 'absolute',
      background: '#FC5A11',
      borderRadius: '50%',
      pointerEvents: 'none',
      zIndex: '-1',
      width: '0px',
      height: '0px',
    });

    Object.assign(container.style, {
      position: 'relative',
      overflow: 'hidden',
    });

    // proxy state — GSAP only ever tweens these plain numbers
    const state = { x: 0, y: 0, scale: 0 };
    applyTransform(span, state.x, state.y, state.scale);

    const xTo = gsap.quickTo(state, 'x', {
      duration: 0.3,
      ease: 'power2.out',
      onUpdate: () => applyTransform(span, state.x, state.y, state.scale),
    });
    const yTo = gsap.quickTo(state, 'y', {
      duration: 0.3,
      ease: 'power2.out',
      onUpdate: () => applyTransform(span, state.x, state.y, state.scale),
    });
    const scaleTo = gsap.quickTo(state, 'scale', {
      duration: 0.5,
      ease: 'power2.out',
      onUpdate: () => applyTransform(span, state.x, state.y, state.scale),
    });

    const entry = { span, state, xTo, yTo, scaleTo };
    cache.set(container, entry);
    return entry;
  }

  function onEnter(e) {
    if (!(e.target instanceof Element)) return;
    const container = e.target.closest('.aae-editor-ripple-btn');
    if (!container) return;
    const ripple = getRipple(container);
    if (!ripple) return;

    const rect = container.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const maxDist = Math.max(
      Math.hypot(x, y),
      Math.hypot(rect.width - x, y),
      Math.hypot(x, rect.height - y),
      Math.hypot(rect.width - x, rect.height - y)
    );
    const size = Math.ceil(maxDist * 2);

    ripple.span.style.width = size + 'px';
    ripple.span.style.height = size + 'px';

    // left/top still set directly — no need for GSAP tracking on enter
    ripple.span.style.left = x + 'px';
    ripple.span.style.top = y + 'px';
    ripple.state.x = x;
    ripple.state.y = y;

    ripple.scaleTo(1);
  }

  function onLeaveOrMove(e) {
    if (!(e.target instanceof Element)) return;
    const container = e.target.closest('.aae-editor-ripple-btn');
    if (!container) return;
    const ripple = getRipple(container);
    if (!ripple) return;

    const rect = container.getBoundingClientRect();
    ripple.xTo(e.clientX - rect.left);
    ripple.yTo(e.clientY - rect.top);
    ripple.scaleTo(0);
  }

  document.addEventListener('mouseenter', onEnter, true);
  document.addEventListener('mouseleave', onLeaveOrMove, true);
})();

*/

// ==================================================================================
// =================================== free button ===================================
// ==================================================================================

// Text-flip (pro-3)
function textFlipSync(container) {
  const contentEl = container.querySelector('.aae-btn-txtflip-content');
  if (!contentEl) return;

  const text = contentEl.textContent.trim();
  if (contentEl.dataset.text !== text) {
    contentEl.dataset.text = text;
  }
}

// Border-divide (pro-1)
function borderDivideSetup(container) {
  const iconEl = container.querySelector('.e-svg-base');
  if (!iconEl) return;
  if (iconEl.querySelector(':scope > .aae-btn-borderdivide-icon-inner')) return;

  const svgEl = iconEl.querySelector('svg');
  if (!svgEl) return;

  const inner = document.createElement('span');
  inner.className = 'aae-btn-borderdivide-icon-inner';
  Object.assign(inner.style, {
    position: 'relative',
    display: 'inline-flex',
    width: '100%',
    height: '100%',
    overflow: 'hidden',
  });
  iconEl.replaceChild(inner, svgEl);
  inner.appendChild(svgEl);

  const clone = svgEl.cloneNode(true);
  clone.removeAttribute('id');
  clone.removeAttribute('data-interaction-id');
  clone.removeAttribute('data-id');
  clone.setAttribute('data-swap-clone', 'true');
  inner.appendChild(clone);
}

// Mask (mask-btn)
function maskBtn(container) {
  const textEl = container.querySelector('.aae-btn-mask-content');
  const effectEl = container.querySelector('.aae-btn-mask-effect');
  if (!textEl || !effectEl) return;

  const text = textEl.textContent.trim();
  container.setAttribute('data-text', text);
  if (effectEl.textContent.trim() !== text) {
    effectEl.textContent = text;
  }
}

function initFreeButtonEffects() {
  document.querySelectorAll('.aae-btn-txtflip').forEach(textFlipSync);
  document.querySelectorAll('.aae-btn-borderdivide').forEach(borderDivideSetup);
  document.querySelectorAll('.aae-btn-mask').forEach(maskBtn);
}

// Run once for whatever is already in the DOM…
initFreeButtonEffects();

// …and again whenever the DOM changes. Elementor's editor preview mounts atomic
// widgets asynchronously (they may not exist yet on first run above), and can also
// replace a widget's markup on selection/setting changes, wiping our injected clone
// or resetting the text-flip data-text sync.
// Disconnect while we mutate so this observer doesn't react to its own changes.
const freeButtonObserver = new MutationObserver(() => {
  freeButtonObserver.disconnect();
  initFreeButtonEffects();
  freeButtonObserver.observe(document.body, { childList: true, subtree: true });
});
freeButtonObserver.observe(document.body, { childList: true, subtree: true });

