/* global WCF_ADDONS_JS */
(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */

    const getSliderOptions = function ($scope) {
        const slider = $($('.helo_team_slider', $scope)[0]);
        const sliderSettings = $($('.helo_team_wrapper', $scope)[0]).data('settings') || {};
        sliderSettings.handleElementorBreakpoints = true;

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
                        '<span class="dash">/</span>' +
                        '<span class="' + totalClass + '"></span>';
                }
            }
        }

        //remove the attribute after getting the slider settings
        // $($('.helo_team_wrapper', $scope)[0]).removeAttr('data-settings');

        return {slider: slider, options: sliderSettings};

    }


    const getThumbSliderOptions = function ($scope) {
        const slider = $('.team_thumb_slider', $scope);
        const sliderSettings = $('.team_thumb_wrapper', $scope).data('settings') || {};
        sliderSettings.handleElementorBreakpoints = true;

        //remove the attribute after getting the slider settings
        $('.team_thumb_wrapper', $scope).removeAttr('data-settings');

        return {thumbSlider: slider, thumbOptions: sliderSettings};
    }


    const HeloteamSlider = function ($scope, $) {
        const {thumbSlider, thumbOptions} = getThumbSliderOptions($scope);
        const {slider, options} = getSliderOptions($scope);

        //if thumb slider enable
        if (thumbSlider.length) {
            new elementorFrontend.utils.swiper(thumbSlider, thumbOptions).then(newSwiperInstance => newSwiperInstance).then((thumbSliderInstance) => {
                new elementorFrontend.utils.swiper(slider, options).then(newSwiperInstance => newSwiperInstance).then(( newSwiperInstance) => {
                    newSwiperInstance.controller.control = thumbSliderInstance;
                    thumbSliderInstance.controller.control = newSwiperInstance;
                });

            });
        } else {
            new elementorFrontend.utils.swiper(slider, options).then(newSwiperInstance => newSwiperInstance);
        }
    };



    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/helo--team-slider.default', HeloteamSlider);
    });
})(jQuery);
