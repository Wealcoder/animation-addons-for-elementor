import { register } from "@elementor/frontend-handlers";
import "../scss/button.scss";

const textFlipSetup = (container) => {
  const spanEl = container.querySelector(".e-paragraph-base span, :scope > span");
  if (!spanEl) return;
  if (!spanEl.dataset.text) {
    spanEl.dataset.text = spanEl.textContent.trim();
  }
};

const borderDivideSwap = (container) => {
  // Disconnect any previous text-sync observer before re-running
  if (container._aaeBorderObserver) {
    container._aaeBorderObserver.disconnect();
    container._aaeBorderObserver = null;
  }

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

  // Keep the visible clone in sync when Elementor updates the original paragraph
  const cloneEl = textWrapper.querySelector(".e-paragraph-base");
  if (cloneEl) {
    const observer = new MutationObserver(() => {
      cloneEl.innerHTML = textEl.innerHTML;
    });
    observer.observe(textEl, { childList: true, subtree: true, characterData: true });
    container._aaeBorderObserver = observer;
  }
};

const maskBtn = (container) => {
  const textEl = container.querySelector(".e-paragraph-base");
  if (!textEl) return;
  container.setAttribute("data-text", textEl.textContent.trim());
};

register({
  elementType: "e-aae-a-button",
  id: "e-aae-a-button-handler",
  callback: ({ element }) => {
    if (element.classList.contains("btn-border-divide")) {
      borderDivideSwap(element);
    } else if (element.classList.contains("btn-text-flip")) {
      textFlipSetup(element);
    } else if (element.classList.contains("wcf-btn-mask")) {
      maskBtn(element);
    }
  },
});
