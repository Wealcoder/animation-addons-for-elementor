import {
  allExtensionFn,
  generalExtensionFn,
  generalGroupExtensionFn,
  gsapAllExtensionFn,
  gsapExtensionFn,
  gsapGroupExtensionFn,
} from "@/lib/extensionService";
import {
  activeFullWidgetFn,
  activeGroupWidgetFn,
  activeWidgetFn,
} from "@/lib/widgetService";
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
      activeWidgetFn(mainState.allWidgets, data, dispatch);
    },
    [mainState.allWidgets]
  );

  const updateActiveGroupWidget = useCallback(
    (data) => {
      activeGroupWidgetFn(mainState.allWidgets, data, dispatch);
    },
    [mainState.allWidgets]
  );

  const updateActiveFullWidget = useCallback(
    (data) => {
      activeFullWidgetFn(mainState.allWidgets, data, dispatch);
    },
    [mainState.allWidgets]
  );

  const updateActiveGeneralExtension = useCallback(
    (data) => {
      generalExtensionFn(mainState.allExtensions, data, dispatch);
    },
    [mainState.allExtensions]
  );

  const updateActiveGeneralGroupExtension = useCallback(
    (data) => {
      generalGroupExtensionFn(mainState.allExtensions, data, dispatch);
    },
    [mainState.allExtensions]
  );

  const updateActiveGsapExtension = useCallback(
    (data) => {
      gsapExtensionFn(mainState.allExtensions, data, dispatch);
    },
    [mainState.allExtensions]
  );

  const updateActiveGsapGroupExtension = useCallback(
    (data) => {
      gsapGroupExtensionFn(mainState.allExtensions, data, dispatch);
    },
    [mainState.allExtensions]
  );

  const updateActiveGsapAllExtension = useCallback(
    (data) => {
      gsapAllExtensionFn(mainState.allExtensions, data, dispatch);
    },
    [mainState.allExtensions]
  );

  const updateActiveFullExtension = useCallback(
    (data) => {
      allExtensionFn(mainState.allExtensions, data, dispatch);
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
    updateActiveGeneralGroupExtension,
    updateActiveGsapExtension,
    updateActiveGsapGroupExtension,
    updateActiveGsapAllExtension,
    updateActiveFullExtension,
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
