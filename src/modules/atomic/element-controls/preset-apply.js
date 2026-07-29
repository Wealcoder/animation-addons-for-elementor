/* eslint-env browser */

/**
 * Shared preset-apply engine.
 *
 * The React PresetPickerControl (the "Apply Preset" dropdown) and the editor
 * auto-preset module (which applies a default preset when a Loop Grid Slider is
 * dropped) both need the exact same transform: sanitize a preset model, unwrap a
 * container root, regenerate local style ids so repeated applies don't collide,
 * optionally rewrite the root type (a slide reusing a grid-item preset must stay
 * a slide), then replace a target element in place. This module is that engine so
 * both call sites stay in lock-step.
 */

import {
  createElements,
  getContainer,
  removeElements,
  selectElement,
} from '@elementor/editor-elements';

import { applySettingsToDoms } from '../editor-bridge/settings-bridge';

// Container element types whose wrapper is unwrapped on apply.
export const CONTAINER_TYPES = ['e-flexbox', 'e-div-block', 'e-grid', 'container'];

// Some element types share another type's preset library. The Loop Grid Slider's
// slide item (`e-aae-a-loop-slide-item`) is a subclass of the Loop Grid item
// (`e-aae-a-loop-item`) with the same authored-card shape, so it reuses the Loop
// Grid presets. Presets are keyed by the type detected inside each preset model
// (always `e-aae-a-loop-item` here), so without this alias the slide item's
// picker is empty. On apply we rewrite the created root's type to the selected
// element's own type so a slide stays a slide.
export const PRESET_TYPE_ALIASES = {
  'e-aae-a-loop-slide-item': 'e-aae-a-loop-item',
};

// Per-type in-memory cache, populated by ensurePresetsLoaded(). Both
// PresetPickerControl (on mount) and auto-preset.js (on drop) read through
// this same cache so a type fetched once this session is available to both
// call sites without duplicate network requests.
const _fetchedPresetsByType = {};
const _pendingFetchesByType = {};

/**
 * Synchronous read of whatever presets have already been fetched THIS
 * session for a type (honouring the alias table). Returns [] for a type
 * that hasn't been fetched yet — callers that can tolerate an empty result
 * on a cold type (e.g. auto-preset's drag-drop default, which awaits
 * ensurePresetsLoaded() first) can use this directly; UI code that must
 * show a real list should await ensurePresetsLoaded() instead.
 */
export function getCachedPresetsForType(type) {
  const key = _fetchedPresetsByType[type] ? type : PRESET_TYPE_ALIASES[type] || type;
  const list = _fetchedPresetsByType[key];
  return Array.isArray(list) ? list : [];
}

/**
 * Fetches (and caches) presets for one element type from this plugin's own
 * REST proxy (aae/v1/presets — see inc/Atomic/Presets/Rest.php), which
 * merges remote (themecrowdy.com) + local bundled presets server-side.
 * Resolves to [] on any network/parse failure — never rejects, so callers
 * never need a .catch().
 *
 * Safe to call repeatedly for the same type: a fetch already in flight is
 * shared (not duplicated), and a type already resolved this session is
 * served straight from the in-memory cache.
 */
export function ensurePresetsLoaded(type) {
  const key = _fetchedPresetsByType[type] !== undefined ? type : PRESET_TYPE_ALIASES[type] || type;

  if (_fetchedPresetsByType[key] !== undefined) {
    return Promise.resolve(_fetchedPresetsByType[key]);
  }

  if (_pendingFetchesByType[key]) {
    return _pendingFetchesByType[key];
  }

  const config = window.AAE_PRESET_CONFIG;
  if (!config || !config.restUrl) {
    _fetchedPresetsByType[key] = [];
    return Promise.resolve([]);
  }

  // config.restUrl is rest_url('aae/v1/presets') — on a PLAIN-permalink
  // install that's already a query string itself, e.g.
  // "https://site.test/?rest_route=/aae/v1/presets" (no pretty rewrite
  // rules), so naively appending "?element_type=…" produces a malformed
  // double-query URL WordPress can't parse (rest_route ends up containing
  // the element_type param as part of its own value, always 404ing).
  // Appending with "&" when restUrl already has a "?" — matching how PHP's
  // add_query_arg() handles the same ambiguity — is correct in both cases.
  const separator = config.restUrl.indexOf('?') === -1 ? '?' : '&';
  const url = `${config.restUrl}${separator}element_type=${encodeURIComponent(key)}`;

  const promise = fetch(url, {
    headers: config.nonce ? { 'X-WP-Nonce': config.nonce } : undefined,
  })
    .then((res) => (res.ok ? res.json() : { presets: [] }))
    .then((data) => (Array.isArray(data?.presets) ? data.presets : []))
    .catch(() => [])
    .then((presets) => {
      _fetchedPresetsByType[key] = presets;
      delete _pendingFetchesByType[key];
      return presets;
    });

  _pendingFetchesByType[key] = promise;
  return promise;
}

