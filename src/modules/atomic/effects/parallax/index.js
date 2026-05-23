const {
    configFor,
    pickConfigResponsive,
} = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_PLX';

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
        speed: Number(r(cfg, 'speed', 0.9)),
        lag: Number(r(cfg, 'lag', 0)),
    };
}

function bind(el, config) { 
    // 1. Set the data attributes so that if ScrollSmoother hasn't initialized yet,
    //    it will automatically pick them up when it does.
    el.setAttribute('data-speed', config.speed);
    
    if (config.lag > 0) {
        el.setAttribute('data-lag', config.lag);
    } else {
        el.removeAttribute('data-lag');
    }

    // 2. If ScrollSmoother is already initialized, manually push the effect so it updates immediately.
    if (typeof ScrollSmoother !== 'undefined') {
        let smoother = ScrollSmoother.get();
        
        if (!smoother) {
            smoother = ScrollSmoother.create({
                smooth: 1,
                effects: true
            });
        }
     
        if (smoother) {
            smoother.effects(el, {
                speed: config.speed,
                lag: config.lag
            });
        }
    }
}

function unbind(el) {
    el.removeAttribute('data-speed');
    el.removeAttribute('data-lag');

    if (typeof ScrollSmoother !== 'undefined') {
        const smoother = ScrollSmoother.get();
        if (smoother) {
            smoother.effects(el, { speed: 1, lag: 0 });
        }
    }
}

window.AAEADDON.register({
    name: 'parallax',
    mapName: MAP,
    boundFlag: 'aae-parallax-bound',
    read,
    bind,
    unbind,
    reset: unbind,
});
