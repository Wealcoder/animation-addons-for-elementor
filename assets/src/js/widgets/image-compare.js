(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const ImageCompare = function ($scope, $) {

        const image_compare = $('.wcf--image-compare', $scope);

        image_compare.beforeAfter({
            movable: true,
            clickMove: true,
            alwaysShow: true,
            position: 50,
            opacity: 0.4,
            activeOpacity: 1,
            hoverOpacity: 0.8,
            separatorColor: '#ffffff',
            bulletColor: '#ffffff',
            arrowColor: '#333333',
        });

    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--image-compare.default', ImageCompare);
    });
})(jQuery);