export function isContainerModel(model) {
  const type = model.widgetType || model.elType;
  return CONTAINER_TYPES.indexOf(type) !== -1;
}

/**
 * Prop types sharing Elementor's image-source XOR shape: an attachment `id`
 * OR a `url`, but NOT both. `svg-src` (the `e-svg` widget's `svg` prop) is
 * structurally identical to `image-src` and enforces the same rule.
 */
const IMAGE_SRC_TYPES = ['image-src', 'svg-src'];

/**
 * Sanitize a preset model so it passes Elementor's save-time style validation.
 *
 * Elementor's Image_Src prop (and svg-src, same shape) enforces an XOR rule:
 * an image source may carry an attachment `id` OR a `url`, but NOT both.
 * Native exports routinely include both (id + cached url), which renders fine
 * in the editor but is REJECTED on publish with "...background: invalid_value"
 * (or "svg: invalid_value" for e-svg). We walk the whole model and, for every
 * image-src/svg-src that has both, drop the `url` (the attachment id is the
 * source of truth; WP regenerates the url from it).
 */
export function sanitizeImageSrc(node) {
  if (Array.isArray(node)) {
    node.forEach(sanitizeImageSrc);
    return;
  }
  if (!node || typeof node !== 'object') {
    return;
  }

  if (IMAGE_SRC_TYPES.indexOf(node.$$type) !== -1 && node.value && typeof node.value === 'object') {
    const src = node.value;
    const hasId = src.id && src.id.value !== undefined && src.id.value !== null && src.id.value !== '';
    const hasUrl =
      src.url &&
      (typeof src.url.value === 'string'
        ? src.url.value !== ''
        : src.url.value !== undefined && src.url.value !== null);
    if (hasId && hasUrl) {
      delete src.url;
    }
  }

  Object.keys(node).forEach((key) => {
    const child = node[key];
    if (child && typeof child === 'object') {
      sanitizeImageSrc(child);
    }
  });
}

/** Fresh, collision-resistant local style id (mirrors Elementor's shape). */
function randomStyleId() {
  const rand = () => Math.random().toString(36).slice(2, 9);
  return `e-${rand()}-${rand()}`;
}

// Elementor's own auto-generated local style ids always look like
// "e-<hash>-<hash>". Some presets deliberately key a style under a literal,
// human-readable name instead (e.g. "aae-btn-txtflip-content") so it doubles
// as the widget's own compiled JS/CSS hook class (see btn.js / btn.scss).
// Those must never be renamed — doing so silently detaches the element from
// the hook it needs to function. Only ids matching Elementor's own generated
// shape are safe to regenerate for collision-avoidance.
const AUTO_STYLE_ID_RE = /^e-[a-z0-9]+-[a-z0-9]+$/i;

/**
 * Recursively regenerate every element's LOCAL style ids in a preset model and
 * rewrite the matching `classes` references, so applying the same styled preset
 * to multiple widgets never shares (collides on) style-id classes. `create` does
 * NOT auto-regenerate style ids (only paste/import/duplicate hooks do), so we do
 * it here before createElements(). Literal hook-class-keyed styles are left
 * untouched — see AUTO_STYLE_ID_RE above.
 */
export function regenerateModelStyleIds(model) {
  if (!model || typeof model !== 'object') {
    return model;
  }

  const styles = model.styles;
  if (styles && typeof styles === 'object' && !Array.isArray(styles)) {
    const changed = {};
    const newStyles = {};

    Object.keys(styles).forEach((oldId) => {
      if (!AUTO_STYLE_ID_RE.test(oldId)) {
        newStyles[oldId] = styles[oldId];
        return;
      }
      const newId = randomStyleId();
      changed[oldId] = newId;
      newStyles[newId] = { ...styles[oldId], id: newId };
    });

    model.styles = newStyles;

    const classesProp = model.settings && model.settings.classes;
    if (classesProp && classesProp.$$type === 'classes' && Array.isArray(classesProp.value)) {
      classesProp.value = classesProp.value.map((cls) => changed[cls] || cls);
    }
  }

  if (Array.isArray(model.elements)) {
    model.elements.forEach(regenerateModelStyleIds);
  }

  return model;
}

