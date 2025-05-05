function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  var ImageCompare = function ImageCompare($scope, $) {
    var $comparisonSlider = $('.wcf--image-compare', $scope);
    var $sliderLeft = $comparisonSlider.find('.slider-left', $scope);
    var $handle = $comparisonSlider.find('.wcf--image-compare-handle', $scope);
    var $caption = $comparisonSlider.find('.slider-caption', $scope);
    var $captionLeft = $comparisonSlider.find('.slider-caption-left', $scope);
    var $captionRight = $comparisonSlider.find('.slider-caption-right', $scope);
    var $comparisonSliderWidth = $comparisonSlider.width();
    var $comparisonSliderHeight = $comparisonSlider.height();
    var $startPosition = $comparisonSliderWidth / 100 * 50;
    var $btnExpandLeft = $('.btn-expand-left', $scope);
    var $btnExpandCenter = $('.btn-expand-center', $scope);
    var $btnExpandRight = $('.btn-expand-right', $scope);

    // GSAP Timeline
    if ((typeof gsap === "undefined" ? "undefined" : _typeof(gsap)) === "object") {
      var tl = gsap.timeline({
        delay: 1
      });

      // Set initial positioning and animations
      tl.set($caption, {
        autoAlpha: 0,
        yPercent: -100
      });
      tl.to($sliderLeft, {
        duration: 0.7,
        width: $startPosition,
        ease: "back.out(1.7)"
      });
      tl.to($handle, {
        duration: 0.7,
        x: $startPosition,
        ease: "back.out(1.7)"
      });
      tl.to($caption, {
        duration: 0.7,
        autoAlpha: 1,
        yPercent: 0,
        ease: "back.inOut(3)",
        stagger: -0.3
      });

      // Draggable
      Draggable.create($handle, {
        type: "x",
        bounds: {
          minX: 0,
          minY: 0,
          maxX: $comparisonSliderWidth,
          maxY: $comparisonSliderHeight
        },
        edgeResistance: 1,
        throwProps: true,
        onDrag: onHandleDrag,
        onLockAxis: function onLockAxis() {
          console.log("onLockAxis");
        }
      });
    }

    // Drag Function
    function onHandleDrag() {
      gsap.set($sliderLeft, {
        width: this.endX
      });

      // Show/hide captions based on the handle's drag position and direction
      if (this.endX >= this.maxX / 2 && this.getDirection() === "right") {
        showLeftCaption();
      }
      if (this.endX <= this.maxX / 2 && this.getDirection() === "left") {
        showRightCaption();
      }
    }

    // Caption Functions
    function showLeftCaption() {
      gsap.to($captionLeft, {
        duration: 0.3,
        autoAlpha: 1,
        yPercent: 0
      });
      gsap.to($captionRight, {
        duration: 0.3,
        autoAlpha: 0,
        yPercent: -100
      });
    }
    function showRightCaption() {
      gsap.to($captionLeft, {
        duration: 0.3,
        autoAlpha: 0,
        yPercent: -100
      });
      gsap.to($captionRight, {
        duration: 0.3,
        autoAlpha: 1,
        yPercent: 0
      });
    }
    function showBothCaptions() {
      gsap.to([$captionLeft, $captionRight], {
        duration: 0.3,
        autoAlpha: 1,
        yPercent: 0
      });
    }

    // Slide Handle Function
    function slideHandleTo(position) {
      var posX = $comparisonSliderWidth / 100 * position;
      gsap.to($sliderLeft, {
        duration: 0.5,
        width: posX,
        ease: "power2.out"
      });
      gsap.to($handle, {
        duration: 0.5,
        x: posX,
        ease: "power2.out"
      });
    }

    // Click Handlers
    $btnExpandLeft.on("click", function () {
      slideHandleTo(100);
      showLeftCaption();
    });
    $btnExpandCenter.on("click", function () {
      slideHandleTo(50);
      showBothCaptions();
    });
    $btnExpandRight.on("click", function () {
      slideHandleTo(0);
      showRightCaption();
    });
  };

  // Make sure you run this code under Elementor.
  $(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--image-compare.default', ImageCompare);
  });
})(jQuery);