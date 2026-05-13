/* eslint-env browser */

import { updateElementSettings } from '@elementor/editor-elements';

import { resolveAtBreakpoint } from './helpers';

/**
 * Stand-alone responsive cell read/write for primitive (scalar) values
 * inside a <ResponsiveSection>. Pair to use-array-cell-value.js — that one
 * handles array-shaped per-bp lists; this one handles strings, numbers, and
 * booleans.
 *
 * Storage shape:
 *   {
 *     $$type: 'aae-rj',
 *     value: { desktop: 'fade', tablet: 'none', mobile: null, ... }
 *   }
 *
 * Inputs:
 *   propValue     — settings[bind] envelope (or null when unedited)
 *   bind          — prop key (e.g. 'aae_anim_effect')
 *   activeBp      — current breakpoint
 *   elementId     — selected element id
 *   defaultValue  — display fallback when nothing is saved at any bp
 *
 * Returns:
 *   value      — cascaded primitive at activeBp (own → cascade → defaultValue → null)
 *   ownValue   — the active bp's OWN primitive (null = inheriting). Drives dot.
 *   setValue   — write a new primitive at activeBp; pass null to inherit
 *   resetValue — convenience for setValue(null)
 */

const RESPONSIVE_KEY = 'aae-rj';

export function useCellValue({ propValue, bind, activeBp, elementId, defaultValue }) {
	const map = (propValue && typeof propValue === 'object' && propValue.$$type === RESPONSIVE_KEY)
		? (propValue.value || {})
		: {};

	const ownPrimitive = map[activeBp];

	let value;
	if (ownPrimitive !== null && ownPrimitive !== undefined && ownPrimitive !== '') {
		value = ownPrimitive;
	} else {
		const cascaded = resolveAtBreakpoint(propValue, activeBp);
		value = (cascaded === null || cascaded === undefined || cascaded === '')
			? (defaultValue ?? null)
			: cascaded;
	}

	const setValue = (next) => {
		const nextMap = { ...map, [activeBp]: (next === undefined ? null : next) };
		const nextEnvelope = { $$type: RESPONSIVE_KEY, value: nextMap };

		updateElementSettings({
			id: elementId,
			props: { [bind]: nextEnvelope },
			withHistory: true,
		});
	};

	const resetValue = () => setValue(null);

	return {
		value,
		ownValue: (ownPrimitive === null || ownPrimitive === undefined || ownPrimitive === '')
			? null
			: ownPrimitive,
		setValue,
		resetValue,
	};
}
