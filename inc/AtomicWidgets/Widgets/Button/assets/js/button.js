import { register } from "@elementor/frontend-handlers";
import "../scss/button.scss";

const rippleBtn = (container) => {
  // In V4 atomic the container IS the <a> root element, so check the element
  // itself before falling back to a descendant search (needed for V3 compat).
  const rippleBtn = container.classList.contains("btn-hover")
    ? container
    : container.querySelector(".btn-hover");

  if (!rippleBtn) return;

  const moveRipple = (e) => {
    // Prefer the named ripple element injected by twig; fall back to bare span.
    const span =
      rippleBtn.querySelector(".aae-ripple-el") ||
      rippleBtn.querySelector("span:first-child");
    if (!span) return;
    const rect = rippleBtn.getBoundingClientRect();
    span.style.left = e.clientX - rect.left + "px";
    span.style.top = e.clientY - rect.top + "px";
  };

  rippleBtn.addEventListener("mouseenter", moveRipple);
  rippleBtn.addEventListener("mouseleave", moveRipple);
};

const groupSwap = (container) => {
  // const is_l = container.classList.contains("style-5");

  // if (!is_l) return null;

  console.log("inside grupSwat");

  const svgEl = container.querySelector(".e-svg-base");
  if (!svgEl) return null;

  const duplicatedSvg = svgEl.cloneNode(true);

  container.prepend(duplicatedSvg);
  // if (is_l) {
  // } else {
  //   container.append(duplicatedSvg);
  // }
};

register({
  elementType: "e-aae-a-button",
  id: "e-aae-a-button-handler",
  callback: ({ element }) => {
    if (element.classList.contains("btn-hover")) {
      rippleBtn(element);
    } else if (element.classList.contains("aae-btn-pro-group")) {
      groupSwap(element);
    }
  },
});
