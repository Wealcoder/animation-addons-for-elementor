import { showPlayButton } from './predicates';
/**
 * Image Hover (Reveal-on-Hover) responsive fields.
 *
 * The native PHP Section already renders three primitives ABOVE this
 * placeholder (Hover Image, Enable On Editor, Z-Index). This config table
 * only owns the responsive numerics — Width / Height / Top / Left —
 * shown unconditionally; the effect itself activates frontend-side as
 * soon as the user picks a real image (vs the placeholder default).
 *
 * All px implicit (no unit picker).
 */
const config = {
	anchorKey: 'aae-section-aae-image-hover',
	bindPrefix: 'aae_ih_',
	fields: [

		{
			bind: 'width', label: 'Width (px)', control: 'number',
			defaultValue: 300
		},
		{
			bind: 'height', label: 'Height (px)', control: 'number',
			defaultValue: 300
		},
		{
			bind: 'top', label: 'Top (px)', control: 'number',
			defaultValue: -15
		},
		{
			bind: 'left', label: 'Left (px)', control: 'number',
			defaultValue: 30
		},
		{
			bind: 'enable_editor', label: 'Enable in Editor', control: 'switch',
			defaultValue: false, responsive: false
		},
		// Manual sync — Elementor's native controls (Hover Image, Z-Index,
		// Enable On Editor) don't always fire the live-bridge change event,
		// so the preview iframe's IMGHOVER cfg can lag behind the panel.
		// Click Play to push the latest cfg into the iframe and rebind.
		{ control: 'play-button', when: showPlayButton },
	],
};

export default config;
