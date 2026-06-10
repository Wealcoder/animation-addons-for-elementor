/* eslint-env browser */
import './index.css';

const { configFor, pickConfigResponsive, getGsap } = window.AAEADDON;

const MAP            = 'AAE_INTERACTIONS_ADVANCE_TOOLTIP';
const TOOLTIP_KEY    = '__aaeTooltipOverlay';
const DISPOSE_KEY    = '__aaeTooltipDispose';
const TOOLTIP_PLAYED = '__aaeTooltipPlayed';
const TL_KEY         = '__aaeTipTimeline';   // stores the GSAP timeline per tooltip

/* ── stylesheet lazy-load ──────────────────────────────────────────────── */

let cssLoaded = false;
function ensureStylesheet() {
	if (cssLoaded) return;
	const url = window.AAE_CONFIG?.tooltip_css_url
		|| (window.WCF_ADDONS_URL
			? window.WCF_ADDONS_URL + 'assets/build/modules/atomic/effects/advance-tooltip.css'
			: '');
	if (!url || document.querySelector(`link[href="${url}"]`)) { cssLoaded = true; return; }
	const link = document.createElement('link');
	link.rel = 'stylesheet'; link.href = url;
	document.head.appendChild(link);
	cssLoaded = true;
}

/* ── config helpers ────────────────────────────────────────────────────── */

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

function read(el) {
	const cfg = configFor(el, MAP);
	if (!cfg) return null;

	const enabled = pickConfigResponsive(cfg, 'enabled');
	if (!enabled || enabled === 'false' || enabled === 'no') return null;

	const arrowRaw   = pickConfigResponsive(cfg, 'arrowEnable');
	const arrowEnable = arrowRaw !== false && arrowRaw !== 'false' && arrowRaw !== 'no';

	return {
		enabled      : true,
		text         : r(cfg, 'text',       ''),
		position     : r(cfg, 'position',   'top'),
		trigger      : r(cfg, 'trigger',    'hover'),
		bg           : r(cfg, 'bg',         '#16181d'),
		color        : r(cfg, 'color',      '#f6f2e9'),
		width        : r(cfg, 'width',      '180px'),
		offset       : Number(r(cfg, 'offset',    10)),
		arrowEnable,
		animation    : r(cfg, 'animation',  'slide'),
		duration     : 0.35,
		arrowSize    : Number(r(cfg, 'arrowSize',  6)),
		alignment    : r(cfg, 'alignment',  'center'),
		border       : pickConfigResponsive(cfg, 'border') || null,
		showDelay    : Number(r(cfg, 'showDelay',  0)),
		hideDelay    : Number(r(cfg, 'hideDelay',  0)),
		interactive  : true,
		padding      : r(cfg, 'padding',   '10px 14px'),
	};
}

/* =====================================================================
 * GSAP Timeline Builders
 *
 * Each builder receives (bub, pos, dur):
 *   bub — the tooltip <span> element
 *   pos — 'top' | 'bottom' | 'left' | 'right'
 *   dur — duration in seconds from config
 *
 * Every timeline is paused on creation.
 * doShow() calls tl.play(), doHide() calls tl.reverse().
 * The wrapper addHideCallback() sets visibility:hidden on reverse complete.
 * ===================================================================== */

