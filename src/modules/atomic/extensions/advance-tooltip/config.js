/* eslint-env browser */

import {
    isEnabled,
    showPlayButton,
    showArrowSize,
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
    { value: 'pop',    label: 'Spring Pop'   },
    { value: 'slide',  label: 'Slide & Fade' },
    { value: 'type',   label: 'Typewriter'   },
    { value: 'flip',   label: 'Flip Card'    },
    { value: 'magnet', label: 'Magnetic'     },
    { value: 'blur',   label: 'Blur Focus'   },
    { value: 'clip',   label: 'Clip Reveal'  },
    { value: 'jelly',  label: 'Jelly Skew'   },
    { value: 'glow',   label: 'Glow Pulse'   },
    { value: 'unfold', label: 'Unfold Stack' },
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
            tab: 'Content',
        },

        {
            bind: 'text',
            label: 'Tooltip Text',
            control: 'textarea',
            defaultValue: '',
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'position',
            label: 'Position',
            control: 'select',
            options: POSITION_OPTIONS,
            defaultValue: 'top',
            when: isEnabled,
            tab: 'Content',
        },
        {
            bind: 'animation',
            label: 'Animation',
            control: 'select',
            options: ANIMATION_OPTIONS,
            defaultValue: 'fade',
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'trigger',
            label: 'Trigger',
            control: 'select',
            options: TRIGGER_OPTIONS,
            defaultValue: 'hover',
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'offset',
            label: 'Offset',
            control: 'slider',
            defaultValue: '10',
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'show_delay',
            label: 'Show Delay (ms)',
            control: 'number',
            defaultValue: 0,
            when: isEnabled,
            tab: 'Content',
        },

        {
            bind: 'hide_delay',
            label: 'Hide Delay (ms)',
            control: 'number',
            defaultValue: 0,
            when: isEnabled,
            tab: 'Content',
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
                    icon: 'eicon-text-align-left'
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
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_'
        },
        {
            bind: 'width',
            label: 'Width',
            control: 'text',
            defaultValue: '200px',
            when: isEnabled,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_',
        },

        {
            bind: 'padding',
            label: 'Padding',
            control: 'dimensions',
            responsive: true,
            defaultValue: {
                top: '8',
                right: '12',
                bottom: '8',
                left: '12',
                unit: 'px',
            },
            when: isEnabled,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_',
        },

        {
            bind: 'arrow_enable',
            label: 'Enable Arrow',
            control: 'switch',
            responsive: true,
            defaultValue: false,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_',
            when: isEnabled,
        },

        {
            bind: 'arrow_size',
            label: 'Arrow Size',
            control: 'slider',
            defaultValue: '10',
            when: showArrowSize,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_'
        },
        {
            bind: 'bg',
            label: 'Background',
            control: 'color',
            defaultValue: '#000000',
            when: isEnabled,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_'
        },

        {
            bind: 'color',
            label: 'Color',
            control: 'color',
            defaultValue: '#004603',
            when: isEnabled,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_'
        },

        {
            bind: 'font_size',
            label: 'Font Size',
            control: 'slider',
            responsive: true,
            units: ['px', 'em', 'rem'],
            defaultValue: {
                size: 14,
                unit: 'px',
            },
            when: isEnabled,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_'
        },

        {
            bind: 'line_height',
            label: 'Line Height',
            control: 'slider',
            responsive: true,
            units: ['px', 'em', 'rem', ''],
            defaultValue: {
                size: 1.5,
                unit: '',
            },
            when: isEnabled,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_'
        },

        {
            bind: 'border',
            label: 'Border',
            control: 'border',
            responsive: true,
            defaultValue: {
                style: '',
                width: { top: '', right: '', bottom: '', left: '' },
                color: '',
                radius: '8px',
            },
            when: isEnabled,
            tab: 'style',
            live_change: true,
            play_group: 'aae_advance_tooltip_',
        },

        // Editor-only controls (non-responsive).
        {
            bind: 'enable_editor',
            label: 'Enable in Editor',
            control: 'switch',
            defaultValue: false,
            responsive: false,
            when: isEnabled,
            tab: 'Content',
        },
        {
            control: 'play-button',
            when: showPlayButton,
            play_group: 'aae_advance_tooltip_',
        },
    ],
};

export default config;