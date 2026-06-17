/* global WCF_ADDONS_JS */
(function ($) {

    const AdvanceAccordion = function ($scope, $) {

        let item = $('.tab-title', $scope);

        // First item active
        if ($scope.hasClass('accordion-first-item-yes')) {
            item.first().parent().addClass('element-active');
            item.first().parent().find('.tab-content').show();
        }

        item.on('click', function () {
             console.log('clicked');
            let currentItem = $(this).parent();

            // Remove active class + hide all others
            item.not($(this)).parent().removeClass('element-active');
            item.not($(this)).parent().find('.tab-content').slideUp('medium');

            // Toggle current item
            currentItem.toggleClass('element-active');
            currentItem.find('.tab-content').slideToggle('medium');
        });
    };

    // Elementor hook
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/wcf--a-accordion.default',
            AdvanceAccordion
        );
    });

})(jQuery);