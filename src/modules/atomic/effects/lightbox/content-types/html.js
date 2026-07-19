/* eslint-env browser */

/**
 * HTML content-type. Renders arbitrary HTML (a cloned DOM child) into the
 * stage — used by the container "Full Child Content" mode, where each direct
 * child of a lightbox container becomes a slide showing its whole markup
 * (heading + text + button + image, etc.).
 *
 * The slide carries `html` (a string) OR `node` (a detached element to mount).
 * `node` is preferred: container.js clones the live child so what you see in
 * the lightbox matches the page exactly.
 *
 * Caveat: interactive JS bound to the original element (sliders, forms, custom
 * handlers) does NOT re-initialize inside the clone — this shows content, not a
 * live interactive copy. Links and native controls still work.
 */
export const htmlType = {
	name: 'html',

	match(slide) {
		return slide.type === 'html' || slide.type === 'content';
	},

	render(slide, stage) {
		const wrap = document.createElement('div');
		wrap.className = 'aae-lb-html'; 

		if (slide.node instanceof Node) {
			wrap.appendChild(slide.node);
		} else if (typeof slide.html === 'string') {
			wrap.innerHTML = slide.html;
		}

		stage.appendChild(wrap);

		return {
			el: wrap,
			destroy() {
				if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
			},
		};
	},
};
