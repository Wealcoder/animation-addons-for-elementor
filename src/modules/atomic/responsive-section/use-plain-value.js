/* eslint-env browser */

import { updateElementSettings } from '@elementor/editor-elements';

/**
 * Stand-alone primitive read/write for non-responsive props inside a
 * <ResponsiveSection>. Pair to use-cell-value.js — that one wraps in a
 * breakpoint map; this one writes the plain transformable envelope
 * ({ $$type: 'boolean'|'string'|'number', value }) the inner primitive
 * prop type expects.
 *
 * Inputs:
 *   propValue    — settings[bind] (already a transformable envelope, or null)
 *   bind         — the prop key (e.g. 'aae_text_enable_editor')
 *   innerType    — primitive prop type ('string' / 'number' / 'boolean')
 *   elementId    — selected element's id
 *   defaultValue — display fallback when nothing is saved. Elementor doesn't
 *                  auto-inject PHP `->default(...)` into editor settings, so
 *                  the JS config carries the default and we surface it here.
 *
 * Returns:
 *   value     — the unwrapped primitive, or defaultValue when unset
 *   setValue  — write a new primitive; pass null to clear the prop entirely
 */
export function usePlainValue({ propValue, bind, innerType, elementId, defaultValue }) {

	const stored = (propValue && typeof propValue === 'object' && '$$type' in propValue)
		? propValue.value
		: propValue;
	const value = (stored === null || stored === undefined)
		? (defaultValue ?? null)
		: stored;

	const setValue = (next) => {
		const envelope = (next === null || next === undefined)
			? null
			: { $$type: innerType, value: next };

		updateElementSettings({
			id: elementId,
			props: { [bind]: envelope },
			withHistory: true,
		});
	};

	return { value, setValue };
}
