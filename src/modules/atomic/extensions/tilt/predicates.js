/* eslint-env browser */

function getValue(settings, bind) {

    const item = settings?.[bind];

    if (!item) {
        return item;
    }

    if (
        item.value &&
        typeof item.value === 'object'
    ) {
        return item.value.desktop;
    }

    if ('value' in item) {
        return item.value;
    }

    return item;
}

export function isEnabled(settings) {

    return !!getValue(
        settings,
        'aae_tilt_enable'
    );
}