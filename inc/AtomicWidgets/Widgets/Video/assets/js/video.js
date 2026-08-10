import { register } from '@elementor/frontend-handlers';

/* AAE Atomic Video.
 *
 * The wrapper renders four fixed native children (e-image poster, e-youtube,
 * e-self-hosted-video, e-button play trigger) plus, for Vimeo only, our own
 * mount div (no native Elementor widget exists for Vimeo). Which one is
 * visible is decided purely by CSS off data-aae-video-type — this file only
 * wires interaction for the two sources we can actually reach:
 *
 *   - hosted: the child IS a real <video data-e-type="e-self-hosted-video">
 *     tag, fully controllable via the standard HTML5 media API.
 *   - vimeo: our own mount + the Vimeo Player SDK, fully controllable.
 *
 * YouTube is deliberately left alone: Elementor's own e-youtube handler
 * keeps its YT.Player instance in a private closure (verified directly in
 * youtube-handler.js — never exposed on the DOM or window), so an external
 * controls bar cannot drive it. The native iframe already ships its own
 * thumbnail, play button and controls, editable on that child's own panel.
 */

const VIMEO_ID_REGEX = /vimeo\.com\/(?:.*\/)?(?:videos\/)?(\d+)/;
const getVimeoIdFromUrl = ( url ) => {
	const match = ( url || '' ).match( VIMEO_ID_REGEX );
	return match ? match[ 1 ] : null;
};

const readConfig = ( el ) => ( {
	type: el.getAttribute( 'data-aae-video-type' ) || 'youtube',
	vimeoUrl: el.getAttribute( 'data-aae-video-vimeo-url' ) || '',
	vimeoAutoplay: el.getAttribute( 'data-aae-video-vimeo-autoplay' ) === 'true',
	vimeoMute: el.getAttribute( 'data-aae-video-vimeo-mute' ) === 'true',
	vimeoLoop: el.getAttribute( 'data-aae-video-vimeo-loop' ) === 'true',
	vimeoDnt: el.getAttribute( 'data-aae-video-vimeo-dnt' ) === 'true',
	poster: el.getAttribute( 'data-aae-video-poster' ) || '',
	placeholder: el.getAttribute( 'data-aae-video-placeholder' ) || '',
	autohide: el.getAttribute( 'data-aae-video-controls-autohide' ) === 'true',
} );

const isEditMode = () => !! window.elementorFrontend?.isEditMode?.();

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

// --- Adapters — same shared interface regardless of backend. ---

const createNativeAdapter = ( videoEl ) => {
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
		on,
		destroy: () => Object.entries( handlers ).forEach( ( [ evt, fn ] ) => videoEl.removeEventListener( evt, fn ) ),
	};
};

