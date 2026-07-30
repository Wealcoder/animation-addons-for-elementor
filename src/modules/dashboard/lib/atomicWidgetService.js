const isValid = WCF_ADDONS_ADMIN.addons_config.wcf_valid;
const isOnlyPro =
  WCF_ADDONS_ADMIN.addons_config?.product_status?.item_id === 13;

const CATEGORY_LABELS = {
  general: "General Widgets",
  form: "Form Widgets",
  dynamic: "Dynamic Widgets",
  // 'single' and 'archive' were separate tabs; they are now one 'blog'
  // category in class-atomic.php. Both old keys stay labelled so a widget
  // still carrying one (a stale entry, or a Pro add-on registering against
  // the old slug) renders under a real heading instead of the raw slug.
  blog: "Blog Widgets",
  single: "Single Post Widgets",
  archive: "Archive Widgets",
  "header-footer": "Header & Footer Widgets",
  slider: "Slider Widgets",
  video: "Video Widgets",
  animation: "Animation Widgets",
  // Not used by any widget today — the extensions registry uses these two
  // categories, and both maps are keyed the same way, so keep them labelled
  // rather than falling through to the raw slug if a widget ever adopts one.
  interaction: "Interaction Widgets",
  utility: "Utility Widgets",
};

// Tab order in the dashboard. Registry insertion order is arbitrary (widgets
// are grouped by feature family in class-atomic.php, not by category), so the
// tabs would otherwise reshuffle whenever a widget is added. Any category not
// listed here falls to the end, alphabetically.
const CATEGORY_ORDER = [
  "general",
  "interaction",
  "form",
  "dynamic",
  "blog",
  "header-footer",
  "slider",
  "video",
  "animation",
  "utility",
];

// Belt-and-braces hide list. This used to mirror the `$internal_widgets`
// array in class-atomic.php::is_widget_active(); that array is gone — internal
// children now inherit their parent's state via WIDGET_PARENT_MAP, and
// get_dashboard_config() already drops every `is_internal` entry before the
// config reaches this file. So most slugs below are now filtered server-side
// too, and the only ones this list still decides are the ALWAYS_ACTIVE_WIDGETS
// (Post Title / Post Image), which ARE dashboard-visible but are force-active
// because they double as seeded Loop Grid children — a switch for them would
// do nothing. Safe to prune, but harmless: hiding an already-hidden slug is a
// no-op, and flattenAtomicWidgets() preserves the saved state of anything
// hidden here.
const INTERNAL_WIDGET_SLUGS = [
  "aae-a-slide",
  "aae-a-slider-track",
  "aae-a-slider-nav-prev",
  "aae-a-slider-nav-next",
  "aae-a-slider-pagination",
  "aae-a-slider-dot",
  "aae-a-slider-indicators",
  "aae-a-slider-current",
  "aae-a-slider-total",
  "aae-a-slider-percentage",
  "aae-a-slider-progress",
  "aae-a-slider-counter",
  "aae-a-slider-divider",
  "aae-a-slider-progress-fill",
  "aae-a-counter-number",
  "aae-a-accordion-item",
  "aae-a-icon-list-item",
  "aae-a-countdown-unit",
  "aae-a-toggle-pane",
  "aae-a-video-mask-btn",
  "aae-a-flip-box-main-face",
  "aae-a-post-card",
  "aae-a-offcanvas-panel",
  "aae-a-timeline-item",
  "aae-a-timeline-main-item",
  "aae-a-social-share-main-item",
  "aae-a-social-share-item",
  "aae-a-nav-item",
  "aae-a-nav-sub-item",
  "aae-a-mobile-nav",
  "aae-a-loop-item",
  "aae-a-loop-layout",
  "aae-a-loop-pagination",
  "aae-a-loop-prev",
  "aae-a-loop-next",
  "aae-a-loop-numbers",
  "aae-a-loop-number",
  "aae-a-loop-loadmore",
  "aae-a-loop-arrow",
  "aae-a-loop-nav-wrap",
  "aae-a-loop-slide-track",
  "aae-a-loop-slide-item",
  "aae-a-loop-slide-pagination",
  "aae-a-post-image",
  "aae-a-post-title",
  "aae-a-form-label",
  "aae-a-form-input",
  "aae-a-form-textarea",
  "aae-a-form-checkbox",
  "aae-a-form-radio",
  "aae-a-form-select",
  "aae-a-form-submit",
  "aae-a-form-success-message",
  "aae-a-form-error-message",
  "aae-a-search-toggle",
  "aae-a-search-panel",
  "aae-a-search-field",
  "aae-a-search-input",
  "aae-a-search-filter-date",
  "aae-a-search-filter-category",
  "aae-a-search-submit",
  "aae-a-search-results",
];

// Demo-only widgets that aren't "-main" suffixed but are still superseded
// duplicates kept around for reference/demo purposes, not for real use:
// - `aae-a-button` (Widgets/Button) / `aae-a-button-pro` (Widgets/ButtonPro)
//   are the older pair superseded by `aae-a-btn` / `aae-a-btn-pro`
//   (Widgets/Btn, Widgets/BtnPro — the ones documented as the real Free/Pro
//   button widgets in CLAUDE.md).
const DEMO_ONLY_SLUGS = ["aae-a-button", "aae-a-button-pro"];

// "-main" suffixed widgets (e.g. `aae-a-image-compare-main`,
// `aae-a-social-share-main`) are demo-only duplicates of their non-"main"
// counterpart and are skipped from the dashboard list on purpose.
const isHiddenAtomicWidget = (slug) => {
  if (INTERNAL_WIDGET_SLUGS.includes(slug)) return true;
  if (/(^|-)main(-|$)/.test(slug)) return true;
  if (DEMO_ONLY_SLUGS.includes(slug)) return true;
  return false;
};

