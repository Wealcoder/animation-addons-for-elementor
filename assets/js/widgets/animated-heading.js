(function () {

    /**
     * @param {HTMLElement} scope Elementor widget wrapper element
     */
    const AnimatedHeading = function (scope) {

        const animatedHeading = scope.querySelector('.animated--heading');
        console.log('Animated Heading Scope:', scope);
        if (!animatedHeading) {
            return;
        }

        // Initial state
        gsap.set(animatedHeading, { opacity: 0 });

        // Fade-in on scroll (once)
        gsap.to(animatedHeading, {
            opacity: 1,
            duration: 1,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: animatedHeading,
                start: 'bottom 100%-=50px',
                once: true
            }
        });

        // Split text
        const splitText = new SplitText(animatedHeading, {
            type: 'words,chars'
        });

        const chars = splitText.chars;

        // Colors from attributes
        const startColor = animatedHeading.dataset.colorStart || '#ff0000';
        const endColor = animatedHeading.dataset.colorEnd || '#0000ff';

        // Gradient color calculator
        const calculateGradientColor = (start, end, ratio) => {

            const hexToRgb = (hex) => {
                const bigint = parseInt(hex.replace('#', ''), 16);
                return [
                    (bigint >> 16) & 255,
                    (bigint >> 8) & 255,
                    bigint & 255
                ];
            };

            const rgbToHex = (rgb) =>
                '#' + rgb.map(v => v.toString(16).padStart(2, '0')).join('');

            const startRGB = hexToRgb(start);
            const endRGB = hexToRgb(end);

            const result = startRGB.map((val, i) =>
                Math.round(val + ratio * (endRGB[i] - val))
            );

            return rgbToHex(result);
        };

        // Infinite animation timeline
        const endTl = gsap.timeline({
            repeat: -1,
            delay: 0.5,
            scrollTrigger: {
                trigger: animatedHeading,
                start: 'bottom 100%-=50px'
            }
        });

        endTl.to(chars, {
            scaleY: 0.6,
            duration: 0.5,
            ease: 'power3.out',
            stagger: 0.04,
            transformOrigin: 'center bottom'
        });

        endTl.to(chars, {
            y: -15,
            duration: 0.8,
            ease: 'elastic',
            stagger: 0.03
        }, 0.5);

        endTl.to(chars, {
            scaleY: 1,
            duration: 1.5,
            ease: 'elastic.out(2.5, 0.2)',
            stagger: 0.03
        }, 0.5);

        endTl.to(chars, {
            color: (i, el, arr) =>
                calculateGradientColor(startColor, endColor, i / arr.length),
            duration: 0.3,
            ease: 'power2.out',
            stagger: 0.03
        }, 0.5);

        endTl.to(chars, {
            y: 0,
            duration: 0.8,
            ease: 'back',
            stagger: 0.03
        }, 0.7);

        endTl.to(chars, {
            color: endColor,
            duration: 1.4,
            stagger: 0.05
        });
    };

    // Elementor hook (PURE JS)
    window.addEventListener('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/wcf--animated-heading.default',
            function ($scope) {
                AnimatedHeading($scope[0]);
            }
        );
    });

})();
