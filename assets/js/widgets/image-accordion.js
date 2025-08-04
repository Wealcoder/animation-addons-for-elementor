(function () {
  const ImageAccordion = elementorModules.frontend.handlers.Base.extend({
    onInit: function () {
      this.run();
    },

    run: function () {
      const expand = this.getElementSettings("expand_style");
      const accordionItems = this.findElement(".accordion-item");

      accordionItems.forEach((item, index) => {
        if (expand === "click") {
          item.addEventListener("click", () => {
            this.openAccordion(index, item, accordionItems);
          });
        } else {
          // Hover style
          item.addEventListener("mouseenter", () => {
            this.openAccordion(index, item, accordionItems);
          });

          item.addEventListener("mouseleave", () => {
            item.classList.remove("accordion-hover-active");
          });
        }
      });
    },

    openAccordion: function (index, item, accordionItems) {
      accordionItems.forEach((single) => {
        if (single === item) {
          single.classList.add("accordion-hover-active");
        } else {
          single.classList.remove("accordion-hover-active");
        }
      });
    },

    findElement: function (selector) {
      // Override to use native querySelectorAll instead of jQuery
      return Array.from(this.$element[0].querySelectorAll(selector));
    },
  });

  elementorFrontend.hooks.addAction(
    "frontend/element_ready/wcf--imag-accordion.default",
    function ($scope) {
      elementorFrontend.elementsHandler.addHandler(ImageAccordion, {
        $element: $scope,
      });
    }
  );
})();
