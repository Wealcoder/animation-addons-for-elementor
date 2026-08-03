/* eslint-env browser */

/**
 * Background Video — frontend runtime for e-flexbox / e-div-block / e-grid.
 *
 * v4's atomic Background style control offers colour, image and gradient but no
 * video, so this puts one behind the container: a layer injected as the
 * container's first child, holding a muted autoplaying <video> sized to cover.
 *
 * The layer is built HERE rather than server-side because those three types are
 * rendered from Elementor's own Twig templates — there is no seam to print a
 * child into, and buffering every container's output to splice one in would
 * cost an ob_start() per container on every page. See Render.php.
 *
 * Config arrives via window.AAE_INTERACTIONS_BGV[<interaction id>], published
 * by Render.php only for containers that have the feature on AND a source that
 * resolves — so this bundle never runs against an element with nothing to play.
 */

const MAP_NAME = 'AAE_INTERACTIONS_BGV';

const HOST_CLASS = 'aae-bgv-host';
const LAYER_CLASS = 'aae-bgv-layer';
const STYLE_ID = 'aae-bgv-css';

/**
 * The layer's structural CSS, injected once per document.
 *
 * Inline rather than a registered stylesheet: it is a handful of lines that only
 * matter on pages this bundle already loaded on, so a second HTTP request and a
 * second registration touchpoint would both be pure overhead.
 *
 * `:where()` on the host's `position` keeps it at zero specificity — a user who
 * sets Position themselves in the Style tab still wins. The layer's own rules
 * are NOT zero-specificity: they are the mechanics of covering the box, not a
 * look, and nothing in the Style panel targets this element.
 *
 * ── Why z-index -1 and isolation, and not z-index 0 ──────────────────────
 * CSS paints a POSITIONED z-index:0 box (step 6 of the painting order) AFTER
 * in-flow non-positioned content (steps 3–5). The container's widgets are
 * ordinary static blocks, so a z-index:0 layer covers them — the video plays
 * over the heading instead of behind it.
 *
 * A negative z-index moves the layer to step 2: above the host's own
 * background/border, below every in-flow child. That only holds if the host is
 * itself a stacking context — otherwise the layer escapes to the nearest
 * ancestor that is one and hides behind the container's own background colour
 * (the classic z-index:-1 disappearing act). `isolation: isolate` creates that
 * context without touching the host's own position or z-index, so it cannot
 * change how the container stacks against its siblings.
 *
 * Elementor's own `--background-overlay` (`.e-con::before`, positioned, z-index
 * auto) still paints above the video — same order v3 produces.
 */
const LAYER_CSS = `
:where(.${HOST_CLASS}){position:relative}
.${HOST_CLASS}{isolation:isolate}
.${LAYER_CLASS}{position:absolute;inset:0;overflow:hidden;z-index:-1;pointer-events:none;background-position:50% 50%;background-size:cover;border-radius:inherit}
.${LAYER_CLASS}>video{display:block;width:100%;height:100%;object-fit:cover}
`;

const ensureStyles = () => {
	if ( document.getElementById( STYLE_ID ) ) {
		return;
	}
	const style = document.createElement( 'style' );
	style.id = STYLE_ID;
	style.textContent = LAYER_CSS;
	document.head.appendChild( style );
};

const isEditMode = () =>
	( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode && elementorFrontend.isEditMode() ) ||
	!! ( document.body && document.body.classList.contains( 'elementor-editor-active' ) );

const reduceMotion = () =>
	!! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

/**
 * Which breakpoint are we on?
 *
 * Always through AAEADDON, never `elementorFrontend.getCurrentDeviceMode()`
 * directly: measured on an atomic-v4-only page, `elementorFrontend` is NOT
 * loaded at all, so a direct call silently answers "desktop" everywhere and
 * every responsive value collapses to its desktop cell. currentBreakpoint()
 * falls back to a width comparison against the site's real registered
 * breakpoints, so it keeps working on those pages.
 */
const currentBp = () => window.AAEADDON.currentBreakpoint();

/**
 * "Play On Mobile" covers both phone breakpoints. Elementor's `mobile_extra`
 * sits between mobile and tablet (phablet widths); a visitor there is on the
 * same metered connection the setting exists to protect.
 */
const isMobile = () => {
	const bp = currentBp();
	return 'mobile' === bp || 'mobile_extra' === bp;
};

/**
 * Read a per-breakpoint value out of one of the `sources` / `posters` maps.
 *
 * These are plain `{ desktop: …, tablet: … }` objects rather than the
 * `key`/`key_tablet` shape pickConfigResponsive() reads, because a URL is
 * resolved server-side from three separate props (source / file / link) and
 * flattening it there is what keeps this file from re-deriving the same answer.
 * The cascade is the shared BP_CASCADE — nearest defined breakpoint upwards,
 * then desktop.
 */
