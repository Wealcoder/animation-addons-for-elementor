( function( $ ) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const WcfCounter = function ($scope, $) {

        const $counter = $('.wcf--counter-number', $scope);

        this.intersectionObserver = elementorModules.utils.Scroll.scrollObserver({
            callback: (event) => {

                if (event.isInViewport) {
                    this.intersectionObserver.unobserve($counter[0]);

                    const data = $counter.data(),
                        decimalDigits = data.toValue.toString().match(/\.(.*)/);

                    if (decimalDigits) {
                        data.rounding = decimalDigits[1].length;
                    }

                    $counter.numerator(data);
                }
            },
        });

        this.intersectionObserver.observe($counter[0]);

    };

    // Make sure you run this code under Elementor.
    $( window ).on( 'elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wcf--counter.default', WcfCounter );
    } );
} )( jQuery );