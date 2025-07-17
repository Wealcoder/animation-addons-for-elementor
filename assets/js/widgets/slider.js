(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */

    const getSliderOptions = function ($scope) {
        const slider = $($('.wcf__slider', $scope)[0]);
      
        const slexist = $scope.find('.wcf__slider').length;
        const sliderSettings = $($('.wcf__slider-wrapper, .wcf__t_slider-wrapper', $scope)[0]).data('settings') || {};
        sliderSettings.handleElementorBreakpoints = true
      
        //navigation
        if (sliderSettings.hasOwnProperty('navigation')) {
            const next = $('.wcf-arrow-next', $scope)[$('.wcf-arrow-next', $scope).length - 1]
            const prev = $('.wcf-arrow-prev', $scope)[$('.wcf-arrow-prev', $scope).length - 1]
            sliderSettings.navigation.nextEl = next;
            sliderSettings.navigation.prevEl = prev;
        }

        //pagination fractions
        if (sliderSettings.hasOwnProperty('pagination')) {
            sliderSettings.pagination.el = $('.swiper-pagination', $scope)[$('.swiper-pagination', $scope).length - 1];

            if (sliderSettings.pagination.hasOwnProperty('type') && 'fraction' === sliderSettings.pagination.type) {
                sliderSettings.pagination.formatFractionCurrent = function (number) {
                    return ('0' + number).slice(-2);
                }
                sliderSettings.pagination.formatFractionTotal = function (number) {
                    return ('0' + number).slice(-2);
                }
                sliderSettings.pagination.renderFraction = function (currentClass, totalClass) {
                    return '<span class="' + currentClass + '"></span>' +
                        '<span class="mid-line"></span>' +
                        '<span class="' + totalClass + '"></span>';
                }
            }
        }

        //remove the attribute after getting the slider settings
        $($('.wcf__slider-wrapper', $scope)[0]).removeAttr('data-settings');

        return {slider: slider, options: sliderSettings, slider_exist: slexist};
    }

    const getThumbSliderOptions = function ($scope) {
        const slider = $('.wcf__thumb_slider', $scope);
        const sliderSettings = $('.wcf__thumb-slider-wrapper', $scope).data('settings') || {};
        sliderSettings.handleElementorBreakpoints = true

        //remove the attribute after getting the slider settings
        $('.wcf__thumb-slider-wrapper', $scope).removeAttr('data-settings');

        return {thumbSlider: slider, thumbOptions: sliderSettings};
    }

    const Slider = function ($scope, $) {
     
        const {thumbSlider, thumbOptions} = getThumbSliderOptions($scope);
        const {slider, options, slider_exist} = getSliderOptions($scope);
       
        //if thumb slider enable
        if (thumbSlider.length) {
         
            new elementorFrontend.utils.swiper(thumbSlider, thumbOptions).then(newSwiperInstance => newSwiperInstance).then((thumbSliderInstance) => {

                new elementorFrontend.utils.swiper(slider, options).then(newSwiperInstance => newSwiperInstance).then((newSwiperInstance) => {

                    newSwiperInstance.controller.control = thumbSliderInstance;
                    thumbSliderInstance.controller.control = newSwiperInstance;
                });

            });
        } else {
          
            if (slider_exist) {
                new elementorFrontend.utils.swiper(slider, options).then(newSwiperInstance => {
                   return newSwiperInstance;
                });
            }
        }

    }

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {

        const WcfSliderWidgets = elementorFrontend.hooks.applyFilters('wcf/widgets/slider', {
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
            'a-portfolio': [
                'skin-portfolio-base',
                'skin-portfolio-one',
                'skin-portfolio-two',
                'skin-portfolio-three',
                'skin-portfolio-four',
                'skin-portfolio-five',
                'skin-portfolio-six',
                'skin-portfolio-seven',
                'skin-portfolio-eight',
                'skin-portfolio-nine',
            ],
        });

        $.each(WcfSliderWidgets, function (widget, $skins) {

            elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--${widget}.default`, Slider);
           
            //if widget has skin
            if ($skins.length) {
                for (const $skin of $skins) {
                    elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--${widget}.${$skin}`, Slider);
                }
            }
        });

    });
})(jQuery);