const BUILDERS = {

	/* 01 — Spring Pop: elastic overshoot from anchor */
	pop(bub, pos, dur) {
		const from = slideFrom(pos, 8);
		return gsap.timeline({ paused: true })
			.set(bub, { transformOrigin: originFor(pos) })
			.fromTo(bub,
				{ opacity: 0, scale: 0.4, ...from },
				{ opacity: 1, scale: 1, x: 0, y: 0,
				  duration: Math.max(dur, 0.5), ease: 'elastic.out(1, .45)' });
	},

	/* 02 — Slide & Fade: classic directional rise/fall */
	slide(bub, pos, dur) {
		const from = slideFrom(pos, 14);
		return gsap.timeline({ paused: true })
			.fromTo(bub,
				{ opacity: 0, ...from },
				{ opacity: 1, x: 0, y: 0, duration: dur, ease: 'power3.out' });
	},

	/* 03 — Typewriter: characters stagger in one by one */
	type(bub, pos, dur) {
		const chars = bub.querySelectorAll('.aae-tip-char');
		return gsap.timeline({ paused: true })
			.set(bub, { opacity: 1 })
			.from(bub, { scaleX: 0.6, duration: dur * 0.6,
			             transformOrigin: '0 50%', ease: 'power2.out' })
			.from(chars,
				{ opacity: 0, duration: 0.04, stagger: 0.03, ease: 'none' },
				'-=0.05');
	},

	/* 04 — Flip Card: 3-D rotateX reveal */
	flip(bub, pos, dur) {
		return gsap.timeline({ paused: true })
			.set(bub, { opacity: 1, transformOrigin: originFor(pos), perspective: 600 })
			.fromTo(bub,
				{ rotateX: -90, y: 6 },
				{ rotateX: 0, y: 0, duration: dur, ease: 'back.out(1.7)' });
	},

	/* 05 — Magnetic: follows cursor (mousemove wired separately) */
	magnet(bub, pos, dur) {
		return gsap.timeline({ paused: true })
			.fromTo(bub,
				{ opacity: 0, scale: 0.88 },
				{ opacity: 1, scale: 1, duration: dur, ease: 'power2.out' });
	},

	/* 06 — Blur Focus: resolves from heavy blur */
	blur(bub, pos, dur) {
		return gsap.timeline({ paused: true })
			.set(bub, { opacity: 1 })
			.fromTo(bub,
				{ filter: 'blur(12px)', scale: 1.25, opacity: 0 },
				{ filter: 'blur(0px)', scale: 1, opacity: 1,
				  duration: dur, ease: 'power2.out' });
	},

	/* 07 — Clip Reveal: clip-path wipe left → right */
	clip(bub, pos, dur) {
		return gsap.timeline({ paused: true })
			.set(bub, { opacity: 1 })
			.fromTo(bub,
				{ clipPath: 'inset(0 100% 0 0)' },
				{ clipPath: 'inset(0 0% 0 0)', duration: dur, ease: 'power3.inOut' });
	},

	/* 08 — Jelly Skew: squash-and-stretch with skew wobble */
	jelly(bub, pos, dur) {
		return gsap.timeline({ paused: true })
			.set(bub, { transformOrigin: originFor(pos) })
			.fromTo(bub,
				{ scaleY: 0.4, scaleX: 1.3, opacity: 0, ...slideFrom(pos, 10) },
				{ scaleY: 1, scaleX: 1, opacity: 1, x: 0, y: 0,
				  duration: Math.max(dur, 0.5), ease: 'elastic.out(1, .4)' })
			.to(bub,
				{ skewX: 6, duration: 0.12, yoyo: true, repeat: 1, ease: 'sine.inOut' },
				'-=0.45');
	},

	/* 09 — Glow Pulse: fades in then breathes with a shadow pulse */
	glow(bub, pos, dur) {
		return gsap.timeline({ paused: true })
			.fromTo(bub,
				{ opacity: 0, ...slideFrom(pos, 10) },
				{ opacity: 1, x: 0, y: 0, duration: dur, ease: 'power2.out' })
			.to(bub, {
				boxShadow : '0 0 0 6px rgba(255,91,53,.0), 0 14px 40px -12px rgba(0,0,0,.5)',
				duration  : 0.9,
				repeat    : -1,
				yoyo      : true,
				ease      : 'sine.inOut',
				startAt   : { boxShadow: '0 0 0 0 rgba(255,91,53,.5), 0 14px 40px -12px rgba(0,0,0,.5)' },
			});
	},

	/* 10 — Unfold Stack: scaleY opens then rotateX settles */
	unfold(bub, pos, dur) {
		return gsap.timeline({ paused: true })
			.set(bub, { opacity: 1, transformOrigin: originFor(pos) })
			.fromTo(bub,
				{ scaleY: 0 },
				{ scaleY: 1, duration: dur * 0.7, ease: 'power2.out' })
			.fromTo(bub,
				{ rotateX: 25 },
				{ rotateX: 0, duration: dur * 0.8, ease: 'back.out(2)' },
				'-=0.1');
	},
};

/* ── animation helpers ─────────────────────────────────────────────────── */

