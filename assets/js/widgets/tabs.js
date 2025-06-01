(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */

  var Tabs = function Tabs($scope) {
    //Default Action
    $('.tab-content', $scope).hide(); //Hide all content
    $('.tab-title:first', $scope).addClass("active").show();
    $('.tab-content:first', $scope).show();

    //mobile title
    var activeMobileTitle = $('.tab-title:first', $scope).attr("aria-controls");
    $(".tab-title[aria-controls='".concat(activeMobileTitle, "']"), $scope).addClass("active");

    //On Click Event
    $('.tab-title', $scope).click(function () {
      if ($(this).hasClass("active")) {
        return;
      }
      $('.tab-title', $scope).removeClass("active");
      $(this).addClass("active");
      $('.tab-content', $scope).hide();
      var activeTab = $(this).attr("aria-controls");
      $(".tab-content[id='".concat(activeTab, "']"), $scope).fadeIn();
      $(".tab-title[aria-controls='".concat(activeTab, "']"), $scope).addClass("active");
      return false;
    });
  };

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    var tab_widgets = ['tabs', 'services-tab'];
    for (var _i = 0, _tab_widgets = tab_widgets; _i < _tab_widgets.length; _i++) {
      var widget = _tab_widgets[_i];
      elementorFrontend.hooks.addAction("frontend/element_ready/wcf--".concat(widget, ".default"), Tabs);
    }
  });
})(jQuery);