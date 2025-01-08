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

export const deviceMediaMatch = () => {
  if (window.matchMedia("(min-width: 1440px)").matches) {
    return 3;
  } else {
    return 2;
  }
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

export const disableAllWidget = (mainContent) => {
  const result = Object.fromEntries(
    Object.entries(mainContent)
      .map(([key, value]) => {
        const filteredElements = Object.fromEntries(
          Object.entries(value.elements || {}).map(([key2, value2]) => {
            return [key2, { ...value2, is_active: false }];
          })
        );

        return [
          key,
          { ...value, is_active: false, elements: filteredElements },
        ];
      })
      .filter(([key, value]) => Object.keys(value.elements).length > 0)
  );

  return result;
};

export const disableGsapExtension = (mainContent) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements || {}).map(([key, value]) => {
      const filteredElements = Object.fromEntries(
        Object.entries(value.elements || {}).map(([key2, value2]) => {
          return [key2, { ...value2, is_active: false }];
        })
      );

      return [key, { ...value, is_active: false, elements: filteredElements }];
    })
  );

  return {
    ...mainContent,
    is_active: false,
    elements: result,
  };
};

export const disableGeneralExtension = (mainContent) => {
  const result = Object.fromEntries(
    Object.entries(mainContent.elements || {}).map(([key2, value2]) => {
      return [key2, { ...value2, is_active: false }];
    })
  );
  return {
    ...mainContent,
    is_active: false,
    elements: result,
  };
};

export const filterWidgets = (mainContent, filterKey) => {
  const result = Object.fromEntries(
    Object.entries(mainContent)
      .map(([key, value]) => {
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
      .filter(([key, value]) => Object.keys(value.elements).length > 0)
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

export const isEqual = (obj1, obj2) => {
  const differences = {};

  const compare = (o1, o2, path = "") => {
    const keys = new Set([...Object.keys(o1 || {}), ...Object.keys(o2 || {})]);

    keys.forEach((key) => {
      const fullPath = path ? `${path}.${key}` : key;

      if (!(key in (o1 || {}))) {
        differences[fullPath] = { type: "added", value: o2[key] };
      } else if (!(key in (o2 || {}))) {
        differences[fullPath] = { type: "removed", value: o1[key] };
      } else if (
        typeof o1[key] === "object" &&
        typeof o2[key] === "object" &&
        o1[key] !== null &&
        o2[key] !== null
      ) {
        // Recurse for nested objects but also handle direct changes
        compare(o1[key], o2[key], fullPath);
      } else if (o1[key] !== o2[key]) {
        differences[fullPath] = {
          type: "modified",
          oldValue: o1[key],
          newValue: o2[key],
        };
      }
    });
  };

  compare(obj1, obj2);
  return differences;
};
