/* eslint-env browser */

/**
 * Auto-apply a default preset when a widget is freshly dropped.
 *
 * Some widgets drop unstyled: a bare Loop Grid Slider is a plain Post Image +
 * Post Title card, and a bare Image Compare is six children in normal flow
 * because all of its layout lives in its presets. To give a good out-of-the-box
 * result we apply a chosen default preset the first time such a widget is
 * created — the same transform the "Apply Preset" dropdown runs, so the outcome
 * is identical to picking that preset by hand.
 *
 * We hook Elementor's command bus (`document/elements/create`) rather than a DOM
 * observer: create fires once per drop with the new element in the model, and we
 * can locate the seeded slide item from the V1 container tree. We only act on the
 * user's own drop (not on undo/redo/import re-creates) and only once per element.
 */

import { track } from './disposables';
import { applyPresetModel, ensurePresetsLoaded, getCachedPresetsForType } from '../element-controls/preset-apply';

/**
 * Which freshly-dropped widget gets which default preset.
 *
 * Rule fields:
 *   presetId        sanitized json basename of the preset to apply
 *   targetType      OPTIONAL. The DESCENDANT type that actually receives the
 *                   preset (the slider styles its slide item, not itself).
 *                   Omit it and the dropped widget receives the preset on
 *                   ITSELF — applyPresetModel replaces it in place.
 *   defaultChildren OPTIONAL freshness test: untouched only while the target
 *                   holds exactly these widget types and nothing else.
 *   defaultMarker   OPTIONAL freshness test: untouched only while EVERY child
 *                   carries this class. Use this instead of defaultChildren
 *                   when the preset seeds the SAME widget types as
 *                   define_default_children() — shape alone can't tell
 *                   "untouched" from "just applied" then, and the watcher would
 *                   re-apply to its own output forever (the replacement element
 *                   gets a fresh id, so `handled` never catches it).
 *   settings        OPTIONAL props seeded BEFORE the preset apply. Values must
 *                   be wrapped as { $$type: 'aae-rj', value: { desktop: <n> } }
 *                   — see applyAutoSettings. (An earlier attempt wrote a bare
 *                   { desktop: <n> }; Elementor rejected it as invalid_value,
 *                   corrupting the settings so publish threw.)
 */
const AUTO_PRESETS = {
  'e-aae-a-loop-grid-slider': {
    targetType: 'e-aae-a-loop-slide-item',
    presetId: 'bold-overlay-zoom',
    // A fresh slide card is Post Image + Post Title; the preset replaces that
    // with a different tree, so shape alone detects "already styled".
    defaultChildren: ['e-aae-a-post-image', 'e-aae-a-post-title'],
    // Default to a single slide per view (no neighbour sliver, small gap). The
    // user can raise slidesPerView from the panel for a multi-up layout.
    settings: {
      aae_ns_slides_per_view: 1,
      aae_ns_peek: 0,
      aae_ns_gap: 16,
    },
  },

  // Progress Bar renders fine bare (its parts are real widget types with their
  // own base styles), so this is a "start from a designed look" default rather
  // than a repair. Circle deliberately: a fresh drop already IS the Line look,
  // so auto-applying `progressbar-line` would be an invisible no-op — and,
  // worse, it would seed the same [track, label] child shape the defaults have,
  // which defaultChildren below could then never distinguish, re-applying
  // forever. Circle's tree is [svg, label], so the test settles on one pass.
  'e-aae-a-progressbar': {
    presetId: 'progressbar-circle',
    defaultChildren: ['e-aae-a-progressbar-track', 'e-aae-a-progressbar-label'],
  },

  // Image Compare carries NO per-child layout of its own: the absolute
  // positioning, the before image's clip-path, the divider/thumb placement and
  // the z-index stack all live in the preset (define_base_styles() only styles
  // the root). Dropped bare it renders as six unstyled children stacked
  // vertically, so it has to arrive pre-set to be usable at all.
  'e-aae-a-image-compare': {
    // No targetType — the preset root IS an e-aae-a-image-compare, so the
    // dropped widget itself is replaced by the styled one.
    presetId: 'image-compare-horizontal',
    // The preset seeds the same six widget types define_default_children()
    // does, so only this marker class separates the two.
    defaultMarker: 'aae-ic-default',
  },
};

// The $$type Elementor uses for the slider's Responsive_JSON props (observed on a
// live slider: { $$type: 'aae-rj', value: { desktop: N } }).
const RESPONSIVE_JSON_TYPE = 'aae-rj';

// Guard so we never auto-apply twice to the same created element.
const handled = new Set();

/** A container's effective element type (widgetType wins over elType). */
function typeOf(container) {
  return container?.model?.get?.('widgetType') || container?.model?.get?.('elType');
}

