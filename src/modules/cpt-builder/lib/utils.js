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

export const generateLabelValue = (rawData) => {
  return Object.entries(rawData).map(([key, value]) => {
    return {
      value,
      label: value
        .replace(/_/g, " ") // Replace underscores with spaces
        .replace(/\b\w/g, (char) => char.toUpperCase()), // Capitalize each word
    };
  });
};
