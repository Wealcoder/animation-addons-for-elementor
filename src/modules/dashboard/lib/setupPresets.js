const isValid = WCF_ADDONS_ADMIN.addons_config.wcf_valid;
const isOnlyPro =
  WCF_ADDONS_ADMIN.addons_config?.product_status?.item_id === 13;

// Categories a "Basic" setup never pre-selects. Animation effects are the
// plugin's premium surface — every animation extension in the registry today
// is already PRO, so this list changes nothing right now. It exists so that a
// FREE animation widget or extension added later still stays out of the
// recommended set instead of silently joining it.
const BASIC_EXCLUDED_CATEGORIES = ["animation"];

/**
 * Should a "Basic" setup switch this item on?
 *
 * Free, and not an animation effect. A PRO badge is a promise to the user, so
 * a `badge_only` item — Pro-badged but shipping free code — deliberately stays
 * OFF here even though it would work: pre-enabling it would make the badge
 * read as a lie and quietly give away the upsell.
 */
const isBasicItem = (def, categoryKey) =>
  !def.is_pro && !BASIC_EXCLUDED_CATEGORIES.includes(categoryKey);

/**
 * Should a "Custom" setup switch this item on?
 *
 * Everything the site can actually register. The three branches mirror
 * activeAtomicFullWidgetFn / activeAtomicFullExtensionFn exactly — change one,
 * change all three — because enabling an item whose class does not exist would
 * paint an "on" card for something that can never render, and the dashboard
 * computes its cards from the raw saved option rather than from
 * `is_widget_active()`.
 */
const isCustomItem = (def) => {
  if (def.is_pro && (def?.pro_only ?? false)) return isOnlyPro;
  if (def.is_pro && !def.badge_only && !isValid) return false;
  return true;
};

/**
 * Seeds a wizard step's selection from the setup type chosen on step 1.
 *
 * Shared by the atomic widget and atomic extension steps: both are grouped
 * into the same `{ elements: { category: { title, is_active, elements } } }`
 * shape, and the rule must not be able to drift between them.
 *
 * Returns a NEW object rather than mutating in place. The V3 twin
 * (activeFullSetupWidgetFn) writes `is_active` straight onto the config
 * objects and relies on the wizard footer reading those same references out of
 * context — which works, but only by accident of nothing cloning in between.
 *
 * @param {Object} grouped   category-grouped widgets or extensions
 * @param {string} setupType "basic" | "advance" (the Custom card's value)
 */
export const applyWizardSetup = (grouped, setupType) => {
  const custom = setupType === "advance";

  const elements = Object.fromEntries(
    Object.entries(grouped?.elements || {}).map(([categoryKey, category]) => {
      const items = Object.fromEntries(
        Object.entries(category.elements || {}).map(([slug, def]) => [
          slug,
          {
            ...def,
            is_active: custom
              ? isCustomItem(def)
              : isBasicItem(def, categoryKey),
          },
        ])
      );

      const all = Object.values(items);

      return [
        categoryKey,
        {
          ...category,
          // "Enable All" reflects the group, so it can only be on when every
          // member is. An empty category must never read as fully enabled.
          is_active: all.length > 0 && all.every((item) => item.is_active),
          elements: items,
        },
      ];
    })
  );

  return { ...grouped, elements };
};