/* "from" offset: tooltip slides IN from this direction toward final spot */
function slideFrom(pos, amt) {
	if (pos === 'bottom') return { x: 0, y: -amt };
	if (pos === 'left')   return { x: amt, y: 0  };
	if (pos === 'right')  return { x: -amt, y: 0 };
	return { x: 0, y: amt }; /* top (default) */
}

/* scale / transform-origin: grows from the edge facing the trigger */
function originFor(pos) {
	if (pos === 'bottom') return '50% 0%';
	if (pos === 'left')   return '100% 50%';
	if (pos === 'right')  return '0% 50%';
	return '50% 100%'; /* top */
}

/*
 * Build the GSAP timeline for the given animation type.
 * Attaches onReverseComplete to hide visibility after the reverse finishes.
 * For 'glow' (repeat:-1) reverse doesn't apply — handled separately.
 */
function buildTimeline(animation, tip, pos, dur) {
	const gsap    = getGsap();
	const builder = BUILDERS[animation] || BUILDERS.slide;
	const tl      = builder(tip, pos, dur);

	if (animation !== 'glow') {
		tl.eventCallback('onReverseComplete', () => {
			gsap.set(tip, { visibility: 'hidden' });
		});
	}
	return tl;
}

/* ── DOM helper ────────────────────────────────────────────────────────── */

function ensureTooltip(el) {
	el.querySelectorAll(':scope > .wcf-advanced-tooltip').forEach(n => n.parentNode?.removeChild(n));

	const tip   = document.createElement('span');
	tip.className = 'wcf-advanced-tooltip';

	const arrow = document.createElement('span');
	arrow.className = 'aae-tip-arrow';
	tip.appendChild(arrow);

	el.appendChild(tip);
	el[TOOLTIP_KEY] = tip;
	return tip;
}

/* ── bind ──────────────────────────────────────────────────────────────── */

