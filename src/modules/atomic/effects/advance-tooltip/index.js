/* eslint-env browser */
import './index.css';

const {
	configFor,
	pickConfigResponsive,
	BP_CASCADE,
} = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_ADVANCE_TOOLTIP';
const TOOLTIP_OVERLAY_KEY = '__aaeTooltipOverlay';
const TOOLTIP_DISPOSE_KEY = '__aaeTooltipDispose';
const TOOLTIP_PLAYED = '__aaeTooltipPlayed';

let cssLoaded = false;
function ensureStylesheet() {
	if (cssLoaded) return;
	const url = window.AAE_CONFIG?.tooltip_css_url || (window.WCF_ADDONS_URL ? window.WCF_ADDONS_URL + 'assets/build/modules/atomic/effects/advance-tooltip.css' : '');

	if (!url) return;
	if (document.querySelector(`link[href="${url}"]`)) {
		cssLoaded = true;
		return;
	}
	const link = document.createElement('link');
	link.rel = 'stylesheet';
	link.href = url;
	document.head.appendChild(link);
	cssLoaded = true;
}

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

function resolvePositionFor(cfg, bp) {
	const chain = BP_CASCADE[bp] || [];
	for (const step of chain) {
		const v = cfg['position_' + step];
		if (v !== undefined && v !== '') return v;
	}
	return cfg['position'] || 'top';
}

function read(el) {
	const cfg = configFor(el, MAP);

	if (!cfg) return null;

	const enabled = pickConfigResponsive(cfg, 'enabled');
	if (!enabled || enabled === 'false' || enabled === 'no') return null;

	const arrowEnableVal = pickConfigResponsive(cfg, 'arrowEnable');
	const arrowEnable = (arrowEnableVal !== false && arrowEnableVal !== 'false' && arrowEnableVal !== 'no');

	return {
		enabled: true,
		text: r(cfg, 'text', ''),
		position: r(cfg, 'position', 'top'),
		trigger: r(cfg, 'trigger', 'hover'),
		bg: r(cfg, 'bg', '#000000'),
		color: r(cfg, 'color', '#ffffff'),
		width: r(cfg, 'width', '200px'),
		offset: Number(r(cfg, 'offset', 10)),
		arrowEnable: arrowEnable,
		animation: r(cfg, 'animation', 'fade'),
		duration: Number(r(cfg, 'duration', 0.3)),
		arrowSize: Number(r(cfg, 'arrowSize', 10)),
		alignment: r(cfg, 'alignment', 'center'),
		borderRadius: pickConfigResponsive(cfg, 'borderRadius') || null,
	};
}

function ensureTooltip(el) {
	let tooltip = el[TOOLTIP_OVERLAY_KEY];
	if (!tooltip || !tooltip.isConnected) {
		tooltip = el.querySelector(':scope > .wcf-advanced-tooltip');
		if (!tooltip) {
			tooltip = document.createElement('span');
			tooltip.className = 'wcf-advanced-tooltip animated';
			el.appendChild(tooltip);
		}
		el[TOOLTIP_OVERLAY_KEY] = tooltip;
	}

	// Remove any duplicate tooltips
	const others = el.querySelectorAll(':scope > .wcf-advanced-tooltip');
	others.forEach((node) => {
		if (node !== tooltip) node.parentNode?.removeChild(node);
	});

	return tooltip;
}

