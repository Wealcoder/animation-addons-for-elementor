/* eslint-env browser */

import { createTransformer } from '@elementor/editor-canvas';

/**
 * The marker the canvas renderer looks for to split one prop into several CSS
 * declarations. Inlined because `createMultiPropsValue` is not on the
 * `elementorV2.editorCanvas` global — only inside the package bundle. The shape
 * is two stable fields and is the same one PHP's Multi_Props::generate() emits.
 */
const createMultiPropsValue = ( props ) => ( { '$$multi-props': true, value: props } );

import { CUSTOM_SHAPE, getShapes, unwrap } from './prop-type';

/**
 * Canvas-side twin of PHP's Mask_Image_Transformer.
 *
 * The editor renders element styles in the browser, so a style prop with no JS
 * transformer registered simply produces nothing on the canvas while working
 * perfectly on the published page — the widget appears broken only while you
 * are building it, which is the worst possible way round.
 *
 * These two implementations MUST agree. Any change to how a shape becomes a CSS
 * value has to land in both this file and Transformers.php, or the canvas shows
 * one thing and the visitor gets another. (Same trap the Formula parity note in
 * CLAUDE.md documents for the form calculator.)
 */
export const maskImageTransformer = createTransformer( ( value ) => {
	const url = resolveUrl( value );

	// Emit nothing rather than a url() that 404s: a mask clips an element to
	// the OPAQUE pixels of its mask image, so a failed load hides the element
	// entirely. No mask is the safe failure for a decorative property.
	if ( ! url ) {
		return null;
	}

	const css = `url("${ url }")`;

	// One prop, two declarations — Safari still needs the prefixed longhands.
	return createMultiPropsValue( {
		'mask-image': css,
		'-webkit-mask-image': css,
	} );
} );

function resolveUrl( value ) {
	if ( ! value || 'object' !== typeof value ) {
		return '';
	}

	const shape = unwrap( value.shape );

	if ( shape && CUSTOM_SHAPE !== shape ) {
		const entry = getShapes()[ shape ];
		return entry && entry.image ? entry.image : '';
	}

	if ( CUSTOM_SHAPE !== shape ) {
		return '';
	}

	const src = unwrap( value.src );
	if ( ! src || 'object' !== typeof src ) {
		return '';
	}

	// `url` is itself an envelope before transformation, a plain string after.
	const url = unwrap( src.url );

	return 'string' === typeof url ? url : '';
}
