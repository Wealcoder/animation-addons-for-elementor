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
  // Guard: already processed
  if (container.dataset.groupSwapped) return null;

  // Find the LAST .e-svg-base (the "original" icon at the end)
  const svgEls = container.querySelectorAll(".e-svg-base");
  if (!svgEls.length) return null;

  const lastSvg = svgEls[svgEls.length - 1];
  const duplicatedSvg = lastSvg.cloneNode(true);
  duplicatedSvg.setAttribute("data-swap-clone", "true");

  container.prepend(duplicatedSvg);
  container.dataset.groupSwapped = "true";
};

const borderDivideSwap_prev = (container) => {
  // Guard: already processed
  if (container.dataset.borderDivideSwapped) return null;

  // Find the .e-svg-base (the icon)
  const svgEl = container.querySelector(".e-svg-base");
  if (!svgEl) return null;

  // Find the text span
  const textEl = container.querySelector(".e-paragraph-base");
  if (!textEl) return null;

  // Create wrapper spans
  const textWrapper = document.createElement("span");
  textWrapper.classList.add("text");

  const iconWrapper = document.createElement("span");
  iconWrapper.classList.add("icon");

  // Clone the svg for the duplicate
  const duplicatedSvg = svgEl.cloneNode(true);
  duplicatedSvg.setAttribute("data-swap-clone", "true");

  // Build icon wrapper: original + clone
  iconWrapper.appendChild(svgEl);
  iconWrapper.appendChild(duplicatedSvg);

  // Build text wrapper
  textWrapper.appendChild(textEl);

  // Clear container and rebuild
  // Remove only the children we're wrapping (leave editor overlays etc untouched)
  container.prepend(iconWrapper);
  container.prepend(textWrapper);

  container.dataset.borderDivideSwapped = "true";
};

const borderDivideSwap = (container) => {
  // Clean up any previous swap wrappers before re-running
  const existingText = container.querySelector(":scope > span.text");
  const existingIcon = container.querySelector(":scope > span.icon");
  if (existingText) existingText.remove();
  if (existingIcon) existingIcon.remove();

  // Find elements from Elementor's display:contents wrappers
  const svgEl = container.querySelector(
    ".elementor-widget-e-svg .e-svg-base, :scope > .e-svg-base",
  );
  if (!svgEl) return null;

  const textEl = container.querySelector(
    ".elementor-widget-e-paragraph .e-paragraph-base, :scope > .e-paragraph-base",
  );
  if (!textEl) return null;

  // Clone the svg for the duplicate
  const duplicatedSvg = svgEl.cloneNode(true);
  duplicatedSvg.setAttribute("data-swap-clone", "true");
  // Remove interaction id from clone to avoid duplicates
  duplicatedSvg.removeAttribute("data-interaction-id");

  // Build wrappers
  const textWrapper = document.createElement("span");
  textWrapper.classList.add("text");

  const iconWrapper = document.createElement("span");
  iconWrapper.classList.add("icon");

  // Append cloned content (don't move originals — they live inside display:contents wrappers)
  const clonedText = textEl.cloneNode(true);
  clonedText.removeAttribute("draggable");
  textWrapper.appendChild(clonedText);

  iconWrapper.appendChild(svgEl.cloneNode(true));
  iconWrapper.appendChild(duplicatedSvg);

  // Prepend both wrappers
  container.prepend(iconWrapper);
  container.prepend(textWrapper);

  container.dataset.borderDivideSwapped = "true";
};

register({
  elementType: "e-aae-a-button",
  id: "e-aae-a-button-handler",
  callback: ({ element }) => {
    if (element.classList.contains("btn-hover")) {
      rippleBtn(element);
    } else if (element.classList.contains("aae-btn-pro-group")) {
      groupSwap(element);
    } else if (element.classList.contains("btn-border-divide")) {
      borderDivideSwap_prev(element);
    }
  },
});
