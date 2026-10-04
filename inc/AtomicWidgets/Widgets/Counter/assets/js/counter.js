import { register } from '@elementor/frontend-handlers';

/* AAE Atomic Counter (single-class composite).
   - Start value + duration come from the parent's data-counter-* attrs
     (set by the panel's Start Number / Duration controls).
   - End value is read from the Number child's text content — whatever
     the user types into the Number element on canvas is the target.
     This makes the editable child the single source of truth for the
     end value and removes the panel-vs-child conflict.

   Deliberately GSAP-free (same approach as the Progressbar widget). The
   `gsap` handle is registered ONLY by the Pro plugin, and only when the
   `wcf_save_extensions` option exists — so on a free site, or with the
   GSAP extensions toggled off, `window.gsap` is simply absent and the old
   implementation degraded to stamping the final number in with no
   animation at all. requestAnimationFrame always exists, so the counter
   now behaves identically everywhere.

   Scroll-in is driven by IntersectionObserver, NOT ScrollTrigger. The old
   `window.gsap.ScrollTrigger` guard could never be true — GSAP's UMD build
   exposes the plugin as `window.ScrollTrigger`, never as a property of the
   gsap object — so the tween always fired at page load and a counter below
   the fold had finished counting before the visitor ever scrolled to it. */

const DEFAULT_DURATION = 2000;

/** power1.out equivalent — matches the easing the GSAP version used. */
const easeOut = ( t ) => 1 - ( 1 - t ) * ( 1 - t );

const isEditMode = () =>
	!! ( window.elementorFrontend?.isEditMode?.() ||
		document.body?.classList.contains( 'elementor-editor-active' ) );

const prefersReducedMotion = () =>
	!! window.matchMedia?.( '(prefers-reduced-motion: reduce)' )?.matches;

/**
 * Parse whatever the user typed into the Number child.
 *
 * Keeps the SHAPE of the typed value so the animation renders in the same
 * format: "1,250" counts up with thousand separators, "99.5" keeps one
 * decimal. The old implementation ran the raw text through parseFloat, so
 * "1,000" became 1 and `snap: { innerHTML: 1 }` threw every decimal away.
 */
const parseTarget = ( raw ) => {
	const text    = ( raw || '' ).trim();
	const cleaned = text.replace( /[^0-9.-]/g, '' );
	const value   = parseFloat( cleaned );

	if ( ! Number.isFinite( value ) ) return null;

	const dot = cleaned.indexOf( '.' );

	return {
		value,
		decimals: dot === -1 ? 0 : cleaned.length - dot - 1,
		group:    /\d[,\s]\d/.test( text ),
	};
};

const format = ( value, info ) => {
	const fixed = value.toFixed( info.decimals );
	if ( ! info.group ) return fixed;

	const [ whole, fraction ] = fixed.split( '.' );
	const grouped = whole.replace( /\B(?=(\d{3})+(?!\d))/g, ',' );

	return fraction ? `${ grouped }.${ fraction }` : grouped;
};

/**
 * The node that actually HOLDS the text, given an element or its editor wrapper.
 *
 * In the editor canvas every atomic child is mounted inside a wrapper
 *   <div class="elementor-element … elementor-widget-e-paragraph">
 * and the real node is the <span class="e-paragraph-base …"> inside it. That
 * span is what carries the base-style class AND the local style class the Style
 * tab writes — i.e. the font-family/size the builder picked. The frontend has no
 * wrapper at all; the span is a direct child of the counter.
 *
 * This matters because play()'s write() assigns textContent. Assigning it to the
 * WRAPPER deletes the styled span outright and leaves a bare text node behind,
 * so the number loses its font on every re-render — in the editor only, which is
 * exactly how the bug presented (verified in the v4 canvas: after a settings
 * change the wrapper's innerHTML went from
 * `<span class="e-paragraph-base …">50</span>` to plain `46`).
 */
const TEXT_NODE_SELECTOR = '.e-paragraph-base, .e-heading-base, [data-interaction-id]';

const resolveTextEl = ( el ) => ( el && el.querySelector( TEXT_NODE_SELECTOR ) ) || el;

/**
 * Locate the animated Number child.
 *
 * `.aae-a-counter-number` is the normal path. It is NOT reliable on its own: the
 * class lives in the child's `classes` prop, and the panel's "Some classes are
 * missing" notice can unapply it (its ✕ calls unapplyClasses on exactly those
 * ids), after which the lookup returns null on a perfectly healthy counter.
 * So the fallback has to be correct, not just a safety net — it picks the first
 * direct child whose text reads as a number (the Prefix child is empty and the
 * Suffix child is "+" by default), then resolves it to the real text node.
 */
