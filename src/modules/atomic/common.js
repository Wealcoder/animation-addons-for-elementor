/* eslint-env browser */

/**
 * Animation Addons — Atomic Core Runtime
 *
 * This is the always-loaded core. Each animation kind lives in its own
 * per-bundle file under `effects/` and registers itself with the runtime
 * at load time via `window.AAEADDON.register(kind)`.
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
 *     mapName:   'AAE_INTERACTIONS_TILT',      // window[mapName][interactionId] → config
 *     boundFlag: 'aae-tilt-bound',             // class to prevent double-bind
 *     playedKey: '__aaeTiltPlayed',            // cached tween / handle on el
 *     read(el)    → config | null,             // null = effect off
 *     play(el, c) → void,                      // run the GSAP tween
 *     bind(el, c) → void,                      // wire trigger → play
 *     unbind(el)  → void,                      // OPTIONAL: tear down listeners /
 *                                              //   ScrollTriggers installed by bind.
 *     reset(el)   → void,                      // OPTIONAL: restore pre-anim DOM state.
 *   }
 *
 * Every kind shares the same dispatch attr — Elementor's universal
 * `data-interaction-id` (frontend + editor). Kinds disambiguate by their
 * own map: text reads window.AAE_INTERACTIONS_TEXT[id], regular reads
 * window.AAE_INTERACTIONS_ANIM[id], etc. An element with entries in
 * multiple maps gets bound by multiple kinds independently.
 *
 * Helpers (getGsap, pickConfigResponsive, etc.) live on `window.AAEADDON`
 * so per-effect bundles read them at runtime instead of importing — that
 * keeps shared code in one file and each effect bundle small (~3-4 KB).
 */

/* =====================================================================
 * Shared helpers
 *
 * Exposed on `window.AAEADDON` (see bottom of file). Per-effect bundles MUST
 * NOT `import` these — that would inline them into every bundle. Read
 * from window.AAEADDON at runtime instead. This keeps shared code in one
 * file (common.js) and effect bundles small (~3-4 KB each).
 * =================================================================== */

function getGsap() {
	return typeof window !== 'undefined' ? window.gsap : null;
}

function getScrollTrigger() {
	return typeof window !== 'undefined' ? window.ScrollTrigger : null;
}

function getSplitText() {
	return typeof window !== 'undefined' ? window.SplitText : null;
}

/**
 * Active breakpoint key for the current viewport. Prefers Elementor's own
 * resolver. Falls back to a minimal width check.
 */
function currentBreakpoint() {
	if (window.elementorFrontend && typeof window.elementorFrontend.getCurrentDeviceMode === 'function') {
		const mode = window.elementorFrontend.getCurrentDeviceMode();
		if (mode) return mode;
	}

	if (window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode()) {
		const editor = window.elementor || window.parent?.elementor;
		const editorMode = editor?.channels?.deviceMode?.request?.('currentMode');
		if (editorMode) return editorMode;
	}

	const configBreakpoints = window?.AAE_CONFIG?.breakpoints;
	const breakpoints = (configBreakpoints && Object.keys(configBreakpoints).length > 0)
		? configBreakpoints
		: { mobile: 767, tablet: 1024 };
	const width = window.innerWidth;

	const widescreen = Number(breakpoints.widescreen);
	if (breakpoints.widescreen && !Number.isNaN(widescreen) && width >= widescreen) {
		return 'widescreen';
	}

	const active = Object.entries(breakpoints)
		.filter(([key]) => key !== 'widescreen')
		.map(([key, val]) => ({ key, val: Number(val) }))
		.filter(({ val }) => !Number.isNaN(val))
		.sort((a, b) => a.val - b.val);

	const matched = active.find(({ val }) => width <= val);
	return matched ? matched.key : 'desktop';
}

const BP_CASCADE = {
	mobile: ['mobile', 'mobile_extra', 'tablet', 'tablet_extra', 'laptop'],
	mobile_extra: ['mobile_extra', 'tablet', 'tablet_extra', 'laptop'],
	tablet: ['tablet', 'tablet_extra', 'laptop'],
	tablet_extra: ['tablet_extra', 'laptop'],
	laptop: ['laptop'],
	desktop: [],
	widescreen: ['widescreen'],
};

