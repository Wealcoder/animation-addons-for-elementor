const {
	configFor,
	pickConfigResponsive,
	getGsap,
	getScrollTrigger,
} = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_HORIZONTAL';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

function read(el) {

	const cfg = configFor(el, MAP);

	if (!cfg) {
		return null;
	}

	const enabled = pickConfigResponsive(
		cfg,
		'enabled'
	);

	if (!enabled) {
		return null;
	}

	return {
		enabled,
		start: r(cfg, 'start', 'top top'),
		markers: r(cfg, 'markers', false),
	};
}

function bind(el, config) {

	unbind(el); // Prevent redundant bindings

	// if editor mode then return
	// if (
	// 	window.elementorFrontend &&
	// 	window.elementorFrontend.isEditMode()
	// ) {
	// 	return;
	// }

	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();

	if (!gsap || !ScrollTrigger) {
		return;
	}

	// Base layout styles
	gsap.set(el, {
		display: 'flex',
		flexWrap: 'nowrap',
		height: 'auto',
		transition: 'none',
		clearProps: 'transform',
	});

	// Get an array of only the direct child panels
	// .elementor-element-overlay is the overlay that is added by elementor
	const panels = gsap
		.utils
		.toArray(el.children)
		.filter(
			panel =>
				!panel.classList.contains(
					'elementor-element-overlay'
				)
		);

	const totalPanels = panels.length;

	if (totalPanels <= 0) {
		return;
	}

	// Strip any native Elementor layout transitions that might fight GSAP
	panels.forEach(panel => {

		gsap.set(panel, {
			transition: 'none',
			flexShrink: 0,
		});

	});

	let tween;

	// Create wrapper for proper pinning
	let wrapper;

	if (
		el.parentNode &&
		el.parentNode.classList.contains(
			'aae-horizontal-wrapper'
		)
	) {

		wrapper = el.parentNode;

	} else {

		wrapper = document.createElement('div');

		wrapper.className =
			'aae-horizontal-wrapper';

		wrapper.style.width = '100%';
		wrapper.style.overflow = 'hidden';
		wrapper.style.position = 'relative';

		el.parentNode.insertBefore(
			wrapper,
			el
		);

		wrapper.appendChild(el);
	}

	// Calculate real scroll amount
	const getScrollAmount = () => {

		const totalWidth = panels.reduce(
			(total, panel) => {

				return (
					total +
					panel.getBoundingClientRect().width
				);

			},
			0
		);

		return Math.max(
			0,
			totalWidth - window.innerWidth
		);
	};

	if (totalPanels === 1) {

		const singleChild = panels[0];

		tween = gsap.to(singleChild, {

			x: () => -getScrollAmount(),

			ease: 'none',

			force3D: false,

			scrollTrigger: {
				trigger: wrapper,
				pin: wrapper,
				scrub: 1,
				start: config.start,
				markers: config.markers,

				end: () =>
					`+=${getScrollAmount()}`,

				invalidateOnRefresh: true,
			},
		});

	} else {

		tween = gsap.to(el, {

			x: () => -getScrollAmount(),

			ease: 'none',

			force3D: false,

			scrollTrigger: {
				trigger: wrapper,
				pin: wrapper,
				scrub: 1,
				start: config.start,
				markers: config.markers,

				end: () =>
					`+=${getScrollAmount()}`,

				invalidateOnRefresh: true,
			},
		});
	}

	el.__aaeHorizontalDispose = () => {

		if (tween?.scrollTrigger) {
			tween.scrollTrigger.kill();
		}

		if (tween) {
			tween.kill();
		}

		// Move element back before removing wrapper
		if (
			el.parentNode &&
			el.parentNode.classList.contains(
				'aae-horizontal-wrapper'
			)
		) {

			const parent = el.parentNode;

			parent.parentNode.insertBefore(
				el,
				parent
			);

			parent.remove();
		}

		gsap.set(el, {
			clearProps:
				"display,flexWrap,height,transition,x,transform"
		});

		panels.forEach(panel => {

			gsap.set(panel, {
				clearProps:
					"transition,flexShrink,x,transform"
			});

		});
	};
}

function unbind(el) {

	if (el.__aaeHorizontalDispose) {

		el.__aaeHorizontalDispose();

		delete el.__aaeHorizontalDispose;
	}
}

function play(el, config) {
	
	bind(el, config);
}

window.AAEADDON.register({
	name: 'horizontal',
	mapName: MAP,
	boundFlag: 'aae-horizontal-bound',
	read,
	bind,
	unbind,
	play,
	reset: unbind,
});