function bind(el, config) {
	ensureStylesheet();
	unbind(el);

	const gsap = getGsap();
	const tip  = ensureTooltip(el);

	/* ── find the arrow DOM element ── */
	let arrow = tip.querySelector('.aae-tip-arrow');
	if (!arrow) {
		arrow = document.createElement('span');
		arrow.className = 'aae-tip-arrow';
		tip.appendChild(arrow);
	}

	/* ── content (everything except the arrow span) ── */
	[...tip.childNodes].forEach(n => { if (n !== arrow) n.remove(); });

	if (config.animation === 'type') {
		const raw = new DOMParser().parseFromString(config.text, 'text/html').body.textContent || config.text;
		const frag = document.createDocumentFragment();
		[...raw].forEach(ch => {
			const s = document.createElement('span');
			s.className         = 'aae-tip-char';
			s.style.display     = 'inline-block';
			s.style.whiteSpace  = 'pre';
			s.textContent       = ch;
			frag.appendChild(s);
		});
		tip.insertBefore(frag, arrow);
	} else {
		const nodes = [...new DOMParser().parseFromString(config.text, 'text/html').body.childNodes];
		nodes.forEach(n => tip.insertBefore(n, arrow));
	}

	/* ── positioning context ── */
	if (getComputedStyle(el).position === 'static') el.style.position = 'relative';

	/* ── a11y ── */
	const tipId = 'aae-tip-' + (el.dataset.interactionId || el.dataset.id || Math.random().toString(36).slice(2));
	tip.id = tipId;
	el.setAttribute('aria-describedby', tipId);

	/* ── visual styles — all via JS inline, zero hardcoded CSS ── */
	tip.style.backgroundColor = config.bg;
	tip.style.color           = config.color;
	tip.style.width           = config.width;
	tip.style.textAlign       = config.alignment;
	/* padding: dimension control may return object or string */
	if (config.padding && typeof config.padding === 'object') {
		const pv = config.padding;
		if (pv.top !== undefined) {
			const u = pv.unit || 'px';
			tip.style.padding = `${pv.top||0}${u} ${pv.right||0}${u} ${pv.bottom||0}${u} ${pv.left||0}${u}`;
		} else {
			const size = pv.size || '';
			const unit = pv.unit || 'px';
			tip.style.padding = size !== '' ? `${size}${unit}` : '10px 14px';
		}
	} else {
		tip.style.padding = config.padding || '10px 14px';
	}
	tip.style.boxShadow       = '0 14px 40px -12px rgba(0,0,0,.5)';
	tip.style.zIndex          = '9999';

	/* ── border (from border control: style, width, color, radius) ── */
	if (config.border && typeof config.border === 'object') {
		const b = config.border;
		if (b.style) {
			tip.style.borderStyle = b.style;
			const w = b.width || {};
			const bw = w.top || w.right || w.bottom || w.left;
			if (bw) {
				tip.style.borderWidth = `${w.top || 0} ${w.right || 0} ${w.bottom || 0} ${w.left || 0}`;
			}
			if (b.color) tip.style.borderColor = b.color;
		}
		tip.style.borderRadius = b.radius || '8px';
	} else {
		tip.style.borderRadius = '8px';
	}

	/* ── position via CSS classes + custom properties ── */
	const pos = config.position || 'top';
	const gap = config.arrowSize + config.offset;

	/* Remove old position classes, add new one */
	tip.classList.remove('pos-top', 'pos-bottom', 'pos-left', 'pos-right');
	tip.classList.add('pos-' + pos);

	/* CSS custom properties drive positioning & arrow color */
	tip.style.setProperty('--tip-bg',         config.bg);
	tip.style.setProperty('--tip-arrow-size', config.arrowSize + 'px');
	tip.style.setProperty('--tip-gap',        gap + 'px');

	if (!config.arrowEnable) tip.classList.add('no-arrow');
	else                     tip.classList.remove('no-arrow');

	/* ── GSAP setup ── */
	const dur = config.duration < 10 ? config.duration : config.duration / 1000;

	if (gsap) {
		/*
		 * Only clear GSAP-managed animation props, NOT positioning styles.
		 * CSS classes + custom properties handle position — GSAP handles
		 * opacity, visibility, transforms (x, y, scale, rotate).
		 */
		gsap.set(tip, {
			clearProps: 'opacity,visibility,scale,scaleX,scaleY,rotation,rotateX,rotateY,skewX,skewY,filter,clipPath,x,y',
		});

		/* left/right use top:50% in CSS — GSAP owns yPercent:-50 for centering */
		if (pos === 'left' || pos === 'right') gsap.set(tip, { yPercent: -50 });

		gsap.set(tip, { visibility: 'hidden', opacity: 0 });
		tip[TL_KEY] = buildTimeline(config.animation, tip, pos, dur);
	}

	/* ── show / hide ── */
	let showTimer = null;
	let hideTimer = null;
	const minHideDelay = config.interactive ? Math.max(config.hideDelay, 150) : config.hideDelay;

	const doShow = () => {
		if (!gsap) return;
		const tl = tip[TL_KEY];
		if (!tl) return;

		tl.pause(0);
		gsap.set(tip, { visibility: 'visible' });
		tip.style.pointerEvents = 'auto';
		tl.play();
	};

	const doHide = () => {
		if (!gsap) return;
		const tl = tip[TL_KEY];
		if (!tl) return;

		tip.style.pointerEvents = 'none';

		if (config.animation === 'glow') {
			tl.pause();
			gsap.killTweensOf(tip);
			gsap.to(tip, {
				opacity : 0,
				duration: 0.2,
				onComplete: () => gsap.set(tip, { visibility: 'hidden' }),
			});
		} else {
			tl.reverse();
		}
	};

	const show = () => {
		clearTimeout(hideTimer);
		showTimer = config.showDelay > 0
			? setTimeout(doShow, config.showDelay)
			: (doShow(), null);
	};

	const hide = () => {
		clearTimeout(showTimer);
		hideTimer = minHideDelay > 0
			? setTimeout(doHide, minHideDelay)
			: (doHide(), null);
	};

	/* ── events ── */
	const cleanups = [];
	cleanups.push(() => { clearTimeout(showTimer); clearTimeout(hideTimer); });

	if (config.trigger === 'click') {
		const onToggle  = (e) => {
			e.stopPropagation();
			const opacity = gsap ? gsap.getProperty(tip, 'opacity') : 0;
			opacity > 0.5 ? doHide() : doShow();
		};
		const onOutside  = () => doHide();
		const onKey      = (e) => { if (e.key === 'Escape') doHide(); };
		const onTipClick = (e) => e.stopPropagation();

		el.addEventListener('click', onToggle);
		tip.addEventListener('click', onTipClick);
		document.addEventListener('click', onOutside);
		document.addEventListener('keydown', onKey);
		cleanups.push(() => {
			el.removeEventListener('click', onToggle);
			tip.removeEventListener('click', onTipClick);
			document.removeEventListener('click', onOutside);
			document.removeEventListener('keydown', onKey);
		});
	} else {
		const onKey = (e) => { if (e.key === 'Escape') hide(); };
		el.addEventListener('mouseenter', show);
		el.addEventListener('mouseleave', hide);
		el.addEventListener('focus',      show);
		el.addEventListener('blur',       hide);
		if (config.interactive) {
			tip.addEventListener('mouseenter', show);
			tip.addEventListener('mouseleave', hide);
		}
		document.addEventListener('keydown', onKey);

		if (config.animation === 'magnet' && gsap) {
			const onMove = (e) => {
				const rect = el.getBoundingClientRect();
				const dx   = (e.clientX - (rect.left + rect.width  / 2)) * 0.4;
				const dy   = (e.clientY - (rect.top  + rect.height / 2)) * 0.15;
				gsap.to(tip, { x: dx, y: dy, duration: 0.5, ease: 'power2.out', overwrite: 'auto' });
			};
			const onLeave = () => gsap.to(tip, { x: 0, y: 0, duration: 0.4, ease: 'power2.out' });
			el.addEventListener('mousemove', onMove);
			el.addEventListener('mouseleave', onLeave);
			cleanups.push(() => {
				el.removeEventListener('mousemove', onMove);
				el.removeEventListener('mouseleave', onLeave);
			});
		}

		cleanups.push(() => {
			el.removeEventListener('mouseenter', show);
			el.removeEventListener('mouseleave', hide);
			el.removeEventListener('focus',      show);
			el.removeEventListener('blur',       hide);
			tip.removeEventListener('mouseenter', show);
			tip.removeEventListener('mouseleave', hide);
			document.removeEventListener('keydown', onKey);
		});
	}

	el[TOOLTIP_PLAYED] = tip;
	el[DISPOSE_KEY]    = () => cleanups.forEach(fn => fn());
}