/* =====================================================================
 * InteractionsMap lookup
 *
 * Server-side Render.php registers each element's config in a per-feature
 * window map (`AAE_INTERACTIONS_TEXT`, `AAE_INTERACTIONS_ANIM`, …) keyed
 * by Elementor's universal `data-interaction-id`. This is the JS-side
 * reader. DOM stays tiny, configs stay structured, and multiple AAE
 * features can attach to the same element without attr collisions.
 * =================================================================== */

/**
 * Universal id helper. Elementor injects `data-interaction-id` on every
 * atomic widget root (frontend + editor); that's the single source of
 * truth for "which element does this config belong to". AAE doesn't
 * need its own attr.
 */
function interactionIdFor(el) {
	if (!el || !el.dataset) return null;
	return el.dataset.interactionId || null;
}

/**
 * Return the registered config object for an element from a named map.
 *
 *   configFor(el, 'AAE_INTERACTIONS_TEXT')
 *
 * Multiple AAE extensions share the same dispatch attr (data-interaction-id)
 * but each owns its own map (AAE_INTERACTIONS_TEXT, _ANIM, …). Coexistence
 * on a single element works because lookup is per-map.
 */
function configFor(el, mapName) {
	const id = interactionIdFor(el);
	if (!id) return null;
	const map = window[mapName];
	return (map && map[id]) || null;
}

// Track active breakpoint so we don't query the DOM repeatedly during config reads
let activeBp = currentBreakpoint();

/**
 * Pick a config field for the active breakpoint, walking BP_CASCADE.
 * Returns `cfg[key]` when no per-bp override applies (i.e. desktop).
 * Per-bp keys are flat: `duration`, `duration_tablet`, `duration_mobile`.
 */
function pickConfigResponsive(cfg, key) {
	if (!cfg) return undefined;
	const bp = activeBp;

	const chain = BP_CASCADE[bp] || [];
	const isEnableKey = (key === 'enabled' || key === 'enable');

	for (const step of chain) {
		const v = cfg[key + '_' + step];
		if (v !== undefined && v !== '') {
			if (isEnableKey && step !== bp) {
				const vBool = (v === true || v === 'true' || v === 1 || v === '1' || v === 'yes');
				if (vBool) {
					return true;
				}
			} else {
				return v;
			}
		}
	}

	const d = cfg[key];
	if (isEnableKey && d !== undefined) {
		return (d === true || d === 'true' || d === 1 || d === '1' || d === 'yes');
	}
	return d;
}

/* =====================================================================
 * Kind registry — populated at runtime by per-effect bundles
 * =================================================================== */

const KINDS = [];

/* =====================================================================
 * Dispatcher: scan / rebind / replay
 * =================================================================== */

/**
 * Return every kind that owns `el`. An element can carry configs for
 * multiple kinds at once (e.g. regular-animation moving the parent while
 * text-animation reveals per-character spans inside it; or a future tilt
 * kind on top of either). Each kind tweens its own target — text tweens
 * the inner <span> pieces, regular tweens the element itself — so they
 * coexist safely without fighting for the same inline styles.
 *
 * Order in the returned array matches KINDS registration order.
 */
function kindsFor(el) {
	const result = [];
	const id = interactionIdFor(el);
	if (!id) return result;
	for (const kind of KINDS) {
		const map = window[kind.mapName];
		if (map && map[id]) result.push(kind);
	}
	return result;
}

/**
 * Find the nearest ancestor of `el` that ALSO has any active animation
 * kind. Used to chain nested animations: parent fades in first, then
 * child text reveals, then any grandchild plays — instead of all firing
 * simultaneously when initial-replay sweeps the canvas.
 *
 * Returns { ancestor, kinds } or null if no animated ancestor. `kinds`
 * is the full list of kinds bound on that ancestor (needed because we
 * watch the LONGEST-running ancestor tween, so the drain happens after
 * every parent kind finishes).
 */
