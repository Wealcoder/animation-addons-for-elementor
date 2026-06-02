/* eslint-env browser */

const {
	configFor,
	pickConfigResponsive,
	getGsap,
	getScrollTrigger,
} = window.AAEADDON;

export const STICKY_MAP = 'AAE_INTERACTIONS_STICKY';

/* ==========================================================================
   Helpers
   ========================================================================== */

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

/* ==========================================================================
   Read Config
   ========================================================================== */

function readSticky(el) {

	const cfg = configFor(el, STICKY_MAP);
	
	if (!cfg) {
		return null;
	}

	const isEnabled = r(cfg, 'enable', false);

	if (!isEnabled) {
		return null;
	}

	return {

		enabled: true,		

		pinTrigger:       r(cfg, 'pinTrigger', 'default'),
		customPinArea:    r(cfg, 'customPinArea', ''),	

		pinEndTrigger:    r(cfg, 'pinEndTrigger', 'default'),
		customPinEndArea: r(cfg, 'customPinEndArea', ''),		

		pin:              r(cfg, 'pin', true),
		customPin:        r(cfg, 'customPin', ''),	

		pinStart:         r(cfg, 'pinStart', 'top top'),
		pinEnd:           r(cfg, 'pinEnd', 'bottom bottom'),

		pinSpacing:       r(cfg, 'pinSpacing', false),		

		pinMarkers:       cfg.pinMarkers === true,

		border:           r(cfg, 'border', null),
		toggleClass:      r(cfg, 'toggleClass', ''),
		bgColor:          r(cfg, 'bgColor', ''),
		
		enableEditor:     cfg.enableEditor === 'yes' || cfg.enableEditor === true,
	};
}

/* ==========================================================================
   Bind
   ========================================================================== */