// Element types that sit inside a slider track and MUST NOT carry their own
// padding: the slider runtime sizes each slide to `100% / slidesPerView`, so any
// padding on the slide root shrinks its content box and pushes the next slide
// partly into view (a ~10% sliver). Presets authored for the plain Loop Grid do
// set a card padding, so we strip it when the preset is applied to these types.
const ZERO_PADDING_ROOT_TYPES = ['e-aae-a-loop-slide-item', 'e-aae-a-loop-item'];

/**
 * Remove `padding` from every style variant of a model's OWN styles (not its
 * children's). Used to neutralise a preset's card padding when it's applied to a
 * slide/loop item whose width is owned by the layout, so it can't leak a sliver
 * of the neighbouring slide.
 */
function stripRootPadding(model) {
  const styles = model && model.styles;
  if (!styles || typeof styles !== 'object') {
    return;
  }
  Object.keys(styles).forEach((sid) => {
    const variants = styles[sid] && styles[sid].variants;
    if (Array.isArray(variants)) {
      variants.forEach((v) => {
        if (v && v.props && 'padding' in v.props) {
          delete v.props.padding;
        }
      });
    }
  });
}

/**
 * Editor-preview sync for AAE interaction settings baked into presets
 * (e.g. the Custom CSS extension props carrying a preset's hover/keyframe
 * CSS). The editor bridge bulk-syncs every element's settings into the
 * preview iframe's interaction maps ONCE on document:loaded — elements
 * created later (a preset apply) never get synced, so their effects stay
 * dead until reload. Mirror the boot pass here for the created subtree,
 * then rescan so the runtime binds the new nodes. Retried because the
 * preview nodes mount asynchronously (bind needs the node present).
 */
function syncAaeInteractionsToPreview(createdElements) {
  const syncTree = (container) => {
    if (!container || !container.model) {
      return;
    }
    try {
      applySettingsToDoms(container);
    } catch (_) {
      /* per-element sync is best-effort */
    }
    const kids = container.children;
    if (kids && typeof kids.forEach === 'function') {
      kids.forEach(syncTree);
    }
  };

  const run = () => {
    try {
      createdElements.forEach(({ containerId }) => syncTree(getContainer(containerId)));
      const win =
        window.elementor?.$preview?.[0]?.contentWindow ||
        document.querySelector('#elementor-preview-iframe')?.contentWindow;
      if (win?.aaeAtomicAnimations?.scan) {
        win.aaeAtomicAnimations.scan(win.document);
      }
    } catch (_) {
      /* cosmetic only — never break the apply */
    }
  };

  [0, 400, 1200, 2400].forEach((delay) => setTimeout(run, delay));
}

/**
 * Cosmetic canvas fix for live-created atomic CONTAINERS (e-flexbox /
 * e-div-block / e-grid): the editor canvas does not stamp their `classes`
 * prop onto the preview node when they are created programmatically — the
 * class attribute only renders on a full document render. The saved model is
 * correct (frontend Twig renders the classes), but until a reload the preset
 * would look unstyled and marker-class CSS (hover overlays) would not match.
 * So after apply we mirror each created container's classes onto its preview
 * node. Widgets are skipped — their own markup already renders classes, and
 * duplicating e.g. a ::after marker onto the wrapper div would double the
 * effect.
 *
 * Retries a few times because the preview nodes mount asynchronously.
 */
function stampContainerClassesIntoPreview(createdElements) {
  const CONTAINER_ELTYPES = ['e-flexbox', 'e-div-block', 'e-grid', 'container'];

  // Every AAE widget (e-aae-a-*) extends the same Atomic_Element_Base /
  // is_container:true base as the core layout containers above, so it is
  // rendered through the same canvas path and hits the same "classes prop
  // isn't stamped on programmatic create" gap — not just the three core
  // container types. Without this, a preset whose root IS an AAE widget
  // (e.g. e-aae-a-btn itself) renders unstyled in the builder until reload,
  // even though the saved model — and the frontend Twig render — are correct.
  const isContainerLikeType = (elType) =>
    typeof elType === 'string' &&
    (CONTAINER_ELTYPES.indexOf(elType) !== -1 || elType.indexOf('e-aae-a-') === 0);

  const stampModel = (model, previewDoc) => {
    if (!model || !model.get) {
      return;
    }
    const elType = model.get('elType');
    if (isContainerLikeType(elType)) {
      const id = model.get('id');
      const classesProp = model.get('settings')?.get?.('classes');
      const list = classesProp && Array.isArray(classesProp.value) ? classesProp.value : null;
      const node = id && previewDoc.querySelector(`[data-id="${id}"]`);
      if (node && list) {
        list.forEach((cls) => {
          if (typeof cls === 'string' && cls) {
            node.classList.add(cls);
          }
        });
      }
    }
    const children = model.get('elements');
    if (children && children.each) {
      children.each((child) => stampModel(child, previewDoc));
    }
  };

  const stampAll = () => {
    try {
      const previewDoc =
        window.elementor?.$preview?.[0]?.contentDocument ||
        document.querySelector('#elementor-preview-iframe')?.contentDocument;
      if (!previewDoc) {
        return;
      }
      createdElements.forEach(({ containerId }) => {
        const container = getContainer(containerId);
        if (container && container.model) {
          stampModel(container.model, previewDoc);
        }
      });
    } catch (_) {
      /* cosmetic only — never break the apply */
    }
  };

  // Nodes mount async; classList.add is idempotent, so just fire a few times.
  [0, 300, 900, 1800].forEach((delay) => setTimeout(stampAll, delay));
}

