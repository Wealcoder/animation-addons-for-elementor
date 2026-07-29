/* eslint-env browser */

/**
 * Default-children models + "Reset to Default" behaviour for preset-capable
 * elements.
 *
 * "Reset to Default" rebuilds the element's CHILDREN back to the plain authored
 * default (what you get on a fresh drop) — a Post Image + a Post Title — stripping
 * whatever preset styling/markup is currently inside. It does NOT touch the
 * element itself (its type, its own styles), only replaces its subtree.
 *
 * The models below are minimal atomic-element shapes: type only, no styles, no
 * settings. Elementor fills defaults and mints ids on create. Keep them in sync
 * with the PHP `define_default_children()` of the Loop Grid / Slider item.
 */

import { createElements, getContainer, removeElements } from '@elementor/editor-elements';

// Widget types whose "Reset to Default" is supported, mapped to the child models
// to rebuild. Both the Loop Grid item and the Loop Grid Slider slide item default
// to Post Image + Post Title.
//
// Atomic widgets are modelled as elType:'widget' + widgetType — NOT
// elType:'<type>'. Using the type as elType makes create silently no-op (verified
// by dumping a real default child's model). Keep this shape in sync with how
// Elementor stores these children.
const POST_CARD_CHILDREN = [
  {
    elType: 'widget',
    widgetType: 'e-aae-a-post-image',
    settings: {},
    elements: [],
    editor_settings: { title: 'Post Image' },
  },
  {
    elType: 'widget',
    widgetType: 'e-aae-a-post-title',
    settings: {},
    elements: [],
    editor_settings: { title: 'Post Title' },
  },
];

export const DEFAULT_CHILD_MODELS = {
  'e-aae-a-loop-item': POST_CARD_CHILDREN,
  'e-aae-a-loop-slide-item': POST_CARD_CHILDREN,
};

/**
 * Reset an element to its default children: remove every current child, then
 * create the default child models inside it. Leaves the element itself intact.
 * No-op if the type has no registered defaults.
 */
export function applyResetToDefault(elementId, type) {
  const defaults = DEFAULT_CHILD_MODELS[type];
  if (!defaults) {
    return;
  }

  const container = getContainer(elementId);
  if (!container) {
    return;
  }

  // Collect the current (preset) child ids.
  const oldChildIds = [];
  const children = container.model?.get?.('elements');
  if (children?.each) {
    children.each((child) => {
      const id = child.get('id');
      if (id) {
        oldChildIds.push(id);
      }
    });
  }

  const createDefaults = () => {
    // Re-resolve the container — the prior reference can go stale once its
    // children collection is mutated, and creating against a stale container
    // silently no-ops.
    const freshContainer = getContainer(elementId);
    if (!freshContainer) {
      return;
    }
    const elementsToCreate = defaults.map((model, i) => ({
      container: freshContainer,
      model: JSON.parse(JSON.stringify(model)),
      options: { at: i, clone: true },
    }));
    createElements({
      title: 'Preset',
      subtitle: 'Reset to default',
      elements: elementsToCreate,
    });
  };

  // Remove the preset children FIRST so the element is empty, then create the
  // fresh defaults inside it. (Creating first left preset nodes behind — indices
  // shifted as defaults were inserted, so clearing first is reliable.) The create
  // is deferred a tick so Elementor finishes committing the removal + settles the
  // container's children collection before we build into it.
  if (oldChildIds.length) {
    removeElements({
      elementIds: oldChildIds,
      title: 'Preset',
      subtitle: 'Cleared preset styles',
    });
    window.requestAnimationFrame(() => window.requestAnimationFrame(createDefaults));
  } else {
    createDefaults();
  }
}