const createVimeoAdapter = ( mountEl, cfg ) => {
	const listeners = {};
	const emit = ( evt ) => ( listeners[ evt ] || [] ).forEach( ( cb ) => cb() );
	const on = ( evt, cb ) => {
		( listeners[ evt ] = listeners[ evt ] || [] ).push( cb );
	};

	const videoId = getVimeoIdFromUrl( cfg.vimeoUrl );
	let player = null;
	let ready = false;
	let mutedState = cfg.vimeoMute || cfg.vimeoAutoplay;
	let currentTime = 0;
	let duration = 0;
	const pending = [];
	const whenReady = ( fn ) => ( ready ? fn() : pending.push( fn ) );

	loadVimeoApi().then( ( Vimeo ) => {
		player = new Vimeo.Player( mountEl, {
			id: videoId,
			autoplay: cfg.vimeoAutoplay,
			muted: mutedState,
			loop: cfg.vimeoLoop,
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
		on,
		destroy: () => player?.destroy?.(),
	};
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

const updateProgress = ( el, controller ) => {
	const duration = controller.getDuration();
	const current = controller.getCurrentTime();
	const pct = duration > 0 ? Math.min( 100, ( current / duration ) * 100 ) : 0;

	const fill = el.querySelector( '.aae-a-video-progress-fill' );
	const track = el.querySelector( '.aae-a-video-progress' );
	const curEl = el.querySelector( '.aae-a-video-time-cur' );
	const durEl = el.querySelector( '.aae-a-video-time-dur' );

	if ( fill ) fill.style.width = `${ pct }%`;
	if ( track ) track.setAttribute( 'aria-valuenow', String( Math.round( pct ) ) );
	if ( curEl ) curEl.textContent = formatTime( current );
	if ( durEl ) durEl.textContent = formatTime( duration );
};

const toggleFullscreen = ( el ) => {
	if ( document.fullscreenElement ) {
		( document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen )?.call( document );
		return;
	}

	const request = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
	const fallbackToVideo = () => el.querySelector( 'video' )?.webkitEnterFullscreen?.();

	if ( request ) {
		const result = request.call( el );
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
	const img = el.querySelector( ':scope > .aae-a-video-poster' );
	if ( ! img ) return;
	if ( cfg.placeholder && img.getAttribute( 'src' ) !== cfg.placeholder ) return;
	img.src = cfg.poster;
};

// --- Wiring ---

const pickAdapter = ( el, cfg ) => {
	if ( 'hosted' === cfg.type ) {
		const videoEl = el.querySelector( ':scope > [data-e-type="e-self-hosted-video"]' );
		return videoEl ? createNativeAdapter( videoEl ) : null;
	}

	if ( 'vimeo' === cfg.type ) {
		const mountEl = el.querySelector( ':scope > .aae-a-video-vimeo-frame' );
		return mountEl ? createVimeoAdapter( mountEl, cfg ) : null;
	}

	return null; // youtube — no external control, see class docblock.
};

const initVideo = ( el, signal ) => {
	const cfg = readConfig( el );
	const opts = signal ? { signal } : {};

	if ( 'youtube' === cfg.type ) return; // native child runs entirely on its own.

	applyAutoPoster( el, cfg );

	let controller = null;

	const bindController = () => {
		if ( controller ) return controller;
		controller = pickAdapter( el, cfg );
		if ( ! controller ) return null;

		controller.on( 'play', () => setPlayingState( el, true ) );
		controller.on( 'pause', () => setPlayingState( el, false ) );
		controller.on( 'ended', () => setPlayingState( el, false ) );
		controller.on( 'timeupdate', () => updateProgress( el, controller ) );

		return controller;
	};

	const playbtn = el.querySelector( ':scope > .aae-a-video-playbtn' );
	if ( playbtn ) {
		playbtn.addEventListener( 'click', ( e ) => {
			e.preventDefault(); // the native button may render as <a> if a Link is set
			bindController()?.togglePlay();
		}, opts );
	}

	const controlsBar = el.querySelector( ':scope > .aae-a-video-controls' );
	if ( controlsBar ) {
		controlsBar.querySelector( '.aae-a-video-btn--playpause' )?.addEventListener( 'click', () => {
			bindController()?.togglePlay();
		}, opts );

		const progressTrack = controlsBar.querySelector( '.aae-a-video-progress' );
		if ( progressTrack ) {
			const seekFromEvent = ( evt ) => {
				if ( ! controller ) return;
				const rect = progressTrack.getBoundingClientRect();
				const pct = Math.min( 1, Math.max( 0, ( evt.clientX - rect.left ) / rect.width ) );
				const duration = controller.getDuration();
				if ( duration > 0 ) controller.seekTo( pct * duration );
			};
			let dragging = false;
			progressTrack.addEventListener( 'pointerdown', ( e ) => { dragging = true; seekFromEvent( e ); }, opts );
			progressTrack.addEventListener( 'pointermove', ( e ) => { if ( dragging ) seekFromEvent( e ); }, opts );
			window.addEventListener( 'pointerup', () => { dragging = false; }, opts );
		}

		const muteBtn = controlsBar.querySelector( '.aae-a-video-btn--mute' );
		if ( muteBtn ) {
			muteBtn.addEventListener( 'click', () => {
				const c = bindController();
				if ( ! c ) return;
				const nextMuted = ! c.isMuted();
				nextMuted ? c.mute() : c.unmute();
				setMutedState( el, nextMuted );
			}, opts );
			setMutedState( el, cfg.vimeoMute || cfg.vimeoAutoplay );
		}

		controlsBar.querySelector( '.aae-a-video-btn--fullscreen' )?.addEventListener( 'click', () => toggleFullscreen( el ), opts );

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
			[ 'pointermove', 'touchstart', 'keydown' ].forEach( ( evt ) => el.addEventListener( evt, onActivity, opts ) );
		}
	}

	// Vimeo autoplay: skip in the editor so builders don't get sound while
	// designing the page, and only bind once the widget scrolls into view.
	if ( 'vimeo' === cfg.type && cfg.vimeoAutoplay && ! isEditMode() ) {
		const observer = new IntersectionObserver( ( entries ) => {
			if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
				bindController()?.play();
				observer.disconnect();
			}
		} );
		observer.observe( el );
		if ( signal ) signal.addEventListener( 'abort', () => observer.disconnect() );
	}
};

register( {
	elementType: 'e-aae-a-video',
	id: 'aae-a-video-handler',
	callback: ( { element, signal } ) => {
		const container = element.classList.contains( 'aae-a-video' ) ? element : element.querySelector( '.aae-a-video' );
		if ( container ) initVideo( container, signal );
	},
} );
