function getValue(settings, bind) {

    const item = settings?.[bind];

    console.log(
        'GET VALUE ITEM',
        bind,
        item
    );

    /*
    |--------------------------------------------------------------------------
    | Missing
    |--------------------------------------------------------------------------
    */

    if (
        item === undefined ||
        item === null
    ) {
        return item;
    }

    /*
    |--------------------------------------------------------------------------
    | Primitive
    |--------------------------------------------------------------------------
    */

    if (
        typeof item !== 'object'
    ) {
        return item;
    }

    /*
    |--------------------------------------------------------------------------
    | Atomic wrapped value
    |--------------------------------------------------------------------------
    */

    if ('value' in item) {

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        if (
            item.value &&
            typeof item.value === 'object'
        ) {
            return item.value.desktop;
        }

        /*
        |--------------------------------------------------------------------------
        | Plain wrapped
        |--------------------------------------------------------------------------
        */

        return item.value;
    }

    return item;
}

export function isEnabled(settings) {

    console.log(
        'ENABLE VALUE',
        settings?.aae_mouse_move_effect_enable
    );

    return !!getValue(
        settings,
        'aae_mouse_move_effect_enable'
    );
}

export function isCustomMovementWrapper(
    settings
) {

    return (
        getValue(
            settings,
            'aae_mouse_move_effect_movement_wrapper'
        ) === 'custom'
    );
}