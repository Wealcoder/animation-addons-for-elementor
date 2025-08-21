import { createContext, useCallback, useReducer } from "react";

const initialState = {
  tabKey: "",
  activated: WCF_ADDONS_ADMIN?.addons_config || {},
};

const reducer = (state, action) => {
  switch (action.type) {
    case "setTabKey":
      return { ...state, tabKey: action.value };
    default:
      throw new Error();
  }
};

const useMainContext = (state) => {
  const [mainState, dispatch] = useReducer(reducer, state);

  const setTabKey = useCallback((data) => {
    dispatch({
      type: "setTabKey",
      value: data,
    });
  }, []);

  return {
    mainState,
    setTabKey,
  };
};

export const AppContext = createContext({
  mainState: initialState,

  setTabKey: () => {},
});

export const AppContextProvider = ({ children }) => {
  return (
    <AppContext.Provider value={useMainContext(initialState)}>
      {children}
    </AppContext.Provider>
  );
};
