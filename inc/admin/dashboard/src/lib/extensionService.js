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
