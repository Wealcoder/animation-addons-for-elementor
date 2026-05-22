const {
    configFor,
} = window.AAEADDON;

const MAP =
    'AAE_INTERACTIONS_SCROLL_TO';

function read(el) {

    const cfg =
        configFor(el, MAP);

    if (!cfg || !cfg.enabled) {
        return null;
    }

    return cfg;
}

function bind(el, config) {

    console.log(
        'Scroll To',
        config
    );

    el.addEventListener(
        'click',
        (e) => {

            e.preventDefault();

            const target =
                document.querySelector(
                    config.target.desktop
                );

            if (!target) {
                return;
            }

            gsap.to(window, {

                duration:
                    parseFloat(
                        config.duration.desktop
                    ),

                ease:
                    config.ease.desktop,

                scrollTo: target,
            });
        }
    );
}

function unbind(el) {
    void el;
}

window.AAEADDON.register({

    name: 'scroll-to',

    mapName: MAP,

    boundFlag:
        'aae-scroll-to-bound',

    read,

    bind,

    unbind,

    reset: unbind,
});