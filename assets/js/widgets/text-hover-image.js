( function( $ ) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const WcfTextHoverImage = function ($scope, $) {

        const hover_text = $('.hover_text', $scope)[0];


        if (hover_text) {
            function hoverImgFunc(event, hover_text) {
                const contentBox = hover_text.getBoundingClientRect();
                const dx = event.clientX - contentBox.x;
                const dy = event.clientY - contentBox.y;
                hover_text.children[0].style.transform = `translate(${dx}px, ${dy}px)`;
            }

            hover_text.addEventListener("mousemove", (event) => {
                setInterval(hoverImgFunc(event, hover_text), 1000);
            });
        }
    };

    // Make sure you run this code under Elementor.
    $( window ).on( 'elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wcf--t-h-image.default', WcfTextHoverImage );
    } );
} )( jQuery );