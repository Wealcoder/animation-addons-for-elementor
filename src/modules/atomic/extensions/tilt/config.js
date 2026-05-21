/* eslint-env browser */

import {
    isEnabled,
} from './predicates';

const config = {

    anchorKey:
        'aae-section-aae-tilt',

    bindPrefix:
        'aae_tilt_',

    fields: [

        {
            bind: 'enable',
            label: 'Enable Tilt',
            control: 'switch',
            responsive: true,
            defaultValue: false,
            tab: 'Content',
        },

        {
            bind: 'max',
            label: 'Max Tilt',
            control: 'slider',
            responsive: true,
            defaultValue: 15,
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'speed',
            label: 'Speed',
            control: 'slider',
            responsive: true,
            defaultValue: 300,
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'scale',
            label: 'Scale',
            control: 'slider',
            responsive: true,
            defaultValue: 1,
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'perspective',
            label: 'Perspective',
            control: 'slider',
            responsive: true,
            defaultValue: 1000,
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'glare',
            label: 'Enable Glare',
            control: 'switch',
            responsive: true,
            defaultValue: false,
            when: isEnabled,
            tab: 'Style',
        },

        {
            bind: 'max_glare',
            label: 'Max Glare',
            control: 'slider',
            responsive: true,
            defaultValue: 0.5,
            when: isEnabled,
            tab: 'Style',
        },

        {
            bind: 'reset',
            label: 'Reset',
            control: 'switch',
            responsive: true,
            defaultValue: true,
            when: isEnabled,
            tab: 'Advanced',
        },

        {
            bind: 'transition',
            label: 'Transition',
            control: 'switch',
            responsive: true,
            defaultValue: true,
            when: isEnabled,
            tab: 'Advanced',
        },

        {
            bind: 'gyroscope',
            label: 'Gyroscope',
            control: 'switch',
            responsive: true,
            defaultValue: true,
            when: isEnabled,
            tab: 'Advanced',
        },
    ],
};

export default config;