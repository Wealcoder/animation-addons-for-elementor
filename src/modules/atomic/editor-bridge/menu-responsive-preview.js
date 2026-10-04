/* eslint-env browser */

/**
 * WP Menu — live canvas preview for every responsive style row.
 *
 * The frontend gets these overrides as a footer <style id="aae-mi-rs-{id}">
 * printed by Widgets/Menu/class-aae-a-menu-responsive.php. In the editor that
 * node exists too (the canvas is a real front-end render), but PHP never runs
 * again while the builder types — so this module rebuilds the SAME node's text
 * from the container's live settings after every write.
 *
 * Why a <style> and not inline vars on the element: per-breakpoint values need
 * media queries, and the canvas iframe is resized per device mode, so real
 * media queries preview correctly with no JS resize handling at all. Writing
 * to the head/body also survives the client-side Twig re-render that replaces
 * the widget's own markup on each settings change.
 *
 * Kept a byte-for-byte mirror of the PHP builder — same selector, same
 * `!important` (the legacy values sit in the element's inline style attribute
 * and no ordinary rule outranks those), same widest→narrowest ordering, and
 * OWN values only so CSS's cascade supplies the breakpoint inheritance. The
 * field table itself is shared with the panel config, so only the two
 * LANGUAGES are duplicated, never the list of fields.
 */

import { CSS_FIELDS } from '../extensions/menu-sections/fields';

const ELEMENT_TYPE = 'e-aae-a-menu';
const RESPONSIVE_KEY = 'aae-rj';

const FALLBACK_BREAKPOINTS = {
	tablet: { value: 1024, direction: 'max' },
	mobile: { value: 767,  direction: 'max' },
};

function envelopeToMap(envelope) {
	if (envelope && typeof envelope === 'object' && envelope.$$type === RESPONSIVE_KEY
		&& envelope.value && typeof envelope.value === 'object') {
		return envelope.value;
	}
	return {};
}

/** Units a dimensions row may store. Mirrored in the PHP builder. */
const DIMENSION_UNITS = ['px', '%', 'em', 'rem'];

/**
 * A 4-side value -> a CSS shorthand, or null to skip it.
 *
 * A BARE NUMBER is the legacy single-value shape: Toggle Padding and Dropdown
 * Panel Padding both shipped as one number meaning all four sides, and a menu
 * saved before this control existed still holds one. Reading it as `Npx` keeps
 * that menu rendering exactly as it did.
 *
 * An empty side is written as 0 — a shorthand cannot leave one out — but a row
 * with NO side filled in emits nothing at all, so an untouched control never
 * overrides the stylesheet's own spacing with a 0 the builder never chose.
 */
function sanitizeDimensions(raw) {
	if (typeof raw === 'number' && Number.isFinite(raw)) return `${raw}px`;
	if (!raw || typeof raw !== 'object') return null;

	const unit = DIMENSION_UNITS.includes(raw.unit) ? raw.unit : 'px';
	const sides = ['top', 'right', 'bottom', 'left'];

	if (!sides.some((side) => Number.isFinite(Number(raw[side])) && raw[side] !== '' && raw[side] !== null)) {
		return null;
	}

	return sides
		.map((side) => {
			const n = Number(raw[side]);
			return raw[side] === '' || raw[side] === null || raw[side] === undefined || !Number.isFinite(n)
				? '0'
				: `${n}${unit}`;
		})
		.join(' ');
}

/** One side of a dimensions value, for the `sideVars` a calc() needs. */
function dimensionSide(raw, side) {
	if (typeof raw === 'number' && Number.isFinite(raw)) return `${raw}px`;
	if (!raw || typeof raw !== 'object') return null;

	const n = Number(raw[side]);
	if (raw[side] === '' || raw[side] === null || raw[side] === undefined || !Number.isFinite(n)) {
		return null;
	}

	const unit = DIMENSION_UNITS.includes(raw.unit) ? raw.unit : 'px';
	return `${n}${unit}`;
}

