/**
 * AAE Form — Multi-Step Forms runtime.
 *
 * A form becomes multi-step the moment it has 2+ `.aae-a-form-step`
 * children — nothing to opt into beyond dropping the widgets. This module:
 *   - shows exactly one step at a time (`aae-form-step-active` class,
 *     revealed by the `.aae-form-step-active { display: flex }` rule in
 *     form.scss — same class-toggle-reveal trick as the form's own
 *     `form-state-{value}`),
 *   - binds Next/Previous behavior to REAL atomic widgets
 *     (e-aae-a-form-next / e-aae-a-form-prev, rendered as
 *     `[data-aae-form-step-nav="next"|"prev"]` buttons) wherever a builder
 *     placed one inside a step — these are fully styleable from the Style
 *     tab, unlike a runtime-injected DOM button,
 *   - falls back to injecting its OWN Prev/Next/progress bar for any step
 *     that has NEITHER widget, but only when the form's
 *     data-aae-form-step-nav-auto attr is "true" (the "Auto Step
 *     Navigation" switch — lives on e-aae-a-form-step, one flag per step,
 *     see class-aae-a-form-step.php). Turning it off on a step means "only
 *     ever use nav widgets I placed myself" for that step,
 *   - gates Next behind the CALLER's validation function (this module
 *     doesn't duplicate validateFrontend/showFieldError/focusFirstInvalid,
 *     which are private to form.js — see initSteps' `validateStep` param),
 *   - plays a pure-CSS transition (per-step selectable — data-aae-form-step-
 *     transition on the ARRIVING step: 'fade' | 'slide' | 'fade-slide' |
 *     'none') when Next/Previous actually change the active step, via
 *     `transitionend` (with a setTimeout safety net) instead of a JS
 *     tweening library — keeps Form's "no GSAP" MVP rule intact (see
 *     CLAUDE.md's Motion policy) with no runtime dependency at all. Respects
 *     `prefers-reduced-motion` (instant swap, no transition).
 *
 * Runs in BOTH the editor preview and the real frontend (like
 * lib/multi-select.js) — a builder needs to see/style every step while
 * editing; only the Next-button VALIDATION gate is frontend-only (the
 * caller decides that by passing null for `validateStep` in edit mode).
 * Animation runs in the editor too (builders should see what they picked).
 *
 * EDITOR RE-RENDER SAFETY (critical, see CLAUDE.md's Multi-Step Forms
 * section): Elementor's editor repaints a form's innerHTML from scratch on
 * ANY settings change on the form or its children (not just "Apply
 * Preset") — the <form> node itself is reused, but every step node inside
 * it is a FRESH element with no aae-form-step-active class. A naive
 * one-time initSteps() closure over the old step nodes would keep
 * operating on now-detached elements, leaving the form with EVERY step
 * hidden (confirmed live: toggling an unrelated switch made the entire
 * form vanish from canvas — steps existed in the DOM but all had
 * display:none, none carried the active class). Fix: state (current step
 * index, autoFallback flag) lives on the <form> element itself
 * (form.__aaeStepState), not in a closure, and resyncSteps() — called by
 * form.js's editor-only polling loop on EVERY tick, not just once — always
 * re-queries fresh step nodes and re-applies the active class/nav
 * visibility, cheaply and idempotently, regardless of whether the DOM
 * nodes changed since the last tick. resyncSteps() is a plain, INSTANT
 * reconciliation (no animation) — only the explicit goNext()/goPrev() user
 * actions play a transition, so the polling loop never triggers a replay.
 */

const ACTIVE_CLASS = 'aae-form-step-active';
const NAV_SELECTOR = '[data-aae-form-step-nav]';
const TRANSITION_MS = 320;

function prefersReducedMotion() {
	return typeof window !== 'undefined' && window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
}

/** This form's step elements, in DOM order (= step order, no separate index prop). */
function stepsOf( form ) {
	return Array.from( form.querySelectorAll( '[data-aae-form-step="true"]' ) );
}

/**
 * Author-placed nav widgets belonging directly to `step` (not a nested
 * step's own nav, if steps were ever nested — scoped by closest()).
 */
function ownNavButtons( step, role ) {
	return Array.from( step.querySelectorAll( `[data-aae-form-step-nav="${ role }"]` ) ).filter(
		( el ) => el.closest( '[data-aae-form-step="true"]' ) === step
	);
}

