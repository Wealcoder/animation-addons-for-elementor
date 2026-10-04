/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * BtnHoverStyleControl — the "Hover Style" dropdown for the Btn widget's
 * "Default" preset (see inc/AtomicWidgets/Widgets/Btn/presets/default.json).
 *
 * Prop-bound (registered with stringPropTypeUtil in ./index.js) to the plain
 * String prop `aae_btn_hover_style` — useBoundProp reads/writes it exactly
 * like a native Select_Control would. What makes it custom: Elementor's
 * atomic controls have no "show this control only if that prop equals X"
 * condition system, and this row must stay hidden until the sibling boolean
 * `aae_btn_hover_effect` is true. Only the "Default" preset sets that marker
 * — every other Btn preset/instance leaves it false, so the row renders
 * nothing for them, same "preset-driven only" family as aae_btn_cross /
 * aae_btn_divide in class-aae-a-btn.php's props schema.
 *
 * The marker is read straight off the live V1 container model (not a second
 * bound prop) via the same useListenTo pattern IconListItemsControl.jsx uses
 * for its own live projection, so toggling it elsewhere (undo, a fresh
 * preset apply) shows/hides this row without a panel reload.
 *
 * The picked value drives a `btn-{value}` hook class from the twig
 * (aae-a-btn.html.twig), matching the classic V3 wcf__btn `btn_hover_list`
 * option values (inc/trait-wcf-button.php) 1:1 — the actual animation CSS
 * lives in btn.scss, hardcoded per value against this preset's real
 * e-divider child (see that file's comment block).
 */

import * as React from 'react';
import { getContainer } from '@elementor/editor-elements';
import {
	__privateUseListenTo as useListenTo,
	commandEndEvent,
	v1ReadyEvent,
} from '@elementor/editor-v1-adapters';
import { useElement } from '@elementor/editor-editing-panel';
import { useBoundProp } from '@elementor/editor-controls';
import { stringPropTypeUtil } from '@elementor/editor-props';
import { MenuItem, Select, Stack, Typography } from '@elementor/ui';

const OPTIONS = [
	[ 'hover-none', 'None' ],
	[ 'hover-divide', 'Divided' ],
	[ 'hover-cross', 'Cross' ],
	[ 'hover-cropping', 'Cropping' ],
	[ 'rollover-top', 'Rollover Top' ],
	[ 'rollover-left', 'Rollover Left' ],
	[ 'parallal-border', 'Parallel Border' ],
	[ 'rollover-cross', 'Rollover Cross' ],
];

/** Live read of the sibling `aae_btn_hover_effect` marker off the V1 model. */
function useHoverEffectMarker( elementId ) {
	return useListenTo(
		[
			v1ReadyEvent(),
			commandEndEvent( 'document/elements/settings' ),
			commandEndEvent( 'document/elements/set-settings' ),
		],
		() =>
			!! getContainer( elementId )
				?.model?.get( 'settings' )
				?.get( 'aae_btn_hover_effect' )?.value,
		[ elementId ]
	);
}

export function BtnHoverStyleControl( { label } ) {
	const { element } = useElement();
	const elementId = element.id;

	const enabled = useHoverEffectMarker( elementId );

	// Called unconditionally — hooks can't be skipped by the early return below.
	const { value, setValue, disabled } = useBoundProp( stringPropTypeUtil );

	if ( ! enabled ) {
		return null;
	}

	return (
		<Stack gap={ 0.5 }>
			<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
				{ label || 'Hover Style' }
			</Typography>
			<Select
				size="tiny"
				fullWidth
				disabled={ disabled }
				value={ value || 'hover-none' }
				onChange={ ( event ) => setValue( event.target.value ) }
			>
				{ OPTIONS.map( ( [ val, text ] ) => (
					<MenuItem key={ val } value={ val }>{ text }</MenuItem>
				) ) }
			</Select>
		</Stack>
	);
}
