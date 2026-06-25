/* eslint-env browser */

/**
 * SlidesControl — the "Slides" element-control for the AAE Nested Slider.
 *
 * This is the native Elementor element-control pattern (the same one the core
 * atomic Tabs widget uses for its "Menu items" list), NOT the AAE
 * ResponsiveSection. It is registered into Elementor's shared controlsRegistry
 * under the type id 'aae-slides' (see ./index.js) and rendered by the editing
 * panel wherever the PHP side places an AAE_A_Slides_Control.
 *
 * The list is a LIVE PROJECTION of the slider's real child elements — one row
 * per <e-aae-a-slide> under the slider's <e-aae-a-slider-track>. There is no
 * stored repeater value; useElementChildren re-reads the element tree on every
 * create/delete/move so the rows always match reality.
 *
 * Interactions:
 *   - Click a row  → select that slide element (panel + navigator highlight,
 *                    scroll-into-view) AND drive the preview slider to it so
 *                    the slide becomes visible for editing.
 *   - "Add Item"   → append a new slide (heading + image) to the track.
 *   - Remove (×)   → delete that slide.
 *   - Rename       → the row popover exposes a "Slide name" field that writes
 *                    the child's editor_settings.title (what the row shows).
 */

import * as React from 'react';
import { Repeater } from '@elementor/editor-controls';
import {
	createElements,
	duplicateElements,
	getContainer,
	moveElements,
	removeElements,
	selectElement,
	updateElementEditorSettings,
	useElementChildren,
	useElementEditorSettings,
} from '@elementor/editor-elements';
import { useElement } from '@elementor/editor-editing-panel';
import { ControlFormLabel, Stack, TextField } from '@elementor/ui';

const TRACK_TYPE = 'e-aae-a-slider-track';
const SLIDE_TYPE = 'e-aae-a-slide';

/**
 * Build the model for a fresh, EMPTY slide (no heading/image — the user fills
 * it). Matches the PHP define_default_children(), which also generates empty
 * slides.
 *
 * `elements: []` is required, not optional: Elementor's delete command runs
 * deselectRecursive(), which does `model.get('elements').forEach(...)` on every
 * descendant — an undefined `elements` collection throws. An empty array is a
 * valid (iterable) collection, so an empty slide deletes cleanly.
 */
function buildSlideModel( position ) {
	return {
		elType: SLIDE_TYPE,
		editor_settings: { title: `Slide ${ position }` },
		elements: [],
	};
}

/**
 * Find a descendant container of `parentId` whose elType === type.
 * The slider keeps the track as a direct child; we walk one level using the
 * V1 container model so we don't depend on rendered DOM.
 */
function findChildContainerByType( parentId, type ) {
	const elementor = window.elementor;
	const parent = elementor?.getContainer?.( parentId );
	const model = parent?.model;
	const children = model?.get?.( 'elements' );

	if ( ! children ) {
		return null;
	}

	let found = null;
	children.each?.( ( childModel ) => {
		if ( found ) {
			return;
		}
		if ( childModel.get( 'elType' ) === type ) {
			found = childModel.get( 'id' );
		}
	} );

	return found ? elementor?.getContainer?.( found ) : null;
}

/**
 * Tell the preview slider to navigate to a slide index. The runtime exposes a
 * hook on the slider DOM node (sliderDiv._aaeGoTo). We also fire the same
 * window CustomEvent the core Tabs widget listens to, as a belt-and-braces
 * fallback for runtimes that wire navigation off the navigator event.
 */
function navigatePreviewToSlide( sliderId, slideId, index ) {
	try {
		const previewWin =
			window.elementor?.$preview?.[ 0 ]?.contentWindow || null;

		if ( ! previewWin ) {
			return;
		}

		const sliderNode =
			previewWin.document.querySelector( `[data-id="${ sliderId }"]` ) ||
			previewWin.document.getElementById( sliderId );

		if ( sliderNode && typeof sliderNode._aaeGoTo === 'function' ) {
			sliderNode._aaeGoTo( index );
		}

		// Fallback: broadcast a navigator-style click the runtime can also act on.
		previewWin.dispatchEvent(
			new previewWin.CustomEvent( 'aae/slider/edit-slide', {
				detail: { sliderId, slideId, index },
			} )
		);
	} catch ( _e ) {
		/* preview not ready — ignore */
	}
}

