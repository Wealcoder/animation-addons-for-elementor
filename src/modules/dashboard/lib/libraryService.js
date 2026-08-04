const isValid = WCF_ADDONS_ADMIN.addons_config.wcf_valid;

export const libraryFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).map(([key2, value2]) => {
          if (key2 === data.slug) {
            if (value2.is_pro && !isValid) {
              return [key2, value2];
            } else {
              value2.is_active = data.value;
              if (!data.value) {
                value.is_active = data.value;
              }
              return [key2, value2];
            }
          } else {
            return [key2, value2];
          }
        })
      );
      return [key, { ...value, elements: filteredElements }];
    })
  );

  dispatch({
    type: "setLibrary",
    value: {
      ...mainContent,
      elements: result,
    },
  });
};

/**
 * Write one library's display-condition rules into the blob.
 *
 * Returns the NEXT state as well as dispatching it: the caller saves the blob
 * to the server in the same gesture (the gear dialog's Apply), and reading
 * state straight after a dispatch would hand it the stale copy.
 *
 * Conditions are a plain extra key on the element. Old plugin versions ignore
 * it, and an element without it means "every page" — so an install that never
 * opens the dialog keeps exactly the behaviour it always had.
 */
export const libraryConditionsFn = (mainContent, data, dispatch) => {
  const next = {
    ...mainContent,
    elements: Object.fromEntries(
      Object.entries(mainContent.elements).map(([key, group]) => [
        key,
        {
          ...group,
          elements: Object.fromEntries(
            Object.entries(group.elements || {}).map(([slug, item]) =>
              slug === data.slug
                ? [slug, { ...item, conditions: data.conditions }]
                : [slug, item]
            )
          ),
        },
      ])
    ),
  };

  dispatch({ type: "setLibrary", value: next });

  return next;
};

export const activeGroupLibraryFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (key === data.slug) {
            if (value2.is_pro && !isValid) {
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

  dispatch({
    type: "setLibrary",
    value: {
      ...mainContent,
      elements: result,
    },
  });
};
