import { createContext, useCallback, useContext, useReducer } from "react";

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

  const updateActiveWidget = useCallback((data) => {
    const result = Object.fromEntries(
      Object.entries(mainState.allWidgets).map(([key, value]) => {
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
      value: result,
    });
  }, []);

  return {
    mainState,
    setAllWidgets,
    setAllExtensions,
    updateActiveWidget,
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
