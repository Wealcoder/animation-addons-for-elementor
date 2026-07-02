// ==================================================================================
// ================================== without gsap ====================================

(function () {
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
// ================================== using gsap ====================================

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