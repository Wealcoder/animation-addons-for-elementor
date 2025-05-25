(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   */
  var WcfAjaxSearch = function WcfAjaxSearch($scope) {
    var $inputField = $scope.find('.search-field');
    var $resultBox = $scope.find('.aae--live-search-results');
    var $searchWrapper = $('.search--wrapper.style-full-screen .wcf-search-container');

    // Debounce function
    function debounce(func, delay) {
      var timeout;
      return function () {
        var context = this;
        var args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function () {
          return func.apply(context, args);
        }, delay);
      };
    }
    function handleSearch() {
      var keyword = $inputField.val().trim();
      if (keyword.length < 1) {
        $resultBox.hide();
        return;
      }
      $.ajax({
        url: WCF_ADDONS_JS.ajaxUrl,
        type: 'POST',
        data: {
          action: 'live_search',
          keyword: keyword
        },
        success: function success(response) {
          if ($searchWrapper.length) {
            $searchWrapper.addClass('ajax-fs-wrap');
          }
          $resultBox.html(response).css('display', 'grid');
          $scope.find('.toggle--close').on('click', function () {
            $resultBox.hide();
            if ($searchWrapper.length) {
              $searchWrapper.removeClass('ajax-fs-wrap');
            }
          });
        },
        error: function error() {
          $resultBox.html('<div class="error">Something went wrong.</div>').show();
        }
      });
    }

    // Attach debounce to keyup
    $inputField.on('keyup input', debounce(handleSearch, 500));
  };

  // Hook into Elementor
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--blog--search--form.default', WcfAjaxSearch);
  });
})(jQuery);