(function ($) {
  $(window).on('elementor/frontend/init', function () {
    let adminbar_height = $('#wpadminbar').height();

    const elementorBreakpoints = elementorFrontend.config.responsive.activeBreakpoints;
    const Modules = elementorModules.frontend.handlers.Base;

    const WcfNavMenu = Modules.extend({
      bindEvents: function () {
        this.run();
      },

      run: function () {
        // ✅ Refresh when screen width < 767px
        this.refreshOnSmallDevices();

        $(window).resize(() => {
          this.mobileMenu();
          this.refreshOnSmallDevices();
        });

        this.mobileMenu();
      },

      refreshOnSmallDevices: function () {
        const device_width = $(window).width();
        if (device_width < 767) {
          // Prevent infinite reload loop
          if (!sessionStorage.getItem('refreshed')) {
            sessionStorage.setItem('refreshed', 'true');
            location.reload();
          }
        } else {
          sessionStorage.removeItem('refreshed');
        }
      },

      mobileMenu: function () {
        const device_width = $(window).width();
        let breakpoint = 0;
        let mobile_back = this.findElement('.mobile-sub-back').html();

        if (this.getElementSettings('mobile_menu_breakpoint') && 'all' !== this.getElementSettings('mobile_menu_breakpoint')) {
          breakpoint = elementorBreakpoints[this.getElementSettings('mobile_menu_breakpoint')].value;
        }

        if ('all' === this.getElementSettings('mobile_menu_breakpoint')) {
          breakpoint = 'all';
        }

        this.findElement('.wcf__nav-menu').removeClass('desktop-menu-active mobile-menu-active');

        const navExpand = [].slice.call(this.findElement('.wcf-nav-menu-nav .menu-item-has-children'));
        const backLink = `<li class="menu-item"><a class="nav-back-link" href="javascript:;">${mobile_back}</a></li>`;

        if (device_width > breakpoint) {
          navExpand.forEach(item => {
            if ($(item).find('.nav-back-link').length) {
              $(item.querySelector('.sub-menu li:first-child')).remove();
            }
          });
          this.findElement('.wcf__nav-menu').removeClass('mobile-menu-active wcf-nav-is-toggled').addClass('desktop-menu-active');
          return;
        }

        this.findElement('.wcf__nav-menu').removeClass('desktop-menu-active').addClass('mobile-menu-active');
        this.findElement('.wcf-nav-menu-container').css({ top: adminbar_height });

        navExpand.forEach(item => {
          if (0 === $(item).find('.nav-back-link').length) {
            item.querySelector('.sub-menu').insertAdjacentHTML('afterbegin', backLink);
          }

          item.querySelector('.nav-back-link').addEventListener('click', (e) => {
            e.preventDefault();
            item.classList.remove('active');
          });
        });

        const sub_expand = this.findElement('.wcf-submenu-indicator');
        sub_expand.on('click', function (e) {
          e.preventDefault();
          let menu_item = $(this).closest('.menu-item');
          menu_item.siblings().removeClass('active');
          menu_item.toggleClass('active');
        });

        this.findElement('.wcf-menu-hamburger').on('click', () => {
          this.findElement('.wcf__nav-menu').addClass('wcf-nav-is-toggled');
        });

        this.findElement('.wcf-menu-close').on('click', () => {
          this.findElement('.wcf__nav-menu').removeClass('wcf-nav-is-toggled');
        });

        $(document).mouseup((e) => {
          let container = this.findElement('.wcf-nav-menu-container');
          if (!container.is(e.target) && container.has(e.target).length === 0) {
            this.findElement('.wcf__nav-menu').removeClass('wcf-nav-is-toggled');
          }
        });
      }
    });

    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--nav-menu.default', function ($scope) {
      elementorFrontend.elementsHandler.addHandler(WcfNavMenu, { $element: $scope });
    });
  });
})(jQuery);
