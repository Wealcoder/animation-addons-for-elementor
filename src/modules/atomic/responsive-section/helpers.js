/* eslint-env browser */

/**
 * Active-breakpoint cascade for responsive-prop reads. Mirrors the PHP-side
 * Render cascade. Order: walk parent breakpoints right-to-left starting
 * from the active one, desktop is always the cascade root.
 */
const PARENT_CASCADE = {
	mobile:       ['mobile_extra', 'tablet', 'tablet_extra', 'laptop', 'desktop'],
	mobile_extra: ['tablet', 'tablet_extra', 'laptop', 'desktop'],
	tablet:       ['tablet_extra', 'laptop', 'desktop'],
	tablet_extra: ['laptop', 'desktop'],
	laptop:       ['desktop'],
	widescreen:   ['desktop'],
};

const RESPONSIVE_KEY = 'aae-rj';

function isEmpty(v) {
	return v === undefined || v === null || v === '';
}

/**
 * Resolve a stored prop value to a plain primitive at the active breakpoint,
 * walking the parent-cascade when the active cell is null/missing.
 *
 * Accepts:
 *   - Responsive envelope: { $$type: 'aae-rj', value: { desktop, … } }
 *   - Plain transformable primitive: { $$type: 'string'|'number'|'boolean', value: <scalar> }
 *   - Bare scalar
 *
 * Returns null when nothing resolvable is found.
 */
export function resolveAtBreakpoint(propValue, activeBp) {
	if (propValue === null || propValue === undefined) return null;

	if (typeof propValue === 'object' && propValue.$$type === RESPONSIVE_KEY) {
		const map = propValue.value || {};
		if (!isEmpty(map[activeBp])) return map[activeBp];

		const chain = PARENT_CASCADE[activeBp] || [];
		for (const parent of chain) {
			if (!isEmpty(map[parent])) return map[parent];
		}
		return isEmpty(map.desktop) ? null : map.desktop;
	}

	// Plain transformable primitive (non-responsive prop read by predicate helpers).
	if (typeof propValue === 'object' && '$$type' in propValue) {
		return propValue.value;
	}

	return propValue;
}

/**
 * Convenience: read settings[bind] and resolve to active-bp scalar.
 * `settings` is the element-settings record from useSelectedElementSettings;
 * each value is a transformable envelope or null.
 */
export function valueAt(settings, bind, activeBp) {
	if (!settings) return null;
	return resolveAtBreakpoint(settings[bind], activeBp);
}

/** True when the active-bp value is in the supplied allow-list. */
export function valueIn(settings, bind, activeBp, allowed) {
	const v = valueAt(settings, bind, activeBp);
	return Array.isArray(allowed) && allowed.includes(v);
}

/** True when the active-bp value strictly equals expected. */
export function valueEq(settings, bind, activeBp, expected) {
	return valueAt(settings, bind, activeBp) === expected;
}
