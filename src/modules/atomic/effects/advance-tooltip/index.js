const {
    configFor,
} = window.AAEADDON;

const MAP =
    'AAE_INTERACTIONS_ADVANCE_TOOLTIP';

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
        'Advance Tooltip',
        config
    );
}

function unbind(el) {
    void el;
}

window.AAEADDON.register({

    name: 'advance-tooltip',

    mapName: MAP,

    boundFlag:
        'aae-advance-tooltip-bound',

    read,

    bind,

    unbind,

    reset: unbind,
});