/* global WCF_ADDONS_JS */
(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const AdvanceSlider = function ($scope, $) {
        const slider = $($('.wcf__slider', $scope)[0]);
        const sliderSettings = $($('.slider-wrapper', $scope)[0]).data('settings') || {};
        let slides = $('.swiper-slide', $scope).clone();
        let Wrapper = $('.swiper-wrapper', $scope);
        sliderSettings.handleElementorBreakpoints = true;

        if (sliderSettings.hasOwnProperty('pagination')) {
            sliderSettings.pagination.renderCustom = function (swiper, current, total) {
                let width = (100  / total) * current;
                return "0"+current + ' <span class="paginate-fill" style="' + '--width:' + width + '%"></span> ' + 0+total;
            };
        }

        const filters = $('.slide-filter li', $scope);
        new elementorFrontend.utils.swiper(slider, sliderSettings).then(newSwiperInstance => newSwiperInstance).then(newSwiperInstance => {

            filters.first().addClass('active');

            filters.on('click', function () {

                if ($(this).hasClass('active')) {
                    return;
                }

                let filter = $(this).data('filter');
                filters.removeClass('active');
                $(this).addClass('active');


                if ('all' === filter) {
                    Wrapper.html(slides)
                    newSwiperInstance.update();
                    newSwiperInstance.updateSlides()
                } else {
                    Wrapper.html(slides.filter(filter))
                    newSwiperInstance.update();
                    newSwiperInstance.updateSlides()
                }
            });

        });
    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--filterable-slider.default', AdvanceSlider);
    });
})(jQuery);
