const {
    getGsap,
    configFor,
    pickConfigResponsive,
    currentBreakpoint,
    BP_CASCADE,
} = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_TILT';
const PLAYED_KEY = '__aaeTiltPlayed';
const DISPOSE_KEY = '__aaeTiltDispose';

/**
 * Robust responsive config helper. Supports both nested objects from PHP Render
 * and flat suffix keys from the Editor Bridge.
 */
function r(cfg, key, fallback) {
    const bp = currentBreakpoint();

    // 1. Check if the config holds a nested breakpoint map (from PHP Render.php)
    if (cfg[key] && typeof cfg[key] === 'object' && !Array.isArray(cfg[key])) {
        const chain = [bp, ...(BP_CASCADE[bp] || []), 'desktop'];
        for (const step of chain) {
            if (cfg[key][step] !== undefined && cfg[key][step] !== '') {
                return cfg[key][step];
            }
        }
        return fallback;
    }

    // 2. Check if the config holds a flat key with _bp suffix (from editor-bridge.js FEATURES buildConfig)
    const chain = [bp, ...(BP_CASCADE[bp] || [])];
    for (const step of chain) {
        const flatKey = key + '_' + step;
        if (cfg[flatKey] !== undefined && cfg[flatKey] !== '') {
            return cfg[flatKey];
        }
    }

    if (cfg[key] !== undefined && cfg[key] !== '') {
        return cfg[key];
    }

    return fallback;
}

function read(el) {
    const cfg = configFor(el, MAP);

    if (!cfg) return null;

    const enabled = r(cfg, 'enabled', false);
    if (!enabled || enabled === 'false' || enabled === 'no') {
        return null;
    }

    return {
        enabled: true,
        max: Number(r(cfg, 'max', 15)),
        speed: Number(r(cfg, 'speed', 300)),
        scale: Number(r(cfg, 'scale', 1)),
        perspective: Number(r(cfg, 'perspective', 1000)),
        glare: r(cfg, 'glare', false) === true || r(cfg, 'glare', false) === 'true' || r(cfg, 'glare', false) === 'yes',
        maxGlare: Number(r(cfg, 'maxGlare', 0.5)),
        reset: r(cfg, 'reset', true) === true || r(cfg, 'reset', true) === 'true' || r(cfg, 'reset', true) === 'yes',
        transition: r(cfg, 'transition', true) === true || r(cfg, 'transition', true) === 'true' || r(cfg, 'transition', true) === 'yes',
        gyroscope: r(cfg, 'gyroscope', true) === true || r(cfg, 'gyroscope', true) === 'true' || r(cfg, 'gyroscope', true) === 'yes',
    };
}

function bind(el, config) {
    unbind(el);
    
    if (!config) return;

    const max = config.max;
    const speed = config.speed;
    const scale = config.scale;
    const perspective = config.perspective;
    const glare = config.glare;
    const maxGlare = config.maxGlare;
    const reset = config.reset;
    const transition = config.transition;
    const gyroscope = config.gyroscope;

    let isMouseOver = false;
    let transitionTimeout = null;

    // Handle Glare Element Injection
    let glareContainer = null;
    let glareInner = null;

    if (glare) {
        glareContainer = document.createElement('div');
        glareContainer.className = 'aae-tilt-glare';
        glareContainer.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;overflow:hidden;pointer-events:none;z-index:9999;border-radius:inherit;';
        
        glareInner = document.createElement('div');
        glareInner.className = 'aae-tilt-glare-inner';
        glareInner.style.cssText = 'position:absolute;top:50%;left:50%;width:200%;height:200%;transform:translate(-50%,-50%);pointer-events:none;opacity:0;transition:opacity 300ms ease;';
        
        glareContainer.appendChild(glareInner);
        el.appendChild(glareContainer);
    }

    // Set perspective
    el.style.transformPerspective = `${perspective}px`;

    function onMouseEnter() {
        isMouseOver = true;
        
        if (transition) {
            el.style.transition = `transform ${speed}ms cubic-bezier(0.03,0.98,0.52,0.99)`;
        }

        if (transitionTimeout) clearTimeout(transitionTimeout);
        transitionTimeout = setTimeout(() => {
            if (isMouseOver) {
                el.style.transition = 'none';
            }
        }, speed);

        if (glareInner) {
            glareInner.style.opacity = maxGlare;
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
        const rotateX = -(tiltY * max).toFixed(2);
        const rotateY = (tiltX * max).toFixed(2);

        el.style.transform = `perspective(${perspective}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(${scale}, ${scale}, ${scale})`;

        if (glareInner) {
            const glareX = (x / width) * 100;
            const glareY = (y / height) * 100;
            glareInner.style.background = `radial-gradient(circle at ${glareX}% ${glareY}%, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 80%)`;
        }
    }

    function onMouseLeave() {
        isMouseOver = false;

        if (transition) {
            el.style.transition = `transform ${speed}ms cubic-bezier(0.03,0.98,0.52,0.99)`;
        }

        if (reset) {
            el.style.transform = `perspective(${perspective}px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        }

        if (glareInner) {
            glareInner.style.opacity = '0';
        }

        if (transitionTimeout) clearTimeout(transitionTimeout);
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

        const rotateX = -(tiltY * max).toFixed(2);
        const rotateY = (tiltX * max).toFixed(2);

        if (transition) {
            el.style.transition = `transform ${speed}ms cubic-bezier(0.03,0.98,0.52,0.99)`;
        }
        el.style.transform = `perspective(${perspective}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(${scale}, ${scale}, ${scale})`;

        if (glareInner) {
            glareInner.style.opacity = maxGlare;
            const glareX = (tiltX + 0.5) * 100;
            const glareY = (tiltY + 0.5) * 100;
            glareInner.style.background = `radial-gradient(circle at ${glareX}% ${glareY}%, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 80%)`;
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

        if (transitionTimeout) clearTimeout(transitionTimeout);

        if (glareContainer && el.contains(glareContainer)) {
            el.removeChild(glareContainer);
        }

        el.style.transform = '';
        el.style.transition = '';
        el.style.transformPerspective = '';
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