function bind(el, config) {	
	ensureStylesheet();
	unbind(el);
	const tooltip = ensureTooltip(el);
	const cfg = configFor(el, MAP) || {};

	// Set content
	const parsed = new DOMParser()
		.parseFromString(config.text, "text/html")
		.body.childNodes;
	tooltip.innerHTML = "";
	tooltip.append(...parsed);

	// Set layout position (relative container)
	if (getComputedStyle(el).position === 'static') {
		el.style.position = 'relative';
	}

	// Resolve and apply responsive classes
	const classesToRemove = [];
	for (const className of el.classList) {
		if (className.startsWith('wcf-advanced-tooltip-')) {
			classesToRemove.push(className);
		}
	}
	classesToRemove.forEach(cls => el.classList.remove(cls));

	const posDesktop = resolvePositionFor(cfg, 'desktop');
	const posTablet = resolvePositionFor(cfg, 'tablet');
	const posMobile = resolvePositionFor(cfg, 'mobile');

	el.classList.add(`wcf-advanced-tooltip-${posDesktop}`);
	el.classList.add(`wcf-advanced-tooltip-tablet-${posTablet}`);
	el.classList.add(`wcf-advanced-tooltip-mobile-${posMobile}`);

	// Set inline styles on the tooltip
	tooltip.style.backgroundColor = config.bg;
	tooltip.style.setProperty('--tooltip-arrow-color', config.bg);
	tooltip.style.color = config.color;
	tooltip.style.width = config.width;
	tooltip.style.textAlign = config.alignment;

	const durationMs = config.duration < 10 ? config.duration * 1000 : config.duration;
	tooltip.style.animationDuration = durationMs + "ms";
	tooltip.style.transitionDuration = durationMs + "ms";

	tooltip.style.setProperty('--tooltip-arrow-distance', config.offset + 'px');

	if (!config.arrowEnable) {
		tooltip.classList.add("no-arrow");
	} else {
		tooltip.classList.remove("no-arrow");
	}

	if (config.borderRadius && typeof config.borderRadius === 'object') {
		const br = config.borderRadius;
		const unit = br.unit || 'px';
		tooltip.style.borderRadius = `${br.top || 0}${unit} ${br.right || 0}${unit} ${br.bottom || 0}${unit} ${br.left || 0}${unit}`;
	} else {
		tooltip.style.borderRadius = '';
	}

	// Set animation class permanently so transitions start from the correct initial state
	tooltip.classList.remove('fade', 'slide', 'scale');
	if (config.animation) {
		tooltip.classList.add(config.animation);
	}

	// Trigger listeners
	const onMouseEnter = () => {
		tooltip.classList.add("show");
	};
	const onMouseLeave = () => {
		tooltip.classList.remove("show");
	};

	let toggleShow = null;
	if (config.trigger === "click") {
		toggleShow = (e) => {
			if (tooltip.classList.contains("show")) {
				tooltip.classList.remove("show");
			} else {
				tooltip.classList.add("show");
			}
		};
		el.addEventListener("click", toggleShow);
	} else {
		el.addEventListener("mouseenter", onMouseEnter);
		el.addEventListener("mouseleave", onMouseLeave);
	}

	el[TOOLTIP_PLAYED] = tooltip;
	el[TOOLTIP_DISPOSE_KEY] = () => {
		if (toggleShow) {
			el.removeEventListener("click", toggleShow);
		} else {
			el.removeEventListener("mouseenter", onMouseEnter);
			el.removeEventListener("mouseleave", onMouseLeave);
		}
	};
}

function play(el, config) {
	ensureStylesheet();
	unbind(el);
	bind(el, config);

	const tooltip = el[TOOLTIP_OVERLAY_KEY];
	if (tooltip) {
		tooltip.classList.add("show");
		setTimeout(() => {
			if (!el.matches(':hover') && el[TOOLTIP_OVERLAY_KEY] === tooltip) {
				tooltip.classList.remove("show");
			}
		}, 2000);
	}
}

function unbind(el) {
	const dispose = el[TOOLTIP_DISPOSE_KEY];
	if (typeof dispose === 'function') {
		try { dispose(); } catch (_) { /* ignore */ }
	}
	el[TOOLTIP_DISPOSE_KEY] = null;

	const classesToRemove = [];
	for (const className of el.classList) {
		if (className.startsWith('wcf-advanced-tooltip-')) {
			classesToRemove.push(className);
		}
	}
	classesToRemove.forEach(cls => el.classList.remove(cls));

	const tooltip = el[TOOLTIP_OVERLAY_KEY];
	if (tooltip && tooltip.parentNode) {
		try { tooltip.parentNode.removeChild(tooltip); } catch (_) { /* ignore */ }
	}
	el[TOOLTIP_OVERLAY_KEY] = null;
	delete el[TOOLTIP_PLAYED];
}

window.AAEADDON.register({
	name: 'advance-tooltip',
	mapName: MAP,
	boundFlag: 'aae-advance-tooltip-bound',
	playedKey: TOOLTIP_PLAYED,
	read,
	play,
	bind,
	unbind,
	reset: unbind,
});

