(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    var adminbar_height = $('#wpadminbar').height();
    var device_width = $(window).width();
    var elementorBreakpoints = elementorFrontend.config.responsive.activeBreakpoints;
    var Modules = elementorModules.frontend.handlers.Base;
    var WcfNavMenu = Modules.extend({
      bindEvents: function bindEvents() {
        this.run();
      },
      run: function run() {
        var _this = this;
        $(window).resize(function () {
          _this.mobileMenu();
        });
        this.mobileMenu();
      },
      mobileMenu: function mobileMenu() {
        var _this2 = this;
        var device_width = $(window).width();
        var breakpoint = 0;
        var mobile_back = this.findElement('.mobile-sub-back').html();
        if (this.getElementSettings('mobile_menu_breakpoint') && 'all' !== this.getElementSettings('mobile_menu_breakpoint')) {
          breakpoint = elementorBreakpoints[this.getElementSettings('mobile_menu_breakpoint')].value;
        }

        //mobile menu in all device
        if ('all' === this.getElementSettings('mobile_menu_breakpoint')) {
          breakpoint = 'all';
        }

        //mobile menu active
        this.findElement('.wcf__nav-menu').removeClass('desktop-menu-active');
        this.findElement('.wcf__nav-menu').removeClass('mobile-menu-active');
        var navExpand = [].slice.call(this.findElement('.wcf-nav-menu-nav .menu-item-has-children'));
        var backLink = "<li class=\"menu-item\"><a class=\"nav-back-link\" href=\"javascript:;\">".concat(mobile_back, "</a></li>");

        //desktop menu active
        if (device_width > breakpoint) {
          //remove back link
          navExpand.forEach(function (item) {
            if ($(item).find('.nav-back-link').length) {
              $(item.querySelector('.sub-menu li:first-child')).remove();
            }
          });
          this.findElement('.wcf__nav-menu').removeClass('mobile-menu-active');
          this.findElement('.wcf__nav-menu').addClass('desktop-menu-active');
          this.findElement('.wcf__nav-menu').removeClass('wcf-nav-is-toggled');
          return;
        }

        //mobile menu active
        this.findElement('.wcf__nav-menu').removeClass('desktop-menu-active');
        this.findElement('.wcf__nav-menu').addClass('mobile-menu-active');

        //set mobile menu top value
        this.findElement('.wcf-nav-menu-container').css({
          "top": adminbar_height
        });
        navExpand.forEach(function (item) {
          if (0 === $(item).find('.nav-back-link').length) {
            item.querySelector('.sub-menu').insertAdjacentHTML('afterbegin', backLink);
          }
          item.querySelector('.nav-back-link').addEventListener('click', function (e) {
            e.preventDefault();
            item.classList.remove('active');
          });
        });

        //mega menu mobile active
        var sub_expand = this.findElement('.wcf-submenu-indicator');
        sub_expand.on('click', function (e) {
          e.preventDefault();
          var menu_item = $(this).closest('.menu-item');
          menu_item.siblings().removeClass('active');
          menu_item.toggleClass('active');
        });

        //open menu
        this.findElement('.wcf-menu-hamburger').on('click', function () {
          _this2.findElement('.wcf__nav-menu').addClass('wcf-nav-is-toggled');
        });

        //close menu
        this.findElement('.wcf-menu-close').on('click', function () {
          _this2.findElement('.wcf__nav-menu').removeClass('wcf-nav-is-toggled');
        });

        //close menu outside menu area click
        $(document).mouseup(function (e) {
          var container = _this2.findElement('.wcf-nav-menu-container');
          // If the target of the click isn't the container
          if (!container.is(e.target) && container.has(e.target).length === 0) {
            _this2.findElement('.wcf__nav-menu').removeClass('wcf-nav-is-toggled');
          }
        });
      }
    });
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--nav-menu.default', function ($scope) {
      elementorFrontend.elementsHandler.addHandler(WcfNavMenu, {
        $element: $scope
      });
    });
  });
})(jQuery);