/* eslint-env browser */

/**
 * PresetPickerControl — the "Presets" element-control for AAE atomic widgets.
 *
 * Registered into Elementor's shared controlsRegistry under the type id
 * 'aae-preset-picker' (see ./index.js) and rendered by the editing panel
 * wherever the PHP side places an AAE_A_Preset_Picker_Control.
 *
 * This is an ACTION control, not a prop-bound control: it carries no stored
 * value. It lists the bundled presets for the selected element's type and, on
 * choosing one, REPLACES the selected element in place with the preset's design
 * (settings + styles + children). Presets are authored as Elementor exports;
 * a preset whose root is a flex/container is UNWRAPPED — its children are placed
 * at the selected element's position, the wrapper dropped.
 *
 * Presets are provided by PHP as window.AAE_WIDGET_PRESETS, keyed by element
 * type (e.g. 'e-aae-a-advanced-heading'). See class-atomic.php::get_widget_presets().
 *
 * Uses the proper @elementor/editor-elements helpers (createElements /
 * removeElements / getContainer) rather than raw $e.run, matching SlidesControl.
 */

import * as React from "react";
import {
  createElements,
  getContainer,
  removeElements,
  selectElement,
} from "@elementor/editor-elements";
import { useElement } from "@elementor/editor-editing-panel";
import { Stack, Typography, MenuItem, Select } from "@elementor/ui";

// Container element types whose wrapper is unwrapped on apply.
const CONTAINER_TYPES = ["e-flexbox", "e-div-block", "e-grid", "container"];

// Some element types share another type's preset library. The Loop Grid Slider's
// slide item (`e-aae-a-loop-slide-item`) is a subclass of the Loop Grid item
// (`e-aae-a-loop-item`) with the same authored-card shape, so it reuses the Loop
// Grid presets. Presets are keyed by the type detected inside each preset model
// (always `e-aae-a-loop-item` here), so without this alias the slide item's
// picker is empty. On apply we rewrite the created root's type to the selected
// element's own type (see applyPreset) so a slide stays a slide.
const PRESET_TYPE_ALIASES = {
  "e-aae-a-loop-slide-item": "e-aae-a-loop-item",
};

function getPresetsForType(type) {
  const all = window.AAE_WIDGET_PRESETS;
  if (!all || typeof all !== "object") {
    return [];
  }
  const key = all[type] ? type : PRESET_TYPE_ALIASES[type] || type;
  const list = all[key];
  return Array.isArray(list) ? list : [];
}

