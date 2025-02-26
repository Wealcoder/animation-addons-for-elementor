import {
  allExtensionFn,
  generalExtensionFn,
  generalGroupExtensionFn,
  gsapAllExtensionFn,
  gsapExtensionFn,
  gsapGroupExtensionFn,
} from "@/lib/extensionService";
import {
  disableAllWidget,
  disableGeneralExtension,
  disableGsapExtension,
} from "@/lib/utils";
import {
  activeFullWidgetFn,
  activeGroupWidgetFn,
  activeWidgetFn,
} from "@/lib/widgetService";
import { createContext, useCallback, useReducer } from "react";

const initialState = {
  allWidgets:
    JSON.parse(JSON.stringify(WCF_ADDONS_ADMIN?.addons_config?.widgets)) || {},
  allExtensions:
    JSON.parse(JSON.stringify(WCF_ADDONS_ADMIN?.addons_config?.extensions)) ||
    {},
  activated: WCF_ADDONS_ADMIN?.addons_config || {},
  setupType: "basic",
  notice: [],
  changelog: [],
  tabKey: "",
  isSkipTerms: false,
};

const reducer = (state, action) => {
  switch (action.type) {
    case "setAllWidgets":
      return { ...state, allWidgets: action.value };
    case "setAllExtensions":
      return { ...state, allExtensions: action.value };
    case "setActivated":
      return { ...state, activated: action.value };
    case "setSetupType":
      return { ...state, setupType: action.value };
    case "setNotice":
      return { ...state, notice: action.value };
    case "setChangelog":
      return { ...state, changelog: action.value };
    case "setTabKey":
      return { ...state, tabKey: action.value };
    case "setIsSkipTerms":
      return { ...state, isSkipTerms: action.value };

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

  const setActivated = useCallback((data) => {
    dispatch({
      type: "setActivated",
      value: data,
    });
  }, []);
  const setNotice = useCallback((data) => {
    dispatch({
      type: "setNotice",
      value: data,
    });
  }, []);
  const setChangelog = useCallback((data) => {
    dispatch({
      type: "setChangelog",
      value: data,
    });
  }, []);

  const setTabKey = useCallback((data) => {
    dispatch({
      type: "setTabKey",
      value: data,
    });
  }, []);

  const setIsSkipTerms = useCallback((data) => {
    dispatch({
      type: "setIsSkipTerms",
      value: data,
    });
  }, []);

  const setSetupType = useCallback((data) => {
    if (data && data === "advance") {
      // update widget state
      const widgetResult = disableAllWidget(mainState.allWidgets.elements);
      setAllWidgets({ ...mainState.allWidgets, elements: widgetResult });

      // update extension state
      const gsapResult = disableGsapExtension(
        mainState.allExtensions.elements["gsap-extensions"]
      );
      const generalResult = disableGeneralExtension(
        mainState.allExtensions.elements["general-extensions"]
      );

      setAllExtensions({
        ...mainState.allExtensions,
        elements: {
          "general-extensions": generalResult,
          "gsap-extensions": gsapResult,
        },
      });
    } else {
      // update widget state to default
      setAllWidgets(WCF_ADDONS_ADMIN?.addons_config?.widgets || {});

      // update extension state to default
      setAllExtensions(WCF_ADDONS_ADMIN?.addons_config?.extensions || {});
    }
    dispatch({
      type: "setSetupType",
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

  const updateNotice = useCallback(
    async (data) => {
      const result = mainState.notice;
      if (result.length >= 10) {
        result.pop();
      }
      result.unshift(data);

      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },

        body: new URLSearchParams({
          action: "wcf_dashboard_notice_store",
          notice: JSON.stringify(result),
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          setNotice(result);
        });
    },
    [mainState.notice]
  );

  return {
    mainState,
    setAllWidgets,
    setAllExtensions,
    setActivated,
    setNotice,
    setChangelog,
    setTabKey,
    setIsSkipTerms,
    setSetupType,
    updateActiveWidget,
    updateActiveGroupWidget,
    updateActiveFullWidget,
    updateActiveGeneralExtension,
    updateActiveGeneralGroupExtension,
    updateActiveGsapExtension,
    updateActiveGsapGroupExtension,
    updateActiveGsapAllExtension,
    updateActiveFullExtension,
    updateNotice,
  };
};

export const AppContext = createContext({
  mainState: initialState,
  setAllWidgets: () => {},
  setAllExtensions: () => {},
  setActivated: () => {},
  setNotice: () => {},
  setChangelog: () => {},
  setTabKey: () => {},
  setIsSkipTerms: () => {},
  setSetupType: () => {},
  updateActiveWidget: () => {},
});

export const AppContextProvider = ({ children }) => {
  return (
    <AppContext.Provider value={useMainContext(initialState)}>
      {children}
    </AppContext.Provider>
  );
};
