/* global WCF_ADDONS_JS */
(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const Category_Slider = function ($scope, $) {
        const slider = $($('.aae--category-slider', $scope)[0]);
        const sliderSettings = $($('.aae--category-slider-wrapper', $scope)[0]).data('settings') || {};
        sliderSettings.handleElementorBreakpoints = true;

        if (sliderSettings.hasOwnProperty('pagination')) {
            sliderSettings.pagination.renderCustom = function (swiper, current, total) {
                let width = (100 / total) * current;
                return "0" + current + ' <span class="paginate-fill" style="' + '--width:' + width + '%"></span> ' + 0 + total;
            };
        }

        new elementorFrontend.utils.swiper(slider, sliderSettings).then(newSwiperInstance => newSwiperInstance);
    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/aae--category-slider.default', Category_Slider);
    });
})(jQuery);
