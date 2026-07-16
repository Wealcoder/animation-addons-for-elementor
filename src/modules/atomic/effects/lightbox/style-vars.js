/* eslint-env browser */

/**
 * Turn a container's published `style` bag into a flat CSS-custom-property map
 * for the active breakpoint, applied on the shared overlay root at open time.
 *
 * The bag shape (from Container_Render::collect_style) is:
 *   { '<key>': { desktop: <val>, tablet: <val>, … }, … }
 * where <val> is one of:
 *   - a slider   → { size, unit }        (sizing / spacing / opacity)
 *   - a color    → '#rrggbb' | 'rgba(…)' (string)
 *   - a select   → 'soft' | 'medium' | … (content shadow preset)
 *   - a bool     → true                  (content-fullwidth)
 *
 * Only KEYS the user actually set are present, and within a key only the
 * breakpoints they touched — so we cascade from the active bp up to desktop.
 */

const { currentBreakpoint, BP_CASCADE } = window.AAEADDON;

// Shadow preset name → box-shadow value.
const SHADOWS = {
	none: 'none',
	soft: '0 10px 30px rgba(0,0,0,0.25)',
	medium: '0 18px 50px rgba(0,0,0,0.4)',
	strong: '0 24px 80px rgba(0,0,0,0.6)',
};

/** Pick a key's value for the active bp, cascading bp → … → desktop. */
function valueForBp(perBp, bp) {
	if (!perBp || typeof perBp !== 'object') return undefined;
	if (perBp[bp] !== undefined) return perBp[bp];
	const chain = BP_CASCADE[bp] || [];
	for (const step of chain) {
		if (perBp[step] !== undefined) return perBp[step];
	}
	// Cascade the other direction toward desktop for narrower-first data.
	if (perBp.desktop !== undefined) return perBp.desktop;
	// Last resort: first defined value.
	const first = Object.values(perBp).find((v) => v !== undefined);
	return first;
}

/** Slider { size, unit } → CSS length string, or '' when unusable. */
function len(v, fallbackUnit = 'px') {
	if (v == null) return '';
	if (typeof v === 'number') return `${v}${fallbackUnit}`;
	if (typeof v === 'string') return v.trim();
	if (typeof v === 'object' && v.size !== undefined && v.size !== '') {
		const unit = v.unit != null ? v.unit : fallbackUnit;
		return `${v.size}${unit}`;
	}
	return '';
}

// Which keys are lengths (slider) vs raw pass-through (color/opacity/etc).
const LENGTH_KEYS = new Set([
	'content-width', 'content-maxwidth', 'content-padding', 'content-radius',
	'arrow-size', 'arrow-box', 'arrow-radius', 'arrow-border-w', 'arrow-offset',
	'close-size', 'close-box', 'close-radius', 'close-border-w',
]);

/**
 * @param {object|null} styleBag  the container's style config
 * @returns {Record<string,string>} CSS var name → value (only set keys)
 */
export function styleVars(styleBag) {
	const out = {};
	if (!styleBag || typeof styleBag !== 'object') return out;

	const bp = currentBreakpoint();

	for (const key of Object.keys(styleBag)) {
		const raw = valueForBp(styleBag[key], bp);
		if (raw === undefined || raw === '' || raw === null) continue;

		if (key === 'content-fullwidth') {
			if (raw === true) out['--aae-lb-content-fullwidth'] = '1';
			continue;
		}
		if (key === 'content-shadow') {
			const sh = SHADOWS[raw];
			if (sh) out['--aae-lb-content-shadow'] = sh;
			continue;
		}
		if (key === 'overlay-opacity') {
			// slider {size} 0–100 → 0–1 alpha, or a raw number.
			const n = typeof raw === 'object' ? raw.size : raw;
			const num = parseFloat(n);
			if (!Number.isNaN(num)) out['--aae-lb-overlay-opacity'] = String(Math.max(0, Math.min(1, num / 100)));
			continue;
		}
		if (LENGTH_KEYS.has(key)) {
			const l = len(raw);
			if (l) out[`--aae-lb-${key}`] = l;
			continue;
		}
		// Colors and everything else pass through as a trimmed string.
		const s = typeof raw === 'string' ? raw.trim() : String(raw);
		if (s) out[`--aae-lb-${key}`] = s;
	}

	return out;
}

/** Write the vars onto `root`; returns the list of names set (for cleanup). */
export function applyStyleVars(root, styleBag) {
	const vars = styleVars(styleBag);
	const names = Object.keys(vars);
	names.forEach((name) => root.style.setProperty(name, vars[name]));
	// Full-width flips a data-attr the CSS keys off (var alone can't toggle layout).
	root.toggleAttribute('data-lb-fullwidth', vars['--aae-lb-content-fullwidth'] === '1');
	return names;
}

/** Remove previously-set vars (called on close so the next open starts clean). */
export function clearStyleVars(root, names) {
	(names || []).forEach((name) => root.style.removeProperty(name));
	root.removeAttribute('data-lb-fullwidth');
}
