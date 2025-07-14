(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */

  var VideoPostsTab = function VideoPostsTab($scope) {
    $('.aae--posts-tab .posts-banner .thumb', $scope).hide();
    $('.aae--posts-tab .posts-banner .thumb:first', $scope).fadeIn();
    $('.aae--posts-tab .aae-post', $scope).click(function (e) {
      e.preventDefault();
      var post_id = $(this).find('.content').data('id');
      $('.aae--posts-tab .posts-banner .thumb', $scope).fadeOut();
      $('.aae--posts-tab .posts-banner .thumb[data-target="' + post_id + '"]', $scope).fadeIn();
    });
  };

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/aae--video-posts-tab.default', VideoPostsTab);
  });
})(jQuery);