const isValid = WCF_ADDONS_ADMIN.addons_config.wcf_valid;

export const activeWidgetFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (key2 === data.slug) {
            value2.is_active = data.value;
            return [key2, value2];
          } else {
            return [key2, value2];
          }
        })
      );

      return [key, { ...value, elements: filteredElements }];
    })
  );

  dispatch({
    type: "setAllWidgets",
    value: {
      ...mainContent,
      elements: result,
    },
  });
};

export const activeGroupWidgetFn = (mainContent, data, dispatch) => {
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
    type: "setAllWidgets",
    value: {
      ...mainContent,
      elements: result,
    },
  });
};

export const activeFullWidgetFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (value2.is_pro && !isValid) {
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
    type: "setAllWidgets",
    value: {
      is_active: data.value,
      elements: result,
    },
  });
};

export const activeFullSetupWidgetFn = (mainContent, data) => {
  console.log(mainContent, data);
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (value2.is_pro && !isValid) {
            return [key2, value2];
          } else {
            if (value2?.setup) {
              value2.is_active = value2.setup?.includes(data);
            } else {
              value2.is_active = false;
            }
            return [key2, value2];
          }
        })
      );
      value.is_active = false;
      return [key, { ...value, elements: filteredElements }];
    })
  );

  console.log(result);

  return result;
};
