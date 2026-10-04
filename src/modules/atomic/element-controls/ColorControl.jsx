/* eslint-env browser */

/**
 * `aae-color` — Elementor's own colour picker, on the CONTENT tab.
 *
 * The atomic panel registers no colour control type: every picker it draws is
 * a Style-tab control bound to a style definition. This re-exports Elementor's
 * `ColorControl` under a type our PHP can name
 * (inc/AtomicWidgets/Controls/class-aae-color-control.php), bound through
 * `colorPropTypeUtil` to a `Color_Prop_Type` prop — the same `{ $$type:
 * 'color', value }` a style colour stores, so global colours and the picker's
 * eyedropper come for free and nothing here re-implements a picker.
 *
 * Read `placeholder` from the PHP control's props so an empty value can show
 * what the stylesheet will fall back to.
 */

import React from 'react';
import { ColorControl as ElementorColorControl } from '@elementor/editor-controls';

export function AaeColorControl( props ) {
	const placeholder = props?.placeholder ?? props?.props?.placeholder ?? '';
	return <ElementorColorControl placeholder={ placeholder || undefined } />;
}
