/**
 * AAE Global Preset — Pro button interactions.
 *
 * Build target: ../../../../../assets/atomic/js/global-preset-pro.js
 * Styles live in ../scss/global-preset-pro.scss (extracted by webpack).
 *
 * Covers: Group Swap L (5), Group Swap R (6), Oval (9).
 */
/* global gsap */
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

// 9  — Oval
function ovalFillSetup(container) {
  // .aae-btn-oval-effect is now a real element in the template (an e-div-block
  // the user can select and style from Elementor's panel), not JS-created —
  // just locate it. Descendant selector, not ':scope >', since it may sit
  // behind an editor-only wrapper depending on how the template nests it.
  const fill = container.querySelector('.aae-btn-oval-effect');
  if (!fill) return;

  // Idempotent — don't attach duplicate listeners if this runs again.
  if (container.dataset.aaeOvalBound) return;
  container.dataset.aaeOvalBound = '1';

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
    container.style.setProperty('--aae-btn-oval-fill-size', Math.ceil(maxDist * 2) + 'px');

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

// 9  — Oval magnetic movement. Mirrors the v3 btn-wrapper + btn-item parallax:
// the button physically follows the cursor within its bounds, then snaps back
// on leave. Requires GSAP (loaded as a dependency) — no-ops silently without
// it, same as the ButtonPro widget's own magneticSetup.
function ovalMagneticSetup(container) {
  if (container.dataset.aaeOvalMagneticBound || typeof gsap === 'undefined') return;
  container.dataset.aaeOvalMagneticBound = '1';

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
  document.querySelectorAll('.aae-btn-grswapl').forEach(groupSwapLeftSetup);
  document.querySelectorAll('.aae-btn-grswapr').forEach(groupSwapRightSetup);
  document.querySelectorAll('.aae-btn-oval').forEach(ovalFillSetup);
  document.querySelectorAll('.aae-btn-oval').forEach(ovalMagneticSetup);
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
