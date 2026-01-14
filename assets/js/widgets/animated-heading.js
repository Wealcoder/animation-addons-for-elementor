(function () {

    const AnimatedHeading = function (scope) {

        const el = scope.querySelector('.animated--heading');
        if (!el || typeof gsap === 'undefined') return;

        gsap.registerPlugin(ScrollTrigger, SplitText);

        /* ----------------------------------------
         * CLEANUP (Elementor Editor Safe)
         * ---------------------------------------- */
        if (el._loopTl) {
            el._loopTl.kill();
            el._loopTl = null;
        }

        if (el._splitText) {
            el._splitText.revert();
            el._splitText = null;
        }

        if (typeof ScrollTrigger !== 'undefined') {
            ScrollTrigger.getAll().forEach(st => {
                if (st.trigger === el) st.kill();
            });
        }

        /* ----------------------------------------
         * SETTINGS (DATA ATTRIBUTES)
         * ---------------------------------------- */
        const colorMode = el.dataset.colormode || 'gradient';
        const startColor = el.dataset.colorstart || '#ff0000';
        const endColor = el.dataset.colorend || '#0000ff';
        const colors = el.dataset.colors ? JSON.parse(el.dataset.colors) : [];

        const triggerType = el.dataset.trigger || 'viewport'; // page_load | scroll | viewport
        const triggerSelector = el.dataset.triggerselector || '';

        /* ----------------------------------------
         * IMPORTANT: DO NOT HIDE TEXT
         * ---------------------------------------- */
        // No gsap.set(el, { opacity: 0 });
        // Text remains visible immediately.

        /* ----------------------------------------
         * SPLIT TEXT
         * ---------------------------------------- */
        const splitText = new SplitText(el, { type: 'words,chars' });
        el._splitText = splitText;
        const chars = splitText.chars;

        /* ----------------------------------------
         * GRADIENT HELPER
         * ---------------------------------------- */
        const calculateGradientColor = (start, end, ratio) => {
            const hexToRgb = hex => {
                const n = parseInt(hex.replace('#', ''), 16);
                return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
            };
            const rgbToHex = rgb =>
                '#' + rgb.map(v => v.toString(16).padStart(2, '0')).join('');

            const s = hexToRgb(start);
            const e = hexToRgb(end);

            return rgbToHex(
                s.map((v, i) => Math.round(v + ratio * (e[i] - v)))
            );
        };

        /* ----------------------------------------
         * LOOP TIMELINE (ALWAYS PAUSED)
         * ---------------------------------------- */
        const loopTl = gsap.timeline({
            repeat: -1,
            paused: true,
            delay: 0.2
        });
        el._loopTl = loopTl;

        loopTl
            .to(chars, {
                scaleY: 0.6,
                duration: 0.5,
                ease: 'power3.out',
                stagger: 0.04,
                transformOrigin: 'center bottom'
            })
            .to(chars, {
                y: -15,
                duration: 0.8,
                ease: 'elastic.out(1, 0.4)',
                stagger: 0.03
            }, 0.35)
            .to(chars, {
                scaleY: 1,
                duration: 1.2,
                ease: 'elastic.out(2.5, 0.2)',
                stagger: 0.03
            }, 0.35);

        /* ----------------------------------------
         * COLOR MODES
         * ---------------------------------------- */
        switch (colorMode) {

            case 'gradient':
                loopTl.to(chars, {
                    color: (i, el, arr) =>
                        calculateGradientColor(
                            startColor,
                            endColor,
                            i / Math.max(arr.length - 1, 1)
                        ),
                    duration: 0.4,
                    ease: 'power2.out',
                    stagger: 0.03
                }, 0.35);
                break;

            case 'repeater':
                if (colors.length) {
                    loopTl.to(chars, {
                        color: i => colors[i % colors.length],
                        duration: 0.4,
                        ease: 'power2.out',
                        stagger: 0.03
                    }, 0.35);
                }
                break;

            case 'single':
                loopTl.to(chars, {
                    color: startColor,
                    duration: 0.4,
                    ease: 'power2.out',
                    stagger: 0.03
                }, 0.35);
                break;

            case 'random':
                if (colors.length) {
                    loopTl.to(chars, {
                        color: () => colors[Math.floor(Math.random() * colors.length)],
                        duration: 0.4,
                        ease: 'power2.out',
                        stagger: 0.03
                    }, 0.35);
                }
                break;

            case 'wave':
                loopTl.to(chars, {
                    color: (i, el, arr) =>
                        calculateGradientColor(
                            startColor,
                            endColor,
                            Math.abs(Math.sin(i / arr.length * Math.PI))
                        ),
                    duration: 0.6,
                    ease: 'sine.inOut',
                    stagger: 0.04
                }, 0.35);
                break;

            case 'hover_reset':
                if (colors.length) {
                    loopTl
                        .to(chars, {
                            color: i => colors[i % colors.length],
                            duration: 0.4,
                            stagger: 0.03
                        }, 0.35)
                        .to(chars, {
                            color: startColor,
                            duration: 0.4,
                            stagger: 0.02
                        }, '+=0.6');
                }
                break;
            case 'gradient_loop':
                loopTl
                    .to(chars, {
                        color: (i, el, arr) =>
                            calculateGradientColor(startColor, endColor, i / Math.max(arr.length - 1, 1)),
                        duration: 0.4,
                        stagger: 0.03
                    }, 0.35)
                    .to(chars, {
                        color: (i, el, arr) =>
                            calculateGradientColor(endColor, startColor, i / Math.max(arr.length - 1, 1)),
                        duration: 0.4,
                        stagger: 0.03
                    }, '+=0.4');
                break;
            case 'alternate':
                if (colors.length >= 2) {
                    loopTl.to(chars, {
                        color: i => (i % 2 === 0 ? colors[0] : colors[1]),
                        duration: 0.4,
                        stagger: 0.02
                    }, 0.35);
                }
                break;
            case 'center_focus':
                loopTl.to(chars, {
                    color: (i, el, arr) => {
                        const center = arr.length / 2;
                        return Math.abs(i - center) < 1.5 ? endColor : startColor;
                    },
                    duration: 0.4,
                    stagger: 0.02
                }, 0.35);
                break;

            case 'edge_fade':
                loopTl.to(chars, {
                    color: (i, el, arr) =>
                        i === 0 || i === arr.length - 1 ? endColor : startColor,
                    duration: 0.4,
                    stagger: 0.02
                }, 0.35);
                break;

            case 'pulse':
                loopTl
                    .to(chars, {
                        color: endColor,
                        duration: 0.25,
                        stagger: 0.02
                    }, 0.35)
                    .to(chars, {
                        color: startColor,
                        duration: 0.25,
                        stagger: 0.02
                    }, '+=0.2');
                break;
            case 'random_cycle':
                if (colors.length) {
                    loopTl.to(chars, {
                        color: () => colors[Math.floor(Math.random() * colors.length)],
                        duration: 0.3,
                        stagger: 0.02
                    }, 0.35);
                }
                break;

            case 'gradient_pingpong':
                loopTl.to(chars, {
                    color: (i, el, arr) =>
                        calculateGradientColor(startColor, endColor, i / Math.max(arr.length - 1, 1)),
                    duration: 0.5,
                    stagger: 0.003
                }, 0.35)
                    .to(chars, {
                        color: (i, el, arr) =>
                            calculateGradientColor(endColor, startColor, i / Math.max(arr.length - 1, 1)),
                        duration: 0.5,
                        stagger: 0.03
                    }, '+=0.3');

                break;
            case 'spectrum_rotate':
                loopTl.to(chars, {
                    color: i => `hsl(${(i * 40 + loopTl.time() * 120) % 360}, 80%, 60%)`,
                    duration: 0.4,
                    stagger: 0.02
                }, 0.35);
                break;
            case 'glitch_flash':
                if (colors.length >= 2) {
                    loopTl
                        .to(chars, {
                            color: () => colors[Math.floor(Math.random() * colors.length)],
                            duration: 0.12,
                            stagger: 0.01
                        }, 0.35)
                        .to(chars, {
                            color: startColor,
                            duration: 0.2,
                            stagger: 0.02
                        }, '+=0.15');
                }
                break;

        }

        loopTl.to(chars, {
            y: 0,
            duration: 0.8,
            ease: 'back.out(1.7)',
            stagger: 0.03
        }, 0.55);

        /* ----------------------------------------
         * START FUNCTION
         * ---------------------------------------- */
        const startAnimation = () => {
            if (loopTl.isActive() || loopTl.progress() > 0) return;
            loopTl.play();
        };

        /* ----------------------------------------
         * TRIGGER HANDLING (NO OPACITY TWEEN)
         * ---------------------------------------- */
        let triggerElement = el;

        if (triggerSelector) {
            const found = document.querySelector(triggerSelector);
            if (found) triggerElement = found;
        }

        // Page load
        if (triggerType === 'page_load') {
            startAnimation();
            return;
        }

        // Container-based trigger
        if (triggerType === 'scroll') {
            ScrollTrigger.create({
                trigger: triggerElement,
                start: 'top 80%',
                once: true,
                onEnter: startAnimation
            });
            return;
        }

        // Viewport (pause/resume)
        ScrollTrigger.create({
            trigger: el,
            start: 'top 80%',
            onEnter: () => loopTl.play(),
            onLeave: () => loopTl.pause(),
            onEnterBack: () => loopTl.play(),
            onLeaveBack: () => loopTl.pause()
        });
    };

    window.addEventListener('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/wcf--animated-heading.default',
            function ($scope) {
                AnimatedHeading($scope[0]);
            }
        );
    });

})();
