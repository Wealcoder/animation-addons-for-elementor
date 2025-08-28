import { useContext } from "react";
import { AppContext } from "S/context/app.context";

export const useTab = () => {
  const {
    mainState: { tabKey },
    updateTabKey,
  } = useContext(AppContext);
  return { tabKey, updateTabKey };
};

export const usePostType = () => {
  const {
    mainState: { allPostTypes },
    setAllPostTypes,
  } = useContext(AppContext);

  return { allPostTypes, setAllPostTypes };
};

export const useTaxonomy = () => {
  const {
    mainState: { allTaxonomy },
    setAllTaxonomy,
  } = useContext(AppContext);

  return { allTaxonomy, setAllTaxonomy };
};
