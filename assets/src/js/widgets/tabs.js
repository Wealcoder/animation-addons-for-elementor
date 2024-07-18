( function( $ ) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */

    const Tabs = function ($scope){
        //Default Action
        $('.tab-content', $scope).hide(); //Hide all content
        $('.tab-title:first', $scope).addClass("active").show();
        $('.tab-content:first', $scope).show();

        //mobile title
        const activeMobileTitle = $('.tab-title:first', $scope).attr("aria-controls");
        $(`.tab-title[aria-controls='${activeMobileTitle}']`, $scope).addClass("active");

        //On Click Event
        $('.tab-title', $scope).click(function () {

            if ( $(this).hasClass("active") ){
                return ;
            }

            $('.tab-title', $scope).removeClass("active");
            $(this).addClass("active");
            $('.tab-content', $scope).hide();
            const activeTab = $(this).attr("aria-controls");

            $(`.tab-content[id='${activeTab}']`, $scope).fadeIn();
            $(`.tab-title[aria-controls='${activeTab}']`, $scope).addClass("active");
            return false;
        });
    }

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        const tab_widgets = [
            'tabs',
            'services-tab',
        ]

        for (const widget of tab_widgets) {
            elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--${widget}.default`, Tabs);
        }
    });
} )( jQuery );