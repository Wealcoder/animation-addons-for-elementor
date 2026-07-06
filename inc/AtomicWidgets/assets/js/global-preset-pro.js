/**
 * AAE Global Preset — Pro button interactions.
 *
 * Build target: ../../../../../assets/atomic/js/global-preset-pro.js
 * Styles live in ../scss/global-preset-pro.scss (extracted by webpack).
 *
 * Covers: Group Swap L (5), Group Swap R (6).
 */
import '../scss/global-preset-pro.scss';

// ==================================================================================
// =================================== pro button ===================================
// ==================================================================================

// 5  — Group Swap L
function groupSwapLeftSetup(container) {
  container.querySelectorAll('[data-swap-clone]').forEach(el => el.remove());

  const icon = container.querySelector('.aae-btn-grswapl-icon');
  if (!icon) return;

  const clone = icon.cloneNode(true);
  clone.setAttribute('data-swap-clone', 'true');
  clone.removeAttribute('data-interaction-id');
  container.prepend(clone);
}

// 6  — Group Swap R
function groupSwapRightSetup(container) {
  container.querySelectorAll('[data-swap-clone]').forEach(el => el.remove());

  const icon = container.querySelector('.aae-btn-grswapr-icon');
  if (!icon) return;

  const clone = icon.cloneNode(true);
  clone.setAttribute('data-swap-clone', 'true');
  clone.removeAttribute('data-interaction-id');
  container.prepend(clone);
}

function initProButtonEffects() {
  document.querySelectorAll('.aae-btn-grswapl').forEach(groupSwapLeftSetup);
  document.querySelectorAll('.aae-btn-grswapr').forEach(groupSwapRightSetup);
}

// Run once for whatever is already in the DOM…
initProButtonEffects();

// …and again whenever the DOM changes. Elementor's editor preview mounts atomic
// widgets asynchronously (they may not exist yet on first run above), and can also
// replace a widget's markup on selection/setting changes, wiping our injected clone.
// Disconnect while we mutate so this observer doesn't react to its own changes.
const proButtonObserver = new MutationObserver(() => {
  proButtonObserver.disconnect();
  initProButtonEffects();
  proButtonObserver.observe(document.body, { childList: true, subtree: true });
});
proButtonObserver.observe(document.body, { childList: true, subtree: true });
