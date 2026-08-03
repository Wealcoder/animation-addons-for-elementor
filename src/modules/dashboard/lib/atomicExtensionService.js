const isValid = WCF_ADDONS_ADMIN.addons_config.wcf_valid;
const isOnlyPro =
  WCF_ADDONS_ADMIN.addons_config?.product_status?.item_id === 13;

const CATEGORY_LABELS = {
  animation: "Animation Extensions",
  interaction: "Interaction Extensions",
  form: "Form Extensions",
  utility: "Utility Extensions",
};

// Tab order in the dashboard. Group order otherwise follows registry insertion
// order in class-atomic.php, which is grouped by feature family rather than by
// category — so 'form' would land after 'utility' purely because the form
// extensions happen to be declared last. Any category not listed here falls to
// the end, alphabetically. Mirrors CATEGORY_ORDER in atomicWidgetService.js.
const CATEGORY_ORDER = ["animation", "interaction", "form", "utility"];

// Reshapes the flat `{ title, elements: { slug: def } }` the PHP Atomic
// registry sends (class-atomic.php::get_dashboard_config()['atomic_extensions'])
// into the same `{ elements: { category: { title, is_active, elements } } }`
// shape the atomic widgets list uses, so the existing Tabs/WidgetCard UI and
// active-toggle helpers below can be reused as-is.
export const groupAtomicExtensionsByCategory = (atomicConfig) => {
  const source = atomicConfig?.elements || {};
  const categories = {};

  Object.entries(source).forEach(([slug, def]) => {
    const categoryKey = def.category || "animation";
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
      (extension) => extension.is_active
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
    title: atomicConfig?.title || "Atomic Extensions",
    elements: ordered,
  };
};

export const activeAtomicExtensionFn = (mainContent, data, dispatch) => {
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
      type: "setAllAtomicExtensions",
      value: {
        ...mainContent,
        is_active: data.value,
        elements: result,
      },
    });
  } else {
    dispatch({
      type: "setAllAtomicExtensions",
      value: {
        ...mainContent,
        elements: result,
      },
    });
  }
};

export const activeAtomicGroupExtensionFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    // `badge_only` = show the PRO chip, do NOT lock the switch.
    //
    // is_pro alone means "paid AND unusable without a licence", so this file
    // refuses to write is_active for it. That is right for a feature whose code
    // is absent without Pro, and wrong for the atomic extensions: their Schema
    // and Controls ship in THIS plugin, so the Elementor panel section has to
    // keep working on a free site — locking the switch would strand anyone who
    // ever turned one off. Same for the moved widgets, which the free plugin
    // still registers while Pro is unlicensed.
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
            } else if (value2.is_pro && !value2.badge_only && !isValid) {
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
      type: "setAllAtomicExtensions",
      value: {
        ...mainContent,
        is_active: data.value,
        elements: result,
      },
    });
  } else {
    dispatch({
      type: "setAllAtomicExtensions",
      value: {
        ...mainContent,
        elements: result,
      },
    });
  }
};

export const activeAtomicFullExtensionFn = (mainContent, data, dispatch) => {
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
          } else if (value2.is_pro && !value2.badge_only && !isValid) {
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
    type: "setAllAtomicExtensions",
    value: {
      is_active: data.value,
      elements: result,
    },
  });
};

// Total/active counts for the dashboard top bar — atomic extensions have no
// server-side pre-computed pair (unlike V3's `WCF_ADDONS_ADMIN.extensions`),
// so derive it client-side from the already-filtered/grouped state.
export const countAtomicExtensions = (allAtomicExtensions) => {
  let total = 0;
  let active = 0;

  Object.values(allAtomicExtensions?.elements || {}).forEach((category) => {
    Object.values(category.elements || {}).forEach((extension) => {
      total += 1;
      if (extension.is_active) active += 1;
    });
  });

  return { total, active };
};

// Flattens the category-grouped state back to the `{ slug: true }` map the
// `aae_save_atomic_extensions` AJAX handler
// (class-atomic.php::ajax_save_extension_settings) expects. Unlike the
// atomic widgets list, every extension in the registry is shown on this
// dashboard page (none are hidden as internal/demo-only), so there is no
// hidden-slug state to merge back in.
export const flattenAtomicExtensions = (allAtomicExtensions) => {
  const result = {};

  Object.values(allAtomicExtensions?.elements || {}).forEach((category) => {
    Object.entries(category.elements || {}).forEach(([slug, extension]) => {
      result[slug] = !!extension.is_active;
    });
  });

  return result;
};