/** Inject a fallback Prev/Next/progress bar for one step (only used when it has no widgets of its own). */
function buildFallbackNav( form, step, doc ) {
	const bar = doc.createElement( 'div' );
	bar.className = 'aae-form-step-progress';
	bar.setAttribute( 'data-aae-step-nav', 'true' );

	const prevBtn = doc.createElement( 'button' );
	prevBtn.type = 'button';
	prevBtn.className = 'aae-a-form-submit aae-form-step-prev';
	prevBtn.setAttribute( 'data-aae-form-step-nav', 'prev' );
	prevBtn.textContent = form.dataset.aaeStepPrevLabel || 'Previous';

	const label = doc.createElement( 'span' );
	label.className = 'aae-form-step-label';

	const nextBtn = doc.createElement( 'button' );
	nextBtn.type = 'button';
	nextBtn.className = 'aae-a-form-submit aae-form-step-next';
	nextBtn.setAttribute( 'data-aae-form-step-nav', 'next' );
	nextBtn.textContent = form.dataset.aaeStepNextLabel || 'Next';

	bar.append( prevBtn, label, nextBtn );
	step.append( bar );

	return { label };
}

/** Does this step want the auto-injected fallback bar? Per-step flag (data-aae-form-step-nav-auto), defaults true. */
function stepWantsFallback( step ) {
	return step.dataset.aaeFormStepNavAuto !== 'false';
}

/** Which transition the ARRIVING step asked for (data-aae-form-step-transition), defaults 'fade-slide'. */
function stepTransitionOf( step ) {
	return step.dataset.aaeFormStepTransition || 'fade-slide';
}

/**
 * Re-query fresh step nodes and repaint active/hidden state from
 * `form.__aaeStepState.current` — safe to call as often as needed (editor
 * polling, a render event, …), and safe even if the step DOM nodes were
 * just replaced by an Elementor re-render (it never touches stale
 * references — everything is re-queried from `form` fresh each call).
 * Also lazily injects a fallback nav bar into any step that wants one and
 * doesn't have it yet (covers steps that appeared after the initial bind,
 * same as a fresh preset apply). INSTANT — never animates (see docblock).
 */
function resyncSteps( form ) {
	const state = form.__aaeStepState;
	if ( ! state ) {
		return;
	}

	const steps = stepsOf( form );
	if ( steps.length < 2 ) {
		return;
	}

	if ( state.current >= steps.length ) {
		state.current = steps.length - 1;
	}

	form.classList.remove( 'aae-form-step-transitioning', 'aae-form-step-anim-run' );

	steps.forEach( ( step ) => {
		const hasOwnNav = ownNavButtons( step, 'next' ).length || ownNavButtons( step, 'prev' ).length;
		const alreadyHasFallback = !! step.querySelector( ':scope > .aae-form-step-progress' );
		if ( ! hasOwnNav && ! alreadyHasFallback && stepWantsFallback( step ) ) {
			const { label } = buildFallbackNav( form, step, form.ownerDocument );
			state.fallbackLabels.set( step, label );
		}
	} );

	steps.forEach( ( step, i ) => {
		const isActive = i === state.current;
		step.classList.toggle( ACTIVE_CLASS, isActive );
		// Clear any leftover inline styles from a previous animated
		// transition — resyncSteps is the "instant, ground-truth" path and
		// must never leave a step visually mid-transition or pinned absolute.
		step.style.transform = '';
		step.style.opacity = '';
		step.style.transitionDuration = '';
		step.style.top = '';
		step.style.left = '';
		step.style.width = '';

		ownNavButtons( step, 'prev' ).forEach( ( btn ) => {
			btn.hidden = 0 === i;
		} );
		ownNavButtons( step, 'next' ).forEach( ( btn ) => {
			btn.hidden = i === steps.length - 1;
		} );

		const label = state.fallbackLabels.get( step );
		if ( label && label.isConnected ) {
			label.textContent = `${ i + 1 } / ${ steps.length }`;
		}
	} );
}

/**
 * Animate from `fromStep` to `toStep` (both already resolved elements),
 * `direction` is +1 for Next, -1 for Previous — used to pick the slide
 * direction so Next always feels like moving forward and Previous like
 * moving back. Pure CSS transition (`transform`/`opacity`, both
 * compositor-friendly) — no JS tweening library. Falls back to an INSTANT
 * swap (calling `onDone` synchronously) when reduced-motion is requested or
 * the arriving step's transition is 'none'.
 */