function bindSticky(el, config) {

	cleanupSticky(el); // Prevent redundant bindings
	
	if (!config?.enabled) {
		return;
	}
	
	// if editor mode then return
	if (window.elementorFrontend && window.elementorFrontend.isEditMode() && config.enableEditor !== true) {			
		return;
	}

	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();
	
	if (!gsap || !ScrollTrigger) return;

	let trigger = el;
	if (config.pinTrigger === 'custom' && config.customPinArea) {
		const customTrigger = document.querySelector(config.customPinArea);
		if (customTrigger) trigger = customTrigger;
	}

	let endTrigger = 'body';
	if (config.pinEndTrigger === 'custom' && config.customPinEndArea) {
		const customEndTrigger = document.querySelector(config.customPinEndArea);
		if (customEndTrigger) endTrigger = customEndTrigger;
	}

	let pin = config.pin;
	if (pin === 'custom' && config.customPin) {
		const customPinEl = document.querySelector(config.customPin);
		if (customPinEl) pin = customPinEl;
	}

	let start = config.pinStart;
	let end = config.pinEnd;
	
	let pinSpacing = config.pinSpacing === 'custom' ? false : config.pinSpacing;

	const elementsToStrip = [el];
	if (trigger instanceof HTMLElement && trigger !== el) elementsToStrip.push(trigger);
	if (pin instanceof HTMLElement && pin !== el && pin !== trigger) elementsToStrip.push(pin);

	// Force remove transitions and enable hardware acceleration
	elementsToStrip.forEach(node => {
		if (node && node.style) {
			node.style.setProperty('transition', 'none', 'important');
			node.style.setProperty('will-change', 'transform', 'important');
		}
	});
	el.__aaeStickyElements = elementsToStrip;

	let prevY = null;
	const EPS = 0.5;
	let translating = false;

	const setTranslating = (isOn) => {
		if (isOn === translating) return;
		translating = isOn;
		if (trigger) {
			trigger.classList.toggle('aae-is-translating', isOn);
			if (trigger.parentElement) {
				trigger.parentElement.classList.toggle('aae-is-translating', isOn);
			}
		}
	};

	let tempConfig = {
		trigger: trigger,
		endTrigger: endTrigger,
		pin: pin,
		pinSpacing: pinSpacing,
		start: start,
		end: end,
		invalidateOnRefresh: true,
		markers: config.pinMarkers,
		onToggle: self => {
			if (self.isActive) {
				self.trigger.classList.add("aae-pro-sticky-active");
				
				let cssProps = {};
				if (config.bgColor) cssProps.backgroundColor = config.bgColor;
				if (config.border) {
					const b = config.border;
					if (b.style && b.style !== 'none') {
						const wUnit = b.width?.unit || 'px';
						cssProps.borderStyle = b.style;
						cssProps.borderWidth = `${b.width?.top || 0}${wUnit} ${b.width?.right || 0}${wUnit} ${b.width?.bottom || 0}${wUnit} ${b.width?.left || 0}${wUnit}`;
						cssProps.borderColor = b.color || '#000';
					}
					if (b.radius) {
						if (typeof b.radius === 'object') {
							const rUnit = b.radius.unit || 'px';
							cssProps.borderRadius = `${b.radius.top || 0}${rUnit} ${b.radius.right || 0}${rUnit} ${b.radius.bottom || 0}${rUnit} ${b.radius.left || 0}${rUnit}`;
						} else {
							cssProps.borderRadius = b.radius;
						}
					}
				}
				
				if (Object.keys(cssProps).length > 0) {
					gsap.to(self.trigger, { ...cssProps, duration: 0.3, overwrite: "auto" });
				}

				if (config.toggleClass) {
					const classes = config.toggleClass.replace(/^\.+/, '').split(' ').filter(Boolean);
					if (classes.length) self.trigger.classList.add(...classes);
				}
			} else {
				self.trigger.classList.remove("aae-pro-sticky-active");
				
				let clearProps = [];
				if (config.bgColor) clearProps.push('backgroundColor');
				if (config.border) {
					if (config.border.style && config.border.style !== 'none') {
						clearProps.push('borderStyle', 'borderWidth', 'borderColor');
					}
					if (config.border.radius) clearProps.push('borderRadius');
				}
				
				if (clearProps.length > 0) {
					gsap.to(self.trigger, { clearProps: clearProps.join(','), duration: 0.3, overwrite: "auto" });
				}

				if (config.toggleClass) {
					const classes = config.toggleClass.replace(/^\.+/, '').split(' ').filter(Boolean);
					if (classes.length) self.trigger.classList.remove(...classes);
				}
			}
		},
		onUpdate: () => {
			if (trigger) {
				const y = gsap.getProperty(trigger, 'y') || 0;
				if (prevY === null) prevY = y;
				
				const changed = Math.abs(y - prevY) > EPS;
				setTranslating(changed);
				prevY = y;

				if (!changed && translating) {
					requestAnimationFrame(() => {
						const y2 = gsap.getProperty(trigger, 'y') || 0;
						setTranslating(Math.abs(y2 - y) > EPS);
					});
				}
			}
		}
	};

	

	if (!endTrigger) {
		delete tempConfig.endTrigger;
	}
   
	el.__aaeStickyInstance = ScrollTrigger.create(tempConfig);
}

/* ==========================================================================
   Cleanup
   ========================================================================== */

function cleanupSticky(el) {
	if (el.__aaeStickyInstance) {
		el.__aaeStickyInstance.kill();
		delete el.__aaeStickyInstance;
	}

	el.classList.remove("aae-pro-sticky-active", "aae-is-translating");
	if (el.parentElement) {
		el.parentElement.classList.remove("aae-is-translating");
	}
	
	const gsap = getGsap && getGsap();
	if (gsap) {
		const elementsToRestore = el.__aaeStickyElements || [el];
		elementsToRestore.forEach(node => {
			if (node && node.style) {
				node.style.transition = '';
				node.style.willChange = '';
			}
		});
	}
	delete el.__aaeStickyElements;
}

function resetSticky(el) {
	cleanupSticky(el);
}

export function playSticky(el, config) {
	cleanupSticky(el);
	bindSticky(el, config);
}

/* ==========================================================================
   Register
   ========================================================================== */

window.AAEADDON.register({
	name:      'sticky',
	mapName:   STICKY_MAP,
	boundFlag: 'aae-sticky-bound',
	read:      readSticky,
	play:      playSticky,
	bind:      bindSticky,
	unbind:    cleanupSticky,
	reset:     resetSticky,
});