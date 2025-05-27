(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     */
    var WcfAjaxSearch = function ($scope) {
        const $inputField = $scope.find('.search-field');
        const $resultBox = $scope.find('.aae--live-search-results');
        const $searchWrapper = $('.search--wrapper.style-full-screen .wcf-search-container');
       
        // Debounce function
        function debounce(func, delay) {
            let timeout;
            return function () {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        }

        function handleSearch() {
            const keyword = $inputField.val().trim();
     
            if (keyword.length < 1) {
                $resultBox.hide();
                return;
            }

            $.ajax({
                url: WCF_ADDONS_JS.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'live_search',
                    keyword: keyword
                },
                success: function (response) {
                    if ($searchWrapper.length) {
                        $searchWrapper.addClass('ajax-fs-wrap');
                    }

                    $resultBox.html(response).css('display', 'grid');

                    $scope.find('.toggle--close').on('click', function () {
                        $resultBox.hide();
                        if ($searchWrapper.length) {
                            $searchWrapper.removeClass('ajax-fs-wrap');
                        }
                    });
                },
                error: function () {
                    $resultBox.html('<div class="error">Something went wrong.</div>').show();
                }
            });
        }

        // Attach debounce to keyup
        $inputField.on('keyup input', debounce(handleSearch, 500));
    };

    // Hook into Elementor
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/wcf--blog--search--form.default',
            WcfAjaxSearch
        );
    });
})(jQuery);
