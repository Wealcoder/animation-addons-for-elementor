import { createContext, useCallback, useReducer } from "react";

const initialState = {
  allWidgets: WCF_ADDONS_ADMIN?.addons_config?.widgets || {},
  allExtensions: WCF_ADDONS_ADMIN?.addons_config?.extensions || {},
};

const reducer = (state, action) => {
  switch (action.type) {
    case "setAllWidgets":
      return { ...state, allWidgets: action.value };
    case "setAllExtensions":
      return { ...state, allExtensions: action.value };
    default:
      throw new Error();
  }
};

const useMainContext = (state) => {
  const [mainState, dispatch] = useReducer(reducer, state);

  const setAllWidgets = useCallback((data) => {
    dispatch({
      type: "setAllWidgets",
      value: data,
    });
  }, []);
  const setAllExtensions = useCallback((data) => {
    dispatch({
      type: "setAllExtensions",
      value: data,
    });
  }, []);

  const updateActiveWidget = useCallback(
    (data) => {
      const result = Object.fromEntries(
        Object.entries(mainState.allWidgets.elements).map(([key, value]) => {
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
          ...mainState.allWidgets,
          elements: result,
        },
      });
    },
    [mainState.allWidgets]
  );

  const updateActiveGroupWidget = useCallback(
    (data) => {
      const result = Object.fromEntries(
        Object.entries(mainState.allWidgets.elements).map(([key, value]) => {
          const filteredElements = Object.fromEntries(
            Object.entries(value.elements || {}).filter(([key2, value2]) => {
              if (key === data.slug) {
                if (value2.is_pro) {
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
          ...mainState.allWidgets,
          elements: result,
        },
      });
    },
    [mainState.allWidgets]
  );

  const updateActiveFullWidget = useCallback(
    (data) => {
      const result = Object.fromEntries(
        Object.entries(mainState.allWidgets.elements).map(([key, value]) => {
          const filteredElements = Object.fromEntries(
            Object.entries(value.elements || {}).filter(([key2, value2]) => {
              if (value2.is_pro) {
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
    },
    [mainState.allWidgets]
  );

  const updateActiveGeneralExtension = useCallback(
    (data) => {
      const result = Object.fromEntries(
        Object.entries(
          mainState.allExtensions.elements["general-extensions"].elements
        ).map(([key, value]) => {
          if (key === data.slug) {
            value.is_active = data.value;
            return [key, value];
          } else {
            return [key, value];
          }
        })
      );

      dispatch({
        type: "setAllExtensions",
        value: {
          ...mainState.allExtensions,
          elements: {
            ...mainState.allExtensions.elements,
            "general-extensions": {
              ...mainState.allExtensions.elements["general-extensions"],
              elements: result,
            },
          },
        },
      });
    },
    [mainState.allExtensions]
  );

  return {
    mainState,
    setAllWidgets,
    setAllExtensions,
    updateActiveWidget,
    updateActiveGroupWidget,
    updateActiveFullWidget,
    updateActiveGeneralExtension,
  };
};

export const AppContext = createContext({
  mainState: initialState,
  setAllWidgets: () => {},
  setAllExtensions: () => {},
  updateActiveWidget: () => {},
});

export const AppContextProvider = ({ children }) => {
  return (
    <AppContext.Provider value={useMainContext(initialState)}>
      {children}
    </AppContext.Provider>
  );
};
