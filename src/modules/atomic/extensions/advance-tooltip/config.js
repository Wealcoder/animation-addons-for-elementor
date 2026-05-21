/* eslint-env browser */

import {
    isEnabled,
} from './predicates';

const POSITION_OPTIONS = [

    { value: 'top', label: 'Top' },
    { value: 'bottom', label: 'Bottom' },
    { value: 'left', label: 'Left' },
    { value: 'right', label: 'Right' },
    { value: 'custom', label: 'Custom' },
];

const TRIGGER_OPTIONS = [

    { value: 'hover', label: 'Hover' },
    { value: 'click', label: 'Click' },
];

const ANIMATION_OPTIONS = [

    { value: 'fade', label: 'Fade' },
    { value: 'scale', label: 'Scale' },
    { value: 'slide', label: 'Slide' },
];

const config = {

    anchorKey:
        'aae-section-aae-advance-tooltip',

    bindPrefix:
        'aae_advance_tooltip_',

    fields: [

        {
            bind: 'enable',
            label: 'Enable Tooltip',
            control: 'switch',
            responsive: true,
            defaultValue: false,
            tab: 'Content', // Goes to Content tab
        },

        {
            bind: 'text',
            label: 'Tooltip Text',
            control: 'textarea',
            defaultValue: '',
            when: isEnabled,
            tab: 'Content', // Goes to Content tab
        },

        {
            bind: 'position',
            label: 'Position',
            control: 'select',
            options: POSITION_OPTIONS,
            defaultValue: 'top',
            when: isEnabled,
            tab: 'Content', // Goes to Content tab
        },
        {
            bind: 'animation',
            label: 'Animation',
            control: 'select',
            options: ANIMATION_OPTIONS,
            defaultValue: 'fade',
            when: isEnabled,
            tab: 'Content', // Goes to Content tab
        },

        {
            bind: 'trigger',
            label: 'Trigger',
            control: 'select',
            options: TRIGGER_OPTIONS,
            defaultValue: 'hover',
            when: isEnabled,
            tab: 'Content', // Goes to Content tab
        },

        {
            bind: 'offset',
            label: 'Offset',
            control: 'slider',
            defaultValue: '10',
            when: isEnabled,
            tab: 'Content', // Goes to Content tab
        },

        {
            bind: 'duration',
            label: 'Duration',
            control: 'slider',
            defaultValue: '0.3',
            when: isEnabled,
            tab: 'Content', // Goes to Content tab
        },
        {
            bind: 'alignment',
            label: 'Alignment',
            control: 'choose',
            responsive: true,
            defaultValue: 'center',
            when: isEnabled,
            options: [
                {
                    value: 'left',
                    label: 'Left',
                    icon: 'eicon-text-align-left' // CSS class from Elementor's core icon library
                },
                {
                    value: 'center',
                    label: 'Center',
                    icon: 'eicon-text-align-center'
                },
                {
                    value: 'right',
                    label: 'Right',
                    icon: 'eicon-text-align-right'
                },
            ],
            tab: 'Content',
        },
        {
            bind: 'width',
            label: 'Width',
            control: 'text',
            defaultValue: '200px',
            when: isEnabled,
            tab: 'style', // Goes to Content tab
        },

        {
            bind: 'arrow_enable',
            label: 'Enable Arrow',
            control: 'switch',
            responsive: true,
            defaultValue: false,
            tab: 'style', // Goes to Content tab
        },

        {
            bind: 'arrow_size',
            label: 'Arrow Size',
            control: 'slider',
            defaultValue: '10',
            when: isEnabled,
            tab: 'style', // Goes to Content tab
        },
        {
            bind: 'bg',
            label: 'Background',
            control: 'color',
            defaultValue: '#000000',
            when: isEnabled,
            tab: 'style', // Goes to Content tab
        },

        {
            bind: 'color',
            label: 'Color',
            control: 'color',
            defaultValue: '#004603',
            when: isEnabled,
            tab: 'style', // Goes to Content tab
        },

        {
            bind: 'borderRadius',

            label: 'Border Radius',

            control: 'dimensions',

            defaultValue: '100%',
            when: isEnabled,
            tab: 'style', // Goes to Content tab
        },
    ],
};

export default config;