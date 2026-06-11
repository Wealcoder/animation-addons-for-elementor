/* eslint-env browser */

import {
    isEnabled,
} from './predicates';

const EASE_OPTIONS = [

    {
        value: 'power1.out',
        label: 'Power1 Out',
    },

    {
        value: 'power2.out',
        label: 'Power2 Out',
    },

    {
        value: 'power3.out',
        label: 'Power3 Out',
    },

    {
        value: 'expo.out',
        label: 'Expo Out',
    },

    {
        value: 'bounce.out',
        label: 'Bounce Out',
    },
];

const config = {

    anchorKey:
        'aae-section-aae-scroll-to',

    bindPrefix:
        'aae_scroll_to_',

    fields: [

        {
            bind: 'enable',
            label: 'Enable Scroll To',
            control: 'switch',
            responsive: true,
            defaultValue: false,
           
        },

        {
            bind: 'target',
            label: 'Target Selector | Number',
            control: 'text',
            responsive: true,
            defaultValue: '#section-id',
            when: isEnabled,
            
        },

        {
            bind: 'duration',
            label: 'Scroll Duration',
            control: 'slider',
            responsive: true,
            defaultValue: 1,
            when: isEnabled,
           
        },

        {
            bind: 'ease',
            label: 'Scroll Ease',
            control: 'select',
            responsive: true,
            defaultValue: 'power2.out',
            options: EASE_OPTIONS,
            when: isEnabled,
            
        },
    ],
};

export default config;