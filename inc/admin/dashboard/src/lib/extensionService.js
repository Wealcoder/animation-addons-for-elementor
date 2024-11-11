export const generalExtensionFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements["general-extensions"].elements).map(
      ([key, value]) => {
        if (key === data.slug) {
          value.is_active = data.value;
          return [key, value];
        } else {
          return [key, value];
        }
      }
    )
  );

  dispatch({
    type: "setAllExtensions",
    value: {
      ...mainContent,
      elements: {
        ...mainContent.elements,
        "general-extensions": {
          ...mainContent.elements["general-extensions"],
          elements: result,
        },
      },
    },
  });
};

export const generalGroupExtensionFn = (mainContent, data, dispatch) => {
  console.log(data);
  const result = Object.fromEntries(
    Object.entries(mainContent.elements["general-extensions"].elements).map(
      ([key, value]) => {
        if (value.is_pro) {
          return [key, value];
        } else {
          value.is_active = data.value;
          return [key, value];
        }
      }
    )
  );

  dispatch({
    type: "setAllExtensions",
    value: {
      ...mainContent,
      elements: {
        ...mainContent.elements,
        "general-extensions": {
          ...mainContent.elements["general-extensions"],
          is_active: data.value,
          elements: result,
        },
      },
    },
  });
};
