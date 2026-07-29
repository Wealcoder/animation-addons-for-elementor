/* eslint-env browser */
/* global gsap, ScrollTrigger, DrawSVGPlugin, MotionPathPlugin */

/**
 * AAE DrawSVG — atomic (v4) frontend + editor runtime.
 *
 * The widget root (.aae-a-draw-svg) carries all config on data-attributes; this
 * splits every drawable SVG element into individual paths and draws them with GSAP
 * DrawSVGPlugin, per-path, optionally on scroll (ScrollTrigger).
 *
 * Frontend: self-initialises on DOM ready + Elementor frontend init.
 * Editor:   the atomic widget is (re)rendered client-side AFTER those events, so a
 *           MutationObserver re-scans the preview and (a) auto-plays each element
 *           once it appears and (b) injects an editor-only "Play" button for manual
 *           replay. The button is JS-injected in the preview only — it is never part
 *           of the saved model or the frontend HTML.
 */
(function () {
	'use strict';

	function pluginsReady() {
		return typeof gsap !== 'undefined'
			&& typeof DrawSVGPlugin !== 'undefined'
			&& typeof MotionPathPlugin !== 'undefined';
	}

	function isEditor() {
		try {
			if ( window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' ) {
				return window.elementorFrontend.isEditMode();
			}
		} catch ( e ) {}
		return !! ( document.body && document.body.classList.contains( 'elementor-editor-active' ) );
	}

	function readConfig( container ) {
		var ds = container.dataset;
		var repeat = parseInt( ds.repeat, 10 );
		if ( isNaN( repeat ) ) { repeat = 0; }
		return {
			method: ds.method || 'from',
			from: ds.from || '0%',
			to: ds.to || '100%',
			duration: parseFloat( ds.duration ) || 1,
			delay: parseFloat( ds.delay ) || 0,
			repeat: repeat,
			repeatDelay: parseFloat( ds.repeatDelay ) || 0,
			ease: ds.ease || 'sine.inOut',
			yoyo: ds.yoyo === 'yes',
			scrollTriggerFlag: ds.scroll_trigger === 'yes',
			start: ds.scrolltriggerstart || 'top 75%',
			end: ds.scrolltriggerend || 'bottom 0%',
			scrub: ds.scrub === 'number' ? ( parseFloat( ds.scrub_number ) || 1 ) : ds.scrub === 'true',
		};
	}

	function splitPaths( paths ) {
		var toSplit = gsap.utils.toArray( paths );
		var newPaths = [];
		toSplit.forEach( function ( element ) {
			var tag = element.tagName.toLowerCase();
			if ( tag === 'circle' || tag === 'rect' || tag === 'ellipse' || tag === 'line' || tag === 'textpath' ) {
				newPaths.push( element );
				return;
			}
			if ( tag === 'path' || tag === 'polyline' || tag === 'polygon' ) {
				var rawPath = MotionPathPlugin.getRawPath( element );
				var parent = element.parentNode;
				var attributes = Array.prototype.slice.call( element.attributes );
				if ( ! rawPath || rawPath.length === 0 ) {
					newPaths.push( element );
					return;
				}
				rawPath.forEach( function ( segment ) {
					var newPath = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
					attributes.forEach( function ( attr ) { newPath.setAttribute( attr.name, attr.value ); } );
					newPath.setAttribute( 'd', 'M' + segment[0] + ',' + segment[1] + 'C' + segment.slice( 2 ).join( ',' ) + ( segment.closed ? 'z' : '' ) );
					parent.insertBefore( newPath, element );
					newPaths.push( newPath );
				} );
				parent.removeChild( element );
			}
		} );
		return newPaths;
	}

	/** Split the SVG once and cache the resulting paths on the container. */
	function prepare( container ) {
		var selector = 'path, circle, rect, line, polyline, polygon, ellipse, textPath';
		var elems = container.querySelectorAll( selector );
		if ( elems.length === 0 ) {
			return false;
		}
		gsap.registerPlugin( ScrollTrigger, DrawSVGPlugin, MotionPathPlugin );
		var paths = splitPaths( elems );
		container.__aaeDrawPaths = paths;
		container.__aaeDrawTotal = paths.reduce( function ( sum, path ) { return sum + path.getTotalLength(); }, 0 ) || 1;
		return paths.length > 0;
	}

	/** Kill any tweens + ScrollTriggers previously created for this container. */
	function killPrev( container ) {
		if ( container.__aaeDrawTweens ) {
			container.__aaeDrawTweens.forEach( function ( t ) {
				if ( t && t.scrollTrigger ) { t.scrollTrigger.kill(); }
				if ( t ) { t.kill(); }
			} );
			container.__aaeDrawTweens = null;
		}
		if ( container.__aaeDrawPaths ) {
			gsap.killTweensOf( container.__aaeDrawPaths );
		}
	}

	/**
	 * Run (or re-run) the draw animation on the cached paths.
	 *
	 * Uses explicit fromTo (deterministic start AND end) rather than gsap.from /
	 * gsap.to — those read the element's CURRENT drawSVG as one endpoint, so hitting
	 * "Play" mid-draw would shrink the range every time (each replay drawing less,
	 * eventually appearing stuck/slow). fromTo pins both ends, so every replay is
	 * identical no matter the current state. Old tweens/ScrollTriggers are killed
	 * first so nothing accumulates across rapid clicks.
	 */
	function play( container ) {
		var paths = container.__aaeDrawPaths;
		if ( ! paths || ! paths.length || ! pluginsReady() ) {
			return;
		}
		var cfg = readConfig( container );
		var total = container.__aaeDrawTotal || 1;

		killPrev( container );

		var tweens = [];
		paths.forEach( function ( elem ) {
			var pathLength = elem.getTotalLength();
			var pathDuration = cfg.duration * ( pathLength / total );

			var startVal, endVal;
			if ( cfg.method === 'to' ) {
				startVal = '100%';
				endVal = cfg.to;
			} else if ( cfg.method === 'fromTo' ) {
				startVal = cfg.from;
				endVal = cfg.to;
			} else { // 'from'
				startVal = cfg.from;
				endVal = '100%';
			}

			var toProps = {
				drawSVG: endVal,
				duration: pathDuration,
				delay: cfg.scrollTriggerFlag ? 0 : cfg.delay,
				repeat: cfg.scrub ? 0 : cfg.repeat,
				yoyo: cfg.scrub ? false : cfg.yoyo,
				repeatDelay: cfg.scrub ? 0 : cfg.repeatDelay,
				ease: cfg.ease,
				overwrite: 'auto',
				scrollTrigger: cfg.scrollTriggerFlag ? { trigger: container, start: cfg.start, end: cfg.end, scrub: cfg.scrub } : null,
			};

			tweens.push( gsap.fromTo( elem, { drawSVG: startVal }, toProps ) );
		} );
		container.__aaeDrawTweens = tweens;
	}

	function bindLink( container ) {
		var ds = container.dataset;
		if ( ds.linkable === 'yes' && ds.linkUrl ) {
			container.style.cursor = 'pointer';
			container.addEventListener( 'click', function () {
				window.open( ds.linkUrl, ds.linkExternal === 'yes' ? '_blank' : '_self' );
			} );
		}
	}

	/**
	 * Ensure the container holds an INLINE <svg> (so GSAP can reach its paths).
	 *
	 * In "SVG Image" mode the server inlines the file on the frontend, but the
	 * editor renders a bare <img> (the inlining is server-only). GSAP cannot animate
	 * paths inside an <img>, so here we fetch the image's SVG source and swap the
	 * <img> for the inline markup. No-op when an inline <svg> is already present
	 * (SVG-code mode / frontend). Async — resolves via callback.
	 */
	function ensureInlineSvg( container, cb ) {
		if ( container.querySelector( 'svg' ) ) {
			cb( true );
			return;
		}
		var img = container.querySelector( 'img' );
		var src = img && ( img.currentSrc || img.src );
		if ( ! src ) {
			cb( false );
			return;
		}
		if ( container.__aaeInlining ) {
			cb( false );
			return;
		}
		container.__aaeInlining = true;
		fetch( src ).then( function ( r ) { return r.text(); } ).then( function ( txt ) {
			container.__aaeInlining = false;
			if ( ! txt || txt.indexOf( '<svg' ) === -1 ) {
				cb( false );
				return;
			}
			var tmp = document.createElement( 'div' );
			tmp.innerHTML = txt;
			var svg = tmp.querySelector( 'svg' );
			if ( ! svg || ! img.parentNode ) {
				cb( false );
				return;
			}
			svg.style.maxWidth = '100%';
			svg.style.height = 'auto';
			svg.style.display = 'block';
			img.parentNode.replaceChild( svg, img );
			cb( true );
		} ).catch( function () {
			container.__aaeInlining = false;
			cb( false );
		} );
	}

	function runDraw( container ) {
		if ( ! prepare( container ) ) {
			return;
		}
		if ( ! isEditor() ) {
			bindLink( container );
		}
		try {
			play( container );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'AAE DrawSVG error:', e );
		}
	}

	function initDrawSvg( container ) {
		if ( ! container || container.dataset.aaeDrawInit === 'true' ) {
			return;
		}
		if ( ! pluginsReady() ) {
			return;
		}
		container.dataset.aaeDrawInit = 'true';
		ensureInlineSvg( container, function ( ok ) {
			if ( ! ok ) {
				return;
			}
			runDraw( container );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Editor: live re-scan + replay hook (panel "Play Animation" control)
	 * ------------------------------------------------------------------ */

	/**
	 * Replay the draw animation for a single element by id. Called cross-frame by
	 * the panel's "Play Animation" element-control (DrawPlayControl.jsx) via
	 * previewWindow.AAEDrawSvg.replay( id ). Prepares the element first if it has
	 * not been initialised yet.
	 */
	function replayById( id ) {
		if ( ! id ) { return false; }
		var container = document.querySelector( '.aae-a-draw-svg[data-id="' + id + '"]' );
		if ( ! container ) { return false; }
		if ( ! pluginsReady() ) { return false; }
		// Not prepared yet (never inited, or SVG-image not inlined) — inline + draw.
		if ( ! container.__aaeDrawPaths ) {
			container.dataset.aaeDrawInit = 'true';
			ensureInlineSvg( container, function ( ok ) {
				if ( ok ) { runDraw( container ); }
			} );
			return true;
		}
		play( container );
		return true;
	}

	window.AAEDrawSvg = window.AAEDrawSvg || {};
	window.AAEDrawSvg.replay = replayById;

	function scanEditor() {
		var nodes = document.querySelectorAll( '.aae-a-draw-svg' );
		Array.prototype.forEach.call( nodes, initDrawSvg );
	}

	var observer = null;
	function bootEditor() {
		scanEditor();
		if ( observer ) {
			return;
		}
		observer = new MutationObserver( function ( mutations ) {
			var touched = false;
			for ( var i = 0; i < mutations.length; i++ ) {
				var added = mutations[ i ].addedNodes;
				for ( var j = 0; j < added.length; j++ ) {
					var node = added[ j ];
					if ( ! node || node.nodeType !== 1 ) {
						continue;
					}
					if ( node.classList && node.classList.contains( 'aae-a-draw-svg' ) ) {
						touched = true; break;
					}
					if ( node.querySelector && node.querySelector( '.aae-a-draw-svg' ) ) {
						touched = true; break;
					}
				}
				if ( touched ) { break; }
			}
			if ( touched ) {
				scanEditor();
			}
		} );
		observer.observe( document.body, { childList: true, subtree: true } );
	}

	/* ------------------------------------------------------------------ *
	 * Boot
	 * ------------------------------------------------------------------ */

	function initAll() {
		var nodes = document.querySelectorAll( '.aae-a-draw-svg' );
		Array.prototype.forEach.call( nodes, initDrawSvg );
	}

	function boot() {
		if ( isEditor() ) {
			bootEditor();
		} else {
			initAll();
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
	window.addEventListener( 'elementor/frontend/init', boot );
}());
