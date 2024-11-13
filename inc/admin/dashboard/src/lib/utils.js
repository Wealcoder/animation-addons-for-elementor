import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

export const debounceFn = (mainFunction, delay = 300) => {
  let timer;

  return function (...args) {
    clearTimeout(timer);

    timer = setTimeout(() => {
      mainFunction(...args);
    }, delay);
  };
};

export const generateWidget = (tabContent, tabItems, filterKey) => {
  let allContent = {};
  tabItems.forEach((el) => {
    if (el.value !== "all") {
      allContent[el.value] = tabContent?.filter((tab) => {
        if (filterKey && (filterKey === "free" || filterKey === "pro")) {
          if (filterKey === "free" && tab.category === el.value && !tab.isPro) {
            return tab;
          } else if (
            filterKey === "pro" &&
            tab.category === el.value &&
            tab.isPro
          ) {
            return tab;
          }
        } else {
          if (tab.category === el.value) {
            return tab;
          }
        }
      });
    }
  });

  return allContent;
};

export const generateGsapExtension = (tabContent, filterKey) => {
  const allData = [];
  tabContent?.map((el) => {
    const value = el?.extensions?.filter((item) => {
      if (filterKey && (filterKey === "free" || filterKey === "pro")) {
        if (filterKey === "free" && !item.isPro) {
          return item;
        } else if (filterKey === "pro" && item.isPro) {
          return item;
        }
      } else {
        return item;
      }
    });
    if (value && value.length) {
      const updateData = { ...el };
      updateData.extensions = value;

      allData.push(updateData);
    }
  });
  return allData;
};
export const generateGeneralExtension = (tabContent, filterKey) => {
  const allData = tabContent?.filter((el) => {
    if (filterKey && (filterKey === "free" || filterKey === "pro")) {
      if (filterKey === "free" && !el.isPro) {
        return el;
      } else if (filterKey === "pro" && el.isPro) {
        return el;
      }
    } else {
      return el;
    }
  });
  return allData;
};

export const generateSearchContent = (
  fullContent = [],
  categoryKey,
  subItems
) => {
  if (subItems) {
    const allItems = [];
    fullContent?.map((el) =>
      el?.[subItems].map((item) => {
        allItems.push(item);
      })
    );

    const result = {
      category: categoryKey,
      items: allItems,
    };

    return result;
  } else {
    const result = {
      category: categoryKey,
      items: fullContent,
    };

    return result;
  }
};

export const filterWidgets = (mainContent, filterKey) => {
  const result = Object.fromEntries(
    Object.entries(mainContent).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (filterKey && (filterKey === "free" || filterKey === "pro")) {
            if (filterKey === "free" && !value2.is_pro) {
              return [key2, value2];
            } else if (filterKey === "pro" && value2.is_pro) {
              return [key2, value2];
            }
          } else {
            return [key2, value2];
          }
        })
      );

      return [key, { ...value, elements: filteredElements }];
    })
  );

  return result;
};

export const filterGeneralExtension = (mainContent, filterKey) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements || {}).filter(([key2, value2]) => {
      if (filterKey && (filterKey === "free" || filterKey === "pro")) {
        if (filterKey === "free" && !value2.is_pro) {
          return [key2, value2];
        } else if (filterKey === "pro" && value2.is_pro) {
          return [key2, value2];
        }
      } else {
        return [key2, value2];
      }
    })
  );
  return {
    ...mainContent,
    elements: result,
  };
};

export const filterGsapExtension = (mainContent, filterKey) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).filter(([key2, value2]) => {
          if (filterKey && (filterKey === "free" || filterKey === "pro")) {
            if (filterKey === "free" && !value2.is_pro) {
              return [key2, value2];
            } else if (filterKey === "pro" && value2.is_pro) {
              return [key2, value2];
            }
          } else {
            return [key2, value2];
          }
        })
      );

      return [key, { ...value, elements: filteredElements }];
    })
  );

  return {
    ...mainContent,
    elements: result,
  };
};