function findAnimatedAncestor(el) {
	let parent = el.parentElement;
	while (parent) {
		const kinds = kindsFor(parent);
		if (kinds.length) return { ancestor: parent, kinds };
		parent = parent.parentElement;
	}
	return null;
}

/**
 * Run `cb` immediately if no animated ancestor exists or its tween has
 * already finished; otherwise queue `cb` to fire when the ancestor's tween
 * completes. Idempotent — if the ancestor never gets a tween (e.g. only
 * bound, never played), `cb` will be lost on purpose: the child doesn't
 * appear until the parent runs, which is the intended chained behaviour.
 *
 * One queue per ancestor element (`__aaeChildQueue`). The ancestor's
 * `play` adds an `onComplete` that drains the queue.
 */
const CHILD_QUEUE_KEY = '__aaeChildQueue';

function queueOnAncestor(ancestor, cb) {
	const queue = ancestor[CHILD_QUEUE_KEY] = ancestor[CHILD_QUEUE_KEY] || [];
	queue.push(cb);
}

function drainChildQueue(el) {
	const queue = el[CHILD_QUEUE_KEY];
	if (!queue || !queue.length) return;
	el[CHILD_QUEUE_KEY] = [];
	for (const cb of queue) {
		try { cb(); } catch (_) { /* never let one queued child break the rest */ }
	}
}

/**
 * After a kind's play() creates `el[kind.playedKey]` (a GSAP tween), hook
 * its onComplete to drain any queued child animations. Chained call to
 * eventCallback preserves the kind's own onComplete (if any).
 */
function chainCompletionDrain(el, kind) {
	const tween = el[kind.playedKey];
	if (!tween || typeof tween.eventCallback !== 'function') {
		// No tween to hook (e.g. play() early-returned because gsap missing).
		// Drain immediately so children don't get stuck waiting.
		drainChildQueue(el);
		return;
	}
	const prev = tween.eventCallback('onComplete');
	tween.eventCallback('onComplete', () => {
		if (typeof prev === 'function') {
			try { prev(); } catch (_) { /* ignore */ }
		}
		drainChildQueue(el);
	});
}

/** Walk a root and bind every animated element. */
function scan(root) {
	const scope = root && root.querySelectorAll ? root : document;

	// Every kind shares Elementor's universal data-interaction-id, so one
	// querySelectorAll covers all kinds. An element may carry multiple
	// kinds at once (regular + text + future tilt) — each on its own target.
	if (!KINDS.length) {
		//console.log('[AAE Debug] No KINDS registered, aborting scan.');
		return;
	}
	
	// The user explicitly injects data-interaction-id via JS, so we only need to look for that.
	const candidates = Array.from(scope.querySelectorAll('[data-interaction-id]'));
	
	// If the scope node itself matches (e.g. MutationObserver passed the exact widget node), include it!
	// querySelectorAll only searches descendants, so we MUST check the node itself!
	if (scope !== document && scope.matches && scope.matches('[data-interaction-id]')) {
		candidates.push(scope);
	}

	for (const el of candidates) {
		const kinds = kindsFor(el);	

		for (const kind of kinds) {
			if (el.classList.contains(kind.boundFlag)) {
				
				continue;
			}
			const config = kind.read(el);
			if (!config) {
				continue;
			}

			if (config.preventBindInEditor) {
				continue;
			}

			el.classList.add(kind.boundFlag);

			kind.bind(el, config);
		}
	}
}

function isKindInPlayGroup(kindName, playGroup) {
	if (!playGroup) return true;
	const group = playGroup.toLowerCase();
	const groupMap = {
		'aae_text_': 'text',
		'aae_anim_': 'regular',
		'aae_img_': 'image-animation',
		'aae_ih_': 'image-hover',
		'aae_cursor_hover_': 'cursor-hover-effect',
		'aae_sticky_': 'sticky',
		'aae_mouse_move_effect_': 'mouse-move-effect',
		'aae_horizontal_': 'horizontal',
		'aae_plx_': 'parallax',
		'aae_advance_tooltip_': 'advance-tooltip',
		'aae_tilt_': 'tilt',
		'aae_custom_css_': 'custom-css',
		'aae_ns_': 'nested-slider',
		'aae_img_ovl_': 'image-overlay',
	};
	return groupMap[group] === kindName;
}

