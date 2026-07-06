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

import { SlidesControl } from './SlidesControl';
import { AccordionItemsControl } from './AccordionItemsControl';
import { GalleryItemsControl } from './GalleryItemsControl';
import { PresetPickerControl } from './PresetPickerControl';
import { MobileNavLifecycleControl, NavItemsControl } from './NavItemsControl';

const ELEMENT_CONTROLS = [
	{ type: 'aae-slides', component: SlidesControl, layout: 'full' },
	{ type: 'aae-items', component: AccordionItemsControl, layout: 'full' },
	{ type: 'aae-gallery-items', component: GalleryItemsControl, layout: 'full' },
	{ type: 'aae-nav-items', component: NavItemsControl, layout: 'full' },
	{ type: 'aae-mobile-nav-lifecycle', component: MobileNavLifecycleControl, layout: 'full' },
	{ type: 'aae-preset-picker', component: PresetPickerControl, layout: 'full' },
];

let registered = false;

export function registerAaeElementControls() {
	if ( registered ) {
		return;
	}
	registered = true;

	ELEMENT_CONTROLS.forEach( ( { type, component, layout } ) => {
		try {
			// Skip if something already claimed this type (defensive).
			if ( controlsRegistry.get?.( type ) ) {
				return;
			}
			controlsRegistry.register( type, component, layout );
		} catch ( _e ) {
			// Already registered or registry shape changed — non-fatal.
		}
	} );
}
