(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  var Social_Share_Count = function Social_Share_Count($scope, $) {
    var $socials = $('.default-details-social-media a', $scope);
    $socials.on('click', function (e) {
      var _WCF_ADDONS_JS;
      if ((_WCF_ADDONS_JS = WCF_ADDONS_JS) !== null && _WCF_ADDONS_JS !== void 0 && _WCF_ADDONS_JS.post_id) {
        $.ajax({
          url: WCF_ADDONS_JS.ajaxUrl,
          // WordPress AJAX handler
          type: 'POST',
          data: {
            action: 'aae_post_shares',
            // Custom action name
            post_id: WCF_ADDONS_JS.post_id,
            // Post ID to update share count
            nonce: WCF_ADDONS_JS._wpnonce
          },
          success: function success(response) {
            console.log(response);
          },
          error: function error() {}
        });
      }
    });
  };

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--blog--post--social-share.default', Social_Share_Count);
  });
})(jQuery);