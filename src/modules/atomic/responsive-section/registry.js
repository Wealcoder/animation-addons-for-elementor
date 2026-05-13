/* eslint-env browser */

/**
 * Tiny module-scope registry mapping anchorKey → section config. Each AAE
 * extension calls registerResponsiveSection({ anchorKey, fields }) at editor
 * bootstrap; ResponsiveSection.jsx looks up the config by anchorKey when its
 * replacement condition fires.
 *
 * Anchor key convention: matches the PHP Section_Anchor_Prop_Type subclass's
 * get_key() — e.g. 'aae-section-aae-animation' for RegularAnimation.
 */
const sections = new Map();

export function addSection(config) {
	if (!config || !config.anchorKey) {
		// eslint-disable-next-line no-console
		console.warn('[AAE] addSection: missing anchorKey', config);
		return;
	}
	sections.set(config.anchorKey, config);
}

export function getSection(anchorKey) {
	return sections.get(anchorKey) || null;
}

export function getAllAnchorKeys() {
	return Array.from(sections.keys());
}
