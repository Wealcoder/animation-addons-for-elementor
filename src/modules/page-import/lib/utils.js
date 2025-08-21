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

export const hideElements = () => {
  const wpAdminBar = document.getElementById("wpadminbar");
  const adminMenuWrap = document.getElementById("adminmenuwrap");
  const adminMenuBack = document.getElementById("adminmenuback");
  const wpfooter = document.getElementById("wpfooter");
  const wpcontent = document.getElementById("wpcontent");
  const wpbodyContent = document.getElementById("wpbody-content");
  const wrap = document.querySelector(".wrap");
  const wpToolbar = document.querySelector(".wp-toolbar");
  const wcfAnim2024 = document.querySelector(".wcf-anim2024");

  if (wpAdminBar) wpAdminBar.style.display = "none";
  if (adminMenuWrap) adminMenuWrap.style.display = "none";
  if (adminMenuBack) adminMenuBack.style.display = "none";
  if (wpfooter) wpfooter.style.display = "none";
  if (wpcontent) wpcontent.style.marginLeft = "0px";
  if (wpcontent) wpcontent.style.paddingLeft = "0px";
  if (wpbodyContent)
    wpbodyContent.style.setProperty("padding-bottom", "0px", "important");
  if (wrap) wrap.style.margin = "0px";
  if (wpToolbar) wpToolbar.style.paddingTop = "0px";
  if (wcfAnim2024) wcfAnim2024.style.overflow = "hidden";
};
