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

export const generateWidgetSearchContent = (mainContent) => {
  let storeData = [];
  Object.entries(mainContent).map(([key, val]) => {
    Object.entries(val.elements).map(([key2, val2]) => {
      const sampleData = {
        icon: val2?.icon || "wcf-icon-Team",
        path: "widgets",
        slug: key2,
        title: val2.label,
        location: val2.location,
      };

      storeData.push(sampleData);
    });
  });

  return {
    category: "Widgets",
    items: storeData,
  };
};

export const generateGsapExtSearchContent = (mainContent) => {
  let storeData = [];
  Object.entries(mainContent).map(([key, val]) => {
    Object.entries(val.elements).map(([key2, val2]) => {
      const sampleData = {
        icon: val2?.icon || "wcf-icon-Floating-Elements",
        path: "extensions",
        slug: key2,
        title: val2.label,
        location: val2.location,
      };

      storeData.push(sampleData);
    });
  });

  return {
    category: "GSAP Extension",
    items: storeData,
  };
};

export const generateGenExtSearchContent = (mainContent) => {
  let storeData = [];
  Object.entries(mainContent).map(([key, val]) => {
    const sampleData = {
      icon: val?.icon || "wcf-icon-Floating-Elements",
      path: "extensions",
      slug: key,
      title: val.label,
      location: val.location,
    };

    storeData.push(sampleData);
  });

  return {
    category: "General Extension",
    items: storeData,
  };
};
