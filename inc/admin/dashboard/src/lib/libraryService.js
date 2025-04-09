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
