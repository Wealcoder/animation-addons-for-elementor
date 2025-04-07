const isValid = WCF_ADDONS_ADMIN.addons_config.wcf_valid;

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

  if (!data.value) {
    dispatch({
      type: "setAllExtensions",
      value: {
        ...mainContent,
        is_active: data.value,
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
  } else {
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
  }
};

export const generalGroupExtensionFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements["general-extensions"].elements).map(
      ([key, value]) => {
        if (value.is_pro && !isValid) {
          return [key, value];
        } else {
          value.is_active = data.value;
          return [key, value];
        }
      }
    )
  );

  if (!data.value) {
    dispatch({
      type: "setAllExtensions",
      value: {
        ...mainContent,
        is_active: data.value,
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
  } else {
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
  }
};

export const gsapExtensionFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements["gsap-extensions"].elements).map(
      ([key, value]) => {
        const filteredElements = Object.fromEntries(
          Object.entries(value.elements || {}).map(([key2, value2]) => {
            if (key2 === data.slug) {
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
        return [key, { ...value, elements: filteredElements }];
      }
    )
  );

  dispatch({
    type: "setAllExtensions",
    value: {
      ...mainContent,
      elements: {
        ...mainContent.elements,
        "gsap-extensions": {
          ...mainContent.elements["gsap-extensions"],
          elements: result,
        },
      },
    },
  });
};

export const gsapGroupExtensionFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements["gsap-extensions"].elements).map(
      ([key, value]) => {
        const filteredElements = Object.fromEntries(
          Object.entries(value.elements || {}).map(([key2, value2]) => {
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
      }
    )
  );

  if (!data.value) {
    dispatch({
      type: "setAllExtensions",
      value: {
        ...mainContent,
        is_active: data.value,
        elements: {
          ...mainContent.elements,
          "gsap-extensions": {
            ...mainContent.elements["gsap-extensions"],
            is_active: data.value,
            elements: {
              ...mainContent.elements["gsap-extensions"].elements,
              ...result,
            },
          },
        },
      },
    });
  } else {
    dispatch({
      type: "setAllExtensions",
      value: {
        ...mainContent,
        elements: {
          ...mainContent.elements,
          "gsap-extensions": {
            ...mainContent.elements["gsap-extensions"],
            elements: {
              ...mainContent.elements["gsap-extensions"].elements,
              ...result,
            },
          },
        },
      },
    });
  }
};

export const gsapAllExtensionFn = (mainContent, data, dispatch) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements["gsap-extensions"].elements).map(
      ([key, value]) => {
        const filteredElements = Object.fromEntries(
          Object.entries(value.elements || {}).map(([key2, value2]) => {
            if (value2.is_pro && !isValid) {
              return [key2, value2];
            } else {
              value2.is_active = data.value;
              return [key2, value2];
            }
          })
        );
        if (value?.elements && Object.keys(value.elements).length) {
          value.is_active = data.value;
        }
        return [key, { ...value, elements: filteredElements }];
      }
    )
  );

  if (!data.value) {
    dispatch({
      type: "setAllExtensions",
      value: {
        ...mainContent,
        is_active: data.value,
        elements: {
          ...mainContent.elements,
          "gsap-extensions": {
            ...mainContent.elements["gsap-extensions"],
            is_active: data.value,
            elements: {
              ...mainContent.elements["gsap-extensions"].elements,
              ...result,
            },
          },
        },
      },
    });
  } else {
    dispatch({
      type: "setAllExtensions",
      value: {
        ...mainContent,
        elements: {
          ...mainContent.elements,
          "gsap-extensions": {
            ...mainContent.elements["gsap-extensions"],
            is_active: data.value,
            elements: {
              ...mainContent.elements["gsap-extensions"].elements,
              ...result,
            },
          },
        },
      },
    });
  }
};

export const allExtensionFn = (mainContent, data, dispatch) => {
  const gsapResult = Object.fromEntries(
    Object.entries(mainContent.elements["gsap-extensions"].elements).map(
      ([key, value]) => {
        const filteredElements = Object.fromEntries(
          Object.entries(value.elements || {}).map(([key2, value2]) => {
            if (value2.is_pro && !isValid) {
              return [key2, value2];
            } else {
              value2.is_active = data.value;
              return [key2, value2];
            }
          })
        );
        if (value?.elements && Object.keys(value.elements).length) {
          value.is_active = data.value;
        }
        return [key, { ...value, elements: filteredElements }];
      }
    )
  );

  const generalResult = Object.fromEntries(
    Object.entries(mainContent.elements["general-extensions"].elements).map(
      ([key, value]) => {
        if (value.is_pro && !isValid) {
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
      is_active: data.value,
      elements: {
        ...mainContent.elements,
        "gsap-extensions": {
          ...mainContent.elements["gsap-extensions"],
          is_active: data.value,
          elements: {
            ...mainContent.elements["gsap-extensions"].elements,
            ...gsapResult,
          },
        },
        "general-extensions": {
          ...mainContent.elements["general-extensions"],
          is_active: data.value,
          elements: generalResult,
        },
      },
    },
  });
};

export const allSetupExtensionFn = (mainContent, data) => {
  const gsapResult = Object.fromEntries(
    Object.entries(mainContent.elements["gsap-extensions"].elements).map(
      ([key, value]) => {
        let isGroupActive = false;
        const filteredElements = Object.fromEntries(
          Object.entries(value.elements || {}).map(([key2, value2]) => {
            if (value2.is_pro && !isValid) {
              return [key2, value2];
            } else {
              const activeItem = value2.setup?.includes(data);
              if (activeItem) {
                isGroupActive = true;
              }
              value2.is_active = activeItem;
              return [key2, value2];
            }
          })
        );
        if (value?.elements && Object.keys(value.elements).length) {
          value.is_active = isGroupActive;
        }
        return [key, { ...value, elements: filteredElements }];
      }
    )
  );

  const generalResult = Object.fromEntries(
    Object.entries(mainContent.elements["general-extensions"].elements).map(
      ([key, value]) => {
        if (value.is_pro && !isValid) {
          return [key, value];
        } else {
          value.is_active = value.setup?.includes(data);
          return [key, value];
        }
      }
    )
  );

  return {
    gsapResult: {
      ...mainContent.elements["gsap-extensions"],
      is_active: false,
      elements: {
        ...mainContent.elements["gsap-extensions"].elements,
        ...gsapResult,
      },
    },
    generalResult: {
      ...mainContent.elements["general-extensions"],
      is_active: false,
      elements: generalResult,
    },
  };
};
