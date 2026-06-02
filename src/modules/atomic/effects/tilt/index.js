const {
    getGsap,
    configFor,
    pickConfigResponsive
} = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_TILT';
const PLAYED_KEY = '__aaeTiltPlayed';
const DISPOSE_KEY = '__aaeTiltDispose';

function read(el) {
    const cfg = configFor(el, MAP);

    if (!cfg) return null;

    const enabled = pickConfigResponsive(cfg, 'enabled');
    if (!enabled || enabled === 'false' || enabled === 'no') {
        return null;
    }

    const val = (k, fallback) => {
        const v = pickConfigResponsive(cfg, k);
        return (v !== undefined && v !== '') ? v : fallback;
    };

    const isTrue = (k, fallback) => {
        const v = val(k, fallback);
        return v === true || v === 'true' || v === 'yes';
    };

    return {
        enabled: true,
        max: Number(val('max', 15)),
        speed: Number(val('speed', 300)),
        scale: Number(val('scale', 1)),
        perspective: Number(val('perspective', 1000)),
        glare: isTrue('glare', false),
        maxGlare: Number(val('maxGlare', 0.5)),
        reset: isTrue('reset', true),
        transition: isTrue('transition', true),
        gyroscope: isTrue('gyroscope', true),
    };
}

function bind(el, config) {
    unbind(el);
    
    if (!config) return;

    const gsap = getGsap();
    if (!gsap) {
        console.warn('GSAP is required for tilt effect');
        return;
    }

    const max = config.max;
    const speed = config.speed / 1000;
    const scale = config.scale;
    const perspective = config.perspective;
    const glare = config.glare;
    const maxGlare = config.maxGlare;
    const reset = config.reset;
    const transition = config.transition;
    const gyroscope = config.gyroscope;

    let isMouseOver = false;

    // Handle Glare Element Injection
    let glareContainer = null;
    let glareInner = null;

    if (glare) {
        glareContainer = document.createElement('div');
        glareContainer.className = 'aae-tilt-glare';
        glareContainer.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;overflow:hidden;pointer-events:none;z-index:9999;border-radius:inherit;';
        
        glareInner = document.createElement('div');
        glareInner.className = 'aae-tilt-glare-inner';
        glareInner.style.cssText = 'position:absolute;top:50%;left:50%;width:200%;height:200%;transform:translate(-50%,-50%);pointer-events:none;opacity:0;';
        
        glareContainer.appendChild(glareInner);
        el.appendChild(glareContainer);
    }

    // Set perspective using GSAP
    gsap.set(el, { transformPerspective: perspective, transformStyle: "preserve-3d" });

    function onMouseEnter() {
        isMouseOver = true;
        if (glareInner) {
            gsap.to(glareInner, { opacity: maxGlare, duration: transition ? speed : 0.1 });
        }
    }

    function onMouseMove(event) {
        const rect = el.getBoundingClientRect();
        const width = rect.width;
        const height = rect.height;
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        // Normalize mouse positions between -0.5 and 0.5
        const tiltX = (x / width) - 0.5;
        const tiltY = (y / height) - 0.5;

        // Map tilt to rotation angles
        const rotateX = -(tiltY * max);
        const rotateY = (tiltX * max);

        gsap.to(el, {
            rotateX: rotateX,
            rotateY: rotateY,
            scale: scale,
            duration: transition ? speed : 0.1,
            ease: "power2.out",
            overwrite: "auto"
        });

        if (glareInner) {
            const glareX = (x / width) * 100;
            const glareY = (y / height) * 100;
            gsap.set(glareInner, {
                background: `radial-gradient(circle at ${glareX}% ${glareY}%, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 80%)`
            });
        }
    }

    function onMouseLeave() {
        isMouseOver = false;

        if (reset) {
            gsap.to(el, {
                rotateX: 0,
                rotateY: 0,
                scale: 1,
                duration: transition ? speed : 0.1,
                ease: "power2.out",
                overwrite: "auto"
            });
        }

        if (glareInner) {
            gsap.to(glareInner, { opacity: 0, duration: transition ? speed : 0.1 });
        }
    }

    function onDeviceOrientation(event) {
        if (isMouseOver) return;

        const beta = event.beta;
        const gamma = event.gamma;

        if (beta === null || gamma === null) return;

        // Constrain orientation bounds to sensible viewing angles (-30 to 30 degrees)
        const xAngle = Math.min(Math.max(beta, -30), 30);
        const yAngle = Math.min(Math.max(gamma, -30), 30);

        // Map angles to tilt percentage (-0.5 to 0.5)
        const tiltX = yAngle / 30; // Rotate around Y axis
        const tiltY = xAngle / 30; // Rotate around X axis

        const rotateX = -(tiltY * max);
        const rotateY = (tiltX * max);

        gsap.to(el, {
            rotateX: rotateX,
            rotateY: rotateY,
            scale: scale,
            duration: transition ? speed : 0.1,
            ease: "power2.out",
            overwrite: "auto"
        });

        if (glareInner) {
            gsap.to(glareInner, { opacity: maxGlare, duration: 0.1 });
            const glareX = (tiltX + 0.5) * 100;
            const glareY = (tiltY + 0.5) * 100;
            gsap.set(glareInner, {
                background: `radial-gradient(circle at ${glareX}% ${glareY}%, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 80%)`
            });
        }
    }

    // Attach event listeners
    el.addEventListener('mouseenter', onMouseEnter);
    el.addEventListener('mousemove', onMouseMove);
    el.addEventListener('mouseleave', onMouseLeave);

    if (gyroscope) {
        window.addEventListener('deviceorientation', onDeviceOrientation);
    }

    // Set disposal function
    el[DISPOSE_KEY] = () => {
        el.removeEventListener('mouseenter', onMouseEnter);
        el.removeEventListener('mousemove', onMouseMove);
        el.removeEventListener('mouseleave', onMouseLeave);

        if (gyroscope) {
            window.removeEventListener('deviceorientation', onDeviceOrientation);
        }

        if (glareContainer && el.contains(glareContainer)) {
            el.removeChild(glareContainer);
        }

        const gsap = getGsap();
        if (gsap) {
            gsap.killTweensOf(el);
            if (glareInner) gsap.killTweensOf(glareInner);
            gsap.set(el, { clearProps: "transform,transformPerspective,transformStyle" });
        }
    };
}

