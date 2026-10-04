/**
 * The "Used" tab's dataset: the category tree with everything unused removed.
 *
 * @param {Object} catWidgets Category tree as the list screens already hold it.
 * @param {Object} usage      `{ slug: pages }` from the scan, or null.
 * @return {Object} Same shape, keeping only widgets on at least one page, and
 *                  dropping categories left with nothing.
 */
export const filterUsedWidgets = (catWidgets, usage) => {
  if (!usage) return {};

  return Object.fromEntries(
    Object.entries(catWidgets || {})
      .map(([category, value]) => [
        category,
        {
          ...value,
          elements: Object.fromEntries(
            Object.entries(value.elements || {}).filter(
              ([slug]) => (usage[slug] ?? 0) > 0
            )
          ),
        },
      ])
      // An empty category heading over an empty grid reads as a rendering bug.
      .filter(([, value]) => Object.keys(value.elements).length > 0)
  );
};

/** Does anything at all qualify? Drives the tab's empty state. */
export const hasUsedWidgets = (catWidgets, usage) =>
  Object.keys(filterUsedWidgets(catWidgets, usage)).length > 0;
