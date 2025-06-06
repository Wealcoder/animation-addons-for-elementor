(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const AnimatedHeading = function ($scope, $) {
        let animated_heading = $('.animated--heading', $scope);
       
        let endTl = gsap.timeline({
            repeat: -1,
            delay: 0.5,
            scrollTrigger: {
                trigger: animated_heading,
                start: 'bottom 100%-=50px'
            }
        });
        gsap.set(animated_heading, {
            opacity: 0
        });
        gsap.to(animated_heading, {
            opacity: 1,
            duration: 1,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: animated_heading,
                start: 'bottom 100%-=50px',
                once: true
            }
        });
        let mySplitText = new SplitText(animated_heading, {type: "words,chars"});
        let chars = mySplitText.chars;

        // Define start and end colors in hex format
        const startColor = animated_heading.attr('data-color-start') || '#ff0000'; // Default start color
        const endColor = animated_heading.attr('data-color-end') || '#0000ff';   // Default end color

        // Function to calculate intermediate colors
        function calculateGradientColor(startColor, endColor, ratio) {
            const hexToRgb = hex => {
                const bigint = parseInt(hex.slice(1), 16);
                return [(bigint >> 16) & 255, (bigint >> 8) & 255, bigint & 255];
            };
            const rgbToHex = rgb => `#${rgb.map(x => x.toString(16).padStart(2, '0')).join('')}`;

            const startRGB = hexToRgb(startColor);
            const endRGB = hexToRgb(endColor);

            const resultRGB = startRGB.map((start, i) => Math.round(start + ratio * (endRGB[i] - start)));
            return rgbToHex(resultRGB);
        }

        // Define animations without Chroma
        endTl.to(chars, {
            duration: 0.5,
            scaleY: 0.6,
            ease: "power3.out",
            stagger: 0.04,
            transformOrigin: 'center bottom'
        });
        endTl.to(chars, {
            y: -15,
            ease: "elastic",
            stagger: 0.03,
            duration: 0.8
        }, 0.5);
        endTl.to(chars, {
            scaleY: 1,
            ease: "elastic.out(2.5, 0.2)",
            stagger: 0.03,
            duration: 1.5
        }, 0.5);
        endTl.to(chars, {
            color: (i, el, arr) => calculateGradientColor(startColor, endColor, i / arr.length),
            ease: "power2.out",
            stagger: 0.03,
            duration: 0.3
        }, 0.5);
        endTl.to(chars, {
            y: 0,
            ease: "back",
            stagger: 0.03,
            duration: 0.8
        }, 0.7);
        endTl.to(chars, {
            color: endColor,
            duration: 1.4,
            stagger: 0.05
        });
    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--animated-heading.default', AnimatedHeading);
    });
})(jQuery);
