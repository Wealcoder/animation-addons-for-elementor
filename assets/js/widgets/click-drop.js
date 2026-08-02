/* global WCF_ADDONS_JS */

(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  // Make sure you run this code under Elementor.
  $(window).on("elementor/frontend/init", function () {

    const ClickDrop = function ($scope) {
      // Convert jQuery object to native DOM element
      const scopeEl = $scope[0]; // or $scope.get(0)

      const btn = scopeEl.querySelector(".aae-clickdrop-btn");
      const modal = scopeEl.querySelector(".aae-clickdrop-modal");

      if (!btn || !modal) return;

      // Toggle modal visibility
      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        modal.classList.toggle("visible");
      });

      // Click outside to hide modal
      document.addEventListener("click", function (e) {
        if (!scopeEl.contains(e.target)) {
          modal.classList.remove("visible");
        }
      });
    };

    elementorFrontend.hooks.addAction(
      `frontend/element_ready/aae--clickdrop.default`,
      ClickDrop
    );

  });
})(jQuery);
