/**
 * AAE Btn Pro — pro-tier button interactions.
 *
 * Build target: ../../../../../assets/atomic/js/btn-pro.js
 * Styles live in ../scss/btn-pro.scss (extracted by webpack).
 *
 * Enqueued on-demand: only when an `e-aae-a-btn-pro` element actually
 * renders on the page (see class-atomic.php's has_script/style_handle
 * entry for `aae-a-btn-pro` + maybe_enqueue_widget_script()). Not loaded
 * on pages with no AAE Btn Pro instance.
 *
 * Covers: Ripple (GSAP), Group Swap L/R, Polygon fill + magnetic move —
 * generic `.aae-btn-*` marker classes you add by hand to elements built
 * inside an AAE Btn Pro wrapper, matching the pro templates in
 * Widgets/BtnPro/presets and z_temp/templates/BtnPro.
 */
import '../scss/btn-pro.scss';

// 4  — Ripple (GSAP)
function rippleGsapSetup(container) {
  const span = container.querySelector('.aae-btn-ripple-effect');
  if (!span || typeof gsap === 'undefined') return;

  if (container.dataset.aaeRippleBound) return;
  container.dataset.aaeRippleBound = '1';

  const xTo = gsap.quickTo(span, 'left', { duration: 0.3, ease: 'power2.out' });
  const yTo = gsap.quickTo(span, 'top', { duration: 0.3, ease: 'power2.out' });

  const track = (e) => {
    const rect = container.getBoundingClientRect();
    xTo(e.clientX - rect.left);
    yTo(e.clientY - rect.top);
  };

  const onEnter = (e) => {
    const rect = container.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    // Diameter must reach the farthest corner from the entry point so the
    // ripple fully covers the button regardless of where the cursor enters.
    const maxDist = Math.max(
      Math.hypot(x, y),
      Math.hypot(rect.width - x, y),
      Math.hypot(x, rect.height - y),
      Math.hypot(rect.width - x, rect.height - y)
    );
    const size = Math.ceil(maxDist * 2) + 'px';
    span.style.width = size;
    span.style.height = size;
    xTo(x);
    yTo(y);
  };

  const onLeave = (e) => {
    // Empty string clears the inline override, so the element falls back to
    // whatever width/height the preset's own style declares — the
    // transition configured there still animates the change.
    span.style.width = '';
    span.style.height = '';
    track(e);
  };

  container.addEventListener('mouseenter', onEnter);
  container.addEventListener('mouseleave', onLeave);
}

// Keeps a single cloned "swap" icon in sync with the live icon WITHOUT
// touching the DOM when nothing actually changed.
//
// Elementor's editor preview can wipe our injected clone on unrelated
// settings/selection changes (see the observer comment below) — but naively
// remove+recreating it on every single re-run, regardless of whether it's
// already correct, fights that re-render in a tight mutate -> observe ->
// mutate loop with no yielding, which can pin a CPU core and hang the tab
// (this is what happened applying the group-swap presets: preset apply
// mounts many elements in rapid succession, and each mutation re-triggered
// an unconditional remove+insert here). Only touch the DOM when the clone
// is actually missing or stale.
function syncSwapClone(container, icon) {
  const existing = container.querySelector(':scope > [data-swap-clone]');

  const candidate = icon.cloneNode(true);
  candidate.setAttribute('data-swap-clone', 'true');
  candidate.removeAttribute('data-interaction-id');

  if (existing && existing.isEqualNode(candidate)) {
    return; // already in sync — no DOM mutation, no observer retrigger
  }

  if (existing) existing.remove();
  container.prepend(candidate);
}

// 5  — Group Swap L
function groupSwapLeftSetup(container) {
  const icon = container.querySelector('.aae-btn-grswapl-icon');
  if (!icon) return;
  syncSwapClone(container, icon);
}

// 6  — Group Swap R
function groupSwapRightSetup(container) {
  const icon = container.querySelector('.aae-btn-grswapr-icon');
  if (!icon) return;
  syncSwapClone(container, icon);
}

// 9/10/11 — Oval / Circle / Ellipse share one "polygon" container class and
// one fill mechanic. .aae-btn-polygon-effect is a real element in the
// template (an e-div-block the user can select and style from Elementor's
// panel), not JS-created — just locate it. Descendant selector, not
// ':scope >', since it may sit behind an editor-only wrapper depending on
// how the template nests it.
function polygonFillSetup(container) {
  const fill = container.querySelector('.aae-btn-polygon-effect');
  if (!fill) return;

  // Idempotent — don't attach duplicate listeners if this runs again.
  if (container.dataset.aaePolygonBound) return;
  container.dataset.aaePolygonBound = '1';

  const onEnter = (e) => {
    const rect = container.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    // Diameter must reach the farthest corner from the entry point so the
    // fill fully covers the button regardless of text length / button size.
    const maxDist = Math.max(
      Math.hypot(x, y),
      Math.hypot(rect.width - x, y),
      Math.hypot(x, rect.height - y),
      Math.hypot(rect.width - x, rect.height - y)
    );
    container.style.setProperty('--aae-btn-polygon-fill-size', Math.ceil(maxDist * 2) + 'px');

    fill.style.left = x + 'px';
    fill.style.top = y + 'px';
  };

  const onLeave = (e) => {
    const rect = container.getBoundingClientRect();
    fill.style.left = (e.clientX - rect.left) + 'px';
    fill.style.top = (e.clientY - rect.top) + 'px';
  };

  container.addEventListener('mouseenter', onEnter);
  container.addEventListener('mouseleave', onLeave);
}

