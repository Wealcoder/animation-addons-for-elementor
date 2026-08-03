/* eslint-env browser */

import { valueAt } from '../../responsive-section/helpers';

/**
 * The enable switch is a PLAIN Boolean prop, not a responsive envelope, so it
 * cannot go through valueAt() (which reads a per-breakpoint map).
 */
function plainBool( s, bind ) {
	const v = s?.[ bind ];
	if ( v && typeof v === 'object' && '$$type' in v ) {
		return !! v.value;
	}
	return !! v;
}

/** True once the feature is switched on — gates every other row. */
export function isBgVideoEnabled( s ) {
	return plainBool( s, 'aae_bgv_enable' );
}

/** Media picker: only for the "Media File" source. */
export function showBgVideoFile( s, bp ) {
	return isBgVideoEnabled( s ) && 'url' !== valueAt( s, 'aae_bgv_source', bp );
}

/** URL field: only for the "External URL" source. */
export function showBgVideoLink( s, bp ) {
	return isBgVideoEnabled( s ) && 'url' === valueAt( s, 'aae_bgv_source', bp );
}
