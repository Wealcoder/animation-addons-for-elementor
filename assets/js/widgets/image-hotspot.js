(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */

    const ImageHotspot = function ($scope, $) {

        // Toggle tooltip on click
        $scope.find('.click .hotspot-icon').on('click', function (e) {
            e.stopPropagation(); // prevent the body click event from firing
            const $thisItem = $(this).closest('.click');
            const $tooltip = $thisItem.find('.tooltip-content');

            // Close all other tooltips
            $scope.find('.tooltip-content').not($tooltip).removeClass('active');

            // Toggle current one
            $tooltip.toggleClass('active');
        });

        // Hide tooltip on outside click
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.click').length) {
                $scope.find('.tooltip-content').removeClass('active');
            }
        });


    };


    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/aae--image-hotspot.default', ImageHotspot);
    });
})(jQuery);
