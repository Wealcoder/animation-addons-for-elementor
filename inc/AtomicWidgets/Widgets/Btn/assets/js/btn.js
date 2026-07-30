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