function animateStepChange( form, fromStep, toStep, direction, onDone ) {
	const transition = stepTransitionOf( toStep );

	if ( 'none' === transition || prefersReducedMotion() ) {
		toStep.classList.add( ACTIVE_CLASS );
		if ( fromStep ) {
			fromStep.classList.remove( ACTIVE_CLASS );
		}
		onDone();
		return;
	}

	const distance = 24; // px — subtle, not a full-width slide.
	const slideEnabled = 'slide' === transition || 'fade-slide' === transition;
	const fadeEnabled = 'fade' === transition || 'fade-slide' === transition;
	const slideFrom = slideEnabled ? direction * distance : 0;
	const slideTo = slideEnabled ? -direction * distance : 0;

	// Both steps are display:flex for the duration of the transition (the
	// active class drives display via form.scss) so the outgoing step can
	// fade/slide out while the incoming one fades/slides in — a brief
	// overlap, not a jarring cut. form.scss pins the OUTGOING one absolutely
	// for this window (aae-form-step-transitioning + aae-form-step-incoming)
	// so the overlap never affects the form's flex-wrap layout/height.
	//
	// CRITICAL: capture fromStep's rect WHILE STILL IN NORMAL FLOW, before
	// switching it to position:absolute — a plain CSS `top:0; left:0;
	// width:100%` positions against the form's BORDER box, ignoring the
	// form's own padding (default 20px), so the outgoing step's edges no
	// longer line up with the incoming step's (which stays in normal,
	// padding-respecting flow) — confirmed live on a real multi-step form:
	// the outgoing step's mid-transition rect was 1110px wide starting at
	// the form's left edge, while the incoming sibling was 1070px wide
	// inset 20px, a visible edge misalignment/overlap during the cross-fade.
	// Fix: read the real getBoundingClientRect() now, convert to
	// form-relative px, and set that explicitly — pixel-perfect regardless
	// of the form's padding/border, and immune to future Style-panel edits.
	const formRect = form.getBoundingClientRect();
	const fromRect = fromStep ? fromStep.getBoundingClientRect() : null;

	form.classList.add( 'aae-form-step-transitioning' );
	toStep.classList.add( ACTIVE_CLASS, 'aae-form-step-incoming' );

	if ( fromStep && fromRect ) {
		fromStep.style.top = `${ fromRect.top - formRect.top }px`;
		fromStep.style.left = `${ fromRect.left - formRect.left }px`;
		fromStep.style.width = `${ fromRect.width }px`;
	}

	let finished = false;
	const finish = () => {
		if ( finished ) {
			return;
		}
		finished = true;
		toStep.removeEventListener( 'transitionend', onTransitionEnd );
		clearTimeout( safetyTimer );

		form.classList.remove( 'aae-form-step-transitioning', 'aae-form-step-anim-run' );
		toStep.classList.remove( 'aae-form-step-incoming' );
		toStep.style.transform = '';
		toStep.style.opacity = '';
		toStep.style.transitionDuration = '';
		if ( fromStep ) {
			fromStep.classList.remove( ACTIVE_CLASS );
			fromStep.style.transform = '';
			fromStep.style.opacity = '';
			fromStep.style.top = '';
			fromStep.style.left = '';
			fromStep.style.width = '';
			fromStep.style.transitionDuration = '';
		}
		onDone();
	};

	const onTransitionEnd = ( event ) => {
		if ( event.target === toStep && ( 'transform' === event.propertyName || 'opacity' === event.propertyName ) ) {
			finish();
		}
	};

	// Safety net: a hidden tab/iframe, a display:none ancestor mid-transition,
	// or a browser that never fires transitionend for a 0-change property
	// (e.g. 'none'-equivalent duration) must not leave the form stuck
	// mid-transition forever.
	const safetyTimer = setTimeout( finish, TRANSITION_MS + 120 );

	// Set the FROM state inline (pre-transition), force layout, then flip to
	// the TO state on the next frame so the browser actually animates the
	// change instead of coalescing both writes into one paint.
	toStep.style.transitionDuration = `${ TRANSITION_MS }ms`;
	toStep.style.opacity = fadeEnabled ? '0' : '1';
	toStep.style.transform = slideFrom ? `translateX(${ slideFrom }px)` : 'translateX(0)';

	if ( fromStep ) {
		fromStep.style.transitionDuration = `${ TRANSITION_MS }ms`;
		fromStep.style.opacity = '1';
		fromStep.style.transform = 'translateX(0)';
	}

	// eslint-disable-next-line no-unused-expressions
	toStep.offsetHeight; // force reflow so the FROM state above is committed before the TO state below.

	toStep.addEventListener( 'transitionend', onTransitionEnd );
	form.classList.add( 'aae-form-step-anim-run' );

	requestAnimationFrame( () => {
		requestAnimationFrame( () => {
			toStep.style.opacity = '1';
			toStep.style.transform = 'translateX(0)';
			if ( fromStep ) {
				fromStep.style.opacity = fadeEnabled ? '0' : '1';
				fromStep.style.transform = slideTo ? `translateX(${ slideTo }px)` : 'translateX(0)';
			}
		} );
	} );
}

