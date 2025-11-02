window.addEventListener("elementor/frontend/init", () => {
    class ImageAccordion {
        constructor($element) {
            this.$element = $element;
            this.expand = this.getElementSetting("expand_style") || "hover";
            this.accordionItems = this.$element.querySelectorAll(".accordion-item");
            this.bindEvents();
        }

        getElementSetting(setting) {
            try {
                const settings = JSON.parse(this.$element.dataset.settings || "{}");
                return settings[setting];
            } catch {
                return null;
            }
        }

        bindEvents() {
            this.accordionItems.forEach((item, index) => {
                if (this.expand === "click") {
                    item.addEventListener("click", () => this.openAccordion(index, item));
                } else {
                    item.addEventListener("mouseenter", () => this.openAccordion(index, item));
                    item.addEventListener("mouseleave", () => {
                        item.classList.remove("accordion-hover-active");
                    });
                }
            });
        }

        openAccordion(index, item) {
            this.accordionItems.forEach((single) => {
                single.classList.toggle("accordion-hover-active", single === item);
            });
        }
    }

    elementorFrontend.hooks.addAction(
        "frontend/element_ready/wcf--image-accordion.default",
        ($scope) => new ImageAccordion($scope[0] || $scope)
    );
});
