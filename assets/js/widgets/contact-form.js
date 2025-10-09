/* global WCF_ADDONS_JS */

(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  // Make sure you run this code under Elementor.
  $(window).on("elementor/frontend/init", function () {

    const contact_form_7 = function ($scope) {
      const submit_btn = $(".wpcf7-submit", $scope);

      let classes = submit_btn.attr("class");
      classes +=
        " wcf-btn-default " + $(".wcf--form-wrapper", $scope).attr("btn-hover");

      submit_btn.replaceWith(function () {
        return $("<button/>", {
          html: $(".btn-icon").html() + submit_btn.attr("value"),
          class: classes,
          type: "submit",
        });
      });
    };

    elementorFrontend.hooks.addAction(
      `frontend/element_ready/wcf--contact-form-7.default`,
      contact_form_7
    );

  });
})(jQuery);
