import { register } from '@elementor/frontend-handlers';

/* AAE Atomic Video.
 *
 * One frontend handler bound to the PARENT element (e-aae-a-video), which
 * owns every source/playback setting as data-aae-video-* attributes. It
 * reaches into its own rendered subtree for:
 *   - .aae-a-video-poster / .aae-a-video-playbtn — the native Image/Button
 *     children (hook classes seeded via their `classes` prop, since native
 *     widgets emit no data-element_type of their own to key off instead).
 *   - .aae-a-video-mount / .aae-a-video-controls — our own Parts\
 *     AAE_A_Video_Player, a "dumb" mount point + controls bar shell with no
 *     settings of its own (see that class's docblock).
 *
 * The mount starts EMPTY — this file builds the actual media element itself
 * (a real <video>, a YT.Player, or a Vimeo.Player) on first interaction (or
 * immediately for an in-view autoplay instance), so the same custom controls
 * bar drives all three uniformly. No dependency on Elementor's own
 * e-youtube/e-self-hosted-video widgets, whose player internals aren't
 * reachable from outside (verified directly in youtube-handler.js).
 */

const YT_ID_REGEX = /^(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?vi?=|(?:embed|v|vi|user|shorts)\/))([^?&"'>]+)/;
const VIMEO_ID_REGEX = /vimeo\.com\/(?:.*\/)?(?:videos\/)?(\d+)/;
const DAILYMOTION_ID_REGEX = /(?:dailymotion\.com\/(?:embed\/)?video\/|dai\.ly\/)([a-zA-Z0-9]+)/;
const VIDEOPRESS_GUID_REGEX = /videopress\.com\/(?:v|embed)\/([a-zA-Z0-9]+)/;

// Kept in sync with AAE_A_Video::extract_youtube_id() (PHP) — both extract
// the same id from the same URL shapes, one server-side (poster
// resolution), one client-side (player mount).
const getYoutubeIdFromUrl = ( url ) => {
	const match = ( url || '' ).match( YT_ID_REGEX );
	return match ? match[ 1 ] : null;
};

const getVimeoIdFromUrl = ( url ) => {
	const match = ( url || '' ).match( VIMEO_ID_REGEX );
	return match ? match[ 1 ] : null;
};

const getDailymotionIdFromUrl = ( url ) => {
	const match = ( url || '' ).match( DAILYMOTION_ID_REGEX );
	return match ? match[ 1 ] : null;
};

const getVideoPressGuidFromUrl = ( url ) => {
	const match = ( url || '' ).match( VIDEOPRESS_GUID_REGEX );
	return match ? match[ 1 ] : null;
};

const readConfig = ( el ) => ( {
	type: el.getAttribute( 'data-aae-video-type' ) || 'youtube',
	youtubeUrl: el.getAttribute( 'data-aae-video-youtube-url' ) || '',
	hostedUrl: el.getAttribute( 'data-aae-video-hosted-url' ) || '',
	vimeoUrl: el.getAttribute( 'data-aae-video-vimeo-url' ) || '',
	dailymotionUrl: el.getAttribute( 'data-aae-video-dailymotion-url' ) || '',
	videopressUrl: el.getAttribute( 'data-aae-video-videopress-url' ) || '',
	autoplay: el.getAttribute( 'data-aae-video-autoplay' ) === 'true',
	mute: el.getAttribute( 'data-aae-video-mute' ) === 'true',
	loop: el.getAttribute( 'data-aae-video-loop' ) === 'true',
	preload: el.getAttribute( 'data-aae-video-preload' ) || 'metadata',
	lazyload: el.getAttribute( 'data-aae-video-lazyload' ) === 'true',
	youtubePrivacy: el.getAttribute( 'data-aae-video-youtube-privacy' ) === 'true',
	vimeoDnt: el.getAttribute( 'data-aae-video-vimeo-dnt' ) === 'true',
	poster: el.getAttribute( 'data-aae-video-poster' ) || '',
	placeholder: el.getAttribute( 'data-aae-video-placeholder' ) || '',
	controlsEnabled: el.getAttribute( 'data-aae-video-controls-enabled' ) === 'true',
	autohide: el.getAttribute( 'data-aae-video-controls-autohide' ) === 'true',
} );

const isEditMode = () => !! window.elementorFrontend?.isEditMode?.();

// --- Lazy SDK loaders — only injected when a widget instance on the page actually needs them. ---

let ytApiPromise = null;
const loadYoutubeApi = () => {
	if ( window.YT?.Player ) return Promise.resolve( window.YT );
	if ( ytApiPromise ) return ytApiPromise;

	ytApiPromise = new Promise( ( resolve ) => {
		const previous = window.onYouTubeIframeAPIReady;
		window.onYouTubeIframeAPIReady = () => {
			if ( typeof previous === 'function' ) previous();
			resolve( window.YT );
		};
		const script = document.createElement( 'script' );
		script.src = 'https://www.youtube.com/iframe_api';
		document.head.appendChild( script );
	} );

	return ytApiPromise;
};

let vimeoApiPromise = null;
const loadVimeoApi = () => {
	if ( window.Vimeo?.Player ) return Promise.resolve( window.Vimeo );
	if ( vimeoApiPromise ) return vimeoApiPromise;

	vimeoApiPromise = new Promise( ( resolve ) => {
		const script = document.createElement( 'script' );
		script.src = 'https://player.vimeo.com/api/player.js';
		script.onload = () => resolve( window.Vimeo );
		document.head.appendChild( script );
	} );

	return vimeoApiPromise;
};

// --- Adapters — each mounts its own media element inside `mountEl` (prepended,
// so the controls bar markup that's already there stays on top) and exposes
// the same interface, so the controls bar never needs to know which backend
// it's driving. To add another provider: write one more createXAdapter() and
// a matching branch in pickAdapter() below. ---

const createNativeAdapter = ( mountEl, cfg ) => {
	const videoEl = document.createElement( 'video' );
	videoEl.className = 'aae-a-video-native';
	videoEl.preload = cfg.preload;
	videoEl.loop = cfg.loop;
	videoEl.playsInline = true;
	videoEl.muted = cfg.mute || cfg.autoplay;
	videoEl.src = cfg.hostedUrl;
	mountEl.insertBefore( videoEl, mountEl.firstChild );

	const listeners = {};
	const emit = ( evt ) => ( listeners[ evt ] || [] ).forEach( ( cb ) => cb() );
	const on = ( evt, cb ) => {
		( listeners[ evt ] = listeners[ evt ] || [] ).push( cb );
	};

	const handlers = {
		timeupdate: () => emit( 'timeupdate' ),
		ended: () => emit( 'ended' ),
		play: () => emit( 'play' ),
		pause: () => emit( 'pause' ),
		loadedmetadata: () => emit( 'timeupdate' ),
	};
	Object.entries( handlers ).forEach( ( [ evt, fn ] ) => videoEl.addEventListener( evt, fn ) );

	return {
		play: () => videoEl.play().catch( () => {} ),
		pause: () => videoEl.pause(),
		togglePlay: () => ( videoEl.paused ? videoEl.play().catch( () => {} ) : videoEl.pause() ),
		seekTo: ( s ) => { videoEl.currentTime = s; },
		getCurrentTime: () => videoEl.currentTime || 0,
		getDuration: () => videoEl.duration || 0,
		mute: () => { videoEl.muted = true; },
		unmute: () => { videoEl.muted = false; },
		isMuted: () => videoEl.muted,
		getFullscreenTarget: () => videoEl,
		on,
		destroy: () => {
			Object.entries( handlers ).forEach( ( [ evt, fn ] ) => videoEl.removeEventListener( evt, fn ) );
			videoEl.remove();
		},
	};
};

const createYoutubeAdapter = ( mountEl, cfg ) => {
	const holder = document.createElement( 'div' );
	holder.className = 'aae-a-video-embed';
	mountEl.insertBefore( holder, mountEl.firstChild );

	const listeners = {};
	const emit = ( evt ) => ( listeners[ evt ] || [] ).forEach( ( cb ) => cb() );
	const on = ( evt, cb ) => {
		( listeners[ evt ] = listeners[ evt ] || [] ).push( cb );
	};

	const videoId = getYoutubeIdFromUrl( cfg.youtubeUrl );
	let player = null;
	let ready = false;
	let mutedState = cfg.mute || cfg.autoplay;
	let pollTimer = null;
	const pending = [];
	const whenReady = ( fn ) => ( ready ? fn() : pending.push( fn ) );

	const stopPoll = () => {
		if ( pollTimer ) clearInterval( pollTimer );
		pollTimer = null;
	};
	// YT.Player has no native `timeupdate` — poll while playing and synthesize it.
	const startPoll = () => {
		stopPoll();
		pollTimer = setInterval( () => emit( 'timeupdate' ), 250 );
	};

	loadYoutubeApi().then( ( YT ) => {
		player = new YT.Player( holder, {
			videoId,
			host: cfg.youtubePrivacy ? 'https://www.youtube-nocookie.com' : undefined,
			playerVars: {
				autoplay: cfg.autoplay ? 1 : 0,
				mute: mutedState ? 1 : 0,
				loop: cfg.loop ? 1 : 0,
				playlist: cfg.loop && videoId ? videoId : undefined,
				controls: 0,
				rel: 0,
				playsinline: 1,
			},
			events: {
				onReady: () => {
					ready = true;
					if ( mutedState ) player.mute();
					pending.splice( 0 ).forEach( ( fn ) => fn() );
				},
				onStateChange: ( e ) => {
					if ( e.data === YT.PlayerState.PLAYING ) { startPoll(); emit( 'play' ); }
					else if ( e.data === YT.PlayerState.PAUSED ) { stopPoll(); emit( 'pause' ); }
					else if ( e.data === YT.PlayerState.ENDED ) { stopPoll(); emit( 'ended' ); }
				},
			},
		} );
	} );

	return {
		play: () => whenReady( () => player.playVideo() ),
		pause: () => whenReady( () => player.pauseVideo() ),
		togglePlay: () => whenReady( () => {
			const state = player.getPlayerState();
			return state === window.YT.PlayerState.PLAYING ? player.pauseVideo() : player.playVideo();
		} ),
		seekTo: ( s ) => whenReady( () => player.seekTo( s, true ) ),
		getCurrentTime: () => ( ready && player.getCurrentTime ? player.getCurrentTime() : 0 ),
		getDuration: () => ( ready && player.getDuration ? player.getDuration() : 0 ),
		mute: () => { mutedState = true; whenReady( () => player.mute() ); },
		unmute: () => { mutedState = false; whenReady( () => player.unMute() ); },
		isMuted: () => mutedState,
		getFullscreenTarget: () => mountEl,
		on,
		destroy: () => { stopPoll(); player?.destroy?.(); holder.remove(); },
	};
};

const createVimeoAdapter = ( mountEl, cfg ) => {
	const holder = document.createElement( 'div' );
	holder.className = 'aae-a-video-embed';
	mountEl.insertBefore( holder, mountEl.firstChild );

	const listeners = {};
	const emit = ( evt ) => ( listeners[ evt ] || [] ).forEach( ( cb ) => cb() );
	const on = ( evt, cb ) => {
		( listeners[ evt ] = listeners[ evt ] || [] ).push( cb );
	};

	const videoId = getVimeoIdFromUrl( cfg.vimeoUrl );
	let player = null;
	let ready = false;
	let mutedState = cfg.mute || cfg.autoplay;
	let currentTime = 0;
	let duration = 0;
	const pending = [];
	const whenReady = ( fn ) => ( ready ? fn() : pending.push( fn ) );

	loadVimeoApi().then( ( Vimeo ) => {
		player = new Vimeo.Player( holder, {
			id: videoId,
			autoplay: cfg.autoplay,
			muted: mutedState,
			loop: cfg.loop,
			playsinline: true,
			controls: false,
			dnt: cfg.vimeoDnt,
		} );

		player.on( 'play', () => emit( 'play' ) );
		player.on( 'pause', () => emit( 'pause' ) );
		player.on( 'ended', () => emit( 'ended' ) );
		player.on( 'timeupdate', ( data ) => {
			currentTime = data.seconds;
			duration = data.duration;
			emit( 'timeupdate' );
		} );

		player.ready().then( () => {
			ready = true;
			if ( mutedState ) player.setVolume( 0 ).catch( () => {} );
			pending.splice( 0 ).forEach( ( fn ) => fn() );
		} );
	} );

	return {
		play: () => whenReady( () => player.play().catch( () => {} ) ),
		pause: () => whenReady( () => player.pause().catch( () => {} ) ),
		togglePlay: () => whenReady( () => player.getPaused().then( ( paused ) => ( paused ? player.play() : player.pause() ) ).catch( () => {} ) ),
		seekTo: ( s ) => whenReady( () => player.setCurrentTime( s ).catch( () => {} ) ),
		getCurrentTime: () => currentTime,
		getDuration: () => duration,
		mute: () => { mutedState = true; whenReady( () => player.setVolume( 0 ).catch( () => {} ) ); },
		unmute: () => { mutedState = false; whenReady( () => player.setVolume( 1 ).catch( () => {} ) ); },
		isMuted: () => mutedState,
		getFullscreenTarget: () => mountEl,
		on,
		destroy: () => { player?.destroy?.(); holder.remove(); },
	};
};

// --- postMessage-based adapters (Dailymotion, VideoPress) ---
//
// Neither provider needs an SDK script or an account-issued player id — both
// embeds accept simple play/pause/seek/mute commands over window.postMessage
// and report state back the same way. This is a best-effort implementation
// against each provider's publicly documented postMessage protocol — unlike
// YouTube/Vimeo there's no official JS SDK to lean on here, so if a command
// or an event doesn't do anything, the exact message shape in DAILYMOTION_
// PROVIDER / VIDEOPRESS_PROVIDER below is the first place to check and
// correct against a real embed's devtools console.

const createPostMessageAdapter = ( mountEl, cfg, provider ) => {
	const iframe = document.createElement( 'iframe' );
	iframe.className = 'aae-a-video-embed';
	iframe.src = provider.buildSrc( cfg );
	iframe.allow = 'autoplay; fullscreen; picture-in-picture';
	iframe.allowFullscreen = true;
	mountEl.insertBefore( iframe, mountEl.firstChild );

	const listeners = {};
	const emit = ( evt ) => ( listeners[ evt ] || [] ).forEach( ( cb ) => cb() );
	const on = ( evt, cb ) => {
		( listeners[ evt ] = listeners[ evt ] || [] ).push( cb );
	};

	let currentTime = 0;
	let duration = 0;
	let isPlaying = false;
	let mutedState = cfg.mute || cfg.autoplay;

	const send = ( command ) => iframe.contentWindow?.postMessage( provider.encode( command ), '*' );

	const onMessage = ( e ) => {
		if ( e.source !== iframe.contentWindow ) return;
		const parsed = provider.decode( e.data );
		if ( ! parsed ) return;

		if ( 'timeupdate' === parsed.type ) {
			if ( 'number' === typeof parsed.currentTime ) currentTime = parsed.currentTime;
			if ( 'number' === typeof parsed.duration ) duration = parsed.duration;
			emit( 'timeupdate' );
		} else if ( 'play' === parsed.type ) {
			isPlaying = true;
			emit( 'play' );
		} else if ( 'pause' === parsed.type ) {
			isPlaying = false;
			emit( 'pause' );
		} else if ( 'ended' === parsed.type ) {
			isPlaying = false;
			emit( 'ended' );
		}
	};
	window.addEventListener( 'message', onMessage );

	return {
		play: () => send( { action: 'play' } ),
		pause: () => send( { action: 'pause' } ),
		togglePlay: () => send( { action: isPlaying ? 'pause' : 'play' } ),
		seekTo: ( s ) => send( { action: 'seek', time: s } ),
		getCurrentTime: () => currentTime,
		getDuration: () => duration,
		mute: () => { mutedState = true; send( { action: 'mute', muted: true } ); },
		unmute: () => { mutedState = false; send( { action: 'mute', muted: false } ); },
		isMuted: () => mutedState,
		getFullscreenTarget: () => iframe,
		on,
		destroy: () => { window.removeEventListener( 'message', onMessage ); iframe.remove(); },
	};
};

const DAILYMOTION_PROVIDER = {
	buildSrc: ( cfg ) => {
		const id = getDailymotionIdFromUrl( cfg.dailymotionUrl );
		const muted = cfg.mute || cfg.autoplay;
		const params = new URLSearchParams( {
			api: 'postMessage',
			autoplay: cfg.autoplay ? '1' : '0',
			mute: muted ? '1' : '0',
			loop: cfg.loop ? '1' : '0',
			controls: '0',
			'queue-enable': '0',
		} );
		return `https://www.dailymotion.com/embed/video/${ id }?${ params.toString() }`;
	},
	// Dailymotion's Player API (`?api=postMessage`) exchanges JSON STRINGS:
	// commands are {command, parameters: [...]}, events are {event, parameters}.
	encode: ( command ) => {
		if ( 'seek' === command.action ) return JSON.stringify( { command: 'seek', parameters: [ command.time ] } );
		if ( 'mute' === command.action ) return JSON.stringify( { command: 'muted', parameters: [ command.muted ] } );
		return JSON.stringify( { command: command.action, parameters: [] } );
	},
	decode: ( raw ) => {
		let data;
		try {
			data = 'string' === typeof raw ? JSON.parse( raw ) : raw;
		} catch ( e ) {
			return null;
		}
		if ( ! data || ! data.event ) return null;
		if ( 'timeupdate' === data.event ) return { type: 'timeupdate', currentTime: data.parameters?.time, duration: data.parameters?.duration };
		if ( 'video_end' === data.event || 'ended' === data.event ) return { type: 'ended' };
		if ( 'play' === data.event || 'playing' === data.event ) return { type: 'play' };
		if ( 'pause' === data.event ) return { type: 'pause' };
		return null;
	},
};

const VIDEOPRESS_PROVIDER = {
	buildSrc: ( cfg ) => {
		const guid = getVideoPressGuidFromUrl( cfg.videopressUrl );
		const muted = cfg.mute || cfg.autoplay;
		const params = new URLSearchParams( {
			hd: '0',
			autoPlay: cfg.autoplay ? '1' : '0',
			muted: muted ? '1' : '0',
			loop: cfg.loop ? '1' : '0',
			controls: '0',
			persisttime: '0',
		} );
		return `https://videopress.com/embed/${ guid }?${ params.toString() }`;
	},
	// VideoPress's embed posts/accepts plain OBJECTS (not JSON strings).
	encode: ( command ) => {
		if ( 'seek' === command.action ) return { event: 'videopress_action_set_currenttime', currentTime: command.time };
		if ( 'mute' === command.action ) return { event: 'videopress_action_set_muted', muted: command.muted };
		if ( 'play' === command.action ) return { event: 'videopress_action_play_video' };
		return { event: 'videopress_action_pause_video' };
	},
	decode: ( raw ) => {
		const data = raw;
		if ( ! data || 'object' !== typeof data || ! data.event ) return null;
		if ( 'videopress_timeupdate' === data.event ) return { type: 'timeupdate', currentTime: data.currentTime, duration: data.duration };
		if ( 'videopress_ended' === data.event ) return { type: 'ended' };
		if ( 'videopress_playing' === data.event ) return { type: 'play' };
		if ( 'videopress_paused' === data.event ) return { type: 'pause' };
		return null;
	},
};

const createDailymotionAdapter = ( mountEl, cfg ) => createPostMessageAdapter( mountEl, cfg, DAILYMOTION_PROVIDER );
const createVideoPressAdapter = ( mountEl, cfg ) => createPostMessageAdapter( mountEl, cfg, VIDEOPRESS_PROVIDER );

const ADAPTERS = {
	hosted: createNativeAdapter,
	youtube: createYoutubeAdapter,
	vimeo: createVimeoAdapter,
	dailymotion: createDailymotionAdapter,
	videopress: createVideoPressAdapter,
};

// --- UI helpers ---

const formatTime = ( seconds ) => {
	if ( ! isFinite( seconds ) || seconds < 0 ) seconds = 0;
	const m = Math.floor( seconds / 60 );
	const s = Math.floor( seconds % 60 );
	return `${ m }:${ String( s ).padStart( 2, '0' ) }`;
};

const setPlayingState = ( el, playing ) => {
	el.classList.toggle( 'is-playing', playing );
	el.querySelectorAll( '.aae-a-video-icon-play' ).forEach( ( icon ) => { icon.hidden = playing; } );
	el.querySelectorAll( '.aae-a-video-icon-pause' ).forEach( ( icon ) => { icon.hidden = ! playing; } );
};

const setMutedState = ( el, muted ) => {
	el.querySelectorAll( '.aae-a-video-icon-unmuted' ).forEach( ( icon ) => { icon.hidden = muted; } );
	el.querySelectorAll( '.aae-a-video-icon-muted' ).forEach( ( icon ) => { icon.hidden = ! muted; } );
};

const updateProgress = ( mountEl, controller ) => {
	const duration = controller.getDuration();
	const current = controller.getCurrentTime();
	const pct = duration > 0 ? Math.min( 100, ( current / duration ) * 100 ) : 0;

	const fill = mountEl.querySelector( '.aae-a-video-progress-fill' );
	const track = mountEl.querySelector( '.aae-a-video-progress' );
	const curEl = mountEl.querySelector( '.aae-a-video-time-cur' );
	const durEl = mountEl.querySelector( '.aae-a-video-time-dur' );

	if ( fill ) fill.style.width = `${ pct }%`;
	if ( track ) track.setAttribute( 'aria-valuenow', String( Math.round( pct ) ) );
	if ( curEl ) curEl.textContent = formatTime( current );
	if ( durEl ) durEl.textContent = formatTime( duration );
};

const toggleFullscreen = ( fsTarget ) => {
	if ( document.fullscreenElement ) {
		( document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen )?.call( document );
		return;
	}

	const request = fsTarget.requestFullscreen || fsTarget.webkitRequestFullscreen || fsTarget.mozRequestFullScreen || fsTarget.msRequestFullscreen;
	const fallbackToVideo = () => ( fsTarget.tagName === 'VIDEO' ? fsTarget : fsTarget.querySelector( 'video' ) )?.webkitEnterFullscreen?.();

	if ( request ) {
		const result = request.call( fsTarget );
		if ( result?.catch ) result.catch( fallbackToVideo );
		return;
	}

	fallbackToVideo();
};

// Swap the poster <img> for the auto-fetched thumbnail, but ONLY while it's
// still showing Elementor's own placeholder — a user-picked image on the
// native Image child must never be overridden.
const applyAutoPoster = ( el, cfg ) => {
	if ( ! cfg.poster ) return;
	// Not `:scope >` — the editor wraps a materialized child widget in its
	// own container for drag/selection purposes, so the poster <img> is a
	// descendant here even though the frontend's server-rendered HTML (which
	// has no such wrapper) makes it a direct child. See installDelegatedClicks().
	const img = el.querySelector( '.aae-a-video-poster' );
	if ( ! img ) return;
	if ( cfg.placeholder && img.getAttribute( 'src' ) !== cfg.placeholder ) return;
	img.src = cfg.poster;
};

// --- Wiring ---

// The video engine (adapter factory + its play/pause/ended/timeupdate wiring)
// is created ONCE per mount and stashed ON the mount element itself, so a
// later, independent call (the editor poll tick below) can retrieve the SAME
// controller-binding closure instead of losing it when initVideo() re-runs.
const getOrCreateEngine = ( el, mountEl, cfg ) => {
	if ( mountEl.__aaeVideoEngine ) return mountEl.__aaeVideoEngine;

	let controller = null;
	const bindController = () => {
		if ( controller ) return controller;
		const factory = ADAPTERS[ cfg.type ];
		if ( ! factory ) return null;

		controller = factory( mountEl, cfg );
		controller.on( 'play', () => setPlayingState( el, true ) );
		controller.on( 'pause', () => setPlayingState( el, false ) );
		controller.on( 'ended', () => setPlayingState( el, false ) );
		controller.on( 'timeupdate', () => updateProgress( mountEl, controller ) );

		return controller;
	};

	const engine = {
		bindController,
		requestPlay: () => bindController()?.play(),
		getController: () => controller,
	};
	mountEl.__aaeVideoEngine = engine;

	return engine;
};

// Poster, play button, click-zone, and the controls-bar's playpause/mute/
// fullscreen buttons all dispatch through ONE delegated, CAPTURE-phase
// listener bound once per document (frontend document AND the editor
// preview iframe's document — same dual-target Accordion's own
// installDelegatedToggle() uses, for the same reason).
//
// Why capture, why delegated, why on `document`: confirmed live (here and
// for Accordion's title/icon child widgets — see accordion.js's
// installDelegatedToggle() docblock) that in the Elementor editor, a click
// landing anywhere inside a widget's DOM subtree is intercepted by
// Elementor's OWN per-widget "select on click" handler, which calls
// stopPropagation() somewhere in the bubble chain. A plain, non-delegated,
// bubble-phase listener bound directly to the poster/button/controls (this
// file's previous approach) never even got a chance to fire — the poster
// click always resolved to "select the Image widget", never to playback.
// Capture always runs top-down, document first, BEFORE any bubble-phase
// listener anywhere in the tree fires and BEFORE Elementor's own
// interception can stop the event — so a listener on `document` itself
// always wins the race regardless of which nested wrapper Elementor's own
// handler sits on.
//
// `preventDefault()` only, never `stopPropagation()`: letting the click keep
// bubbling to Elementor's own handler afterwards is what keeps the poster/
// button still individually selectable/editable in the builder (same
// trade-off Accordion's own delegated handler documents).
//
// Resolved fresh from `e.target` on every click (not a cached reference) —
// this is also what makes rebinding-per-tick unnecessary even though
// Elementor can re-render the native Image/Button children after drop.
const TRIGGER_SELECTOR = '.aae-a-video-poster, .aae-a-video-playbtn, .aae-a-video-clickzone';

const resolveVideoContext = ( target ) => {
	const el = target.closest( '.aae-a-video' );
	if ( ! el ) return null;
	const mountEl = el.querySelector( '.aae-a-video-mount' );
	if ( ! mountEl ) return null;
	return { el, mountEl, engine: getOrCreateEngine( el, mountEl, readConfig( el ) ) };
};

const installDelegatedClicks = ( doc ) => {
	if ( ! doc || doc.__aaeVideoDelegated ) return;
	doc.__aaeVideoDelegated = true;

	doc.addEventListener( 'click', ( e ) => {
		if ( ! e.target.closest ) return;

		const trigger = e.target.closest( TRIGGER_SELECTOR );
		if ( trigger ) {
			const ctx = resolveVideoContext( trigger );
			if ( ! ctx ) return;
			e.preventDefault(); // the native button may render as <a> if a Link is set
			ctx.engine.bindController()?.togglePlay();
			return;
		}

		const playPauseBtn = e.target.closest( '.aae-a-video-btn--playpause' );
		if ( playPauseBtn ) {
			resolveVideoContext( playPauseBtn )?.engine.bindController()?.togglePlay();
			return;
		}

		const muteBtn = e.target.closest( '.aae-a-video-btn--mute' );
		if ( muteBtn ) {
			const ctx = resolveVideoContext( muteBtn );
			const controller = ctx?.engine.bindController();
			if ( ! controller ) return;
			const nextMuted = ! controller.isMuted();
			nextMuted ? controller.mute() : controller.unmute();
			setMutedState( ctx.el, nextMuted );
			return;
		}

		const fsBtn = e.target.closest( '.aae-a-video-btn--fullscreen' );
		if ( fsBtn ) {
			const ctx = resolveVideoContext( fsBtn );
			if ( ! ctx ) return;
			toggleFullscreen( ctx.engine.getController()?.getFullscreenTarget?.() || ctx.mountEl );
		}
	}, true ); // capture phase
};

// Everything else (controls-bar wiring, autoplay) lives on the mount's own
// stable, twig-rendered markup — confirmed live to never get swapped out
// independently the way poster/button can — so it's safe to bind once,
// guarded by the mount's own flag.
//
// Deliberately NOT passing Elementor's register()-callback `signal` to any
// addEventListener call here (or in installDelegatedClicks()) — confirmed live that
// the editor can abort it independently of these DOM nodes actually being
// removed (per spec, an aborted signal silently detaches any listener
// registered with it), which was the real cause of a widget that bound
// correctly on drop going permanently unresponsive moments later. Our own
// per-element/per-mount flags already make (re)binding idempotent, so
// nothing here depends on the signal for correctness — the trade-off is
// that a listener on a truly-removed node is only cleaned up by garbage
// collection instead of an explicit abort, which is harmless at this scale.
const bindEngineOnce = ( el, mountEl, engine, cfg, signal ) => {
	if ( mountEl.dataset.aaeVideoBound ) return;
	mountEl.dataset.aaeVideoBound = 'true';

	applyAutoPoster( el, cfg );

	const controlsBar = mountEl.querySelector( '.aae-a-video-controls' );
	if ( controlsBar ) {
		if ( ! cfg.controlsEnabled ) {
			controlsBar.remove();
		} else {
			// playpause/mute/fullscreen clicks are handled by the single
			// delegated document-capture listener — see installDelegatedClicks().
			const progressTrack = controlsBar.querySelector( '.aae-a-video-progress' );
			if ( progressTrack ) {
				const seekFromEvent = ( evt ) => {
					const controller = engine.getController();
					if ( ! controller ) return;
					const rect = progressTrack.getBoundingClientRect();
					const pct = Math.min( 1, Math.max( 0, ( evt.clientX - rect.left ) / rect.width ) );
					const duration = controller.getDuration();
					if ( duration > 0 ) controller.seekTo( pct * duration );
				};
				let dragging = false;
				progressTrack.addEventListener( 'pointerdown', ( e ) => { dragging = true; seekFromEvent( e ); } );
				progressTrack.addEventListener( 'pointermove', ( e ) => { if ( dragging ) seekFromEvent( e ); } );
				window.addEventListener( 'pointerup', () => { dragging = false; } );
			}

			if ( controlsBar.querySelector( '.aae-a-video-btn--mute' ) ) {
				setMutedState( el, cfg.mute || cfg.autoplay );
			}

			// Auto-hide the controls bar while playing; :hover/:focus-within in
			// CSS provide an always-available reveal path independent of this timer.
			if ( cfg.autohide ) {
				let hideTimer = null;
				const clearHide = () => { if ( hideTimer ) clearTimeout( hideTimer ); hideTimer = null; };
				const scheduleHide = () => {
					clearHide();
					hideTimer = setTimeout( () => {
						if ( el.classList.contains( 'is-playing' ) ) el.classList.add( 'is-controls-hidden' );
					}, 2500 );
				};
				const onActivity = () => {
					el.classList.remove( 'is-controls-hidden' );
					if ( el.classList.contains( 'is-playing' ) ) scheduleHide();
				};
				[ 'pointermove', 'touchstart', 'keydown' ].forEach( ( evt ) => el.addEventListener( evt, onActivity ) );
			}
		}
	}

	// Autoplay: force-mute regardless of the Mute setting (browsers block
	// audible autoplay), and skip it entirely in the editor so builders
	// don't get sound while designing the page.
	if ( cfg.autoplay && ! isEditMode() ) {
		if ( cfg.lazyload && 'hosted' !== cfg.type ) {
			const observer = new IntersectionObserver( ( entries ) => {
				if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
					engine.requestPlay();
					observer.disconnect();
				}
			} );
			observer.observe( el );
			if ( signal ) signal.addEventListener( 'abort', () => observer.disconnect() );
		} else {
			engine.requestPlay();
		}
	}
};

const initVideo = ( el, signal ) => {
	const mountEl = el.querySelector( '.aae-a-video-mount' );
	if ( ! mountEl ) return;

	const cfg = readConfig( el );
	const engine = getOrCreateEngine( el, mountEl, cfg );

	installDelegatedClicks( el.ownerDocument );
	bindEngineOnce( el, mountEl, engine, cfg, signal );
};

register( {
	elementType: 'e-aae-a-video',
	id: 'aae-a-video-handler',
	callback: ( { element, signal } ) => {
		const container = element.classList.contains( 'aae-a-video' ) ? element : element.querySelector( '.aae-a-video' );
		if ( container ) initVideo( container, signal );
	},
} );

// Editor-only polling fallback (same pattern as form.js's multi-step
// re-render fix — see CLAUDE.md). register()'s callback only fires once,
// right after a fresh drop; it is never re-invoked when Elementor repaints
// this element's native children later (a settings change, or — confirmed
// live — even shortly after the very first drop, with no setting touched at
// all), which is exactly what leaves a freshly-added video unplayable in the
// editor while the very same instance works fine on the frontend (which
// never repaints after load). initVideo() is cheap: installDelegatedClicks()
// no-ops after its first call per document, and bindEngineOnce() no-ops once
// the mount's own stable markup has been wired.
if ( isEditMode() ) {
	setInterval( () => {
		document.querySelectorAll( '.aae-a-video' ).forEach( ( el ) => initVideo( el, null ) );
	}, 1000 );
}
