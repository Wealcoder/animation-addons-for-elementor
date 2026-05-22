const {
    configFor,
} = window.AAEADDON;

const MAP =
    'AAE_INTERACTIONS_WRAPPER_LINK';

function read(el) {

    const cfg =
        configFor(el, MAP);

    if (!cfg || !cfg.url) {
        return null;
    }

    return cfg;
}

function bind(el, config) {

    console.log(
        'Wrapper Link',
        config
    );

    el.style.cursor = 'pointer';

    el.addEventListener(
        'click',
        (e) => {

            if (
                e.target.closest('a')
            ) {
                return;
            }

            e.preventDefault();

            if (
                config.isExternal
            ) {

                window.open(
                    config.url,
                    '_blank',
                    'noopener,noreferrer'
                );

                return;
            }

            window.location.href =
                config.url;
        }
    );
}
function unbind(el) {
    void el;
}

window.AAEADDON.register({

    name: 'wrapper-link',

    mapName:
        'AAE_INTERACTIONS_WRAPPER_LINK',

    boundFlag:
        'aae-wrapper-link-bound',

    read,

    bind,

    unbind,

    reset: unbind,
});