/** Mirror of PHP sanitize_value(): returns null for anything unusable. */
function sanitizeValue(raw, meta) {
	if (raw === null || raw === undefined || raw === '') return null;

	if (meta.kind === 'px') {
		const num = Number(raw);
		return Number.isFinite(num) ? `${num}px` : null;
	}

	// Duration only — the easing curve is fixed, matching the Twig, so a
	// builder can never inject an arbitrary transition value.
	if (meta.kind === 'transition') {
		const num = Number(raw);
		return Number.isFinite(num) ? `${num}ms cubic-bezier(.4,0,.2,1)` : null;
	}

	if (meta.kind === 'enum') {
		return (meta.allowed || []).includes(String(raw)) ? String(raw) : null;
	}

	if (meta.kind === 'dimensions') return sanitizeDimensions(raw);

	if (typeof raw !== 'string') return null;
	const clean = raw.trim();
	return /^[A-Za-z0-9#(),.%/ _-]+$/.test(clean) ? clean : null;
}

/**
 * Active non-desktop breakpoints, ordered min-width first then max-width
 * widest→narrowest, so the narrowest query is the last one to match.
 */
function breakpoints() {
	const config = window.elementor?.config?.responsive?.breakpoints;
	const active = {};

	if (config && typeof config === 'object') {
		Object.keys(config).forEach((key) => {
			const bp = config[key];
			if (!bp || bp.is_enabled === false) return;
			const value = Number(bp.value ?? bp.default_value);
			if (!Number.isFinite(value)) return;
			active[key] = { value, direction: bp.direction === 'min' ? 'min' : 'max' };
		});
	}

	const source = Object.keys(active).length ? active : FALLBACK_BREAKPOINTS;

	const mins = Object.entries(source).filter(([, bp]) => bp.direction === 'min');
	const maxes = Object.entries(source)
		.filter(([, bp]) => bp.direction !== 'min')
		.sort((a, b) => b[1].value - a[1].value);

	return [...mins, ...maxes];
}

/** All declarations one breakpoint OWNS, as a CSS body string. */
function declarationsFor(settings, bp) {
	let out = '';

	Object.keys(CSS_FIELDS).forEach((prop) => {
		const map = envelopeToMap(settings[prop]);
		// Own value only — a missing cell inherits through the CSS cascade.
		if (!(bp in map)) return;

		const meta = CSS_FIELDS[prop];
		const value = sanitizeValue(map[bp], meta);
		if (value === null) return;

		out += `${meta.cssVar}:${value} !important;`;

		// Two rules in menu.scss feed a padding into calc(), which takes one
		// length and not a 4-value shorthand — so those rows publish the side
		// that calc needs on a variable of its own.
		if (meta.sideVars) {
			Object.keys(meta.sideVars).forEach((side) => {
				const sideValue = dimensionSide(map[bp], side);
				if (sideValue !== null) out += `${meta.sideVars[side]}:${sideValue} !important;`;
			});
		}
	});

	return out;
}

export function buildCss(id, settings) {
	const selector = `.aae-a-menu[data-id="${id}"]`;
	const groups = [];

	const desktop = declarationsFor(settings, 'desktop');
	if (desktop) groups.push(`${selector}{${desktop}}`);

	breakpoints().forEach(([name, bp]) => {
		const decls = declarationsFor(settings, name);
		if (!decls) return;
		groups.push(`@media(${bp.direction}-width:${bp.value}px){${selector}{${decls}}}`);
	});

	return groups.join('');
}

function widgetTypeOf(container) {
	const raw = container?.model?.get?.('widgetType') || container?.model?.get?.('elType');
	if (!raw) return '';
	return raw.startsWith('e-') ? raw : `e-${raw}`;
}

/**
 * Rewrite (or remove) this element's override block inside the preview iframe.
 * Called from applySettingsToDom after every responsive-cell write.
 */
export function syncMenuResponsiveCss(win, container) {
	if (!win || !container || widgetTypeOf(container) !== ELEMENT_TYPE) return;

	const doc = win.document;
	if (!doc) return;

	const id = container.id;
	const settings = container.settings?.attributes || {};
	const css = buildCss(id, settings);

	// getElementById finds the node wherever it is — the PHP block printed in
	// the footer on canvas load, or the one this function created earlier — so
	// there is never a second, competing copy.
	let node = doc.getElementById(`aae-mi-rs-${id}`);

	if (!css) {
		if (node) node.remove();
		return;
	}

	if (!node) {
		node = doc.createElement('style');
		node.id = `aae-mi-rs-${id}`;
		doc.body.appendChild(node);
	}

	node.textContent = css;
}
