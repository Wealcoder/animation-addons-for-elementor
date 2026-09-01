/******/ (function() { // webpackBootstrap
/*!*******************************************************************************!*\
  !*** ./inc/AtomicWidgets/Widgets/ToggleSwitcher/assets/js/toggle-switcher.js ***!
  \*******************************************************************************/
// Persists active state across editor re-initializations, keyed by
// switcher data-id — mirrors ToggleSwitcherMain's toggleState map.
const tsState = new Map();
function findSwitcher(el) {
  return el.closest('.aae-a-toggle-switcher');
}
function applyTsState(wrapper, checked) {
  const id = wrapper.dataset.id || '';
  if (id) tsState.set(id, checked);
  const panes = wrapper.querySelectorAll('.aae-ts-pane');
  panes.forEach((pane, i) => {
    const show = checked ? i === 1 : i === 0;
    pane.classList.toggle('show', show);
    // Inline !important so Elementor's editor CSS (display:flex on .e-con
    // containers) cannot override the hidden state.
    if (show) {
      pane.style.removeProperty('display');
    } else {
      pane.style.setProperty('display', 'none', 'important');
    }
  });
  const before = wrapper.querySelector('.aae-ts-label-before');
  const after = wrapper.querySelector('.aae-ts-label-after');
  // `active` drives legacy Switch/Label-Highlight preset CSS; `e--selected`
  // is Elementor's own class-based Style-panel state (Style_States::SELECTED)
  // — the default Tab widget's "active tab" look (accent color + underline)
  // is a real Style-panel state keyed off this class, not raw CSS. Both are
  // toggled together so either mechanism works no matter which preset (or
  // the plain default) supplied the tabs.
  before?.classList.toggle('active', !checked);
  before?.classList.toggle('e--selected', !checked);
  before?.setAttribute('aria-selected', String(!checked));
  after?.classList.toggle('active', checked);
  after?.classList.toggle('e--selected', checked);
  after?.setAttribute('aria-selected', String(checked));
  // `active` still drives the transform-based slide (both the classic
  // Knob's fixed 20px travel and the Pill Knob's 100% travel — see
  // toggle-switcher.scss); `e--selected` is the same Style-panel state as
  // the labels above, now also exposed on Track and Knob so their own
  // background/border/color can be edited per-instance for the "on" look,
  // replacing the old hardcoded `var(--aae-ts-switcher-active-bg)` rule.
  wrapper.querySelectorAll('.aae-ts-switch').forEach(el => {
    el.classList.toggle('active', checked);
    el.classList.toggle('e--selected', checked);
  });
  wrapper.querySelectorAll('.aae-ts-knob').forEach(el => el.classList.toggle('e--selected', checked));
}
function syncInitialState(wrapper) {
  var _tsState$get;
  const panes = wrapper.querySelectorAll('.aae-ts-pane');
  if (!panes.length) return false;
  const id = wrapper.dataset.id || '';
  const saved = id ? (_tsState$get = tsState.get(id)) !== null && _tsState$get !== void 0 ? _tsState$get : false : false;
  applyTsState(wrapper, saved);
  return true;
}
function initAllSwitchers() {
  document.querySelectorAll('.aae-a-toggle-switcher').forEach(syncInitialState);
}

// Capture phase fires before any ancestor/Elementor click handler that may
// call preventDefault(). Delegation on document means newly-inserted labels,
// switches, or panes (editor mounts atomic widgets asynchronously) work
// without any per-element rebinding.
document.addEventListener('click', e => {
  var _tsState$get2;
  const trigger = e.target.closest('.aae-ts-label-before, .aae-ts-label-after, .aae-ts-switch');
  if (!trigger) return;
  const wrapper = findSwitcher(trigger);
  if (!wrapper) return;
  e.preventDefault();

  // Only two states exist, so "select the side you clicked" and "flip to
  // the other side" are the same thing except when you click the side
  // that's already active — there, a direct-select model no-ops (you
  // asked for what's already showing) while a toggle model still flips.
  // Every trigger (either label, or the switch itself) uses the toggle
  // model uniformly, so clicking either tab always moves the selection.
  const id = wrapper.dataset.id || '';
  const current = id ? (_tsState$get2 = tsState.get(id)) !== null && _tsState$get2 !== void 0 ? _tsState$get2 : false : false;
  applyTsState(wrapper, !current);
}, true);

// Run once for whatever is already in the DOM…
initAllSwitchers();

// True if this mutation batch actually added a switcher node, OR added
// content inside an already-existing switcher (Elementor frequently mounts
// the .aae-a-toggle-switcher wrapper first and renders its .aae-ts-pane
// content in as a later, separate mutation — that content isn't itself a
// switcher and doesn't contain one, so it only shows up via `closest`).
// Elementor's editor canvas mutates the DOM constantly for reasons that have
// nothing to do with this widget (typing, hovering, selecting other
// elements) — without this check, every single one of those unrelated
// mutations still pays for a full document.body disconnect + querySelectorAll
// + reconnect cycle, which adds up fast with several such observers on one
// page (and gets much worse with DevTools open, which adds real overhead per
// DOM mutation).
function touchesToggleSwitcher(mutations) {
  for (const m of mutations) {
    for (const node of m.addedNodes) {
      if (node.nodeType !== 1) continue;
      if (node.matches?.('.aae-a-toggle-switcher') || node.querySelector?.('.aae-a-toggle-switcher') || node.closest?.('.aae-a-toggle-switcher')) {
        return true;
      }
    }
  }
  return false;
}

// …and again whenever the DOM changes. Elementor's editor preview mounts
// atomic widgets asynchronously (they may not exist yet on first run above),
// and can also replace a widget's markup on selection/setting changes,
// wiping the pane's inline display override. Disconnect while mutating so
// this observer doesn't react to its own changes.
//
// Last-resort safety net: initAllSwitchers() is meant to be idempotent (a
// no-op once every switcher's state matches), so this should settle almost
// immediately. But if the DOM never settles — e.g. something else keeps
// mutating document.body in response to our own writes — this fires in a
// tight synchronous loop and hangs the tab. This exact failure already hit
// the Btn and Btn Pro bundles (see their REINIT_BURST_LIMIT comments); cap
// how many times we're willing to re-run inside a short burst and fall back
// to a slow interval instead of spinning forever.
const REINIT_BURST_LIMIT = 30;
const REINIT_BURST_WINDOW_MS = 1000;
let reinitBurstCount = 0;
let reinitBurstStart = 0;
const tsObserver = new MutationObserver(mutations => {
  if (!touchesToggleSwitcher(mutations)) return;
  const now = Date.now();
  if (now - reinitBurstStart > REINIT_BURST_WINDOW_MS) {
    reinitBurstStart = now;
    reinitBurstCount = 0;
  }
  reinitBurstCount += 1;
  if (reinitBurstCount > REINIT_BURST_LIMIT) {
    tsObserver.disconnect();
    // eslint-disable-next-line no-console
    console.warn('[AAE Toggle Switcher] DOM re-init loop exceeded ' + REINIT_BURST_LIMIT + ' runs/second — disabling the live observer to avoid hanging the tab. ' + 'Falling back to a periodic rescan every 2s.');
    setInterval(initAllSwitchers, 2000);
    return;
  }
  tsObserver.disconnect();
  initAllSwitchers();
  tsObserver.observe(document.body, {
    childList: true,
    subtree: true
  });
});
tsObserver.observe(document.body, {
  childList: true,
  subtree: true
});
/******/ })()
;
//# sourceMappingURL=toggle-switcher.js.map