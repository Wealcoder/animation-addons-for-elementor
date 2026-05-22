/* global WCF_ADDONS_JS */
(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const AdvanceAccordion = function ($scope, $) {
        let item = $('.tab-title', $scope);
      
        if ($scope.hasClass('accordion-first-item-yes')) {
            item.first().parent().addClass('element-active');
            item.first().attr('aria-expanded', 'true');
            item.first().parent().find('.tab-content').show();
        }

        item.on('click', function () {
            const $current = $(this);
            const $parent = $current.parent();
            const willOpen = !$parent.hasClass('element-active');

            item.not($current).each(function () {
                const $other = $(this);
                $other.parent().removeClass('element-active');
                $other.attr('aria-expanded', 'false');
                $other.parent().find('.tab-content').slideUp('medium');
            });

            $parent.toggleClass('element-active', willOpen);
            $current.attr('aria-expanded', willOpen ? 'true' : 'false');
            $parent.find('.tab-content').slideToggle('medium');
        });
    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--a-accordion.default', AdvanceAccordion);
    });
})(jQuery);