/** Clear bound state and re-bind one element across every owning kind. */
function rebind(el, playGroup = "") {
	if (!el) return;

	// Full destroy of the previous animation before re-binding:
	//   1. kind.reset → tween.revert() (restores GSAP-applied inline styles,
	//      un-splits text pieces, etc.) — a new effect should always start
	//      from the original DOM state, not from the previous tween's output.
	//   2. kind.unbind → kills the trigger (ScrollTrigger / hover listener /
	//      page-load handle) so no leftover event keeps firing.
	//   3. drop the playedKey handle and remove the bound class.
	//
	// Loop every KIND, not just current owners, so a config that was removed
	// since the last bind (effect=none, deleted setting) still gets fully
	// destroyed.
	for (const kind of KINDS) {
		if (playGroup && !isKindInPlayGroup(kind.name, playGroup)) {
			continue;
		}
		// Nothing to destroy unless THIS kind actually bound or played on this
		// element (bind() sets boundFlag, play() sets playedKey — that's the
		// invariant scan()/rebind() already rely on). Skipping unbound kinds
		// keeps the removed-config guarantee — a stale binding always carries
		// its flag — while making loaded-but-unused effect bundles nearly
		// free: measured on a 281-element page, the destroy loop spent 88% of
		// its time calling reset/unbind for kinds that never touched the
		// element.
		if (!el.classList.contains(kind.boundFlag) && !el[kind.playedKey]) {
			continue;
		}
		if (typeof kind.reset === 'function') {
			try { kind.reset(el); } catch (_) { /* never let reset throw */ }
		}
		if (typeof kind.unbind === 'function') {
			try { kind.unbind(el); } catch (_) { /* never let cleanup throw */ }
		}
		el.classList.remove(kind.boundFlag);
		if (el[kind.playedKey]) {
			el[kind.playedKey].kill?.();
			delete el[kind.playedKey];
		}
	}

	for (const kind of kindsFor(el)) {
		if (playGroup && !isKindInPlayGroup(kind.name, playGroup)) {
			continue;
		}
		const config = kind.read(el);
		if (!config) continue;

		if (config.preventBindInEditor) {
			continue;
		}

		el.classList.add(kind.boundFlag);
		kind.bind(el, config);
	}
}

function updateBodyDeviceMode(bp) {
	if (typeof document !== 'undefined' && document.body) {
		document.body.setAttribute('data-elementor-device-mode', bp);
	}
}

if (typeof document !== 'undefined') {
	if (document.body) {
		updateBodyDeviceMode(activeBp);
	} else {
		document.addEventListener('DOMContentLoaded', () => {
			updateBodyDeviceMode(currentBreakpoint());
		});
	}
}

/**
 * Cheap per-element signature of every kind's EFFECTIVE config (post
 * pickConfigResponsive) at the current breakpoint. Used only by the
 * resize/device-mode path below to tell "this element's animation settings
 * actually differ at the new breakpoint" apart from "same settings, viewport
 * just changed" — most widgets never override per-breakpoint, so most
 * elements hit the latter and can skip the GSAP/ScrollTrigger teardown.
 */
const CONFIG_SIG_KEY = '__aaeConfigSig';

function configSignature(el) {
	const kinds = kindsFor(el);
	if (!kinds.length) return '';
	let sig = '';
	for (const kind of kinds) {
		const config = kind.read(el);
		sig += kind.name + ':' + (config ? JSON.stringify(config) : '') + '|';
	}
	return sig;
}

/**
 * Resize/device-mode entry point (NOT used by the settings-panel path in
 * settings-bridge.js, which must always force a full rebind when the user
 * actually edits a setting). Only pays for reset→unbind→bind when this
 * element's effective config really changed at the new breakpoint;
 * otherwise the previous bind is left running untouched.
 */
function rebindIfChanged(el) {
	if (!el) return;
	const sig = configSignature(el);
	if (el[CONFIG_SIG_KEY] === sig) return;
	el[CONFIG_SIG_KEY] = sig;
	rebind(el);
}

