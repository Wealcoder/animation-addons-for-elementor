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

/** Presets bundled for a given element type, honouring the alias table. */
export function getPresetsForType(type) {
  const all = window.AAE_WIDGET_PRESETS;
  if (!all || typeof all !== 'object') {
    return [];
  }
  const key = all[type] ? type : PRESET_TYPE_ALIASES[type] || type;
  const list = all[key];
  return Array.isArray(list) ? list : [];
}

export function isContainerModel(model) {
  const type = model.widgetType || model.elType;
  return CONTAINER_TYPES.indexOf(type) !== -1;
}

/**
 * Sanitize a preset model so it passes Elementor's save-time style validation.
 *
 * Elementor's Image_Src prop enforces an XOR rule: an image source may carry an
 * attachment `id` OR a `url`, but NOT both. Native exports routinely include
 * both (id + cached url), which renders fine in the editor but is REJECTED on
 * publish with "...background: invalid_value". We walk the whole model and, for
 * every image-src that has both, drop the `url` (the attachment id is the source
 * of truth; WP regenerates the url from it).
 */
export function sanitizeImageSrc(node) {
  if (Array.isArray(node)) {
    node.forEach(sanitizeImageSrc);
    return;
  }
  if (!node || typeof node !== 'object') {
    return;
  }

  if (node.$$type === 'image-src' && node.value && typeof node.value === 'object') {
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

/**
 * Recursively regenerate every element's LOCAL style ids in a preset model and
 * rewrite the matching `classes` references, so applying the same styled preset
 * to multiple widgets never shares (collides on) style-id classes. `create` does
 * NOT auto-regenerate style ids (only paste/import/duplicate hooks do), so we do
 * it here before createElements().
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
  // element is valid where it lands (right Twig/class).
  if (!isContainerModel(root) && targetType && root.elType && root.elType !== targetType) {
    if (root.widgetType) {
      root.widgetType = targetType;
    }
    root.elType = targetType;
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