// The raw, unfiltered widget map exactly as `class-atomic.php::get_dashboard_config()`
// sends it — used to preserve the current saved state of widgets hidden from
// this list when saving (see flattenAtomicWidgets below).
const RAW_ATOMIC_WIDGETS =
  WCF_ADDONS_ADMIN?.addons_config?.atomic_widgets?.elements || {};

// Reshapes the flat `{ title, elements: { slug: def } }` the PHP Atomic
// registry sends into the same `{ elements: { category: { title, is_active,
// elements } } }` shape the V3 widgets list uses, so the existing
// Tabs/WidgetCard UI and active-toggle helpers below can be reused as-is.
export const groupAtomicWidgetsByCategory = (atomicConfig) => {
  const source = atomicConfig?.elements || {};
  const categories = {};

  Object.entries(source).forEach(([slug, def]) => {
    if (isHiddenAtomicWidget(slug)) return;

    const categoryKey = def.category || "general";
    if (!categories[categoryKey]) {
      categories[categoryKey] = {
        title: CATEGORY_LABELS[categoryKey] || categoryKey,
        is_active: true,
        elements: {},
      };
    }
    categories[categoryKey].elements[slug] = def;
  });

  Object.values(categories).forEach((category) => {
    category.is_active = Object.values(category.elements).every(
      (widget) => widget.is_active
    );
  });

  const rank = (key) => {
    const i = CATEGORY_ORDER.indexOf(key);
    return i === -1 ? CATEGORY_ORDER.length : i;
  };

  const ordered = Object.fromEntries(
    Object.entries(categories).sort(
      ([a], [b]) => rank(a) - rank(b) || a.localeCompare(b)
    )
  );

  return {
    title: atomicConfig?.title || "Atomic Widgets",
    elements: ordered,
  };
};

export const activeAtomicWidgetFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (key2 === data.slug) {
            value2.is_active = data.value;
            if (!data.value) {
              value.is_active = data.value;
            }
            return [key2, value2];
          } else {
            return [key2, value2];
          }
        })
      );

      return [key, { ...value, elements: filteredElements }];
    })
  );

  if (!data.value) {
    dispatch({
      type: "setAllAtomicWidgets",
      value: {
        ...mainContent,
        is_active: data.value,
        elements: result,
      },
    });
  } else {
    dispatch({
      type: "setAllAtomicWidgets",
      value: {
        ...mainContent,
        elements: result,
      },
    });
  }
};

export const activeAtomicGroupWidgetFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (key === data.slug) {
            if (value2.is_pro && (value2?.pro_only ?? false)) {
              if (isOnlyPro) {
                value2.is_active = data.value;
                return [key2, value2];
              } else {
                return [key2, value2];
              }
            } else if (value2.is_pro && !isValid) {
              return [key2, value2];
            } else {
              value2.is_active = data.value;
              return [key2, value2];
            }
          } else {
            return [key2, value2];
          }
        })
      );
      if (key === data.slug) {
        value.is_active = data.value;
      }
      return [key, { ...value, elements: filteredElements }];
    })
  );

  if (!data.value) {
    dispatch({
      type: "setAllAtomicWidgets",
      value: {
        ...mainContent,
        is_active: data.value,
        elements: result,
      },
    });
  } else {
    dispatch({
      type: "setAllAtomicWidgets",
      value: {
        ...mainContent,
        elements: result,
      },
    });
  }
};

export const activeAtomicFullWidgetFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (value2.is_pro && (value2?.pro_only ?? false)) {
            if (isOnlyPro) {
              value2.is_active = data.value;
              return [key2, value2];
            } else {
              return [key2, value2];
            }
          } else if (value2.is_pro && !isValid) {
            return [key2, value2];
          } else {
            value2.is_active = data.value;
            return [key2, value2];
          }
        })
      );
      value.is_active = data.value;
      return [key, { ...value, elements: filteredElements }];
    })
  );

  dispatch({
    type: "setAllAtomicWidgets",
    value: {
      is_active: data.value,
      elements: result,
    },
  });
};

// Total/active counts for the dashboard top bar (V3 gets these pre-computed
// from PHP via `WCF_ADDONS_ADMIN.widgets`; atomic has no such server-side
// pair, so derive it client-side from the already-filtered/grouped state).
export const countAtomicWidgets = (allAtomicWidgets) => {
  let total = 0;
  let active = 0;

  Object.values(allAtomicWidgets?.elements || {}).forEach((category) => {
    Object.values(category.elements || {}).forEach((widget) => {
      total += 1;
      if (widget.is_active) active += 1;
    });
  });

  return { total, active };
};

// Flattens the category-grouped state back to the `{ slug: true }` map the
// `aae_save_atomic_widgets` AJAX handler (class-atomic.php::ajax_save_settings)
// expects. That handler does a full `update_option()` replace, so the
// payload must include every widget slug it knows about — not just the
// ones this list shows — or hidden ("Main"/internal/demo) widgets would be
// silently switched off on save. Their current saved state (unreachable from
// this UI, so it can only ever be what the page loaded with) is merged in
// from the untouched dashboard config.
export const flattenAtomicWidgets = (allAtomicWidgets) => {
  const result = {};

  Object.entries(RAW_ATOMIC_WIDGETS).forEach(([slug, def]) => {
    if (isHiddenAtomicWidget(slug)) {
      result[slug] = !!def.is_active;
    }
  });

  Object.values(allAtomicWidgets?.elements || {}).forEach((category) => {
    Object.entries(category.elements || {}).forEach(([slug, widget]) => {
      result[slug] = !!widget.is_active;
    });
  });

  return result;
};