function isContainerModel(model) {
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
 *
 * Generic deep walk so it covers image-src anywhere — background overlays,
 * settings, nested children, any depth.
 */
function sanitizeImageSrc(node) {
  if (Array.isArray(node)) {
    node.forEach(sanitizeImageSrc);
    return;
  }
  if (!node || typeof node !== "object") {
    return;
  }

  if (node.$$type === "image-src" && node.value && typeof node.value === "object") {
    const src = node.value;
    const hasId = src.id && src.id.value !== undefined && src.id.value !== null && src.id.value !== "";
    const hasUrl =
      src.url &&
      (typeof src.url.value === "string"
        ? src.url.value !== ""
        : src.url.value !== undefined && src.url.value !== null);
    // XOR: keep id, drop url when both are present.
    if (hasId && hasUrl) {
      delete src.url;
    }
  }

  Object.keys(node).forEach((key) => {
    const child = node[key];
    if (child && typeof child === "object") {
      sanitizeImageSrc(child);
    }
  });
}

/**
 * Generate a fresh, collision-resistant local style id.
 * Mirrors Elementor's getRandomStyleId shape: e-<rand>-<rand>.
 */
function randomStyleId() {
  const rand = () => Math.random().toString(36).slice(2, 9);
  return `e-${rand()}-${rand()}`;
}

/**
 * Recursively regenerate every element's LOCAL style ids in a preset model and
 * rewrite the matching `classes` references, so applying the same styled preset
 * to multiple widgets never shares (collides on) style-id classes.
 *
 * Elementor only auto-regenerates style ids on paste/import/duplicate hooks —
 * not on `create` — so we do it here before createElements(). Mirrors
 * modules/atomic-widgets/.../regenerate-local-style-ids.js.
 */
function regenerateModelStyleIds(model) {
  if (!model || typeof model !== "object") {
    return model;
  }

  const styles = model.styles;
  if (styles && typeof styles === "object" && !Array.isArray(styles)) {
    const changed = {};
    const newStyles = {};

    Object.keys(styles).forEach((oldId) => {
      const newId = randomStyleId();
      changed[oldId] = newId;
      newStyles[newId] = { ...styles[oldId], id: newId };
    });

    model.styles = newStyles;

    // Rewrite settings.classes that referenced the old ids.
    const classesProp = model.settings && model.settings.classes;
    if (
      classesProp &&
      classesProp.$$type === "classes" &&
      Array.isArray(classesProp.value)
    ) {
      classesProp.value = classesProp.value.map((cls) => changed[cls] || cls);
    }
  }

  if (Array.isArray(model.elements)) {
    model.elements.forEach(regenerateModelStyleIds);
  }

  return model;
}

/**
 * Resolve the selected element's parent container + its index within it, using
 * the V1 container model (not the DOM), matching how SlidesControl works.
 */
function getParentAndIndex(elementId) {
  const container = getContainer(elementId);
  const parent = container?.parent || null;
  if (!parent) {
    return { parent: null, index: 0 };
  }

  let index = 0;
  const children = parent.model?.get?.("elements");
  if (children?.each) {
    let i = 0;
    children.each((child) => {
      if (child.get("id") === elementId) {
        index = i;
      }
      i += 1;
    });
  }
  return { parent, index };
}

export function PresetPickerControl({ label }) {
  const { element } = useElement();
  const elementId = element.id;
  const type = element.type || element.model?.get?.("elType");

  const presets = getPresetsForType(type);

  const applyPreset = (preset) => {
    if (!preset?.model) {
      return;
    }

    const { parent, index } = getParentAndIndex(elementId);
    if (!parent) {
      return;
    }

    // Unwrap: a flex/container preset applies its children; otherwise itself.
    const root = JSON.parse(JSON.stringify(preset.model));

    // Type alias (e.g. the slider slide item reusing Loop Grid presets): the
    // preset root is `e-aae-a-loop-item`, but this element is an
    // `e-aae-a-loop-slide-item` living in a slide track. Rewrite the root's type
    // to the selected element's own type so the created element is a valid slide
    // (right Twig/class), not a grid item the track won't lay out. Only when the
    // root is NOT a container (containers get unwrapped, so their type is dropped).
    if (!isContainerModel(root) && type && root.elType && root.elType !== type) {
      const rootType = root.widgetType || root.elType;
      if (rootType !== type) {
        // Atomic elements report their type via elType; keep widgetType in sync
        // if the model used it.
        if (root.widgetType) {
          root.widgetType = type;
        }
        root.elType = type;
      }
    }

    const models = isContainerModel(root)
      ? Array.isArray(root.elements)
        ? root.elements
        : []
      : [root];

    if (!models.length) {
      return;
    }

    // Regenerate local style ids so applying the same styled preset multiple
    // times never shares (collides on) style-id classes. `create` does NOT
    // auto-regenerate style ids (only paste/import/duplicate hooks do), so we
    // do it explicitly here. Element ids are intentionally NOT pre-assigned:
    // Elementor's create command ignores any model.id and mints its own, so we
    // capture the real ids from the command result instead (see below).
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

    // createElements() executes immediately and returns { createdElements: [...] },
    // each entry carrying the REAL containerId Elementor assigned. We need the
    // first one so we can re-select it after removing the original — otherwise
    // the panel goes blank (nothing selected) once the original is gone.
    const result = createElements({
      title: "Preset",
      subtitle: `Applied "${preset.name}"`,
      elements: elementsToCreate,
    });

    const firstNewId =
      result &&
      Array.isArray(result.createdElements) &&
      result.createdElements[0]
        ? result.createdElements[0].containerId
        : null;

    // Replace in place: remove the original after the preset children are added.
    removeElements({
      elementIds: [elementId],
      title: "Preset",
      subtitle: "Replaced element",
    });

    // Re-select the first newly created element so the editing panel stays on a
    // valid target instead of going blank after the original is removed.
    if (firstNewId) {
      try {
        selectElement(firstNewId);
      } catch (_) {
        /* selection is best-effort; ignore if the container isn't ready */
      }
    }
  };

  if (!presets.length) {
    return null;
  }

  return (
    <Stack gap={1}>
      <Typography
        variant="caption"
        sx={{ fontWeight: 500, color: "text.secondary" }}
      >
        {label || "Presets"}
      </Typography>
      <Select
        size="tiny"
        displayEmpty
        value=""
        onChange={(e) => {
          const preset = presets.find((p) => p.id === e.target.value);
          if (preset) {
            applyPreset(preset);
          }
        }}
      >
        <MenuItem value="" disabled>
          {"Apply a preset…"}
        </MenuItem>
        {presets.map((p) => (
          <MenuItem key={p.id} value={p.id}>
            {p.name}
          </MenuItem>
        ))}
      </Select>
    </Stack>
  );
}
