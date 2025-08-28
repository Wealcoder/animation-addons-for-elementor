import { createContext, useCallback, useReducer } from "react";

const initialState = {
  tabKey: "",
  allPostTypes: [],
  allTaxonomy: [],
  currentPostTypes: {},
};

const reducer = (state, action) => {
  switch (action.type) {
    case "setTabKey":
      return { ...state, tabKey: action.value };
    case "setAllPostTypes":
      return { ...state, allPostTypes: action.value };
    case "setAllTaxonomy":
      return { ...state, allTaxonomy: action.value };
    case "setCurrentPostType":
      return { ...state, currentPostTypes: action.value };
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

  const setAllPostTypes = useCallback((data) => {
    dispatch({
      type: "setAllPostTypes",
      value: data,
    });
  }, []);

  const setAllTaxonomy = useCallback((data) => {
    dispatch({
      type: "setAllTaxonomy",
      value: data,
    });
  }, []);

  const setCurrentPostType = useCallback((data) => {
    dispatch({
      type: "setCurrentPostType",
      value: data,
    });
  }, []);

  const updateTabKey = useCallback(
    (data) => {
      setTabKey(data);
    },
    [mainState.tabKey]
  );

  return {
    mainState,
    setTabKey,
    setAllPostTypes,
    setAllTaxonomy,
    setCurrentPostType,
    updateTabKey,
  };
};

export const AppContext = createContext({
  mainState: initialState,
  setTabKey: () => {},
  setAllPostTypes: () => {},
  setAllTaxonomy: () => {},
  setCurrentPostType: () => {},
});

export const AppContextProvider = ({ children }) => {
  return (
    <AppContext.Provider value={useMainContext(initialState)}>
      {children}
    </AppContext.Provider>
  );
};
