/**
 * AAE Global Preset — Free button interactions.
 *
 * Build target: ../../../../../assets/atomic/js/global-preset-free.js
 * Styles live in ../scss/global-preset-free.scss (extracted by webpack).
 *
 * Covers: ripple hover span, Text-flip, Border-divide.
 */
import '../scss/global-preset-free.scss';

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

