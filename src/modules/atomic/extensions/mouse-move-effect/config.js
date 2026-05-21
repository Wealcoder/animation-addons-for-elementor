import {
	isEnabled,
	isCustomMovementWrapper,
	showPlayButton,
} from './predicates';
import { CUSTOM_PROPERTY_OPTIONS } from '../regular-animation/config';
import { placeholder } from '@codemirror/view';
const config = {

    /*
    |--------------------------------------------------------------------------
    | MUST MATCH
    |--------------------------------------------------------------------------
    |
    | Section_Anchor_Prop_Type::get_key()
    |
    | */

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

            responsive: true,

            defaultValue: false,
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

            responsive: true,

            defaultValue: 'default',

            options: [

                {
                    label: 'Default',
                    value: 'default',
                },

                {
                    label: 'Custom Area',
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

            responsive: true,

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

            responsive: true,

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

            responsive: true,

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
            label: 'Custom Area',
            control: 'text',
            placeholder: '.custom-area',
            responsive: true,
            defaultValue: '',
            when: isCustomMovementWrapper,
        },

        /*
        |--------------------------------------------------------------------------
        | Custom Properties
        |--------------------------------------------------------------------------
        */

        {
            bind: 'custom_props',

            label: 'Custom Properties',

            control: 'repeater',

            defaultValue: [],

            when: isEnabled,

            addLabel: 'Add Property',

            rowDefaults: { property: 'opacity', value: '' },

            cells: [
                {
                    bind: 'property',
                    type: 'select',
                    placeholder: 'Property',
                    options: CUSTOM_PROPERTY_OPTIONS,
                },
                {
                    bind: 'value',
                    type: 'text',
                    placeholder: 'value',
                },
            ],
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
        | Play Now
        |--------------------------------------------------------------------------
        */

        {
            control: 'play-button',
            when: showPlayButton,
            play_group: 'aae_mouse_move_effect_',
        },
    ],
};

export default config;