/* eslint-env browser */

/**
 * Navigate the preview slider to a slide when that slide (or anything inside it)
 * is selected in the editor — from the Navigator/Structure panel, the canvas, or
 * anywhere else. Mirrors what the panel's "Slides" list row click does, but keyed
 * off Elementor's own selection instead of a panel control.
 *
 * Why a selection hook (not a preview DOM listener): the Structure panel lives in
 * the MAIN editor window, not the preview iframe, so a click there never reaches
 * the runtime's in-iframe pointer handler. Elementor's command bus fires on every
 * selection change in any surface, so we hang off that and inspect the result.
 */

import { track } from './disposables';

const SLIDE_TYPE = 'e-aae-a-slide';
const TRACK_TYPE = 'e-aae-a-slider-track';
const SLIDER_TYPE = 'e-aae-a-slider';

/** Walk up the container chain to the slide this element belongs to (or itself). */
function findSlideAncestor( container ) {
	let c = container;
	while ( c ) {
		const elType = c.model?.get?.( 'elType' ) || c.type;
		if ( elType === SLIDE_TYPE ) {
			return c;
		}
		c = c.parent;
	}
	return null;
}

/** From a slide container, resolve { sliderId, index } within its track. */
function resolveSlidePosition( slide ) {
	const track = slide.parent;
	if ( ! track ) {
		return null;
	}
	const trackType = track.model?.get?.( 'elType' ) || track.type;
	if ( trackType !== TRACK_TYPE ) {
		return null;
	}
	const slider = track.parent;
	if ( ! slider ) {
		return null;
	}
	const sliderType = slider.model?.get?.( 'elType' ) || slider.type;
	if ( sliderType !== SLIDER_TYPE ) {
		return null;
	}

	// Index = the slide's position among real slide children of the track.
	const children = track.model?.get?.( 'elements' );
	let index = -1;
	let i = 0;
	children?.each?.( ( childModel ) => {
		if ( childModel.get( 'elType' ) === SLIDE_TYPE ) {
			if ( childModel.get( 'id' ) === slide.id ) {
				index = i;
			}
			i++;
		}
	} );

	if ( index < 0 ) {
		return null;
	}
	return { sliderId: slider.id, slideId: slide.id, index };
}

/** Drive the preview slider DOM node to the given slide index. */
function navigatePreviewToSlide( sliderId, slideId, index ) {
	try {
		const previewWin = window.elementor?.$preview?.[ 0 ]?.contentWindow || null;
		if ( ! previewWin ) {
			return;
		}
		const sliderNode =
			previewWin.document.querySelector( `[data-id="${ sliderId }"]` ) ||
			previewWin.document.getElementById( sliderId );

		if ( sliderNode && typeof sliderNode._aaeGoTo === 'function' ) {
			sliderNode._aaeGoTo( index );
		}
		previewWin.dispatchEvent(
			new previewWin.CustomEvent( 'aae/slider/edit-slide', {
				detail: { sliderId, slideId, index },
			} )
		);
	} catch ( _e ) {
		/* preview not ready — ignore */
	}
}

let isFromStructure = false;
let structureClickTime = 0;

if ( typeof window !== 'undefined' ) {
	window.addEventListener(
		'pointerdown',
		( e ) => {
			const inNavigator = !!(
				e.target &&
				e.target.closest &&
				( e.target.closest( '#elementor-navigator' ) ||
					e.target.closest( '.elementor-navigator' ) ||
					e.target.closest( '[data-elementor-panel="navigator"]' ) ||
					e.target.closest( '.elementor-navigator-tree' ) )
			);
			if ( inNavigator ) {
				isFromStructure = true;
				structureClickTime = Date.now();
			}
		},
		{ capture: true }
	);
}

/** Check the current selection; if it resolves to a slide and originated from Structure panel, navigate to it. */
function handleSelectionChange( last ) {
	const isRecentStructureClick =
		isFromStructure && Date.now() - structureClickTime < 1500;
	if ( ! isRecentStructureClick ) {
		return;
	}

	let selected = null;
	try {
		const els = window.elementor?.selection?.getElements?.();
		selected = els && els.length ? els[ 0 ] : null;
	} catch ( _ ) {
		return;
	}
	if ( ! selected ) {
		return;
	}

	const slide = findSlideAncestor( selected );
	if ( ! slide ) {
		return;
	}
	const pos = resolveSlidePosition( slide );
	if ( ! pos ) {
		return;
	}

	// Dedupe: selection fires several commands per click; only navigate when the
	// resolved slide actually changed (or after a beat) to avoid repeat pulses.
	const now = Date.now();
	if ( last.slideId === pos.slideId && now - last.t < 250 ) {
		return;
	}
	last.slideId = pos.slideId;
	last.t = now;
	isFromStructure = false;

	navigatePreviewToSlide( pos.sliderId, pos.slideId, pos.index );
}

/**
 * Install the selection → preview-navigation hook. Idempotent; tracked for
 * teardown on document switch / unload.
 */
export function startSlideSelectNav() {
	const $e = window.$e;
	if ( ! $e?.commands?.on ) {
		return;
	}
	const last = { slideId: null, t: 0 };
	const onRunAfter = () => handleSelectionChange( last );
	$e.commands.on( 'run:after', onRunAfter );
	track( () => {
		try {
			$e.commands.off( 'run:after', onRunAfter );
		} catch ( _ ) { /* command bus gone */ }
	} );
}
