const {
    configFor,
} = window.AAEADDON;

const MAP =
    'AAE_INTERACTIONS_TILT';

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
        'Tilt',
        config
    );
}

function unbind(el) {
    void el;
}

window.AAEADDON.register({

    name: 'tilt',

    mapName: MAP,

    boundFlag:
        'aae-tilt-bound',

    read,

    bind,

    unbind,

    reset: unbind,
});