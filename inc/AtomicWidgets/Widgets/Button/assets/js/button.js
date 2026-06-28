import { register } from "@elementor/frontend-handlers";
import "../scss/button.scss";

const textFlipSetup = (container) => {
  const spanEl = container.querySelector(".e-paragraph-base span, :scope > span");
  if (!spanEl) return;
  if (!spanEl.dataset.text) {
    spanEl.dataset.text = spanEl.textContent.trim();
  }
};

// Re-sync the visible clones in a btn-border-divide button from the live originals.
const syncBorderDivideClones = (btn) => {
  const textWrapper = btn.querySelector(":scope > span.text");
  const iconWrapper = btn.querySelector(":scope > span.icon");

  if (textWrapper) {
    const liveText = btn.querySelector(
      ".elementor-widget-e-paragraph .e-paragraph-base, :scope > .e-paragraph-base",
    );
    const clone = textWrapper.querySelector(".e-paragraph-base");
    if (liveText && clone && liveText.innerHTML !== clone.innerHTML) {
      clone.innerHTML = liveText.innerHTML;
    }
  }

  if (iconWrapper) {
    const liveSvg = btn.querySelector(
      ".elementor-widget-e-svg .e-svg-base, :scope > .e-svg-base",
    );
    if (liveSvg) {
      iconWrapper.querySelectorAll(".e-svg-base").forEach((clone) => {
        if (clone.innerHTML !== liveSvg.innerHTML) {
          clone.innerHTML = liveSvg.innerHTML;
        }
      });
    }
  }
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

const maskBtn = (container) => {
  const textEl = container.querySelector(".e-paragraph-base");
  if (!textEl) return;
  container.setAttribute("data-text", textEl.textContent.trim());
};

// When a child atomic widget (e-paragraph or e-svg) is re-rendered by the
// Elementor editor, it replaces its own DOM element. Any MutationObserver
// placed on the old element becomes deaf. Instead, hook into Elementor's
// frontend/element_ready event — fired for every widget render including
// editor live-updates — and re-sync the visible clones from the new DOM.
// We register these hooks once (after elementorFrontend is guaranteed ready)
// using a flag so repeated button initializations don't stack duplicates.
function hookChildReadyOnce() {
  if (window._aaeBorderDivideHooked) return;
  if (!window.elementorFrontend?.hooks) return;
  window._aaeBorderDivideHooked = true;

  const onChildReady = ($scope) => {
    const btn = $scope?.[0]?.closest?.(".btn-border-divide");
    if (btn) syncBorderDivideClones(btn);
  };

  elementorFrontend.hooks.addAction("frontend/element_ready/e-paragraph", onChildReady);
  elementorFrontend.hooks.addAction("frontend/element_ready/e-svg", onChildReady);
}

register({
  elementType: "e-aae-a-button",
  id: "e-aae-a-button-handler",
  callback: ({ element }) => {
    if (element.classList.contains("btn-border-divide")) {
      borderDivideSwap(element);
      hookChildReadyOnce();
    } else if (element.classList.contains("btn-text-flip")) {
      textFlipSetup(element);
    } else if (element.classList.contains("wcf-btn-mask")) {
      maskBtn(element);
    }
  },
});
