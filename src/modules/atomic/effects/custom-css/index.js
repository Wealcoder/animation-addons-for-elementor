/* eslint-env browser */

const CustomCSSKind = {
	name: 'custom-css',
	mapName: 'AAE_INTERACTIONS_CUSTOM_CSS',
	boundFlag: 'aae-custom-css-bound',
	playedKey: '__aaeCustomCssHandle',

	read(el) {
		return window.AAEADDON.configFor(el, 'AAE_INTERACTIONS_CUSTOM_CSS');
	},

	bind(el, config) {
		if (!config || !window.AAEADDON.pickConfigResponsive(config, 'enabled')) {
			return;
		}

		const id = window.AAEADDON.interactionIdFor(el);
		if (!id) {
			return;
		}

		let css = window.AAEADDON.pickConfigResponsive(config, 'css');

		if (!css) {
			return;
		}

		// Replace "selector" with the specific widget attribute
		css = css.replace(/selector/g, `[data-interaction-id="${id}"]`);

		let style = document.getElementById(`aae-atomic-css-${id}`);
		if (!style) {
			style = document.createElement('style');
			style.id = `aae-atomic-css-${id}`;
			document.head.appendChild(style);
		}
		
		style.textContent = css;

		el.__aaeCustomCssHandle = style;
		el.classList.add('has-aae-custom-css');
	},

	play(el, config) {		
		CustomCSSKind.unbind(el);
		CustomCSSKind.bind(el, config);
 	},

	reset(el) {
		if (el.__aaeCustomCssHandle) {
			el.__aaeCustomCssHandle.remove();
			delete el.__aaeCustomCssHandle;
		}
		el.classList.remove('has-aae-custom-css');
	},

	unbind(el) {
		if (el.__aaeCustomCssHandle) {
			el.__aaeCustomCssHandle.remove();
			delete el.__aaeCustomCssHandle;
		}
		el.classList.remove('has-aae-custom-css');
	},
};

window.AAEADDON.register(CustomCSSKind);
