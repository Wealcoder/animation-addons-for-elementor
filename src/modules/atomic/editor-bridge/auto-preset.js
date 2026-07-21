/* eslint-env browser */

/**
 * Auto-apply a default preset when a widget is freshly dropped.
 *
 * A bare Loop Grid Slider drops as a plain Post Image + Post Title card, which
 * looks unstyled. To give a good out-of-the-box result we apply a chosen default
 * preset to the slide item the first time the slider is created — the same
 * transform the "Apply Preset" dropdown runs, so the outcome is identical to
 * picking that preset by hand.
 *
 * We hook Elementor's command bus (`document/elements/create`) rather than a DOM
 * observer: create fires once per drop with the new element in the model, and we
 * can locate the seeded slide item from the V1 container tree. We only act on the
 * user's own drop (not on undo/redo/import re-creates) and only once per element.
 */

import { track } from './disposables';
import { applyPresetModel, ensurePresetsLoaded, getCachedPresetsForType } from '../element-controls/preset-apply';

// Which freshly-dropped widget gets which default preset (by preset id — the
// sanitized json basename) AND which slider settings to seed so the preset lands
// polished. The `settings` keys are the slider's atomic props (NestedSlider
// Schema NS_*); they are Responsive_JSON props whose value must be wrapped as
// { $$type: 'aae-rj', value: { desktop: <n> } } — see applySliderSettings. (An
// earlier attempt wrote a bare { desktop: <n> } and Elementor rejected it as
// invalid_value, corrupting the slider settings so publish threw.)
const AUTO_PRESETS = {
  'e-aae-a-loop-grid-slider': {
    slideItemType: 'e-aae-a-loop-slide-item',
    presetId: 'bold-overlay-zoom',
    // Default to a single slide per view (no neighbour sliver, small gap). The
    // user can raise slidesPerView from the panel for a multi-up layout.
    settings: {
      aae_ns_slides_per_view: 1,
      aae_ns_peek: 0,
      aae_ns_gap: 16,
    },
  },
};

// The $$type Elementor uses for the slider's Responsive_JSON props (observed on a
// live slider: { $$type: 'aae-rj', value: { desktop: N } }).
const RESPONSIVE_JSON_TYPE = 'aae-rj';

// Guard so we never auto-apply twice to the same created element.
const handled = new Set();

// The widgetTypes a freshly-seeded default card holds — a slide item that still
// contains ONLY these (in any order, nothing else) is unstyled and safe to
// auto-preset. Anything more/different means it's already been styled.
const DEFAULT_CARD_WIDGETS = ['e-aae-a-post-image', 'e-aae-a-post-title'];

/**
 * True when `slideItem` still holds exactly the plain default children (Post
 * Image + Post Title, nothing else) — i.e. a genuine fresh drop, not a slider
 * that's already been given a preset. Guards against restyling existing sliders.
 */
function hasOnlyDefaultChildren(slideItem) {
  const kids = slideItem.children || [];
  if (kids.length !== DEFAULT_CARD_WIDGETS.length) {
    return false;
  }
  const kinds = kids.map((c) => c.model?.get?.('widgetType') || c.model?.get?.('elType'));
  // Every default widget present, and no extras (lengths already match).
  return DEFAULT_CARD_WIDGETS.every((w) => kinds.includes(w));
}

/** Depth-first find the first container of `type` under `root`. */
function findByType(root, type) {
  if (!root) {
    return null;
  }
  const t = root.model?.get?.('widgetType') || root.model?.get?.('elType');
  if (t === type) {
    return root;
  }
  const children = root.children || [];
  for (const child of children) {
    const hit = findByType(child, type);
    if (hit) {
      return hit;
    }
  }
  return null;
}

/**
 * All containers in the current document whose type has an auto-preset rule and
 * that we haven't handled yet. We scan the whole tree rather than trusting the
 * command args/selection (which vary between a real drag-drop and a programmatic
 * create), so we reliably catch the newly-dropped widget.
 */
function pendingAutoPresetTargets() {
  const out = [];
  const root = window.elementor?.documents?.getCurrent?.()?.container;
  if (!root) {
    return out;
  }
  const walk = (c) => {
    if (!c) {
      return;
    }
    const type = c.model?.get?.('widgetType') || c.model?.get?.('elType');
    if (AUTO_PRESETS[type] && !handled.has(c.id)) {
      out.push(c);
    }
    (c.children || []).forEach(walk);
  };
  walk(root);
  return out;
}

/**
 * Seed slider settings on the slider container. Each value is wrapped in the
 * slider's Responsive_JSON prop shape — { $$type: 'aae-rj', value: { desktop: N } }
 * — the SAME shape a live slider stores (a bare { desktop: N } is rejected as
 * invalid_value and corrupts the settings, breaking publish). Best-effort.
 */
function applySliderSettings(container, settings) {
  try {
    const $e = window.$e;
    if (!$e?.run) {
      return;
    }
    const payload = {};
    Object.keys(settings).forEach((key) => {
      payload[key] = {
        $$type: RESPONSIVE_JSON_TYPE,
        value: { desktop: settings[key] },
      };
    });
    $e.run('document/elements/settings', {
      container,
      settings: payload,
      options: { external: true },
    });
  } catch (_e) {
    /* settings are best-effort — never block the preset apply */
  }
}

