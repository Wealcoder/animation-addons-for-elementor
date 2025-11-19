/**
 * WCF Nav Menu 
 */

window.addEventListener("elementor/frontend/init", () => {
  const Base = elementorModules.frontend.handlers.Base;

  class WcfNavMenu extends Base {
    bindEvents() {
      this.run();
      window.addEventListener("resize", () => {
        this.mobileMenu();
        this.reRunForMobile();
      });
    }

    run() {
      this.mobileMenu();

    }

    // Function to re-run only once when device < 767px
    reRunForMobile() {
      const width = window.innerWidth;
      const bpSetting = this.getElementSettings("mobile_menu_breakpoint");
      if (bpSetting === undefined) {
        return;
      }
      if (width < 767 && !this._rerunTriggered) {
        this._rerunTriggered = true;       
        this.mobileMenu(); // call your existing logic again
      } else if (width >= 767) {
        this._rerunTriggered = false; // reset when back to desktop
      }
    }

    mobileMenu() {
      const adminbar = document.querySelector("#wpadminbar");
      const adminbarHeight = adminbar ? adminbar.offsetHeight : 0;

      const deviceWidth    = window.innerWidth;
      const navMenu        = this.findElement(".wcf__nav-menu");
      const container      = this.findElement(".wcf-nav-menu-container");
      const navItems       = this.findElements(".wcf-nav-menu-nav .menu-item-has-children");
      const mobileBackHtml = this.findElement(".mobile-sub-back")?.innerHTML || "Back";

      // Get breakpoint
      let breakpoint = 0;
      const bpSetting = this.getElementSettings("mobile_menu_breakpoint");
      if (bpSetting === undefined || bpSetting == '') {
        return;
      }
      const bpConfig = elementorFrontend.config.responsive.activeBreakpoints;
     
      if (bpSetting && bpSetting !== "all") {
        breakpoint = bpConfig[bpSetting].value;
      } else {
        breakpoint = "all";
      }

      // Reset classes
      navMenu.classList.remove("desktop-menu-active", "mobile-menu-active", "wcf-nav-is-toggled");

      const backLinkHTML = `<li class="menu-item"><a class="nav-back-link" href="#">${mobileBackHtml}</a></li>`;

      // === Desktop Mode ===
      if (breakpoint !== "all" && deviceWidth > breakpoint) {
        navItems.forEach((item) => {
          const firstLi = item.querySelector(".sub-menu li:first-child");
          if (firstLi?.querySelector(".nav-back-link")) {
            firstLi.remove();
          }
        });

        navMenu.classList.add("desktop-menu-active");
        return;
      }

      // === Mobile Mode ===
      navMenu.classList.add("mobile-menu-active");
      if (container) container.style.top = `${adminbarHeight}px`;

      // Insert back buttons
      navItems.forEach((item) => {
        const subMenu = item.querySelector(".sub-menu");
        if (!subMenu) return;

        if (!subMenu.querySelector(".nav-back-link")) {
          subMenu.insertAdjacentHTML("afterbegin", backLinkHTML);
        }

        const backLink = subMenu.querySelector(".nav-back-link");
        backLink?.addEventListener("click", (e) => {
          e.preventDefault();
          item.classList.remove("active");
          item.parentElement.closest(".menu-item")?.classList.add("active");
        });
      });

      // Fixed submenu toggle logic (multi-level support)
      this.findElements(".wcf-submenu-indicator").forEach((indicator) => {
        indicator.addEventListener("click", (e) => {
          e.preventDefault();
          const menuItem = indicator.closest(".menu-item");
          if (!menuItem) return;

          // Only close siblings, not all
          const siblingItems = menuItem.parentElement.querySelectorAll(":scope > .menu-item.active");
          siblingItems.forEach((el) => {
            if (el !== menuItem) el.classList.remove("active");
          });

          menuItem.classList.toggle("active");
        });
      });

      // Open / Close menu buttons
      this.findElement(".wcf-menu-hamburger")?.addEventListener("click", () => {
        navMenu.classList.add("wcf-nav-is-toggled");
        document.body.style.overflow = 'hidden';
      });

      this.findElement(".wcf-menu-close")?.addEventListener("click", () => {
        navMenu.classList.remove("wcf-nav-is-toggled");
        document.body.style.overflow = 'auto';
      });

      // Click outside to close
      document.addEventListener("mouseup", (e) => {
        if (!container?.contains(e.target)) {
          navMenu.classList.remove("wcf-nav-is-toggled");
          document.body.style.overflow = 'auto';
        }
      });
    }

    // Helper functions for scoped querying
    findElement(selector) {
      return this.$element[0]?.querySelector(selector);
    }
    findElements(selector) {
      return Array.from(this.$element[0]?.querySelectorAll(selector) || []);
    }
  }

  // Elementor Hook
  elementorFrontend.hooks.addAction("frontend/element_ready/wcf--nav-menu.default", ($scope) => {
    elementorFrontend.elementsHandler.addHandler(WcfNavMenu, { $element: $scope });
  });
});