const findNumberEl = ( parent ) => {
	const byClass = parent.querySelector( '.aae-a-counter-number' );
	if ( byClass ) return resolveTextEl( byClass );

	for ( const child of parent.children ) {
		if ( child.classList.contains( 'elementor-element-overlay' ) ) continue;
		if ( parseTarget( child.textContent ) ) return resolveTextEl( child );
	}

	return null;
};

// numberEl -> { value, decimals, group, lastWritten }
const targets = new WeakMap();
// numberEl -> active requestAnimationFrame id
const frames = new WeakMap();

const stop = ( numberEl ) => {
	const id = frames.get( numberEl );
	if ( id ) window.cancelAnimationFrame( id );
	frames.delete( numberEl );
};

/**
 * Resolve the end value, remembering it across re-inits.
 *
 * The handler re-runs on every editor re-render (and can run more than once
 * on the frontend). Re-reading textContent blindly meant a re-init landing
 * mid-count read a PARTIAL number — "37" — and made that the new permanent
 * target. We therefore only re-parse when the current text differs from what
 * we last wrote ourselves, i.e. when the user actually changed it.
 */
const resolveTarget = ( numberEl ) => {
	const cached  = targets.get( numberEl );
	const current = ( numberEl.textContent || '' ).trim();

	if ( cached && cached.lastWritten === current ) return cached;

	const parsed = parseTarget( current );
	if ( ! parsed ) return cached || null;

	const info = { ...parsed, lastWritten: current };
	targets.set( numberEl, info );

	return info;
};

/** True while the visitor/builder is typing inside the counter — never
 *  overwrite text out from under an inline-editing session. */
const isBeingEdited = ( parent, numberEl ) => {
	if ( numberEl.isContentEditable ) return true;
	if ( parent.querySelector( '[contenteditable="true"]' ) ) return true;

	const active = parent.ownerDocument?.activeElement;
	return !! ( active && active !== parent.ownerDocument.body && parent.contains( active ) );
};

const play = ( parent, numberEl ) => {
	const info = resolveTarget( numberEl );
	if ( ! info ) return;

	const fromAttr = parseFloat( parent.getAttribute( 'data-counter-from' ) );
	const from     = Number.isFinite( fromAttr ) ? fromAttr : 0;

	const durationAttr = parseFloat( parent.getAttribute( 'data-counter-duration' ) );
	const duration     = Number.isFinite( durationAttr ) && durationAttr > 0
		? durationAttr
		: DEFAULT_DURATION;

	const write = ( value ) => {
		const text = format( value, info );
		numberEl.textContent = text;
		info.lastWritten     = text;
	};

	stop( numberEl );

	if ( from === info.value || prefersReducedMotion() ) {
		write( info.value );
		return;
	}

	const startedAt = performance.now();

	const tick = ( now ) => {
		const progress = Math.min( ( now - startedAt ) / duration, 1 );
		write( from + ( info.value - from ) * easeOut( progress ) );

		if ( progress < 1 ) {
			frames.set( numberEl, window.requestAnimationFrame( tick ) );
		} else {
			frames.delete( numberEl );
		}
	};

	write( from );
	frames.set( numberEl, window.requestAnimationFrame( tick ) );
};

register( {
	elementType: 'e-aae-a-counter',
	id:          'e-aae-a-counter-handler',
	callback: ( { element } ) => {
		const numberEl = findNumberEl( element );
		if ( ! numberEl ) return;

		// Cache the typed value before anything overwrites it.
		resolveTarget( numberEl );

		// Editor: replay on every re-render so the builder sees the effect,
		// but debounced (a panel keystroke re-renders on each character) and
		// never while an inline-edit session is in progress. No viewport gate
		// — an off-screen counter sitting at its start value reads as broken
		// while building.
		if ( isEditMode() ) {
			const timer = window.setTimeout( () => {
				if ( ! isBeingEdited( element, numberEl ) ) play( element, numberEl );
			}, 350 );

			return () => {
				window.clearTimeout( timer );
				stop( numberEl );
			};
		}

		if ( typeof IntersectionObserver === 'undefined' ) {
			play( element, numberEl );
			return () => stop( numberEl );
		}

		const observer = new IntersectionObserver( ( entries, obs ) => {
			for ( const entry of entries ) {
				if ( entry.isIntersecting ) {
					play( element, numberEl );
					obs.disconnect();
				}
			}
		}, { threshold: 0.3 } );

		observer.observe( element );

		return () => {
			observer.disconnect();
			stop( numberEl );
		};
	},
} );
