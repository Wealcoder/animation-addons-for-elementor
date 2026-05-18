/* eslint-env browser */

import { updateElementSettings } from '@elementor/editor-elements';

/**
 * Stand-alone read/write for a Responsive_Json_Prop_Type cell — the
 * "whole list per-breakpoint" responsive shape used by RepeaterInput.
 *
 * Storage:
 *   {
 *     $$type: 'aae-rj',
 *     value: {
 *       desktop: [ ...rows ],
 *       tablet:  [ ...rows ] | null,
 *       mobile:  [ ...rows ] | null,
 *       ...
 *     }
 *   }
 *
 * Same cascade rules as scalar cells: null at the active bp inherits from
 * the parent bp; desktop is the cascade root.
 *
 * Inputs:
 *   propValue     — settings[bind] envelope (or null when unedited)
 *   bind          — prop key (e.g. 'aae_anim_custom_props')
 *   activeBp      — breakpoint key ('desktop', 'tablet', …)
 *   elementId     — selected element id (used by updateElementSettings)
 *   defaultValue  — fallback array when nothing is saved at any bp
 *
 * Returns:
 *   value      — the resolved rows array at activeBp (own → cascade → defaultValue → [])
 *   ownValue   — the rows array OWN-set at activeBp (null = inheriting). Drives dot indicator.
 *   setValue   — replace the bp's rows array; pass null to inherit
 *   resetValue — convenience for setValue(null)
 */

const PARENT_CASCADE = {
	mobile: ['mobile_extra', 'tablet', 'tablet_extra', 'laptop', 'desktop'],
	mobile_extra: ['tablet', 'tablet_extra', 'laptop', 'desktop'],
	tablet: ['tablet_extra', 'laptop', 'desktop'],
	tablet_extra: ['laptop', 'desktop'],
	laptop: ['desktop'],
	widescreen: ['desktop'],
};

const RESPONSIVE_JSON_KEY = 'aae-rj';

function isEmptyArray(v) {
	return !Array.isArray(v);
}

export function useArrayCellValue({ propValue, bind, activeBp, elementId, defaultValue }) {
	const map = (propValue && typeof propValue === 'object' && propValue.$$type === RESPONSIVE_JSON_KEY)
		? (propValue.value || {})
		: {};

	const ownRaw = map[activeBp];
	const ownValue = Array.isArray(ownRaw) ? ownRaw : null;

	let value;
	if (ownValue) {
		value = ownValue;
	} else {
		value = cascadedRead(map, activeBp);
		if (isEmptyArray(value)) {
			value = Array.isArray(defaultValue) ? defaultValue : [];
		}
	}

	const setValue = (nextRows) => {
		const cellValue = (nextRows === null || nextRows === undefined) ? null : nextRows;
		const nextMap = { ...map, [activeBp]: cellValue };
		const nextEnvelope = { $$type: RESPONSIVE_JSON_KEY, value: nextMap };

		updateElementSettings({
			id: elementId,
			props: { [bind]: nextEnvelope },
			withHistory: true,
		});

	
	};

	const resetValue = () => setValue(null);

	return {
		value,
		ownValue,
		setValue,
		resetValue,
	};
}

function cascadedRead(map, breakpoint) {
	const chain = PARENT_CASCADE[breakpoint] || [];
	for (const parent of chain) {
		const v = map[parent];
		if (Array.isArray(v)) return v;
	}
	const desktop = map.desktop;
	return Array.isArray(desktop) ? desktop : [];
}
