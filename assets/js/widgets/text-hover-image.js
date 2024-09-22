(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  var WcfTextHoverImage = function WcfTextHoverImage($scope, $) {
    var hover_text = $('.hover_text', $scope)[0];
    if (hover_text) {
      var hoverImgFunc = function hoverImgFunc(event, hover_text) {
        var contentBox = hover_text.getBoundingClientRect();
        var dx = event.clientX - contentBox.x;
        var dy = event.clientY - contentBox.y;
        hover_text.children[0].style.transform = "translate(".concat(dx, "px, ").concat(dy, "px)");
      };
      hover_text.addEventListener("mousemove", function (event) {
        setInterval(hoverImgFunc(event, hover_text), 1000);
      });
    }
  };

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--t-h-image.default', WcfTextHoverImage);
  });
})(jQuery);