/** A container's `classes` prop value, whichever shape `settings` comes back as. */
function classesOf(container) {
  const settings = container?.model?.get?.('settings');
  const classes = settings?.get?.('classes') ?? settings?.classes;
  return classes?.value || [];
}

/**
 * True when `target` still holds its plain seeded children — a genuine fresh
 * drop, not something the user (or a previous apply) has already styled.
 *
 * This is the belt-and-braces guard behind the load-time baseline: if the
 * baseline ever misses an element to a model-ready race, this still refuses to
 * clobber it. A rule carrying neither test opts out and is presetted on sight.
 */
function isUntouched(target, rule) {
  const kids = target.children || [];

  if (rule.defaultMarker) {
    return kids.length > 0 && kids.every((c) => classesOf(c).includes(rule.defaultMarker));
  }

  if (rule.defaultChildren) {
    if (kids.length !== rule.defaultChildren.length) {
      return false;
    }
    const kinds = kids.map(typeOf);
    // Every default widget present, and no extras (lengths already match).
    return rule.defaultChildren.every((w) => kinds.includes(w));
  }

  return true;
}

/** Depth-first find the first container of `type` under `root`. */
function findByType(root, type) {
  if (!root) {
    return null;
  }
  if (typeOf(root) === type) {
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
    const type = typeOf(c);
    if (AUTO_PRESETS[type] && !handled.has(c.id)) {
      out.push(c);
    }
    (c.children || []).forEach(walk);
  };
  walk(root);
  return out;
}

/**
 * Seed a rule's `settings` on the dropped container. Each value is wrapped in
 * the Responsive_JSON prop shape — { $$type: 'aae-rj', value: { desktop: N } } —
 * the SAME shape a live widget stores (a bare { desktop: N } is rejected as
 * invalid_value and corrupts the settings, breaking publish). Best-effort.
 */
function applyAutoSettings(container, settings) {
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
    const type = typeOf(container);
    const rule = AUTO_PRESETS[type];
    if (!rule) {
      return;
    }
    if (handled.has(container.id)) {
      return;
    }

    // Which element the preset is keyed to and lands on: a descendant when the
    // rule names one (the slider styles its slide item), else the dropped
    // widget itself (Image Compare — the preset root is that same type, so
    // applyPresetModel swaps it in place).
    const presetType = rule.targetType || type;

    // Presets are now fetched on demand (remote + local merged server-side —
    // see preset-apply.js's ensurePresetsLoaded) rather than read from an
    // eager global. Kick the fetch off immediately, in parallel with the
    // target poll below, so it's very likely already resolved by the time the
    // target appears; getCachedPresetsForType() is a plain synchronous read
    // once ensurePresetsLoaded's promise settles.
    const presetsReady = ensurePresetsLoaded(presetType);

    // A descendant target (track → slide item → post image/title) is seeded
    // asynchronously after create, so poll briefly for it. A self-target is
    // there by definition and resolves on the first pass.
    let attempts = 0;
    const tryApply = () => {
      attempts += 1;
      const target = rule.targetType ? findByType(container, rule.targetType) : container;
      if (!target) {
        if (attempts < 20) {
          window.setTimeout(tryApply, 100);
        }
        return;
      }

      // Belt-and-braces guard: only auto-apply while the target still holds its
      // PLAIN seeded children. If it already carries preset structure — an
      // existing, user-styled element that slipped past the load-time baseline
      // due to a model-ready race — we must NOT clobber it. This makes the
      // feature safe even if the baseline missed: styled elements are never
      // restyled, only genuinely-default ones.
      if (!isUntouched(target, rule)) {
        handled.add(container.id); // styled already — stop reconsidering it
        return;
      }

      // Wait for the preset fetch (almost always already settled by now —
      // it started before the poll loop) before deciding whether to apply.
      presetsReady.then(() => {
        // Re-check: the target may have been styled (or the container removed)
        // during the wait for a slow fetch.
        if (handled.has(container.id) || !isUntouched(target, rule)) {
          return;
        }

        const presets = getCachedPresetsForType(presetType);
        const preset = presets.find((p) => p.id === rule.presetId) || presets[0];
        if (!preset || !preset.model) {
          return;
        }

        handled.add(container.id);

        // Seed settings (correct aae-rj shape) BEFORE the preset apply below
        // (which re-selects a new element). Best-effort — never blocks the
        // preset. Skipped for a self-target: applyPresetModel replaces that
        // very element, so anything written here is discarded with it — the
        // preset model carries its own root settings instead.
        if (rule.settings && rule.targetType) {
          applyAutoSettings(container, rule.settings);
        }

        applyPresetModel(preset.model, target.id, presetType, {
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
