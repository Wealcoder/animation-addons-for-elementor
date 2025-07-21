(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    var WcfTypewriter = function WcfTypewriter($scope, $) {

        const typewriterElement = $('.typed_list', $scope);

        // Retrieve the text array from data attribute
        const textArray = typewriterElement.data('text');

        if (!textArray || !Array.isArray(textArray)) {
            console.warn('Typewriter text data is missing or not in array format.');
            return;
        }

        typing(0, textArray);

        function typing(index, text) {
            let textIndex = 1;

            let typingInterval = setInterval(function () {
                if (textIndex < text[index].length + 1) {
                    typewriterElement.text(text[index].substr(0, textIndex));
                    textIndex++;
                } else {
                    setTimeout(function () {
                        deleting(index, text);
                    }, 2000);
                    clearInterval(typingInterval);
                }
            }, 150);
        }

        function deleting(index, text) {
            let textIndex = text[index].length;

            let deletingInterval = setInterval(function () {
                if (textIndex + 1 > 0) {
                    typewriterElement.text(text[index].substr(0, textIndex));
                    textIndex--;
                } else {
                    index = (index + 1) % text.length;  // Loop to the next text in the array
                    typing(index, text);
                    clearInterval(deletingInterval);
                }
            }, 150);
        }

    };

    // Initialize the typewriter effect on Elementor widget load
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--typewriter.default', WcfTypewriter);
    });
})(jQuery);