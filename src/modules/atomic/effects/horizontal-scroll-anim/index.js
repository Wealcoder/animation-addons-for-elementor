
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
	const cfg =	configFor(el, MAP);
	if (!cfg) return null;

	const enabled = pickConfigResponsive(cfg, 'enabled');
	if (!enabled) {
		return null;
	}

	const resolved = {
		enabled,
		width: r(cfg, 'width', '300%'),
		end: r(cfg, 'end', '3000'),
		start: r(cfg, 'start', 'top top'),
	};
	
	return resolved;
}

function bind(el, config) {	
	unbind(el); // Prevent redundant bindings
	// if editor mode then return
	if (window.elementorFrontend && window.elementorFrontend.isEditMode()) {
		return;
	}
	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();

	if (!gsap || !ScrollTrigger) return;

	gsap.set(el, { 
		width: config.width,
		flexWrap: 'nowrap',
		overflowX: 'hidden',
		maxWidth: `min(100%, ${config.width})`,
		height: 'auto'
	});
	// Get an array of only the direct child panels, .elementor-element-overlay is the overlay that is added by elementor
	const panels = gsap.utils.toArray(el.children).filter(panel => !panel.classList.contains('elementor-element-overlay'));
	const totalPanels = panels.length;

	gsap.set(el,{transition: "none"	});

	if (totalPanels > 0) {
		// Strip any native Elementor layout transitions that might fight GSAP
		// set child width 100% if not set any css value
		panels.forEach(panel => {
			const propsToSet = { transition: "none", flexShrink: 0 };
			gsap.set(panel, propsToSet);
		});	

		let tween;

		if (totalPanels === 1) {
			const singleChild = panels[0];
			tween = gsap.to(singleChild, {
				x: () => -(singleChild.scrollWidth - window.innerWidth),
				ease: "none",
				scrollTrigger: {
					trigger: el,
					pin: el,
					scrub: 1,
					start: config.start,
					end: () => "+=" + Math.max(0, singleChild.scrollWidth - window.innerWidth),
					invalidateOnRefresh: true
				}
			});
		} else {
			tween = gsap.to(panels, {
				xPercent: -100 * (totalPanels - 1),
				ease: "none",
				scrollTrigger: {
					trigger: el,
					pin: el,				
					scrub: 1,
					start: config.start,
					end: "+=" + config.end,
				}
			});
		}

		el.__aaeHorizontalDispose = () => {
			if (tween && tween.scrollTrigger) tween.scrollTrigger.kill();
			if (tween) tween.kill();
			gsap.set(el, { clearProps: "width,flexWrap,overflowX,transition" });
			panels.forEach(panel => gsap.set(panel, { clearProps: "transition,xPercent,x" }));
		};
	}
}

function unbind(el) {
	if (el.__aaeHorizontalDispose) {
		el.__aaeHorizontalDispose();
		delete el.__aaeHorizontalDispose;
	}
}

window.AAEADDON.register({
	name: 'horizontal',
	mapName: MAP,
	boundFlag: 'aae-horizontal-bound',
	read,	
	bind,
	unbind,
	reset: unbind,
});