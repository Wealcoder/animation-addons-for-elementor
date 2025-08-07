/* global WCF_ADDONS_JS */
(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const AdvanceAccordion = function ($scope, $) {
        let item = $('.tab-title', $scope);
      
        if ($scope.hasClass('accordion-first-item-yes')) {
            item.first().parent().toggleClass('element-active');
            item.first().parent().find('.tab-content').show();
        }

        item.on('click', function () {
            $(this).parent().toggleClass('element-active');
            $(this).parent().find('.tab-content').slideToggle("medium");

            item.not($(this)).parent().find('.tab-content').slideUp('medium');
        });
    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--a-accordion.default', AdvanceAccordion);
    });
})(jQuery);
