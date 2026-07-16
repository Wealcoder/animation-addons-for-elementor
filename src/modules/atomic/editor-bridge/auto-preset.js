/* eslint-env browser */

/**
 * Auto-apply a default preset when a widget is freshly dropped.
 *
 * A bare AAE widget (Loop Grid Slider, Progress Bar, Button, Button Pro,
 * Timeline, Toggle Switcher, Social Share, …) drops with its plain
 * define_default_children() output, which looks unstyled. To give a good
 * out-of-the-box result we apply a chosen default preset the first time each
 * one is created — the same transform the "Apply Preset" dropdown runs, so
 * the outcome is identical to picking that preset by hand.
 *
 * We hook Elementor's command bus (`document/elements/create`) rather than a DOM
 * observer: create fires once per drop with the new element in the model, and we
 * can locate the seeded slide item from the V1 container tree. We only act on the
 * user's own drop (not on undo/redo/import re-creates) and only once per element.
 */

import { track } from './disposables';
import { applyPresetModel, getPresetsForType } from '../element-controls/preset-apply';

// Which freshly-dropped widget gets which default preset (by preset id — the
// sanitized json basename) AND which slider settings to seed so the preset lands
// polished. The `settings` keys are the slider's atomic props (NestedSlider
// Schema NS_*); they are Responsive_JSON props whose value must be wrapped as
// { $$type: 'aae-rj', value: { desktop: <n> } } — see applySliderSettings. (An
// earlier attempt wrote a bare { desktop: <n> } and Elementor rejected it as
// invalid_value, corrupting the slider settings so publish threw.)
//
// Two shapes of rule:
//  - `slideItemType` set: the preset targets a nested item inside the dropped
//    container (e.g. the slider's slide item), located via findByType().
//  - `slideItemType` absent: the dropped widget itself is the preset target
//    (e.g. the progress bar) — `isDefault(container)` decides whether it still
//    holds its untouched default children and is safe to replace.
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
  'e-aae-a-progressbar': {
    presetId: 'progressbar-line',
    // A fresh drop's children (Track/Fill/Percentage) all carry `aae-pb-default`
    // — see AAE_A_Progressbar::define_default_children(). Any preset apply, or
    // hand-built children, drop that class, so this only ever matches an
    // untouched instance.
    isDefault: (container) => {
      const kids = container.children || [];
      return kids.length > 0 && kids.every((kid) => getClassesList(kid).includes('aae-pb-default'));
    },
  },
  // These widgets carry no marker class on their default children, so
  // "still default" is decided structurally instead — the exact multiset of
  // child types define_default_children() seeds. See matchesDefaultShape().
  'e-aae-a-btn': {
    presetId: 'button-free-default-divide-div',
    isDefault: (container) => matchesDefaultShape(container, ['e-paragraph', 'e-svg']),
  },
  'e-aae-a-timeline': {
    presetId: 'timeline-social',
    isDefault: (container) => matchesDefaultShape(container, Array(4).fill('e-aae-a-timeline-item')),
  },
  'e-aae-a-toggle-switcher': {
    presetId: 'toggle-switcher-style-switch',
    isDefault: (container) =>
      matchesDefaultShape(container, [
        'e-paragraph',
        'e-div-block',
        'e-paragraph',
        'e-aae-a-toggle-pane',
        'e-aae-a-toggle-pane',
      ]),
  },
  'e-aae-a-social-share': {
    presetId: 'social-share-outlined',
    isDefault: (container) => matchesDefaultShape(container, Array(3).fill('e-aae-a-social-share-item')),
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

/** The `classes` prop value (array of class names) a container currently carries. */
function getClassesList(container) {
  const classes = container?.settings?.attributes?.classes;
  return classes && Array.isArray(classes.value) ? classes.value : [];
}

/**
 * True when `container`'s direct children are exactly `expectedTypes` — same
 * length, same widget/elType values, order-independent (a multiset match).
 * Used to detect "still holds its untouched define_default_children() output"
 * for widgets whose defaults carry no marker class to check instead.
 */
function matchesDefaultShape(container, expectedTypes) {
  const kids = container.children || [];
  if (kids.length !== expectedTypes.length) {
    return false;
  }
  const kinds = kids.map((c) => c.model?.get?.('widgetType') || c.model?.get?.('elType')).sort();
  const expected = [...expectedTypes].sort();
  return kinds.every((k, i) => k === expected[i]);
}

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

    // The default children are seeded asynchronously after create. Poll
    // briefly for the actual preset target (a nested item, or the dropped
    // container itself), then apply.
    let attempts = 0;
    const tryApply = () => {
      attempts += 1;

      // Rules with `slideItemType` target a nested item; rules without it
      // target the dropped container directly (e.g. the progress bar).
      const target = rule.slideItemType ? findByType(container, rule.slideItemType) : container;
      const targetType = rule.slideItemType || type;

      if (!target || !(target.children || []).length) {
        if (attempts < 40) {
          window.setTimeout(tryApply, 50);
        }
        return;
      }

      // Belt-and-braces guard: only auto-apply when the target still holds its
      // PLAIN default children. If it already carries preset structure — an
      // existing, user-styled element that slipped past the load-time baseline
      // due to a model-ready race — we must NOT clobber it. This makes the
      // feature safe even if the baseline missed: styled elements are never
      // restyled, only genuinely-default ones.
      const isDefault = rule.isDefault ? rule.isDefault(target) : hasOnlyDefaultChildren(target);
      if (!isDefault) {
        handled.add(container.id); // styled already — stop reconsidering it
        return;
      }

      const presets = getPresetsForType(targetType);
      const preset = presets.find((p) => p.id === rule.presetId) || presets[0];
      if (!preset || !preset.model) {
        return;
      }

      handled.add(container.id);

      // Seed extra settings (correct aae-rj shape) BEFORE the preset apply below
      // (which re-selects a new element). Best-effort — never blocks the preset.
      if (rule.settings) {
        applySliderSettings(container, rule.settings);
      }

      applyPresetModel(preset.model, target.id, targetType, {
        title: 'Default preset',
        subtitle: `Applied "${preset.name}"`,
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
  //
  // 150ms (not the original 1000ms): at 1s, a freshly dropped widget visibly
  // renders with its bare default children for up to a full second before the
  // watcher notices and swaps in the preset — a jarring flash of unstyled
  // content. 150ms keeps that window imperceptible while still being cheap
  // (a single small tree walk) for any document size in practice.
  establishBaseline();
  const intervalId = window.setInterval(tick, 150);
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
