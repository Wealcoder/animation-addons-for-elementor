/* eslint-env browser */

/**
 * Soft-gradient look for the Nav's own sections in the editing panel.
 *
 * Pure presentation: one <style> element in the editor document, scoped to the
 * Nav panel. Each section is found through Elementor's own accessible label
 * (`aria-label="<Section label> section"`), and the Nav's panel is recognised by
 * its "Mobile Menu Icons" section, so no other widget's panel is touched. The
 * stock "Settings" section (shared by every widget) is left as it is.
 * If a future Elementor changes that markup the rules simply stop matching - the
 * panel falls back to the default look and nothing breaks.
 *
 * The cards are outline-only (no fill), so text and input colours stay the
 * editor's own and work in both its light and dark theme.
 */

const STYLE_ID = 'aae-nav-panel-style';

const NAV_PANEL = '.MuiStack-root:has([aria-label="Mobile Menu Icons section"])';

const SECTIONS = [
	'Presets',
	'Menu Items',
	'Dropdown Behavior',
	'Mobile Menu',
	'Mobile Menu Icons',
	'Dropdown Icon',
];

const LINE = 'rgba(249, 115, 22, 0.24)';

/* All sections of a panel share ONE list, so each card is built from the
 * section's header and the Collapse that directly follows it - never from their
 * parent. */
const sectionCss = ( label ) => {
	const HEAD = `${ NAV_PANEL } [aria-label="${ label } section"]`;
	const BODY = `${ HEAD } + .MuiCollapse-root`;
	const OPEN = `${ HEAD }:has(+ .MuiCollapse-root:not(.MuiCollapse-hidden))`;

	return `
${ HEAD } {
	margin: 8px 10px 0;
	width: auto;
	background: transparent;
	border: 1px solid ${ LINE };
	border-radius: 14px;
}
${ OPEN } {
	border-bottom-color: transparent;
	border-radius: 14px 14px 0 0;
}
${ BODY } {
	margin: 0 10px 8px;
	background: transparent;
	border: 1px solid ${ LINE };
	border-top: 0;
	border-radius: 0 0 14px 14px;
}
${ BODY }.MuiCollapse-hidden {
	border: 0;
	margin-bottom: 0;
}
${ BODY } .MuiSwitch-switchBase.Mui-checked + .MuiSwitch-track {
	background-color: #F97316;
	opacity: 1;
}
${ BODY } .MuiSwitch-thumb,
${ BODY } .MuiSwitch-switchBase.Mui-checked .MuiSwitch-thumb {
	background-color: #fff;
	color: #fff;
	box-shadow: 0 1px 3px rgba(42, 22, 8, 0.25);
}
${ BODY } .MuiOutlinedInput-root {
	border-radius: 9px;
}
${ BODY } .MuiOutlinedInput-root.Mui-focused .MuiOutlinedInput-notchedOutline,
${ BODY } .MuiOutlinedInput-root:hover .MuiOutlinedInput-notchedOutline {
	border-color: #F97316;
}
${ BODY } .MuiButton-outlined {
	color: #EA580C;
	border-color: #F97316;
	border-radius: 9px;
	background-color: transparent;
}
${ BODY } .MuiButton-outlined:hover {
	border-color: #EA580C;
	background-color: rgba(249, 115, 22, 0.12);
}
${ BODY } .MuiButton-contained {
	color: #fff;
	background-color: #F97316;
	border-radius: 9px;
	box-shadow: none;
}
${ BODY } .MuiButton-contained:hover {
	background-color: #EA580C;
	box-shadow: none;
}
/* The Menu Items control draws its own soft card; inside the section card it
 * would be a card on a card, so it drops its own background and frame. */
${ BODY } [data-aae-soft-card] {
	background: none;
	border: 0;
	box-shadow: none;
	padding: 0;
}
`;
};

const CSS = `
/* the dividers between sections would cut through the gaps between cards */
${ NAV_PANEL } .MuiList-root > .MuiDivider-root {
	display: none;
}
${ SECTIONS.map( sectionCss ).join( '' ) }`;

/* The editor bridge can run inside the PREVIEW iframe, while the editing panel
 * lives in the parent editor document - so the rules go into that one. */
function panelDocument() {
	try {
		if ( window.parent && window.parent !== window && window.parent.document ) {
			return window.parent.document;
		}
	} catch ( error ) {
		/* Cross-origin parent: fall through to this document. */
	}
	return document;
}

export function startNavPanelStyle() {
	if ( typeof document === 'undefined' ) {
		return;
	}

	const target = panelDocument();

	if ( target.getElementById( STYLE_ID ) ) {
		return;
	}

	const style = target.createElement( 'style' );
	style.id = STYLE_ID;
	style.textContent = CSS;
	target.head.appendChild( style );
}