// Device-mode switches (in the editor) and real window resizes both fire
// native `resize` on this window. Elementor's device-mode switch can emit a
// short burst of resize events while the preview iframe settles, and a real
// window drag fires many events per second — debounce so the (potentially
// page-wide) rebind pass runs once per settle, not once per event.
let resizeSettleTimer = null;
const RESIZE_SETTLE_MS = 120;

window.addEventListener('resize', () => {
	if (resizeSettleTimer) clearTimeout(resizeSettleTimer);
	resizeSettleTimer = setTimeout(() => {
		resizeSettleTimer = null;
		const newBp = currentBreakpoint();
		if (newBp !== activeBp) {
			activeBp = newBp;
			updateBodyDeviceMode(newBp);
			document.querySelectorAll('[data-interaction-id]').forEach((el) => {
				try { rebindIfChanged(el); } catch (_) { }
			});
		}
	}, RESIZE_SETTLE_MS);
});

/**
 * Reset every kind owning this element back to its pre-animation state.
 * Used by the editor-bridge when "Enable On Editor" flips OFF — kills
 * the tween, reverts GSAP-applied styles, un-splits text pieces, then
 * tears down the trigger so no further events fire.
 */
function resetEl(el, playGroup = "") {


	if (!el) return;

	if (!playGroup) {
		// Kill all tweens
		gsap.killTweensOf(el);

		// Remove inline styles added by GSAP
		gsap.set(el, {
			clearProps: "all"
		});
	}

	for (const kind of KINDS) {
		if (!configFor(el, kind.mapName)) continue;

		if (playGroup && !isKindInPlayGroup(kind.name, playGroup)) {
			continue;
		}

		if (typeof kind.reset === 'function') {
			try {
				kind.reset(el);
			} catch (_) { }
		}

		if (typeof kind.unbind === 'function') {
			try {
				kind.unbind(el);
			} catch (_) { }
		}

		el.classList.remove(kind.boundFlag);
		if (el[kind.playedKey]) {
			el[kind.playedKey].kill?.();
			delete el[kind.playedKey];
		}
	}
}

/**
 * Editor-only: play ONE interaction row in isolation, regardless of its
 * trigger (click / scroll / page-load). Used by the per-row play icon so the
 * user can preview exactly what a single interaction looks like.
 *
 * `rowCfg`, when provided, is the EXACT runtime row config the editor wants
 * to preview — passing it sidesteps any index mismatch between the editor's
 * row list and the runtime's (the runtime drops effect=none rows and applies
 * the exclusive-trigger dedupe, so positions can differ). The kind's
 * `playRow(el, config, rowIndex, rowCfg)` uses rowCfg directly when present.
 */
function replayRow(el, playGroup = "", rowIndex = 0, rowCfg = null) {
	if (!el) return;
	for (const kind of kindsFor(el)) {
		if (playGroup && !isKindInPlayGroup(kind.name, playGroup)) continue;
		const config = kind.read(el);
		if (!config) continue;
		if (typeof kind.playRow === 'function') {
			try { kind.playRow(el, config, rowIndex, rowCfg); } catch (_) {}
		} else if (typeof kind.play === 'function') {
			try { kind.play(el, config); } catch (_) {}
		}
	}
}

/** Force-replay (used by editor Play Now + initial-replay). Falls through
 *  to descendants. If `el` has an animated ancestor that's currently in
 *  the middle of its tween, defer this replay until the ancestor completes
 *  (chained nested-animation behaviour — parent fades in first, THEN
 *  child text reveals). Skip the chain check when this replay was itself
 *  triggered by an ancestor's drain (`fromChain=true`). */