function unbind(el) {
    if (el[PLAYED_KEY]) {
        try {
            el[PLAYED_KEY].kill?.();
        } catch (_) {}
        delete el[PLAYED_KEY];
    }

    const dispose = el[DISPOSE_KEY];
    if (typeof dispose === 'function') {
        try {
            dispose();
        } catch (_) {}
    }
    el[DISPOSE_KEY] = null;
}

function play(el, config) {
    console.log(config);
    const gsap = getGsap();
    if (!gsap) {
        unbind(el);
        bind(el, config);
        return;
    }

    unbind(el);
    bind(el, config);

    const max = config.max;
    const speed = config.speed;
    const scale = config.scale;
    const perspective = config.perspective;
    const glare = config.glare;
    const maxGlare = config.maxGlare;

    let glareInner = null;
    const glareContainer = el.querySelector(':scope > .aae-tilt-glare');
    if (glareContainer) {
        glareInner = glareContainer.querySelector('.aae-tilt-glare-inner');
    }

    el.style.transition = 'none';
    gsap.set(el, { transformPerspective: perspective });

    const tl = gsap.timeline({
        onComplete: () => {
            if (glareInner) {
                gsap.to(glareInner, { opacity: 0, duration: speed / 1000 });
            }
            gsap.to(el, {
                rotateX: 0,
                rotateY: 0,
                scale: 1,
                duration: speed / 1000,
                ease: 'power2.out',
                clearProps: 'transform',
                onComplete: () => {
                    delete el[PLAYED_KEY];
                }
            });
        }
    });

    el[PLAYED_KEY] = tl;

    // Swing to top-right
    tl.to(el, {
        rotateX: -(max * 0.5),
        rotateY: (max * 0.5),
        scale: scale,
        duration: speed / 1000,
        ease: 'power2.out'
    });

    if (glareInner) {
        tl.to(glareInner, {
            opacity: maxGlare * 0.7,
            backgroundImage: `radial-gradient(circle at 75% 75%, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 80%)`,
            duration: speed / 1000,
            ease: 'power2.out'
        }, 0);
    }

    // Swing to bottom-left
    tl.to(el, {
        rotateX: (max * 0.5),
        rotateY: -(max * 0.5),
        duration: (speed * 2) / 1000,
        ease: 'power2.inOut'
    });

    if (glareInner) {
        tl.to(glareInner, {
            backgroundImage: `radial-gradient(circle at 25% 25%, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 80%)`,
            duration: (speed * 2) / 1000,
            ease: 'power2.inOut'
        }, '>');
    }
}

window.AAEADDON.register({
    name: 'tilt',
    mapName: MAP,
    boundFlag: 'aae-tilt-bound',
    playedKey: PLAYED_KEY,
    read,
    bind,
    unbind,
    play,
    reset: unbind,
});

