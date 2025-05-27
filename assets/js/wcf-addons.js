/* global WCF_ADDONS_JS */

(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    var Modules = elementorModules.frontend.handlers.Base;
    var contact_form_7 = function contact_form_7($scope) {
      var submit_btn = $('.wpcf7-submit', $scope);
      var classes = submit_btn.attr('class');
      classes += ' wcf-btn-default ' + $('.wcf--form-wrapper', $scope).attr('btn-hover');
      submit_btn.replaceWith(function () {
        return $('<button/>', {
          html: $('.btn-icon').html() + submit_btn.attr('value'),
          "class": classes,
          type: 'submit'
        });
      });
    };
    var Countdown = Modules.extend({
      bindEvents: function bindEvents() {
        this.run();
      },
      run: function run() {
        var _this = this;
        // Update the count down every 1 second
        var x = setInterval(function () {
          _this.count_down(x);
        }, 1000);
        this.count_down(x);
      },
      count_down: function count_down(x) {
        // Set the date we're counting down to
        var countDownDate = new Date(this.getElementSettings('countdown_timer_due_date')).getTime();

        // Get today's date and time
        var now = new Date().getTime();

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor(distance % (1000 * 60 * 60 * 24) / (1000 * 60 * 60));
        var minutes = Math.floor(distance % (1000 * 60 * 60) / (1000 * 60));
        var seconds = Math.floor(distance % (1000 * 60) / 1000);

        // If the count down is over, write some text
        if (distance < 0) {
          clearInterval(x);
          this.findElement('.wcf--countdown').html(this.time_finish_content());
        } else {
          this.findElement('.wcf--countdown').html(this.timer_content({
            'days': days,
            'hours': hours,
            'minutes': minutes,
            'seconds': seconds
          }));
        }
      },
      timer_content: function timer_content() {
        var _this2 = this;
        var times = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
        if (0 === times.length) {
          return;
        }
        var time_content = '';
        $.each(times, function (index, time) {
          var title = _this2.getElementSettings("countdown_timer_".concat(index, "_label"));
          time_content += "<div class=\"timer-content timer-item-".concat(index, " \"><span class=\"time-count ").concat(index, "-count\">").concat(time, "</span><span class=\"time-title ").concat(index, "-title\">").concat(title, "</span></div>");
        });
        return time_content;
      },
      time_finish_content: function time_finish_content() {
        var title = this.getElementSettings('time_expire_title');
        var description = this.getElementSettings('time_expire_desc');
        var finish_content = '<div class="countdown-expire">';
        if (title) {
          finish_content += "<div class=\"countdown-expire-title\">".concat(title, "</div>");
        }
        if (description) {
          finish_content += "<div class=\"countdown-expire-desc\">".concat(description, "</div>");
        }
        finish_content += '</div>';
        return finish_content;
      }
    });
    elementorFrontend.hooks.addAction("frontend/element_ready/wcf--contact-form-7.default", contact_form_7);
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--countdown.default', function ($scope) {
      elementorFrontend.elementsHandler.addHandler(Countdown, {
        $element: $scope
      });
    });
    var Search = function Search($scope) {
      var searchWrapper = $('.search--wrapper', $scope);
      var toggle_open = $('.toggle--open', $scope);
      var toggle_close = $('.toggle--close', $scope);
      toggle_open.on('click', function (e) {
        searchWrapper.addClass('search-visible');
      });
      toggle_close.on('click', function (e) {
        searchWrapper.removeClass('search-visible');
      });
      $("input", $scope).focus(function () {
        $(".wcf-search-form", $scope).addClass('wcf-search-form--focus');
      });
      $("input", $scope).focusout(function () {
        $(".wcf-search-form", $scope).removeClass('wcf-search-form--focus');
      });
    };
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--blog--search--form.default', Search);
  });
})(jQuery);