/* global WCF_ADDONS_JS */
(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  var WcfProgressbar = function WcfProgressbar($scope, $) {
    var progressbarWrap = $('.wcf__progressbar ', $scope);
    var progressbar = progressbarWrap.find('.progressbar');
    var settings = progressbarWrap.data('settings');
    var percent = settings['percentage'] / 100;
    var dotAnimation = function dotAnimation() {
      var count = 0;
      var animationDots = Math.floor(percent * 100 / 20);
      var animation = setInterval(function () {
        if (count >= animationDots) {
          clearInterval(animation);
        } else {
          $(progressbar.find('.dot')[count]).addClass('active');
          count++;
        }
      }, 500);
    };
    var progressAnimation = function progressAnimation() {
      var progressBar = null;
      var progressbarOptions = {
        strokeWidth: settings['stroke-width'],
        trailWidth: settings['trail-width'],
        color: settings['color'],
        trailColor: settings['trail-color'],
        duration: 1400
      };

      //show percentage
      if ('show' === settings['display-percentage']) {
        progressbarOptions['text'] = {
          value: settings['percentage'] + '%'
        };
        if ('line' === settings['progress-type']) {
          var rightValue = 100 - settings['percentage'];
          progressbarOptions['text']['style'] = {
            position: 'absolute',
            right: rightValue + '%'
          };
        }
      }
      if ('line' === settings['progress-type']) {
        progressBar = new ProgressBar.Line(progressbar[0], progressbarOptions);
      }
      if ('circle' === settings['progress-type']) {
        progressBar = new ProgressBar.Circle(progressbar[0], progressbarOptions);
      }
      return progressBar;
    };
    var createObserver = function createObserver() {
      var options = {
        root: null,
        threshold: 0,
        rootMargin: '0px'
      };
      return new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            if ('dot' === settings['progress-type']) {
              dotAnimation();
            } else {
              progressAnimation().animate(percent);
            }
            observer.unobserve(entry.target);
          }
        });
      }, options);
    };
    var observer = createObserver();
    observer.observe(progressbarWrap[0]);

    //remove the attribute after getting the progressbar settings
    progressbarWrap.removeAttr('data-settings');
  };

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--progressbar.default', WcfProgressbar);
  });
})(jQuery);