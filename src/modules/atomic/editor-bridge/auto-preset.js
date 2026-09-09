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
import {
  applyPresetModel,
  ensurePresetsLoaded,
  getCachedPresetsForType,
  isAutoPresetSuppressed,
} from '../element-controls/preset-apply';

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

  // Image Compare carries NO per-child layout of its own: the absolute
  // positioning, the before image's clip-path, the divider/thumb placement and
  // the z-index stack all live in the preset (define_base_styles() only styles
  // the root). Dropped bare it renders as six unstyled children stacked
  // vertically, so it has to arrive pre-set to be usable at all.
  'e-aae-a-image-compare': {
    // No targetType — the preset root IS an e-aae-a-image-compare, so the
    // dropped widget itself is replaced by the styled one. (Verified against
    // remote-12004: its model root is that type directly, not a container, so
    // applyPresetModel has nothing to unwrap.)
    //
    // This was 'image-compare-horizontal' — the id Local_Fallback derives from
    // the bundled presets/image-compare-horizontal.json filename. That folder
    // is being removed in favour of the remote catalog, so the local id stops
    // resolving and the rule has to name the remote row.
    //
    // Worth knowing why this did not fail loudly when local went away: with no
    // `presetName` set, maybeAutoApply()'s last fallback is presets[0], and the
    // remote 'Horizontal' happens to sort first. So the drop would still have
    // landed on the right preset — by luck of ordering, until the catalog gains
    // another image-compare preset that sorts ahead of it. Naming it removes
    // that dependence.
    presetId: 'remote-12004',
    // Remote ids are the preset server's own row ids, so they can move if the
    // catalog is ever re-seeded. Name is the stable identity — see the
    // resolution order in maybeAutoApply(). It only has to be unique within
    // THIS element type's preset list, which 'Horizontal' is ('Horizontal' vs
    // 'Vertical').
    presetName: 'Horizontal',
    // The preset seeds the same six widget types define_default_children()
    // does, so only this marker class separates the two. Stamped by
    // class-aae-a-image-compare.php on all six seeded children; registered in
    // hook-classes-provider.js so the panel cannot strip it.
    defaultMarker: 'aae-ic-default',
  },

  // A bare Stack Cards drops as four empty cards in a plain vertical list —
  // readable, but it gives no hint that the widget is a scroll-scrubbed deck.
  // This lands it on a real pile with styled cards.
  'e-aae-a-stack-cards': {
    // No targetType: the preset's model root IS an e-aae-a-stack-cards, so the
    // dropped widget itself is what gets replaced.
    //
    // Scroll Stack is the pick because it matches the widget's OWN defaults:
    // animations.js exports DEFAULT_ANIMATION = 'scroll-stack', so a fresh
    // drop already behaves this way and the preset adds the styling without
    // also changing the motion out from under the panel's shown values.
    presetId: 'remote-12059',
    // Remote ids are the preset server's own row ids and move if the catalog
    // is re-seeded, so the name is the stable identity — see the resolution
    // order in maybeAutoApply(). Note the em dash: it is the real character in
    // the preset's name, not a hyphen.
    presetName: 'Scroll Stack — Showcase',
    // Marker, not shape: a fresh drop and EVERY preset for this widget are
    // both four e-aae-a-stack-card children, so shape cannot tell them apart.
    // See AAE_A_Stack_Cards::DEFAULT_CHILD_MARKER.
    defaultMarker: 'aae-sc-default',
  },

  // A bare Nested Slider drops as one empty slide plus every chrome part
  // (nav, pagination, indicators) in its own default styling, which reads as
  // a scattered set of controls rather than a slider. This lands it on a real
  // layout instead.
  'e-aae-a-slider': {
    // No targetType: the preset's root is an e-flexbox wrapping the slider,
    // and applyPresetModel unwraps a container root (CONTAINER_TYPES), so the
    // dropped slider itself is what gets replaced.
    presetId: 'remote-11948',
    // Remote ids are the preset server's own row ids, so they can move if the
    // catalog is ever re-seeded. Name is the stable identity — see the
    // resolution order in maybeAutoApply().
    presetName: 'Testimonials - Clerk slider',
    // Marker, not shape — the same call Image Compare makes, for the same
    // reason. Shape would "work" against the preset we ship today (five
    // default children vs the preset's two), but it is only as good as that
    // coincidence: a preset that keeps all five parts, which is a perfectly
    // reasonable thing for a user to export, would be indistinguishable from a
    // fresh drop and the watcher would re-apply to its own output forever
    // (the replacement gets a new id, so `handled` never catches it).
    // AAE_A_Slider::DEFAULT_CHILD_MARKER stamps this class on every seeded
    // child; no preset carries it, so its absence is a definite "already
    // presetted".
    defaultMarker: 'aae-slider-default',
    // No `settings`: this is a self-target, and applyAutoSettings is skipped
    // for those (the replaced element would discard them). The preset model
    // carries its own slider settings.
  },
};

// The $$type Elementor uses for the slider's Responsive_JSON props (observed on a
// live slider: { $$type: 'aae-rj', value: { desktop: N } }).
const RESPONSIVE_JSON_TYPE = 'aae-rj';

// Guard so we never auto-apply twice to the same created element.
const handled = new Set();

