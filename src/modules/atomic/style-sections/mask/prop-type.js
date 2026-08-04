/* eslint-env browser */

import { createPropUtils } from '@elementor/editor-props';
import { z } from '@elementor/schema';

/**
 * Editor-side counterpart of PHP's Mask_Image_Prop_Type (`aae-mask-image`).
 *
 * The key MUST match the PHP prop type's get_key(): it is what the panel writes
 * into `_elementor_data` and what both transformers (PHP for the published
 * page, JS for the canvas) are registered under. Change it in one place only
 * and saved masks stop rendering with no error anywhere.
 *
 * `shape` and `src` are z.any() because each holds a nested prop ENVELOPE
 * (`{ $$type: 'string', value: … }`), whose exact shape is Elementor's business,
 * not ours — the real validation is PHP's, which runs on save and is the only
 * one that can protect the database.
 */
export const maskImagePropTypeUtil = createPropUtils(
	'aae-mask-image',
	z
		.object( {
			shape: z.any().nullable().optional(),
			src: z.any().nullable().optional(),
		} )
		.nullable()
);

export const MASK_IMAGE_KEY = 'mask-image';
export const MASK_SIZE_KEY = 'mask-size';
export const MASK_POSITION_KEY = 'mask-position';
export const MASK_REPEAT_KEY = 'mask-repeat';

/** The slug that means "use my own SVG" — mirrors Shapes::CUSTOM in PHP. */
export const CUSTOM_SHAPE = 'custom';

/** Shape catalogue, sent from PHP (Shapes::all()) on the bridge object. */
export function getShapes() {
	const bridge = typeof window !== 'undefined' ? window.aaeAtomicBridge : null;
	const shapes = bridge && bridge.mask_shapes;

	return shapes && 'object' === typeof shapes ? shapes : {};
}

/** Unwrap a `{ $$type, value }` envelope, tolerating an already-plain value. */
export function unwrap( value ) {
	return value && 'object' === typeof value && '$$type' in value ? value.value : value;
}
