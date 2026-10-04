/* eslint-env browser */

/**
 * WP Menu → one <ResponsiveSection> per CSS-variable-driven panel section.
 *
 * These are WIDGET sections, not extensions: each attaches to an anchor prop
 * AAE_A_Menu declares in its own schema, so they appear on e-aae-a-menu only.
 * Same wiring as the Nested Slider's panel — the responsive framework binds by
 * anchor key, never by element type.
 *
 * Every row is a NEW responsive prop. The widget's original props
 * (text_color, padding_x, hamburger_size, …) are untouched and still drive the
 * Twig's inline CSS variables; each row simply shows the legacy value as its
 * display default, so an existing menu opens looking exactly as before and
 * only starts emitting an override once the builder edits it.
 *
 * Frontend/canvas delivery is CSS, not JS config:
 *   PHP  → Widgets/Menu/class-aae-a-menu-responsive.php  (footer <style>)
 *   JS   → editor-bridge/menu-responsive-preview.js      (same node, live)
 */

import { MENU_SECTIONS, MENU_PLAY_GROUP } from './fields';

/** Read a legacy (non-responsive) prop's scalar out of its envelope. */
function legacyValue(settings, key, fallback) {
	const v = settings?.[key];
	const raw = v && typeof v === 'object' && '$$type' in v ? v.value : v;
	return raw === '' || raw === null || raw === undefined ? fallback : raw;
}

/**
 * `defaultValue` is evaluated per element (ResponsiveSection calls it with the
 * current settings), which is what lets a row inherit the value the builder
 * already saved on the legacy prop instead of a constant.
 */
const fromLegacy = (key, fallback) => (settings) => legacyValue(settings, key, fallback);

/**
 * Read one cell out of a RESPONSIVE (`aae-rj`) prop, following the same
 * widest-wins cascade the panel shows: the breakpoint's own value if it has
 * one, otherwise desktop.
 */
function responsiveCell(settings, key, activeBp) {
	const env = settings?.[key];
	const map = env && typeof env === 'object' && env.value && typeof env.value === 'object'
		? env.value
		: null;
	if (!map) return undefined;

	const own = map[activeBp];
	if (own !== null && own !== undefined && own !== '') return own;

	const desktop = map.desktop;
	return desktop === null || desktop === '' ? undefined : desktop;
}

/**
 * Starting value for a 4-side Padding row that REPLACES an X / Y pair.
 *
 * The row must open showing the padding the menu already renders with, or the
 * control lies about the current state and the builder's first edit looks like
 * a jump. So it reads, in order: the responsive X / Y override for the
 * breakpoint being edited, then the legacy single-value prop, then the Twig's
 * own fallback.
 *
 * Returned unlinked, because X and Y are only equal by coincidence — linking
 * them would make the first keystroke silently rewrite the other axis.
 */
const paddingFromLegacy = (prefix, pair, fallback) => (settings, activeBp) => {
	const read = (axis) => {
		const responsive = responsiveCell(settings, prefix + pair[axis], activeBp);
		if (responsive !== undefined) return Number(responsive);
		return Number(legacyValue(settings, pair[axis], fallback[axis]));
	};

	const x = read('x');
	const y = read('y');

	return { top: y, right: x, bottom: y, left: x, unit: 'px', isLinked: false };
};

/** The input a kind is edited with. Enums carry their own option list. */
function controlFor(field) {
	if (field.kind === 'enum') return 'select';
	// A real swatch + picker. This was a bare text field, which meant every
	// colour on the widget had to be typed as a hex code from memory.
	if (field.kind === 'color') return 'color';
	// Top / Right / Bottom / Left with a unit and a link toggle.
	if (field.kind === 'dimensions') return 'dimensions';
	return 'number'; // px + transition — both are plain numbers to the builder
}

/** The starting value a row opens with. */
function defaultFor(section, field) {
	if (field.kind === 'dimensions') {
		if (field.legacyPair) {
			return paddingFromLegacy(section.prefix, field.legacyPair, field.legacyPairDefault);
		}
		// Converted-in-place rows (Toggle / Panel Padding) still carry a single
		// legacy number, and DimensionsInput cannot parse one — hand it the same
		// value already spread across the four sides, which is what it meant.
		if (field.legacy) {
			return (settings) => {
				const n = Number(legacyValue(settings, field.legacy, field.legacyDefault));
				if (!Number.isFinite(n)) return null;
				return { top: n, right: n, bottom: n, left: n, unit: 'px', isLinked: true };
			};
		}
		// Margin: nothing to inherit, so it opens empty and emits nothing.
		return null;
	}

	return fromLegacy(field.legacy, field.legacyDefault);
}

const configs = MENU_SECTIONS.map((section) => ({
	anchorKey: section.anchorKey,
	bindPrefix: section.prefix,
	// A `hidden` row keeps its prop and keeps emitting — it just stops drawing a
	// control, because a 4-side row now covers the same ground. See the PADDING
	// AND MARGIN note in fields.js.
	fields: section.fields.filter((field) => !field.hidden).map((field) => {
		const row = {
			bind: field.bind,
			label: field.label,
			control: controlFor(field),
			defaultValue: defaultFor(section, field),
			play_group: MENU_PLAY_GROUP,
		};

		if (field.placeholder) row.placeholder = field.placeholder;
		if (field.help) row.help = field.help;
		if (field.allowed) row.options = field.allowed;
		// No negative padding, size or duration — the CSS has no use for one and
		// a stray minus reads as a broken field rather than a deliberate value.
		if (field.kind === 'px' || field.kind === 'transition') row.min = 0;
		if (field.kind === 'dimensions') {
			row.units = ['px', '%', 'em', 'rem'];
			row.defaultUnit = 'px';
		}

		return row;
	}),
}));

export default configs;