function maybeAutoApply() {
  const containers = pendingAutoPresetTargets();
  containers.forEach((container) => {
    const type = container.model?.get?.('widgetType') || container.model?.get?.('elType');
    const rule = AUTO_PRESETS[type];
    if (!rule) {
      return;
    }
    if (handled.has(container.id)) {
      return;
    }

    // Presets are now fetched on demand (remote + local merged server-side —
    // see preset-apply.js's ensurePresetsLoaded) rather than read from an
    // eager global. Kick the fetch off immediately, in parallel with the
    // slide-item poll below, so it's very likely already resolved by the
    // time the slide item appears; getCachedPresetsForType() is a plain
    // synchronous read once ensurePresetsLoaded's promise settles.
    const presetsReady = ensurePresetsLoaded(rule.slideItemType);

    // The default children (track → slide item → post image/title) are seeded
    // asynchronously after create. Poll briefly for the slide item, then apply.
    let attempts = 0;
    const tryApply = () => {
      attempts += 1;
      const slideItem = findByType(container, rule.slideItemType);
      if (!slideItem) {
        if (attempts < 20) {
          window.setTimeout(tryApply, 100);
        }
        return;
      }

      // Belt-and-braces guard: only auto-apply when the slide item still holds its
      // PLAIN default children (a fresh drop is Post Image + Post Title, nothing
      // else). If it already carries preset structure — an existing, user-styled
      // slider that slipped past the load-time baseline due to a model-ready race —
      // we must NOT clobber it. This makes the feature safe even if the baseline
      // missed: styled sliders are never restyled, only genuinely-default ones.
      if (!hasOnlyDefaultChildren(slideItem)) {
        handled.add(container.id); // styled already — stop reconsidering it
        return;
      }

      // Wait for the preset fetch (almost always already settled by now —
      // it started before the poll loop) before deciding whether to apply.
      presetsReady.then(() => {
        // Re-check: the slide item may have been styled (or the container
        // removed) during the wait for a slow fetch.
        if (handled.has(container.id) || !hasOnlyDefaultChildren(slideItem)) {
          return;
        }

        const presets = getCachedPresetsForType(rule.slideItemType);
        const preset = presets.find((p) => p.id === rule.presetId) || presets[0];
        if (!preset || !preset.model) {
          return;
        }

        handled.add(container.id);

        // Seed slider settings (correct aae-rj shape) BEFORE the preset apply
        // below (which re-selects a new element). Best-effort — never blocks
        // the preset.
        if (rule.settings) {
          applySliderSettings(container, rule.settings);
        }

        applyPresetModel(preset.model, slideItem.id, rule.slideItemType, {
          title: 'Default preset',
          subtitle: `Applied "${preset.name}"`,
        });
      });
    };
    tryApply();
  });
}

/**
 * Install the auto-preset watcher. Idempotent; tracked for teardown.
 *
 * Atomic-widget creation in this Elementor version does NOT emit a catchable
 * `document/elements/create` on the command bus (verified: run:after never fires
 * for an atomic drop, and the parent's model collection emits no 'add'). So we
 * can't hook the drop directly. Instead we take a baseline of the sliders present
 * at load, then poll the model tree on a light heartbeat: any auto-preset-
 * eligible widget that appears AFTER the baseline is a fresh drop → apply its
 * default preset once. The `handled` set guarantees a single application.
 */
export function startAutoPreset() {
  if (!window.elementor?.documents?.getCurrent) {
    return;
  }

  let baselined = false;
  let stopHeartbeat = null;

  // Take the baseline ONLY once the document model is actually populated —
  // marking every slider already on the page as handled so we never restyle an
  // existing, user-styled slider on load. If we baselined against an empty tree
  // (bootstrap can run before the model is ready) the existing sliders would look
  // "new" and get clobbered. So we wait for a non-empty tree, then start polling.
  const establishBaseline = () => {
    const root = window.elementor?.documents?.getCurrent?.()?.container;
    // A ready document has a container with children (the page's elements). If the
    // tree isn't ready yet, keep waiting — do NOT poll for drops in the meantime.
    if (!root || !(root.children && root.children.length)) {
      return false;
    }
    pendingAutoPresetTargets().forEach((c) => handled.add(c.id));
    baselined = true;
    return true;
  };

  const tick = () => {
    if (!baselined) {
      // Still waiting for the document to populate; only try to baseline.
      establishBaseline();
      return;
    }
    maybeAutoApply();
  };

  // Try immediately (usual case: tree already there), then keep ticking. The
  // first successful tick establishes the baseline; subsequent ticks apply to
  // genuinely new drops. setInterval (not rAF) so drop detection keeps working
  // even if the frame isn't actively painting; the per-tick scan is cheap.
  establishBaseline();
  const intervalId = window.setInterval(tick, 1000);
  stopHeartbeat = () => window.clearInterval(intervalId);

  track(() => {
    try {
      if (stopHeartbeat) {
        stopHeartbeat();
      }
    } catch (_e) {
      /* nothing to clear */
    }
  });
}
