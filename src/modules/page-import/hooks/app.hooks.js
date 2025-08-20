import { AppContext } from "S/context/app.context";
import { useContext } from "react";

export const useTNavigation = () => {
  const {
    mainState: { tabKey },
    setTabKey,
  } = useContext(AppContext);

  return { tabKey, setTabKey };
};

export const useActivate = () => {
  const {
    mainState: { activated },
  } = useContext(AppContext);
  return { activated };
};
