function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */

  var getSliderOptions = function getSliderOptions($scope) {
    var slider = $($('.wcf__slider', $scope)[0]);
    var slexist = $scope.find('.wcf__slider').length;
    var sliderSettings = $($('.wcf__slider-wrapper, .wcf__t_slider-wrapper', $scope)[0]).data('settings') || {};
    sliderSettings.handleElementorBreakpoints = true;

    //navigation
    if (sliderSettings.hasOwnProperty('navigation')) {
      var next = $('.wcf-arrow-next', $scope)[$('.wcf-arrow-next', $scope).length - 1];
      var prev = $('.wcf-arrow-prev', $scope)[$('.wcf-arrow-prev', $scope).length - 1];
      sliderSettings.navigation.nextEl = next;
      sliderSettings.navigation.prevEl = prev;
    }

    //pagination fractions
    if (sliderSettings.hasOwnProperty('pagination')) {
      sliderSettings.pagination.el = $('.swiper-pagination', $scope)[$('.swiper-pagination', $scope).length - 1];
      if (sliderSettings.pagination.hasOwnProperty('type') && 'fraction' === sliderSettings.pagination.type) {
        sliderSettings.pagination.formatFractionCurrent = function (number) {
          return ('0' + number).slice(-2);
        };
        sliderSettings.pagination.formatFractionTotal = function (number) {
          return ('0' + number).slice(-2);
        };
        sliderSettings.pagination.renderFraction = function (currentClass, totalClass) {
          return '<span class="' + currentClass + '"></span>' + '<span class="mid-line"></span>' + '<span class="' + totalClass + '"></span>';
        };
      }
    }

    //remove the attribute after getting the slider settings
    $($('.wcf__slider-wrapper', $scope)[0]).removeAttr('data-settings');
    return {
      slider: slider,
      options: sliderSettings,
      slider_exist: slexist
    };
  };
  var getThumbSliderOptions = function getThumbSliderOptions($scope) {
    var slider = $('.wcf__thumb_slider', $scope);
    var sliderSettings = $('.wcf__thumb-slider-wrapper', $scope).data('settings') || {};
    sliderSettings.handleElementorBreakpoints = true;

    //remove the attribute after getting the slider settings
    $('.wcf__thumb-slider-wrapper', $scope).removeAttr('data-settings');
    return {
      thumbSlider: slider,
      thumbOptions: sliderSettings
    };
  };
  var Slider = function Slider($scope, $) {
    var _getThumbSliderOption = getThumbSliderOptions($scope),
      thumbSlider = _getThumbSliderOption.thumbSlider,
      thumbOptions = _getThumbSliderOption.thumbOptions;
    var _getSliderOptions = getSliderOptions($scope),
      slider = _getSliderOptions.slider,
      options = _getSliderOptions.options,
      slider_exist = _getSliderOptions.slider_exist;

    //if thumb slider enable
    if (thumbSlider.length) {
      new elementorFrontend.utils.swiper(thumbSlider, thumbOptions).then(function (newSwiperInstance) {
        return newSwiperInstance;
      }).then(function (thumbSliderInstance) {
        new elementorFrontend.utils.swiper(slider, options).then(function (newSwiperInstance) {
          return newSwiperInstance;
        }).then(function (newSwiperInstance) {
          newSwiperInstance.controller.control = thumbSliderInstance;
          thumbSliderInstance.controller.control = newSwiperInstance;
        });
      });
    } else {
      if (slider_exist) {
        new elementorFrontend.utils.swiper(slider, options).then(function (newSwiperInstance) {
          return newSwiperInstance;
        });
      }
    }
  };

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    var WcfSliderWidgets = elementorFrontend.hooks.applyFilters('wcf/widgets/slider', {
      // Add Widget name Here
      'theme-post-image': [],
      'testimonial': [],
      'testimonial2': [],
      'testimonial3': [],
      'a-testimonial': [],
      'event-slider': [],
      'image-box-slider': [],
      'video-box-slider': [],
      'brand-slider': [],
      'content-slider': [],
      'a-portfolio': ['skin-portfolio-base', 'skin-portfolio-one', 'skin-portfolio-two', 'skin-portfolio-three', 'skin-portfolio-four', 'skin-portfolio-five', 'skin-portfolio-six', 'skin-portfolio-seven', 'skin-portfolio-eight', 'skin-portfolio-nine']
    });
    $.each(WcfSliderWidgets, function (widget, $skins) {
      elementorFrontend.hooks.addAction("frontend/element_ready/wcf--".concat(widget, ".default"), Slider);

      //if widget has skin
      if ($skins.length) {
        var _iterator = _createForOfIteratorHelper($skins),
          _step;
        try {
          for (_iterator.s(); !(_step = _iterator.n()).done;) {
            var $skin = _step.value;
            elementorFrontend.hooks.addAction("frontend/element_ready/wcf--".concat(widget, ".").concat($skin), Slider);
          }
        } catch (err) {
          _iterator.e(err);
        } finally {
          _iterator.f();
        }
      }
    });
  });
})(jQuery);