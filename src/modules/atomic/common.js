/* eslint-env browser */

/**
 * Animation Addons — Atomic Core Runtime
 *
 * This is the always-loaded core. Each animation kind lives in its own
 * per-bundle file under `effects/` and registers itself with the runtime
 * at load time via `window.AAERegistry.register(kind)`.
 *
 * Loading flow (server-driven):
 *   1. Render.php sees an AAE setting on a widget and enqueues frontend.js.
 *   2. Render.php inspects which effect(s) the page actually uses and
 *      enqueues only those per-effect bundles (e.g. effects/animation.js,
 *      effects/tilt.js).
 *   3. Each effect file calls AAERegistry.register({...}) once it loads.
 *   4. Register triggers an automatic re-scan so newly-arrived kinds bind
 *      any matching elements already in the DOM.
 *
 * Kind interface (what each effect file must pass to register):
 *   {
 *     name:      'tilt',                       // logs / debugging
 *     selector:  '[data-aae-tilt-enable]',     // scan() queries this
 *     boundFlag: 'aae-tilt-bound',             // class to prevent double-bind
 *     playedKey: '__aaeTiltPlayed',            // cached tween / handle on el
 *     read(el)    → config | null,             // null = effect off
 *     play(el, c) → void,                      // run the GSAP tween
 *     bind(el, c) → void,                      // wire trigger → play
 *   }
 *
 * Helpers (getGsap, pickResponsive, etc.) are exported so per-effect
 * bundles can `import` them at build time — webpack inlines the imports
 * per bundle, so each effect file ships only the helper code it needs.
 */

/* =====================================================================
 * Shared helpers — exported for per-effect bundles
 * =================================================================== */

export function getGsap() {
	return typeof window !== 'undefined' ? window.gsap : null;
}

export function getScrollTrigger() {
	return typeof window !== 'undefined' ? window.ScrollTrigger : null;
}

/** Snake-case breakpoint key → camelCase suffix for dataset access. */
export function bpToSuffix(bp) {
	return bp.split('_')
		.map((p) => p.charAt(0).toUpperCase() + p.slice(1))
		.join('');
}

/**
 * Active breakpoint key for the current viewport. Prefers Elementor's own
 * resolver. Falls back to a minimal width check.
 */
export function currentBreakpoint() {
	const ef = window.elementorFrontend;
	if (typeof ef?.getCurrentDeviceMode === 'function') {
		try {
			const mode = ef.getCurrentDeviceMode();
			if (mode) return mode;
		} catch (_) { /* fall through */ }
	}
	const bp = ef?.config?.responsive?.breakpoints || ef?.config?.breakpoints || {};
	const tabletMax = bp.lg?.value || bp.tablet?.value || bp.lg || bp.tablet || 1024;
	const mobileMax = bp.md?.value || bp.mobile?.value || bp.md || bp.mobile || 768;
	const w = window.innerWidth;
	if (w <= mobileMax) return 'mobile';
	if (w <= tabletMax) return 'tablet';
	return 'desktop';
}

/** Cascade: for each mode, parent chain to walk for a non-empty value. */
export const BP_CASCADE = {
	mobile:       [ 'mobile', 'tablet' ],
	mobile_extra: [ 'mobile_extra', 'mobile', 'tablet' ],
	tablet:       [ 'tablet' ],
	tablet_extra: [ 'tablet_extra', 'tablet' ],
	laptop:       [ 'laptop' ],
	desktop:      [],
	widescreen:   [ 'widescreen' ],
};

/** Pick a `data-aae-*` value for the active breakpoint, walking the cascade. */
export function pickResponsive(el, baseKey) {
	const bp = currentBreakpoint();
	const chain = BP_CASCADE[bp] || [];
	for (const step of chain) {
		const v = el.dataset[baseKey + bpToSuffix(step)];
		if (v !== undefined && v !== '') return v;
	}
	return el.dataset[baseKey];
}

