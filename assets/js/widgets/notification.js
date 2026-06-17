/* global WCF_ADDONS_JS */
(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const AAENotification = function ($scope, $) {
        const close = $('.aae--notification .close-icon', $scope);
        const notify = $('.aae--notification', $scope);

        close.on('click', function () {
            notify.hide();
        });
    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/aae--notification.default', AAENotification);
    });
})(jQuery);
