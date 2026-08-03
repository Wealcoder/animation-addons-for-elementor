/* eslint-env browser */

import {
	useClassesProp,
	useElement,
	useStyle,
	useStylesRerender,
} from '@elementor/editor-editing-panel';
import {
	createElementStyle,
	shouldCreateNewLocalStyle,
	updateElementStyle,
} from '@elementor/editor-elements';
import { getVariantByMeta } from '@elementor/editor-styles';

/**
 * Read/write one style prop of the currently-edited style variant.
 *
 * This is a hand-rolled equivalent of core's <StylesField>, and it exists
 * because StylesField, StylesFieldLayout and useStylesField are NOT on the
 * `elementorV2.editorEditingPanel` runtime global — they appear as exports
 * inside the package BUNDLE, which is not the same thing as the public surface.
 * Importing them yielded `undefined` components and React error #130, which the
 * panel's error boundary turned into "the Mask section vanishes when you click
 * it". Everything used below was verified against the live global instead.
 *
 * The pieces mirror what useStylesFields does internally:
 *   useStyle()          → which style definition + variant (breakpoint/state)
 *                         the panel is currently editing. This is what makes a
 *                         value land on `:hover` rather than the base variant.
 *   useStylesRerender() → re-render when styles change elsewhere.
 *   provider.actions.get → the saved style, from which the variant's props read.
 *   create/updateElementStyle → the same mutations core commits.
 *
 * Not reimplemented: undo/redo grouping (core wraps these in `undoable()` via a
 * private hook). Changes still apply and still save; they just land in history
 * as individual document changes.
 */

/** The label core gives an element's own local style (see any saved element). */
const LOCAL_STYLE_LABEL = 'local';

export function useMaskStyleField( bind ) {
	const { element } = useElement();
	const { id: styleId, meta, provider, canEdit } = useStyle();
	const classesProp = useClassesProp();

	useStylesRerender();

	let value = null;

	if ( provider && styleId && element?.id ) {
		try {
			const style = provider.actions.get( styleId, { elementId: element.id } );
			const variant = style ? getVariantByMeta( style, meta ) : null;
			value = variant?.props?.[ bind ] ?? null;
		} catch ( e ) {
			// Style went away mid-render (deleted class, provider swap) — treat
			// as "no value" rather than taking the panel down with us.
			value = null;
		}
	}

	const setValue = ( next ) => {
		if ( ! element?.id ) {
			return;
		}

		const props = { [ bind ]: next };

		// No local style yet — the element has never been styled, so one has to
		// be created and attached to its classes prop first.
		if ( shouldCreateNewLocalStyle( { styleId, provider } ) ) {
			createElementStyle( {
				elementId: element.id,
				classesProp,
				label: LOCAL_STYLE_LABEL,
				meta,
				props,
			} );
			return;
		}

		updateElementStyle( {
			elementId: element.id,
			styleId,
			meta,
			props,
		} );
	};

	return { value, setValue, canEdit: false !== canEdit };
}

/** Wrap a scalar in the prop envelope the schema expects. */
export function stringProp( value ) {
	return { $$type: 'string', value };
}
