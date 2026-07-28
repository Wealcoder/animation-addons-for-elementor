/* eslint-env browser */

/**
 * Registers AAE element-controls into Elementor's shared controlsRegistry.
 *
 * Element-controls (unlike prop-bound controls) carry no stored value — they
 * project the element tree. The editing panel resolves a PHP control that
 * serialises as { type: 'element-control', value: { type: 'aae-slides' } } to
 * the component registered here under the same type id.
 *
 * controlsRegistry.register() throws if a type is already registered, so this
 * is guarded to stay idempotent across re-inits / HMR.
 */

import { controlsRegistry } from '@elementor/editor-editing-panel';
import { stringArrayPropTypeUtil } from '@elementor/editor-props';

import { SlidesControl } from './SlidesControl';
import { AccordionItemsControl } from './AccordionItemsControl';
import { TimelineItemsControl } from './TimelineItemsControl';
import { PresetPickerControl } from './PresetPickerControl';
import { FormActionsControl } from './FormActionsControl';
import { FormConditionsControl } from './FormConditionsControl';
import { MobileNavLifecycleControl, NavItemsControl, NavSubItemsControl } from './NavItemsControl';
import { QueryChipsControl } from './QueryChipsControl';
import { DrawPlayControl } from './DrawPlayControl';
import { HotspotsControl } from './HotspotsControl';

const ELEMENT_CONTROLS = [
	{ type: 'aae-slides', component: SlidesControl, layout: 'full' },
	{ type: 'aae-hotspots', component: HotspotsControl, layout: 'full' },
	{ type: 'aae-items', component: AccordionItemsControl, layout: 'full' },
	{ type: 'aae-timeline-items', component: TimelineItemsControl, layout: 'full' },
	{ type: 'aae-nav-items', component: NavItemsControl, layout: 'full' },
	{ type: 'aae-nav-sub-items', component: NavSubItemsControl, layout: 'full' },
	{ type: 'aae-mobile-nav-lifecycle', component: MobileNavLifecycleControl, layout: 'full' },
	{ type: 'aae-preset-picker', component: PresetPickerControl, layout: 'full' },
	{ type: 'aae-form-actions', component: FormActionsControl, layout: 'full' },
	{ type: 'aae-form-conditions', component: FormConditionsControl, layout: 'full' },
	{ type: 'aae-draw-play', component: DrawPlayControl, layout: 'full' },
	// Prop-bound (unlike the element-controls above): the panel wraps it in a
	// SettingsField for its bind key; useBoundProp(stringArrayPropTypeUtil)
	// reads/writes the String_Array prop.
	{ type: 'aae-query-chips', component: QueryChipsControl, layout: 'full', propTypeUtil: stringArrayPropTypeUtil },
];

let registered = false;

export function registerAaeElementControls() {
	if ( registered ) {
		return;
	}
	registered = true;

	ELEMENT_CONTROLS.forEach( ( { type, component, layout, propTypeUtil } ) => {
		try {
			// Skip if something already claimed this type (defensive).
			if ( controlsRegistry.get?.( type ) ) {
				return;
			}
			controlsRegistry.register( type, component, layout, propTypeUtil );
		} catch ( _e ) {
			// Already registered or registry shape changed — non-fatal.
		}
	} );
}