// 9/10/11 — polygon magnetic movement. Mirrors the v3 btn-wrapper + btn-item
// parallax: the button physically follows the cursor within its bounds, then
// snaps back on leave. Requires GSAP (loaded as a dependency) — no-ops
// silently without it, same as the ButtonPro widget's own magneticSetup.
function polygonMagneticSetup(container) {
  if (container.dataset.aaePolygonMagneticBound || typeof gsap === 'undefined') return;
  container.dataset.aaePolygonMagneticBound = '1';

  container.addEventListener('mousemove', (e) => {
    const rect = container.getBoundingClientRect();
    gsap.to(container, {
      duration: 0.5,
      x: ((e.clientX - rect.left - rect.width / 2) / rect.width) * 80,
      y: ((e.clientY - rect.top - rect.height / 2) / rect.height) * 80,
      ease: 'power2.out',
    });
  });

  container.addEventListener('mouseleave', () => {
    gsap.to(container, { duration: 0.5, x: 0, y: 0, ease: 'power2.out' });
  });
}

function initProButtonEffects() {
  document.querySelectorAll('.aae-btn-ripple').forEach(rippleGsapSetup);
  document.querySelectorAll('.aae-btn-grswapl').forEach(groupSwapLeftSetup);
  document.querySelectorAll('.aae-btn-grswapr').forEach(groupSwapRightSetup);
  document.querySelectorAll('.aae-btn-polygon').forEach(polygonFillSetup);
  document.querySelectorAll('.aae-btn-polygon').forEach(polygonMagneticSetup);
}

// Run once for whatever is already in the DOM…
initProButtonEffects();

// Relevant classes only — matches initProButtonEffects()'s own selectors.
const PRO_BUTTON_SELECTOR = '.aae-btn-ripple, .aae-btn-grswapl, .aae-btn-grswapr, .aae-btn-polygon';

// True if this mutation batch actually added a node we care about, OR added
// content inside an already-existing button (Elementor frequently mounts a
// button's wrapper first and renders its inner content — ripple/polygon
// effect element, swap icon — in as a later, separate mutation; that content
// isn't itself a match and doesn't contain one, so it only shows up via
// `closest`). Elementor's editor canvas mutates the DOM constantly for
// reasons that have nothing to do with these buttons (typing, hovering,
// selecting other elements) — without this check, every single one of those
// unrelated mutations would still pay for a full document.body disconnect +
// querySelectorAll + reconnect cycle, which adds up fast with several such
// observers on one page (and gets much worse with DevTools open, which adds
// real overhead per DOM mutation).
function touchesProButton(mutations) {
  for (const m of mutations) {
    for (const node of m.addedNodes) {
      if (node.nodeType !== 1) continue;
      if (
        node.matches?.(PRO_BUTTON_SELECTOR) ||
        node.querySelector?.(PRO_BUTTON_SELECTOR) ||
        node.closest?.(PRO_BUTTON_SELECTOR)
      ) {
        return true;
      }
    }
  }
  return false;
}

// …and again whenever the DOM changes. Elementor's editor preview mounts atomic
// widgets asynchronously (they may not exist yet on first run above), and can also
// replace a widget's markup on selection/setting changes, wiping our injected clone.
// Disconnect while we mutate so this observer doesn't react to its own changes.
//
// Last-resort safety net: every setup function above is meant to be
// idempotent (no-op when nothing changed), so this should settle almost
// immediately. But if a future change reintroduces an unconditional DOM
// mutation, the DOM never settles and this fires in a tight synchronous
// loop — hanging the tab. Cap how many times we're willing to re-run inside
// a short burst; if it's exceeded, stop the live observer and fall back to
// a slow interval so the effects still (eventually) apply without pinning
// a CPU core.
const REINIT_BURST_LIMIT = 30;
const REINIT_BURST_WINDOW_MS = 1000;
let reinitBurstCount = 0;
let reinitBurstStart = 0;

const proButtonObserver = new MutationObserver((mutations) => {
  if (!touchesProButton(mutations)) return;

  const now = Date.now();
  if (now - reinitBurstStart > REINIT_BURST_WINDOW_MS) {
    reinitBurstStart = now;
    reinitBurstCount = 0;
  }
  reinitBurstCount += 1;

  if (reinitBurstCount > REINIT_BURST_LIMIT) {
    proButtonObserver.disconnect();
    // eslint-disable-next-line no-console
    console.warn(
      '[AAE Btn Pro] DOM re-init loop exceeded ' + REINIT_BURST_LIMIT +
      ' runs/second — disabling the live observer to avoid hanging the tab. ' +
      'Falling back to a periodic rescan every 2s.'
    );
    setInterval(initProButtonEffects, 2000);
    return;
  }

  proButtonObserver.disconnect();
  initProButtonEffects();
  proButtonObserver.observe(document.body, { childList: true, subtree: true });
});
proButtonObserver.observe(document.body, { childList: true, subtree: true });