const pickFromMap = ( map ) => {
	if ( ! map || 'object' !== typeof map ) {
		return '';
	}

	const bp = currentBp();
	const chain = [ bp ].concat( window.AAEADDON.BP_CASCADE[ bp ] || [] );

	for ( let i = 0; i < chain.length; i++ ) {
		if ( map[ chain[ i ] ] ) {
			return map[ chain[ i ] ];
		}
	}

	return map.desktop || '';
};

const readFlag = ( config, key ) => {
	const value = window.AAEADDON.pickConfigResponsive( config, key );
	return true === value || 'true' === value || 1 === value || '1' === value;
};

const removeLayer = ( el ) => {
	if ( el.__aaeBgvLayer ) {
		el.__aaeBgvLayer.remove();
		delete el.__aaeBgvLayer;
	}
	el.classList.remove( HOST_CLASS );
};

const BackgroundVideoKind = {
	name: 'background-video',
	mapName: MAP_NAME,
	boundFlag: 'aae-bgv-bound',
	playedKey: '__aaeBgvLayer',

	read( el ) {
		return window.AAEADDON.configFor( el, MAP_NAME );
	},

	bind( el, config ) {
		if ( ! config || ! config.enabled ) {
			return;
		}

		// A re-render in the editor hands us the same element again.
		removeLayer( el );

		const src = pickFromMap( config.sources );
		if ( ! src ) {
			return;
		}

		const poster = pickFromMap( config.posters );

		ensureStyles();

		const layer = document.createElement( 'div' );
		layer.className = LAYER_CLASS;
		layer.setAttribute( 'aria-hidden', 'true' );

		// Painted immediately, and left in place underneath the video: it is
		// what covers the gap before the first frame decodes, and the whole of
		// the background on the paths below that never load a video at all.
		if ( poster ) {
			layer.style.backgroundImage = `url("${ poster.replace( /"/g, '%22' ) }")`;
		}

		// Skip playback, keep the poster: on mobile unless asked (autoplaying
		// video on mobile data is a real cost, and it is v3's default too), and
		// for a visitor who asked for reduced motion.
		const skipPlayback = ( isMobile() && ! readFlag( config, 'playOnMobile' ) ) || reduceMotion();

		if ( ! skipPlayback ) {
			const video = document.createElement( 'video' );
			video.autoplay = true;
			video.muted = true;
			// Safari ignores the property unless the ATTRIBUTE is present too,
			// and without it iOS opens the video fullscreen instead of playing
			// it inline — the classic "works everywhere but iPhone" report.
			video.setAttribute( 'muted', '' );
			video.setAttribute( 'playsinline', '' );
			video.playsInline = true;
			video.loop = ! readFlag( config, 'playOnce' );
			video.preload = 'auto';
			video.setAttribute( 'role', 'presentation' );
			video.setAttribute( 'tabindex', '-1' );

			if ( poster ) {
				video.poster = poster;
			}

			video.src = src;
			layer.appendChild( video );

			// Autoplay can still be refused (a battery-saver profile, a browser
			// setting). The poster is already showing, so there is nothing to
			// repair — just don't leave an unhandled rejection in the console.
			const played = video.play();
			if ( played && 'function' === typeof played.catch ) {
				played.catch( () => {} );
			}
		}

		el.classList.add( HOST_CLASS );
		el.insertBefore( layer, el.firstChild );
		el.__aaeBgvLayer = layer;
	},

	// Nothing to trigger — the layer plays as soon as it is bound. play() exists
	// because the editor replay path calls it.
	play( el, config ) {
		BackgroundVideoKind.bind( el, config );
	},

	reset( el ) {
		removeLayer( el );
	},

	unbind( el ) {
		removeLayer( el );
	},
};

window.AAEADDON.register( BackgroundVideoKind );

/* =====================================================================
 * Editor
 *
 * window.AAE_INTERACTIONS_BGV is printed by PHP at preview-footer time, from
 * whatever the document held when the preview last rendered. Toggling Enable or
 * picking a file in the panel does none of that — the atomic settings
 * transaction updates the MODEL and re-renders the element's Twig client-side,
 * so the map still describes the page as it was on load and a freshly-configured
 * video would not appear until a full reload.
 *
 * So on the canvas the config is read from the element's live settings instead
 * of the map, on a slow tick. Same reconciler shape the Offcanvas and Stack
 * Cards widgets use, and for the same reason: an atomic control has no reliable
 * "settings committed" event to listen for.
 * =================================================================== */

const TARGET_TYPES = [ 'e-flexbox', 'e-div-block', 'e-grid' ];

const getEditorWindow = () => ( window.parent && window.parent !== window ? window.parent : window );

/** Atomic props arrive either raw or wrapped as { $$type, value }. */
const unwrap = ( v ) => ( v && 'object' === typeof v && 'value' in v ? v.value : v );

