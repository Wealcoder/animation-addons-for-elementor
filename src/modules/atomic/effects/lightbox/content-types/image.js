/* eslint-env browser */

/**
 * Image content-type. Renders a slide's image into the stage, with a loading
 * spinner while the full-size source decodes. Exposes a simple contract shared
 * by every content-type:
 *
 *   { name, match(slide), render(slide, stage) -> { el, destroy } }
 *
 * `render` returns the created media element (for the zoom controller to grab)
 * and a `destroy` to tear it down when the slide changes.
 */
export const imageType = {
	name: 'image',

	match(slide) {
		return !slide.type || slide.type === 'image';
	},

	render(slide, stage) {
		const spinner = document.createElement('div');
		spinner.className = 'aae-lb-loading';
		stage.appendChild(spinner);

		const img = document.createElement('img');
		img.className = 'aae-lb-media';
		img.alt = slide.alt || slide.title || '';
		img.draggable = false;

		const done = () => {
			if (spinner.parentNode) spinner.parentNode.removeChild(spinner);
		};
		img.addEventListener('load', done, { once: true });
		img.addEventListener('error', () => {
			done();
			img.alt = 'Image failed to load';
		}, { once: true });

		img.src = slide.src;
		stage.appendChild(img);

		return {
			el: img,
			destroy() {
				if (img.parentNode) img.parentNode.removeChild(img);
				if (spinner.parentNode) spinner.parentNode.removeChild(spinner);
			},
		};
	},
};
