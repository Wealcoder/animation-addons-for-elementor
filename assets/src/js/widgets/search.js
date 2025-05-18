(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    var WcfAjaxSearch = function WcfTypewriter($scope, $) {

        console.log($scope);


    };

    // Initialize the typewriter effect on Elementor widget load
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--blog--search--form.default', WcfAjaxSearch);
    });
})(jQuery);