// Module scope, not a local in startAutoPreset(), so the debug hook below can
// report it — "was the baseline ever taken?" is the first thing you need to
// know when a drop silently fails to get its preset.
let baselined = false;

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
    // isAutoPresetSuppressed covers "Reset to Default": that recreates the
    // element, so the restored one has a new id `handled` knows nothing about,
    // and its shape is the plain default the freshness test calls "fresh".
    // Without this check the next heartbeat put the preset straight back and
    // Reset appeared to do nothing.
    if (AUTO_PRESETS[type] && !handled.has(c.id) && !isAutoPresetSuppressed(c.id)) {
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

        // id first, then name, then — only for rules that named no preset —
        // the historical "just take the first one" fallback.
        //
        // A rule that DOES carry `presetName` deliberately opts out of that
        // fallback: for a remote preset the id is the server's row id, so if
        // the catalog is re-seeded the id goes stale, and falling through to
        // presets[0] would then silently stamp an arbitrary design onto every
        // slider the user drops. Applying nothing is the better failure.
        const preset =
          presets.find((p) => p.id === rule.presetId) ||
          (rule.presetName && presets.find((p) => p.name === rule.presetName)) ||
          (rule.presetName ? null : presets[0]);

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
 * `window.__aaeAutoPreset` — console surface for "why did my drop not get its
 * preset?". Every gate in this module is silent by design (a wrong guess must
 * never clobber a styled element), which makes a failure invisible without
 * this. Mirrors the existing `window.__aaeAtomicBridge` convention.
 *
 *   __aaeAutoPreset.explain()   per eligible element, which gate it fails
 *   __aaeAutoPreset.state()     baselined? heartbeat installed? handled ids
 *   __aaeAutoPreset.run()       force one pass now, ignoring the heartbeat
 *   __aaeAutoPreset.forget(id)  drop an id from `handled` so it can re-apply
 */
function installDebugHook() {
  window.__aaeAutoPreset = {
    rules: AUTO_PRESETS,

    state: () => ({
      baselined,
      handled: Array.from(handled),
      heartbeatInstalled: !!window.__aaeAutoPresetHeartbeat,
      docReady: !!window.elementor?.documents?.getCurrent?.()?.container,
    }),

    /** Per eligible element: the value of every gate, in the order applied. */
    explain: () => {
      const root = window.elementor?.documents?.getCurrent?.()?.container;
      const rows = [];

      const walk = (c) => {
        if (!c) {
          return;
        }
        const type = typeOf(c);
        const rule = AUTO_PRESETS[type];

        if (rule) {
          const presetType = rule.targetType || type;
          const target = rule.targetType ? findByType(c, rule.targetType) : c;
          const presets = getCachedPresetsForType(presetType);

          rows.push({
            id: c.id,
            type,
            // gates, in the order maybeAutoApply applies them
            gate_handled: handled.has(c.id),
            gate_suppressed: isAutoPresetSuppressed(c.id),
            gate_baselined: baselined,
            gate_targetFound: !!target,
            gate_untouched: target ? isUntouched(target, rule) : null,
            presetsLoaded: presets.length,
            presetResolved:
              presets.find((p) => p.id === rule.presetId)?.name ||
              (rule.presetName && presets.find((p) => p.name === rule.presetName)?.name) ||
              null,
            children: (target?.children || []).map((k) => ({
              type: typeOf(k),
              classes: classesOf(k),
            })),
          });
        }

        (c.children || []).forEach(walk);
      };

      walk(root);
      return rows;
    },

    run: () => {
      baselined = true;
      maybeAutoApply();
      return 'maybeAutoApply() invoked — watch for a "Default preset" history entry';
    },

    forget: (id) => {
      handled.delete(id);
      return `dropped ${id} from handled`;
    },
  };
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
  installDebugHook();

  if (!window.elementor?.documents?.getCurrent) {
    return;
  }

  let stopHeartbeat = null;

  // Take the baseline ONLY once the document model is actually populated —
  // marking every slider already on the page as handled so we never restyle an
  // existing, user-styled slider on load. If we baselined against an empty tree
  // (bootstrap can run before the model is ready) the existing sliders would look
  // "new" and get clobbered. So we wait for a non-empty tree, then start polling.
  const establishBaseline = () => {
    // ONCE per editor session, not once per bootstrap.
    //
    // bootstrap() re-runs on every `preview:loaded` — document switch,
    // responsive-mode change, and any other preview reload — and it calls
    // startAutoPreset() again, which called this again. Each of those re-runs
    // swallowed whatever was on the page AT THAT MOMENT into `handled`,
    // including a widget the user had just dropped and that had not been
    // presetted yet. From then on it was permanently ineligible: `handled`
    // is module-scoped and nothing removes an id from it. The drop silently
    // got no preset, and every gate downstream looked fine — which is exactly
    // the failure we were chasing.
    //
    // The baseline means "what was already on the page when we started
    // watching". That question has one answer per session, so answer it once.
    if (baselined) {
      return true;
    }

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
  // Recorded so __aaeAutoPreset.state() can say whether the heartbeat is
  // actually running — "installed but never ticking" and "never installed"
  // look identical from the outside otherwise.
  window.__aaeAutoPresetHeartbeat = intervalId;
  stopHeartbeat = () => {
    window.clearInterval(intervalId);
    if (window.__aaeAutoPresetHeartbeat === intervalId) {
      delete window.__aaeAutoPresetHeartbeat;
    }
  };

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
