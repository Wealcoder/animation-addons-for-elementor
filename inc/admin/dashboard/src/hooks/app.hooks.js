import { AppContext } from "@/context/app.context";
import { useContext } from "react";

export const useWidgets = () => {
  const {
    mainState: { allWidgets },
    setAllWidgets,
  } = useContext(AppContext);
  return { allWidgets, setAllWidgets };
};

export const useExtensions = () => {
  const {
    mainState: { allExtensions },
    setAllExtensions,
  } = useContext(AppContext);
  return { allExtensions, setAllExtensions };
};

export const useActiveItem = () => {
  const { updateActiveWidget, updateActiveGroupWidget, updateActiveFullWidget, updateActiveGeneralExtension } = useContext(AppContext);
  return { updateActiveWidget, updateActiveGroupWidget, updateActiveFullWidget, updateActiveGeneralExtension };
};
