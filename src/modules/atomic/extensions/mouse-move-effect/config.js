import {
	isEnabled,
	isCustomMovementWrapper,
} from './predicates';
const config = {

    /*
    |--------------------------------------------------------------------------
    | MUST MATCH
    |--------------------------------------------------------------------------
    |
    | Section_Anchor_Prop_Type::get_key()
    |
    */

    anchorKey:
        'aae-section-aae-mouse-move-effect-anchor',

    /*
    |--------------------------------------------------------------------------
    | CHANGE PREFIX
    |--------------------------------------------------------------------------
    */

    bindPrefix:
        'aae_mouse_move_effect_',

    fields: [

        /*
        |--------------------------------------------------------------------------
        | Enable
        |--------------------------------------------------------------------------
        */

        {
            bind: 'enable',

            label: 'Enable',

            control: 'switch',

            responsive: false,

            defaultValue: false,
        },

        /*
        |--------------------------------------------------------------------------
        | Enable On Editor
        |--------------------------------------------------------------------------
        */

        {
            bind: 'enable_editor',

            label: 'Enable on Editor',

            control: 'switch',

            responsive: false,

            defaultValue: false,

            when: isEnabled,
        },

        /*
        |--------------------------------------------------------------------------
        | Movement Wrapper
        |--------------------------------------------------------------------------
        */

        {
            bind: 'movement_wrapper',

            label: 'Movement Wrapper',

            control: 'select',

            defaultValue: 'default',

            options: [

                {
                    label: 'Default',
                    value: 'default',
                },

                {
                    label: 'Custom',
                    value: 'custom',
                },
            ],

            when: isEnabled,
        },

        /*
        |--------------------------------------------------------------------------
        | Move X
        |--------------------------------------------------------------------------
        */

        {
            bind: 'move_x',

            label: 'Move X',

            control: 'text',

            defaultValue: '100',

            when: isEnabled,
        },

        /*
        |--------------------------------------------------------------------------
        | Move Y
        |--------------------------------------------------------------------------
        */

        {
            bind: 'move_y',

            label: 'Move Y',

            control: 'text',

            defaultValue: '100',

            when: isEnabled,
        },

        /*
        |--------------------------------------------------------------------------
        | Duration
        |--------------------------------------------------------------------------
        */

        {
            bind: 'duration',

            label: 'Duration',

            control: 'text',

            defaultValue: '1',

            when: isEnabled,
        },

        /*
        |--------------------------------------------------------------------------
        | Custom Movement
        |--------------------------------------------------------------------------
        */

        {
            bind: 'customs',

            label: 'Customs',

            control: 'text',

            defaultValue: '',

            when: isCustomMovementWrapper,
        },
    ],
};

export default config;