/**
 * Resolve the target element's parent container + its index within it (V1
 * container model, not the DOM).
 */
export function getParentAndIndex(elementId) {
  const container = getContainer(elementId);
  const parent = container?.parent || null;
  if (!parent) {
    return { parent: null, index: 0 };
  }

  let index = 0;
  const children = parent.model?.get?.('elements');
  if (children?.each) {
    let i = 0;
    children.each((child) => {
      if (child.get('id') === elementId) {
        index = i;
      }
      i += 1;
    });
  }
  return { parent, index };
}

/**
 * Replace `elementId` in place with the models built from `presetModel`.
 *
 * - container roots are unwrapped (their children are placed at the target's
 *   position, the wrapper dropped);
 * - a non-container root whose elType differs from `targetType` is rewritten to
 *   `targetType` (so a slide reusing a grid-item preset stays a slide);
 * - local style ids are regenerated and image-src XOR is enforced;
 * - the original is removed and the first new element re-selected.
 *
 * Returns the first created element's id, or null on failure.
 */
export function applyPresetModel(presetModel, elementId, targetType, meta = {}) {
  if (!presetModel) {
    return null;
  }

  const { parent, index } = getParentAndIndex(elementId);
  if (!parent) {
    return null;
  }

  const root = JSON.parse(JSON.stringify(presetModel));

  // Type alias (e.g. the slider slide item reusing Loop Grid presets): rewrite a
  // non-container root's type to the target element's own type so the created
  // element is valid where it lands (right Twig/class). Compare the EFFECTIVE
  // type (widgetType wins over elType): native atomic widgets export as
  // { elType: 'widget', widgetType: 'e-heading' } and that shape must be kept
  // as-is — rewriting elType to 'e-heading' makes the v1 addElement child-type
  // check delegate into a widget view and crash ("addElement is not a
  // function"), because atomic containers only whitelist 'widget', not the
  // native atomic type names.
  const rootType = root.widgetType || root.elType;
  if (!isContainerModel(root) && targetType && rootType && rootType !== targetType) {
    if (root.widgetType) {
      root.widgetType = targetType;
    } else {
      root.elType = targetType;
    }
  }

  // Neutralise the preset's card padding when the applied root is a slide/loop
  // item — the layout owns its width, so its own padding would leak a sliver of
  // the next slide (the reported "right next slide 10% dekha jay").
  const effectiveRootType = root.widgetType || root.elType;
  if (ZERO_PADDING_ROOT_TYPES.includes(effectiveRootType)) {
    stripRootPadding(root);
  }

  const models = isContainerModel(root)
    ? Array.isArray(root.elements)
      ? root.elements
      : []
    : [root];

  if (!models.length) {
    return null;
  }

  const elementsToCreate = models.map((child, i) => {
    const model = JSON.parse(JSON.stringify(child));
    delete model.id;
    regenerateModelStyleIds(model);
    sanitizeImageSrc(model);
    return {
      container: parent,
      model,
      options: { at: index + i, clone: true },
    };
  });

  const result = createElements({
    title: meta.title || 'Preset',
    subtitle: meta.subtitle || 'Applied preset',
    elements: elementsToCreate,
  });

  const firstNewId =
    result && Array.isArray(result.createdElements) && result.createdElements[0]
      ? result.createdElements[0].containerId
      : null;

  if (result && Array.isArray(result.createdElements)) {
    stampContainerClassesIntoPreview(result.createdElements);
    syncAaeInteractionsToPreview(result.createdElements);
  }

  removeElements({
    elementIds: [elementId],
    title: meta.title || 'Preset',
    subtitle: 'Replaced element',
  });

  if (firstNewId) {
    try {
      selectElement(firstNewId);
    } catch (_) {
      /* selection is best-effort */
    }
  }

  return firstNewId;
}