/**
 * Wire step navigation onto `form`. Idempotent (guarded by a bound flag on
 * the form, same convention as initForm's own aaeFormReady guard) — the
 * CLICK LISTENER only needs attaching once (event delegation on the form
 * node itself, which Elementor never replaces, only repaints inside).
 * Re-render safety (new step nodes losing their active class) is handled
 * separately by resyncSteps(), which the caller should invoke on every
 * editor poll tick regardless of whether initSteps ran this time.
 *
 * @param {HTMLFormElement} form
 * @param {(stepEl: HTMLElement) => Array} [validateStep] Returns
 *   `[[control, message], …]` errors for the given step's controls, or
 *   omit/pass null to skip validation entirely (editor preview — clicking
 *   Next there should just navigate, since previews never submit anyway).
 * @param {(stepEl: HTMLElement, errors: Array) => void} [onBlocked] Called
 *   when validateStep returns errors — the caller owns painting them
 *   (showFieldError/focusFirstInvalid are private to form.js).
 */
export function initSteps( form, validateStep, onBlocked ) {
	if ( form.dataset.aaeStepsBound === 'true' ) {
		resyncSteps( form ); // re-render may have happened since the last bind — always safe to reconcile.
		return;
	}

	const steps = stepsOf( form );
	if ( steps.length < 2 ) {
		return; // single-page form (0 or 1 step element) — nothing to do.
	}

	form.dataset.aaeStepsBound = 'true';
	form.classList.add( 'aae-form-multistep' );

	form.__aaeStepState = { current: 0, fallbackLabels: new WeakMap(), animating: false };

	const goPrev = () => {
		const state = form.__aaeStepState;
		if ( state.animating || state.current <= 0 ) {
			return;
		}
		const freshSteps = stepsOf( form );
		const fromStep = freshSteps[ state.current ];
		state.current -= 1;
		const toStep = freshSteps[ state.current ];

		state.animating = true;
		animateStepChange( form, fromStep, toStep, -1, () => {
			state.animating = false;
			resyncSteps( form ); // reconcile nav hidden-state/labels post-animation.
			form.dispatchEvent( new CustomEvent( 'aae-form-step-change', { detail: { index: state.current } } ) );
		} );
	};

	const goNext = () => {
		const state = form.__aaeStepState;
		if ( state.animating ) {
			return;
		}
		const freshSteps = stepsOf( form );
		const stepEl = freshSteps[ state.current ];
		const errors = validateStep ? validateStep( stepEl ) : [];

		if ( errors && errors.length ) {
			if ( onBlocked ) {
				onBlocked( stepEl, errors );
			}
			return; // Next only after the current step validates — non-negotiable.
		}

		if ( state.current >= freshSteps.length - 1 ) {
			return;
		}

		const fromStep = freshSteps[ state.current ];
		state.current += 1;
		const toStep = freshSteps[ state.current ];

		state.animating = true;
		animateStepChange( form, fromStep, toStep, 1, () => {
			state.animating = false;
			resyncSteps( form );
			form.dispatchEvent( new CustomEvent( 'aae-form-step-change', { detail: { index: state.current } } ) );
		} );
	};

	// Delegated on the form itself — covers author-placed widgets (anywhere
	// inside any step, present from initial render) AND injected fallback
	// buttons (appended after this listener is attached), and keeps working
	// after a re-render since it's never re-queried, only the target lookup
	// inside the handler is (via stepsOf/closest at click time).
	form.addEventListener( 'click', ( event ) => {
		const navBtn = event.target.closest( NAV_SELECTOR );
		if ( ! navBtn || ! form.contains( navBtn ) ) {
			return;
		}
		const freshSteps = stepsOf( form );
		const state = form.__aaeStepState;
		if ( navBtn.closest( '[data-aae-form-step="true"]' ) !== freshSteps[ state.current ] ) {
			return; // a nav button in a non-active step (shouldn't be clickable, but stay safe).
		}

		if ( 'next' === navBtn.dataset.aaeFormStepNav ) {
			goNext();
		} else if ( 'prev' === navBtn.dataset.aaeFormStepNav ) {
			goPrev();
		}
	} );

	resyncSteps( form );
}

export { resyncSteps };
