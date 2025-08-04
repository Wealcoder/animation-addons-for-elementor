(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */

    const AdvancedButton = function ($scope, $) {
        $('.btn-hover', $scope).on('mouseenter', function (e) {
            const x = e.pageX - $(this).offset().left;
            const y = e.pageY - $(this).offset().top;

            $(this).find('span').css({
                top: y,
                left: x
            });
        });

        $('.btn-hover', $scope).on('mouseout', function (e) {
            const x = e.pageX - $(this).offset().left;
            const y = e.pageY - $(this).offset().top;

            $(this).find('span').css({
                top: y,
                left: x
            });
        });

    };


    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/aae--advanced-button.default', AdvancedButton);
    });
})(jQuery);
