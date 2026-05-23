const {
    configFor,
    pickConfigResponsive,
} = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_SCROLLTO';

function r(cfg, key, fallback) {
    const v = pickConfigResponsive(cfg, key);
    return (v === undefined || v === '') ? fallback : v;
}

function read(el) {
    const cfg = configFor(el, MAP);
    if (!cfg) {
        return null;
    }
    const enabled = pickConfigResponsive(cfg, 'enabled');
    if (!enabled || enabled === 'false' || enabled === 'no') {
        return null;
    }

    return {
        target: String(r(cfg, 'target', '')),
        duration: Number(r(cfg, 'duration', 1)),
        ease: String(r(cfg, 'ease', 'power2.out')),
    };
}

function scrollToTarget(target, config) {
    gsap.to(window, {
        duration: config.duration,
        ease: config.ease,
        scrollTo: target,
    });
}

function bind(el, config) {
    const target = config.target;

    el.addEventListener(
        'click',
        (e) => {
            e.preventDefault();
            if (!target) {
                return;
            }           

            // Update URL hash
            if (target.startsWith('#')) {
                if (window.history && window.history.pushState) {
                    window.history.pushState(null, null, target);
                } else {
                    window.location.hash = target;
                }
            }

            scrollToTarget(target, config);
        }
    );

    // Scroll on page reload if hash matches the target
    if (target && target.startsWith('#') && window.location.hash === target) {
        if (!window.__aaeScrolledToHash) {
            window.__aaeScrolledToHash = true;
            
            // Slight delay to ensure layout is ready
            setTimeout(() => {
                scrollToTarget(target, config);
            }, 300);
        }
    }
}
function unbind(el) {}
window.AAEADDON.register({
    name: 'scroll-to',
    mapName: MAP,
    boundFlag:'aae-scroll-to-bound',
    read,
    bind,
    unbind,
    reset: unbind,
});