/* =====================================================================
 * Kind registry — populated at runtime by per-effect bundles
 * =================================================================== */

const KINDS = [];

/** Find the first registered kind that recognises this element. */
function kindFor(el) {
	for (const kind of KINDS) {
		if (el.matches?.(kind.selector)) return kind;
	}
	return null;
}

/* =====================================================================
 * Dispatcher: scan / rebind / replay
 * =================================================================== */

/** Walk a root and bind every animated element. Registration order = precedence. */
function scan(root) {
	const scope = root && root.querySelectorAll ? root : document;

	for (const kind of KINDS) {
		// Skip elements that an earlier kind already bound (same root may carry
		// multiple dispatch attrs when both Regular and Text effects are set).
		const skipFlags = KINDS
			.filter((k) => k !== kind && KINDS.indexOf(k) < KINDS.indexOf(kind))
			.map((k) => `:not(.${k.boundFlag})`)
			.join('');
		const filter = `:not(.${kind.boundFlag})` + skipFlags;

		scope.querySelectorAll(kind.selector + filter).forEach((el) => {
			const config = kind.read(el);
			if (!config) return;
			el.classList.add(kind.boundFlag);
			kind.bind(el, config);
		});
	}
}

/** Clear bound state and re-bind one element. */
function rebind(el) {
	if (!el) return;
	for (const kind of KINDS) {
		el.classList.remove(kind.boundFlag);
		if (el[kind.playedKey]) {
			el[kind.playedKey].kill?.();
			delete el[kind.playedKey];
		}
	}

	const kind = kindFor(el);
	if (!kind) return;
	const config = kind.read(el);
	if (!config) return;
	el.classList.add(kind.boundFlag);
	kind.bind(el, config);
}

/** Force-replay (used by editor Play Now). Falls through to descendants. */
function replay(el) {
	if (!el) return;

	const kind = kindFor(el);
	if (kind) {
		const config = kind.read(el);
		if (config) {
			if (el[kind.playedKey]) {
				el[kind.playedKey].kill?.();
				delete el[kind.playedKey];
			}
			kind.play(el, config);
		}
		return;
	}

	// No animation on this element — try descendants.
	if (!KINDS.length) return;
	const allSelectors = KINDS.map((k) => k.selector).join(', ');
	el.querySelectorAll(allSelectors).forEach(replay);
}

/* =====================================================================
 * Public registry — per-effect bundles call register(kind)
 * =================================================================== */

const Registry = {
	/** Register a new animation kind. Triggers a rescan so already-rendered
	 *  elements bind immediately. Returns the kind for chaining. */
	register(kind) {
		if (!kind?.selector || typeof kind.read !== 'function') return kind;
		if (KINDS.some((k) => k.name === kind.name)) return kind; // dedupe by name
		KINDS.push(kind);
		// Re-scan once the registration settles in the microtask queue, so
		// effect files that register at module top-level still see the DOM.
		Promise.resolve().then(() => scan(document));
		return kind;
	},
	scan,
	rebind,
	replay,
};

window.AAERegistry = Registry;

// Backwards-compat alias used by editor-bridge.js — the existing API.
window.aaeAtomicAnimations = { scan, rebind, replay };

/* =====================================================================
 * Bootstrap
 * =================================================================== */

function init() {
	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();
	if (ScrollTrigger && gsap?.registerPlugin) {
		gsap.registerPlugin(ScrollTrigger);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => scan(document));
	} else {
		scan(document);
	}

	window.addEventListener('elementor/element/render', (event) => {
		const el = event.detail && event.detail.element;
		if (el && el.matches) {
			for (const kind of KINDS) {
				if (el.matches(kind.selector)) {
					rebind(el);
					break;
				}
			}
		}
		scan(el);
	});

	window.addEventListener('elementor/frontend/init', () => {
		if (window.elementorFrontend?.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/global', ($scope) => {
				scan($scope && $scope[0]);
			});
		}
	});
}

init();