/* ── play (editor preview) ─────────────────────────────────────────────── */

function play(el, config) {
	ensureStylesheet();
	unbind(el);
	bind(el, config);

	const gsap = getGsap();
	const tip  = el[TOOLTIP_KEY];

	if (tip && gsap) {
		const tl = tip[TL_KEY];
		if (tl) {
			tl.pause(0);
			gsap.set(tip, { visibility: 'visible' });
			tip.style.pointerEvents = 'auto';
			tl.play();
		}
		setTimeout(() => {
			if (!el.matches(':hover') && el[TOOLTIP_KEY] === tip) {
				tip.style.pointerEvents = 'none';
				if (config.animation === 'glow') {
					tl?.pause();
					gsap.to(tip, { opacity: 0, duration: 0.2, onComplete: () => gsap.set(tip, { visibility: 'hidden' }) });
				} else {
					tl?.reverse();
				}
			}
		}, 2000);
	}
}

/* ── unbind ────────────────────────────────────────────────────────────── */

function unbind(el) {
	const dispose = el[DISPOSE_KEY];
	if (typeof dispose === 'function') { try { dispose(); } catch (_) {} }
	el[DISPOSE_KEY] = null;

	el.removeAttribute('aria-describedby');

	const tip = el[TOOLTIP_KEY];
	if (tip) {
		const gsap = getGsap();
		if (gsap) {
			if (tip[TL_KEY]) { tip[TL_KEY].kill(); delete tip[TL_KEY]; }
			gsap.killTweensOf(tip);
		}
		tip.parentNode?.removeChild(tip);
	}
	el[TOOLTIP_KEY] = null;
	delete el[TOOLTIP_PLAYED];
}

/* ── register ──────────────────────────────────────────────────────────── */

window.AAEADDON.register({
	name      : 'advance-tooltip',
	mapName   : MAP,
	boundFlag : 'aae-advance-tooltip-bound',
	playedKey : TOOLTIP_PLAYED,
	read, play, bind, unbind,
	reset     : unbind,
});