const {
    configFor,
} = window.AAEADDON || {};

const MAP = 'AAE_INTERACTIONS_WRAPPER_LINK';

function read(el) {
    if (!configFor) {
        return null;
    }

    const cfg = configFor(el, MAP);

    if (!cfg) {
        return null;
    }

    let url = cfg.url;

    // "Current Post" mode: the wrapper-link config is keyed by element id, but
    // loop repeats share one id — the per-instance URL rides the DOM instead
    // (data-aae-post-url printed by the Loop Item twig, per post).
    if (cfg.source === 'post') {
        const host = el.closest('[data-aae-post-url]');
        url = (host && host.getAttribute('data-aae-post-url')) || cfg.url;
    }

    if (!url) {
        return null;
    }

    return {
        url,
        isExternal: !!cfg.isExternal,
        enableEditor: !!cfg.enableEditor,
    };
}

function bind(el, config) {
    unbind(el);
    if (!config || !config.url) return;

    const isEditMode = window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode();
    if (isEditMode && !config.enableEditor) {
        return; // Do not bind in editor if enableEditor is false
    }

    el.style.cursor = 'pointer';

    const clickHandler = (e) => {
        if (e.target.closest('a')) {
            return;
        }

        e.preventDefault();

        if (config.isExternal) {
            window.open(
                config.url,
                '_blank',
                'noopener,noreferrer'
            );
        } else {
            window.location.href = config.url;
        }
    };

    el.addEventListener('click', clickHandler);
    el.__aaeWrapperLinkHandler = clickHandler;
}

function unbind(el) {
    el.style.cursor = '';
    const handler = el.__aaeWrapperLinkHandler;
    if (handler) {
        el.removeEventListener('click', handler);
        delete el.__aaeWrapperLinkHandler;
    }
}

if (window.AAEADDON) {
    window.AAEADDON.register({
        name: 'wrapper-link',
        mapName: 'AAE_INTERACTIONS_WRAPPER_LINK',
        boundFlag: 'aae-wrapper-link-bound',
        playedKey: '__aaeWrapperLinkPlayed',
        read,
        undefined, // No play function needed since the effect is instantaneous
        bind,
        unbind,
        reset: unbind,
    });
}