function replay(el, fromChain = false, playGroup = "") {
	if (!el) return;

	const owningKinds = kindsFor(el);

	if (owningKinds.length) {
		// Chained nesting: if a parent has an active animation, queue this
		// child to play when the parent's tween completes instead of firing
		// now. fromChain=true means we ARE the drain — don't re-queue.
		if (!fromChain) {
			const found = findAnimatedAncestor(el);

			if (found) {
				// Parent is "still running" if any of its bound kinds has an
				// in-flight tween. If none are tweening (all done or never
				// started), play immediately — no point deferring forever.
				const stillRunning = found.kinds.some((k) => {
					const t = found.ancestor[k.playedKey];
					return t && t.progress?.() !== 1;
				});
				if (stillRunning) {
					queueOnAncestor(found.ancestor, () => replay(el, true, playGroup));
					return;
				}
			}
		}

		// Play every owning kind. Each kind tweens its own target (text →
		// inner spans, regular → the element itself) so they coexist.
		let didPlay = false;
		for (const kind of owningKinds) {
			if (playGroup && !isKindInPlayGroup(kind.name, playGroup)) {
				continue;
			}

			const config = kind.read(el);

			if (!config) continue;
			if (el[kind.playedKey]) {
				if (typeof kind.reset === 'function') {
					try { kind.reset(el); } catch (_) {}
				} else {
					el[kind.playedKey].kill?.();
					delete el[kind.playedKey];
				}
			}
			if (typeof kind.play === 'function') {
				kind.play(el, config);
				chainCompletionDrain(el, kind);
				didPlay = true;
			}
		}
		if (didPlay) return;
	}

	// No animation on this element — try descendants. Every kind shares
	// data-interaction-id, so one selector covers all of them.
	if (!KINDS.length) return;
	// Wrap so forEach's `(el, idx, arr)` doesn't accidentally set fromChain.
	el.querySelectorAll('[data-interaction-id]').forEach((node) => replay(node, false, playGroup));
}

/* =====================================================================
 * Public registry — per-effect bundles call register(kind)
 * =================================================================== */

const Registry = {
	/** Register a new animation kind. Triggers a rescan so already-rendered
	 *  elements bind immediately. Returns the kind for chaining. */
	register(kind) {

		if (!kind || typeof kind.read !== 'function' || !kind.mapName) return kind;
		if (KINDS.some((k) => k.name === kind.name)) return kind; // dedupe by name

		// Image-gate seam (Pro engine, free seam — same pattern as the form
		// hooks). When Pro's image-gate module is present it exposes
		// window.AAEImageGate; kind.play then waits for the element's images
		// before revealing them, which keeps the editor replay path ("Play
		// Now", initial replay) honest on lazyloaded markup. Without Pro the
		// wrap short-circuits to today's exact behaviour — and the FRONTEND
		// play path never goes through kind.play at all (it is a closure
		// inside each effect's bind), so this is a belt, not the fix; the
		// fix lives in Pro's wireTrigger. See CLAUDE.md → cache compat.
		if (typeof kind.play === 'function') {
			const rawPlay = kind.play;
			kind.play = (el, config) => {
				const gate = window.AAEImageGate;
				if (gate && typeof gate.whenImagesSettled === 'function') {
					gate.whenImagesSettled(el, () => rawPlay(el, config));
				} else {
					rawPlay(el, config);
				}
			};
		}

		KINDS.push(kind);
		// Re-scan once the registration settles in the microtask queue, so
		// effect files that register at module top-level still see the DOM.
		Promise.resolve().then(() => scan(document));
		return kind;
	},
	scan,
	rebind,
	replay,
	replayRow,
	reset: resetEl,
};

// Single namespace for both runtime control AND shared helpers, so future
// effect bundles never need to `import` anything from this file. Read
// helpers off window.AAEADDON at module init time.
window.AAEADDON = {
	// helpers
	getGsap,
	getScrollTrigger,
	getSplitText,
	currentBreakpoint,
	interactionIdFor,       // shared id lookup (data-interaction-id)
	configFor,              // interactions-map reader
	pickConfigResponsive,   // interactions-map reader, with bp cascade
	BP_CASCADE,
	// registry / dispatcher
	register: Registry.register,
	scan,
	rebind,
	replay,
	replayRow,
	reset: resetEl,
};

// Editor-bridge / preview-iframe entry point. The editor talks to the
// runtime through this surface (rebind, scan, replay, replayRow, reset on a
// single element) so it doesn't need to know about the kind registry.
window.aaeAtomicAnimations = { scan, rebind, replay, replayRow, reset: resetEl };

