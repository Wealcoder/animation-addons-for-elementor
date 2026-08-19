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

/** The input a kind is edited with. Enums carry their own option list. */
function controlFor(field) {
	if (field.kind === 'enum') return 'select';
	if (field.kind === 'color') return 'text';
	return 'number'; // px + transition — both are plain numbers to the builder
}

const configs = MENU_SECTIONS.map((section) => ({
	anchorKey: section.anchorKey,
	bindPrefix: section.prefix,
	fields: section.fields.map((field) => {
		const row = {
			bind: field.bind,
			label: field.label,
			control: controlFor(field),
			defaultValue: fromLegacy(field.legacy, field.legacyDefault),
			play_group: MENU_PLAY_GROUP,
		};

		if (field.placeholder) row.placeholder = field.placeholder;
		if (field.help) row.help = field.help;
		if (field.allowed) row.options = field.allowed;
		// No negative padding, size or duration — the CSS has no use for one and
		// a stray minus reads as a broken field rather than a deliberate value.
		if (field.kind === 'px' || field.kind === 'transition') row.min = 0;

		return row;
	}),
}));

export default configs;
