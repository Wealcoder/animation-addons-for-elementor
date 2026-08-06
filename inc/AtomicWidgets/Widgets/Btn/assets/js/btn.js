// Mask button background — a real asset import (not a bare scss import), so
// webpack hashes/copies it under both `npm start` and `npm run build` and
// resolves the URL itself. Exposed as a CSS var (--aae-btn-mask-img) that
// btn.scss reads, matching that file's existing --aae-btn-hover-color
// pattern; the gulp-compiled copy of btn.scss keeps the literal
// url("../images/mask-btn.png") as its own fallback.
import maskBtnImg from '../images/mask-btn.png';

document.documentElement.style.setProperty('--aae-btn-mask-img', `url(${maskBtnImg})`);

// Text-flip (pro-3)
// Targets Elementor's own unconditional `.e-paragraph-base` class (always
// rendered by atomic-paragraph.html.twig, regardless of the `classes` prop)
// instead of a custom hook class on the child — that class can never be
// flagged "missing" or stripped via the Style panel's dismiss action, and it
// keeps working on pages saved before this change (the old literal
// `aae-btn-txtflip-content` class is simply unused now, not required).
function textFlipSync(container) {
  const contentEl = container.querySelector('.e-paragraph-base');
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
// Targets Elementor's own unconditional `.e-paragraph-base` / `.e-divider-base`
// classes (see textFlipSync() above for why) instead of the custom
// `aae-btn-mask-content` / `aae-btn-mask-effect` hook classes on the
// children — querySelector is scoped to this button's own `container`, so
// there's no risk of matching a paragraph/divider outside it.
function maskBtn(container) {
  const textEl = container.querySelector('.e-paragraph-base');
  const effectEl = container.querySelector('.e-divider-base');
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

// Relevant classes only — matches initFreeButtonEffects()'s own selectors.
const FREE_BUTTON_SELECTOR = '.aae-btn-txtflip, .aae-btn-borderdivide, .aae-btn-mask';

// True if this mutation batch actually added a node we care about, OR added
// content inside an already-existing button (Elementor frequently mounts a
// button's wrapper first and renders its inner content — icon, mask text —
// in as a later, separate mutation; that content isn't itself a match and
// doesn't contain one, so it only shows up via `closest`). Elementor's editor
// canvas mutates the DOM constantly for reasons that have nothing to do with
// these buttons (typing, hovering, selecting other elements) — without this
// check, every single one of those unrelated mutations would still pay for a
// full document.body disconnect + querySelectorAll + reconnect cycle, which
// adds up fast with several such observers on one page (and gets much worse
// with DevTools open, which adds real overhead per DOM mutation).
function touchesFreeButton(mutations) {
  for (const m of mutations) {
    for (const node of m.addedNodes) {
      if (node.nodeType !== 1) continue;
      if (
        node.matches?.(FREE_BUTTON_SELECTOR) ||
        node.querySelector?.(FREE_BUTTON_SELECTOR) ||
        node.closest?.(FREE_BUTTON_SELECTOR)
      ) {
        return true;
      }
    }
  }
  return false;
}

// …and again whenever the DOM changes. Elementor's editor preview mounts atomic
// widgets asynchronously (they may not exist yet on first run above), and can also
// replace a widget's markup on selection/setting changes, wiping our injected clone
// or resetting the text-flip data-text sync.
// Disconnect while we mutate so this observer doesn't react to its own changes.
//
// Last-resort safety net: every setup function above is idempotent (no-op
// when nothing changed), so this should settle almost immediately. But if a
// future change reintroduces an unconditional DOM mutation, the DOM never
// settles and this fires in a tight synchronous loop, hanging the tab (see
// the sibling Btn Pro bundle for a case this actually happened). Cap how
// many times we're willing to re-run inside a short burst; if exceeded,
// stop the live observer and fall back to a slow interval instead.
const REINIT_BURST_LIMIT = 30;
const REINIT_BURST_WINDOW_MS = 1000;
let reinitBurstCount = 0;
let reinitBurstStart = 0;

const freeButtonObserver = new MutationObserver((mutations) => {
  if (!touchesFreeButton(mutations)) return;

  const now = Date.now();
  if (now - reinitBurstStart > REINIT_BURST_WINDOW_MS) {
    reinitBurstStart = now;
    reinitBurstCount = 0;
  }
  reinitBurstCount += 1;

  if (reinitBurstCount > REINIT_BURST_LIMIT) {
    freeButtonObserver.disconnect();
    // eslint-disable-next-line no-console
    console.warn(
      '[AAE Btn] DOM re-init loop exceeded ' + REINIT_BURST_LIMIT +
      ' runs/second — disabling the live observer to avoid hanging the tab. ' +
      'Falling back to a periodic rescan every 2s.'
    );
    setInterval(initFreeButtonEffects, 2000);
    return;
  }

  freeButtonObserver.disconnect();
  initFreeButtonEffects();
  freeButtonObserver.observe(document.body, { childList: true, subtree: true });
});
freeButtonObserver.observe(document.body, { childList: true, subtree: true });