/** A Responsive_JSON prop unwraps to a { desktop: …, tablet: … } map. */
const bpMap = ( v ) => {
	const inner = unwrap( v );
	return inner && 'object' === typeof inner ? inner : {};
};

/**
 * Rebuild the wire config Render.php would have produced, from live settings.
 * Kept deliberately close to Render::resolve_urls() — if the two disagree, the
 * canvas lies about what the visitor will get.
 */
const readEditorConfig = ( el ) => {
	const id = el.getAttribute( 'data-id' );
	if ( ! id ) {
		return null;
	}

	let container = null;
	try {
		const editor = getEditorWindow().elementor;
		container = editor && editor.getContainer ? editor.getContainer( id ) : null;
	} catch ( e ) {
		return null;
	}
	if ( ! container ) {
		return null;
	}

	const get = ( key ) => {
		try {
			return container.settings && container.settings.get ? container.settings.get( key ) : undefined;
		} catch ( e ) {
			return undefined;
		}
	};

	if ( ! unwrap( get( 'aae_bgv_enable' ) ) ) {
		return { enabled: false };
	}

	const sourceMap = bpMap( get( 'aae_bgv_source' ) );
	const fileMap   = bpMap( get( 'aae_bgv_file' ) );
	const linkMap   = bpMap( get( 'aae_bgv_link' ) );
	const posterMap = bpMap( get( 'aae_bgv_poster' ) );

	const breakpoints = Array.from( new Set(
		[ 'desktop' ].concat(
			Object.keys( sourceMap ), Object.keys( fileMap ),
			Object.keys( linkMap ), Object.keys( posterMap )
		)
	) );

	const sources = {};
	const posters = {};

	breakpoints.forEach( ( bp ) => {
		const source = unwrap( sourceMap[ bp ] ) || unwrap( sourceMap.desktop ) || 'file';

		const url = 'url' === source
			? ( unwrap( linkMap[ bp ] ) || unwrap( linkMap.desktop ) || '' )
			: ( ( unwrap( fileMap[ bp ] ) || unwrap( fileMap.desktop ) || {} ).url || '' );

		if ( url ) {
			sources[ bp ] = url;
		}

		const poster = unwrap( posterMap[ bp ] );
		if ( poster && poster.url ) {
			posters[ bp ] = poster.url;
		}
	} );

	// Resolved to flat scalars, not per-bp keys: pickConfigResponsive() reads the
	// base key when no `key_<bp>` override exists, which is what these become.
	//
	// pickFromMap() cannot be reused here — it walks for the first TRUTHY cell,
	// which is right for a URL (an empty one should inherit) and wrong for a
	// switch, where a deliberate per-breakpoint `false` must win over a `true`
	// further up the cascade.
	const flag = ( key ) => {
		const map = bpMap( get( key ) );
		const bp = currentBp();
		const chain = [ bp ].concat( window.AAEADDON.BP_CASCADE[ bp ] || [] );

		for ( let i = 0; i < chain.length; i++ ) {
			const value = unwrap( map[ chain[ i ] ] );
			if ( undefined !== value && null !== value && '' !== value ) {
				return true === value || 'true' === value || 1 === value || '1' === value;
			}
		}

		const desktop = unwrap( map.desktop );
		return true === desktop || 'true' === desktop || 1 === desktop || '1' === desktop;
	};

	return {
		enabled: true,
		sources,
		posters,
		playOnce: flag( 'aae_bgv_play_once' ),
		playOnMobile: flag( 'aae_bgv_play_on_mobile' ),
	};
};

const signatureOf = ( config ) => JSON.stringify( config || null );

if ( isEditMode() ) {
	window.setInterval( () => {
		const selector = TARGET_TYPES.map( ( t ) => `[data-e-type="${ t }"]` ).join( ',' );

		document.querySelectorAll( selector ).forEach( ( el ) => {
			const config = readEditorConfig( el );

			// No container yet (mid-render), or the element is not ours.
			if ( ! config ) {
				return;
			}

			const wanted = !! ( config.enabled && pickFromMap( config.sources ) );

			if ( ! wanted ) {
				if ( el.__aaeBgvLayer ) {
					removeLayer( el );
					delete el.__aaeBgvSignature;
				}
				return;
			}

			const signature = signatureOf( config );
			const attached = el.__aaeBgvLayer && el.__aaeBgvLayer.parentElement === el;

			// Rebind when the settings changed, or when a re-render dropped the
			// layer we injected (Elementor rebuilds the element's innerHTML).
			if ( attached && el.__aaeBgvSignature === signature ) {
				return;
			}

			el.__aaeBgvSignature = signature;
			BackgroundVideoKind.bind( el, config );
		} );
	}, 500 );
}