export function SlidesControl( { label } ) {
	const { element } = useElement();
	const sliderId = element.id;

	// One row per real slide under the track.
	const { [ SLIDE_TYPE ]: slides } = useElementChildren( sliderId, {
		[ TRACK_TYPE ]: SLIDE_TYPE,
	} );

	const repeaterValues = ( slides || [] ).map( ( slide, index ) => ( {
		id: slide.id,
		title: slide.editorSettings?.title || `Slide ${ index + 1 }`,
		index,
	} ) );

	const setValues = ( _newValues, _options, meta ) => {
		const action = meta?.action;
		if ( ! action ) {
			return;
		}

		const track = findChildContainerByType( sliderId, TRACK_TYPE );
		if ( ! track ) {
			return;
		}

		if ( action.type === 'add' ) {
			action.payload.forEach( ( { index } ) => {
				const position = index + 1;
				createElements( {
					title: 'Slide',
					subtitle: 'Slide added',
					elements: [
						{
							container: track,
							model: buildSlideModel( position ),
							options: { at: index },
						},
					],
				} );
			} );
			return;
		}

		if ( action.type === 'remove' ) {
			const ids = action.payload
				.map( ( { item } ) => item?.id )
				.filter( Boolean );
			if ( ids.length ) {
				removeElements( {
					elementIds: ids,
					title: 'Slide',
					subtitle: 'Slide removed',
				} );
			}
			return;
		}

		if ( action.type === 'duplicate' ) {
			const ids = action.payload
				.map( ( { item } ) => item?.id )
				.filter( Boolean );
			if ( ids.length ) {
				duplicateElements( {
					elementIds: ids,
					title: 'Slide',
					subtitle: 'Slide duplicated',
				} );
			}
			return;
		}

		if ( action.type === 'reorder' ) {
			const { from, to } = action.payload;
			const movedId = slides?.[ from ]?.id;
			const movedElement = movedId ? getContainer( movedId ) : null;
			// Guard against a stale index (a concurrent create/delete between
			// render and drop): only move if the resolved slide is still a child
			// of this track.
			if ( movedElement && movedElement.parent?.id === track.id ) {
				moveElements( {
					title: 'Slide',
					subtitle: 'Slide reordered',
					moves: [
						{
							element: movedElement,
							targetContainer: track,
							options: { at: to },
						},
					],
				} );
			}
		}
	};

	// Row click: select the slide in the editor AND move the preview to it.
	// The Repeater fires onPopoverOpen twice for a single click (once from the
	// tag's onClick, once from the popover's open handler), so dedupe by id +
	// time to avoid a doubled select command and a double preview nav pulse.
	const lastNav = React.useRef( { id: null, t: 0 } );
	const onPopoverOpen = ( value ) => {
		if ( ! value?.id ) {
			return;
		}
		const now = Date.now();
		if ( lastNav.current.id === value.id && now - lastNav.current.t < 300 ) {
			return;
		}
		lastNav.current = { id: value.id, t: now };
		selectElement( value.id );
		navigatePreviewToSlide( sliderId, value.id, value.index );
	};

	return (
		<Repeater
			showToggle={ false }
			showDuplicate={ true }
			values={ repeaterValues }
			setValues={ setValues }
			showRemove={ repeaterValues.length > 1 }
			label={ label }
			itemSettings={ {
				getId: ( { item } ) => item.id,
				initialValues: { id: '', title: 'Slide' },
				Label: SlideRowLabel,
				Content: SlideRowContent,
				Icon: () => null,
				onPopoverOpen,
			} }
		/>
	);
}

function SlideRowLabel( { value } ) {
	return (
		<Stack sx={ { minHeight: 20 } } direction="row" alignItems="center" gap={ 1.5 }>
			<span>{ value?.title }</span>
		</Stack>
	);
}

function SlideRowContent( { value } ) {
	if ( ! value?.id ) {
		return null;
	}
	return (
		<Stack p={ 2 } gap={ 1.5 }>
			<SlideNameField elementId={ value.id } />
		</Stack>
	);
}

function SlideNameField( { elementId } ) {
	const editorSettings = useElementEditorSettings( elementId );
	const label = editorSettings?.title ?? '';

	return (
		<Stack gap={ 1 }>
			<ControlFormLabel>{ 'Slide name' }</ControlFormLabel>
			<TextField
				size="tiny"
				value={ label }
				onChange={ ( { target } ) =>
					updateElementEditorSettings( {
						elementId,
						settings: { title: target.value },
					} )
				}
			/>
		</Stack>
	);
}
