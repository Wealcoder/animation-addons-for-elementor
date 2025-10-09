/* global WCF_ADDONS_JS */

(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  // Make sure you run this code under Elementor.
  $(window).on("elementor/frontend/init", function () {
 
    //Toggle Switcher
    const toggle_switcher = function ($scope) {
      const checked = $("input", $scope);
      const toggle_pane = $(".toggle-pane", $scope);
      const toggle_label = $(".before_label, .after_label", $scope);      
      checked.change(function () {
        toggle_pane.toggleClass("show");
        toggle_label.toggleClass("active");
      });
    };
    elementorFrontend.hooks.addAction(
      `frontend/element_ready/wcf--toggle-switch.default`,
      toggle_switcher
    );
    
  });
})(jQuery);
