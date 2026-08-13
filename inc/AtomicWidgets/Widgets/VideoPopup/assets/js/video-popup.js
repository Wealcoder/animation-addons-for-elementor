import { register } from '@elementor/frontend-handlers';

/* AAE Video Popup — frontend runtime.
 *
 * Self-contained: this file does NOT import or share code with the AAE
 * Video widget's own video.js. The video engine below (adapters, controls
 * wiring, delegated clicks) is a deliberate, renamed copy — every hook
 * class here is `aae-a-video-popup-*` / `aae-video-popup-*`, never the
 * plain `aae-a-video-*` names AAE Video's video.js already claims via its
 * OWN document-level delegated click listener. Reusing those names would
 * let two independent scripts fight over the same clicks on any page that
 * has both widgets.
 *
 * Two concerns live in this one file:
 *   1. The video engine (adapters + controls bar), bound to the Panel
 *      (`.aae-a-video-popup-source`) — functionally identical to AAE
 *      Video's engine, just under renamed selectors.
 *   2. Popup mechanics: teleporting the Overlay + Panel to a body-level
 *      portal, open/close CSS animation, close-on-overlay/Esc, scroll lock,
 *      and the editor-preview toggle — mirrors AAE Offcanvas's own
 *      offcanvas.js (see that file's comments for the full teleport
 *      reasoning), but CSS-only (no GSAP) to match this widget having no
 *      GSAP dependency at all.
 */

// ───────────────────────── Video engine (renamed copy) ─────────────────────

const YT_ID_REGEX = /^(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?vi?=|(?:embed|v|vi|user|shorts)\/))([^?&"'>]+)/;
const VIMEO_ID_REGEX = /vimeo\.com\/(?:.*\/)?(?:videos\/)?(\d+)/;
const DAILYMOTION_ID_REGEX = /(?:dailymotion\.com\/(?:embed\/)?video\/|dai\.ly\/)([a-zA-Z0-9]+)/;
const VIDEOPRESS_GUID_REGEX = /videopress\.com\/(?:v|embed)\/([a-zA-Z0-9]+)/;

// Kept in sync with AAE_A_Video_Popup_Panel::extract_youtube_id() (PHP).
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

const readVideoConfig = ( el ) => ( {
	type: el.getAttribute( 'data-aae-video-type' ) || 'youtube',
	youtubeUrl: el.getAttribute( 'data-aae-video-youtube-url' ) || '',
	hostedUrl: el.getAttribute( 'data-aae-video-hosted-url' ) || '',
	externalUrl: el.getAttribute( 'data-aae-video-external-url' ) || '',
	vimeoUrl: el.getAttribute( 'data-aae-video-vimeo-url' ) || '',
	dailymotionUrl: el.getAttribute( 'data-aae-video-dailymotion-url' ) || '',
	videopressUrl: el.getAttribute( 'data-aae-video-videopress-url' ) || '',
	autoplay: el.getAttribute( 'data-aae-video-autoplay' ) === 'true',
	mute: el.getAttribute( 'data-aae-video-mute' ) === 'true',
	loop: el.getAttribute( 'data-aae-video-loop' ) === 'true',
	preload: el.getAttribute( 'data-aae-video-preload' ) || 'metadata',
	youtubePrivacy: el.getAttribute( 'data-aae-video-youtube-privacy' ) === 'true',
	vimeoDnt: el.getAttribute( 'data-aae-video-vimeo-dnt' ) === 'true',
	controlsEnabled: el.getAttribute( 'data-aae-video-controls-enabled' ) === 'true',
	autohide: el.getAttribute( 'data-aae-video-controls-autohide' ) === 'true',
} );

const isEditMode = () => !! window.elementorFrontend?.isEditMode?.();

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

// Shared by 'hosted' and 'external' — both are a real <video> tag, only the
// source attribute differs (see AAE Video's video.js for the same split).
const createNativeAdapter = ( mountEl, cfg ) => {
	const videoEl = document.createElement( 'video' );
	videoEl.className = 'aae-a-video-popup-native';
	videoEl.preload = cfg.preload;
	videoEl.loop = cfg.loop;
	videoEl.playsInline = true;
	videoEl.muted = cfg.mute || cfg.autoplay;
	videoEl.src = 'external' === cfg.type ? cfg.externalUrl : cfg.hostedUrl;
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
	holder.className = 'aae-a-video-popup-embed';
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
				modestbranding: 1,
				iv_load_policy: 3,
				disablekb: 1,
				fs: 0,
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
	holder.className = 'aae-a-video-popup-embed';
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

const createPostMessageAdapter = ( mountEl, cfg, provider ) => {
	const iframe = document.createElement( 'iframe' );
	iframe.className = 'aae-a-video-popup-embed';
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

	const setPlaying = ( playing ) => {
		if ( isPlaying === playing ) return;
		isPlaying = playing;
		emit( playing ? 'play' : 'pause' );
	};

	const onMessage = ( e ) => {
		if ( e.source !== iframe.contentWindow ) return;
		const parsed = provider.decode( e.data );
		if ( ! parsed ) return;

		if ( 'timeupdate' === parsed.type ) {
			if ( 'number' === typeof parsed.currentTime ) currentTime = parsed.currentTime;
			if ( 'number' === typeof parsed.duration ) duration = parsed.duration;
			emit( 'timeupdate' );
		} else if ( 'play' === parsed.type ) {
			setPlaying( true );
		} else if ( 'pause' === parsed.type ) {
			setPlaying( false );
		} else if ( 'ended' === parsed.type ) {
			isPlaying = false;
			emit( 'ended' );
		}
	};
	window.addEventListener( 'message', onMessage );

	return {
		play: () => { send( { action: 'play' } ); setPlaying( true ); },
		pause: () => { send( { action: 'pause' } ); setPlaying( false ); },
		togglePlay: () => {
			const next = ! isPlaying;
			send( { action: next ? 'play' : 'pause' } );
			setPlaying( next );
		},
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
			video: id,
			api: 'postMessage',
			autoplay: cfg.autoplay ? '1' : '0',
			mute: muted ? '1' : '0',
			loop: cfg.loop ? '1' : '0',
			controls: '0',
			'queue-enable': '0',
		} );
		return `https://geo.dailymotion.com/player.html?${ params.toString() }`;
	},
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
	external: createNativeAdapter,
	youtube: createYoutubeAdapter,
	vimeo: createVimeoAdapter,
	dailymotion: createDailymotionAdapter,
	videopress: createVideoPressAdapter,
};

const formatTime = ( seconds ) => {
	if ( ! isFinite( seconds ) || seconds < 0 ) seconds = 0;
	const m = Math.floor( seconds / 60 );
	const s = Math.floor( seconds % 60 );
	return `${ m }:${ String( s ).padStart( 2, '0' ) }`;
};

const setPlayingState = ( el, playing ) => {
	el.classList.toggle( 'is-playing', playing );
	el.querySelectorAll( '.aae-a-video-popup-icon-play' ).forEach( ( icon ) => { icon.hidden = playing; } );
	el.querySelectorAll( '.aae-a-video-popup-icon-pause' ).forEach( ( icon ) => { icon.hidden = ! playing; } );
};

const setMutedState = ( el, muted ) => {
	el.querySelectorAll( '.aae-a-video-popup-icon-unmuted' ).forEach( ( icon ) => { icon.hidden = muted; } );
	el.querySelectorAll( '.aae-a-video-popup-icon-muted' ).forEach( ( icon ) => { icon.hidden = ! muted; } );
};

const updateProgress = ( mountEl, controller ) => {
	const duration = controller.getDuration();
	const current = controller.getCurrentTime();
	const pct = duration > 0 ? Math.min( 100, ( current / duration ) * 100 ) : 0;

	const fill = mountEl.querySelector( '.aae-a-video-popup-progress-fill' );
	const track = mountEl.querySelector( '.aae-a-video-popup-progress' );
	const curEl = mountEl.querySelector( '.aae-a-video-popup-time-cur' );
	const durEl = mountEl.querySelector( '.aae-a-video-popup-time-dur' );

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

const getOrCreateEngine = ( el, mountEl, cfg ) => {
	if ( mountEl.__aaeVideoPopupEngine ) return mountEl.__aaeVideoPopupEngine;

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
	mountEl.__aaeVideoPopupEngine = engine;

	return engine;
};

const TRIGGER_SELECTOR = '.aae-a-video-popup-poster, .aae-a-video-popup-playbtn, .aae-a-video-popup-clickzone';

const resolveVideoContext = ( target ) => {
	const el = target.closest( '.aae-a-video-popup-source' );
	if ( ! el ) return null;
	const mountEl = el.querySelector( '.aae-a-video-popup-mount' );
	if ( ! mountEl ) return null;
	return { el, mountEl, engine: getOrCreateEngine( el, mountEl, readVideoConfig( el ) ) };
};

const installDelegatedClicks = ( doc ) => {
	if ( ! doc || doc.__aaeVideoPopupDelegated ) return;
	doc.__aaeVideoPopupDelegated = true;

	doc.addEventListener( 'click', ( e ) => {
		if ( ! e.target.closest ) return;

		const trigger = e.target.closest( TRIGGER_SELECTOR );
		if ( trigger ) {
			const ctx = resolveVideoContext( trigger );
			if ( ! ctx ) return;
			e.preventDefault();
			ctx.engine.bindController()?.togglePlay();
			return;
		}

		const playPauseBtn = e.target.closest( '.aae-a-video-popup-btn--playpause' );
		if ( playPauseBtn ) {
			resolveVideoContext( playPauseBtn )?.engine.bindController()?.togglePlay();
			return;
		}

		const muteBtn = e.target.closest( '.aae-a-video-popup-btn--mute' );
		if ( muteBtn ) {
			const ctx = resolveVideoContext( muteBtn );
			const controller = ctx?.engine.bindController();
			if ( ! controller ) return;
			const nextMuted = ! controller.isMuted();
			nextMuted ? controller.mute() : controller.unmute();
			setMutedState( ctx.el, nextMuted );
			return;
		}

		const fsBtn = e.target.closest( '.aae-a-video-popup-btn--fullscreen' );
		if ( fsBtn ) {
			const ctx = resolveVideoContext( fsBtn );
			if ( ! ctx ) return;
			toggleFullscreen( ctx.engine.getController()?.getFullscreenTarget?.() || ctx.mountEl );
		}
	}, true ); // capture phase — see AAE Video's video.js for why capture+delegated+document is required in the editor.
};

const bindEngineOnce = ( el, mountEl, engine, cfg ) => {
	if ( mountEl.dataset.aaeVideoPopupBound ) return;
	mountEl.dataset.aaeVideoPopupBound = 'true';

	const controlsBar = mountEl.querySelector( '.aae-a-video-popup-controls' );
	if ( controlsBar ) {
		if ( ! cfg.controlsEnabled ) {
			controlsBar.remove();
		} else {
			const progressTrack = controlsBar.querySelector( '.aae-a-video-popup-progress' );
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

			if ( controlsBar.querySelector( '.aae-a-video-popup-btn--mute' ) ) {
				setMutedState( el, cfg.mute || cfg.autoplay );
			}

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
};

// The 'external' source's poster falls back to the video file's own first
// frame (see aae-a-video-popup-panel.html.twig's `use_frame_poster`) — same
// nudge-past-0 fix AAE Video's own video.js uses for the identical case.
const primeFramePoster = ( videoEl ) => {
	if ( videoEl.dataset.aaeFramePrimed ) return;
	videoEl.dataset.aaeFramePrimed = 'true';

	videoEl.addEventListener( 'loadeddata', () => {
		if ( videoEl.currentTime === 0 ) {
			try { videoEl.currentTime = 0.1; } catch ( e ) {}
		}
	}, { once: true } );
};

const initVideoInPanel = ( panel ) => {
	const mountEl = panel.querySelector( '.aae-a-video-popup-mount' );
	if ( ! mountEl ) return;

	const framePoster = panel.querySelector( '.aae-a-video-popup-poster-frame' );
	if ( framePoster ) primeFramePoster( framePoster );

	const cfg = readVideoConfig( panel );
	const engine = getOrCreateEngine( panel, mountEl, cfg );

	installDelegatedClicks( panel.ownerDocument );
	bindEngineOnce( panel, mountEl, engine, cfg );

	return { panel, mountEl, engine, cfg };
};

// ───────────────────────── Popup mechanics ─────────────────────────────────

// Resting → visible transform per animation preset. `null` = plain fade
// only. `ease` defaults to the CSS keyword `ease` when omitted; the
// bouncier presets (zoom-in/rotate-in/elastic-bounce) use an overshooting
// cubic-bezier instead — a SINGLE opacity/transform transition can already
// overshoot past 100% and settle back with the right curve, so there is no
// need for a multi-stage `@keyframes` (or GSAP) to get a back.out/
// elastic.out-style bounce. `filter` (blur-in only) rides its own
// transition entry, added conditionally in open()/close() below.
const ANIM_FROM = {
	fade: null,
	'scale-reveal': { transform: 'scale(0.85)' },
	'slide-up': { transform: 'translateY(40px)' },
	'zoom-in': { transform: 'scale(0.3)', ease: 'cubic-bezier(0.34, 1.56, 0.64, 1)' },
	// The `perspective(...)` FUNCTION is what gives this depth, applied
	// inline in the panel's own transform value — NOT the `perspective`
	// CSS *property* on an ancestor (that was tried and reverted: per the
	// CSS spec, `perspective` on an ancestor makes it a containing block
	// for `position: fixed` descendants, which broke the whole teleport-
	// to-viewport mechanism this widget depends on — the panel rendered
	// against the near-empty portal instead of the viewport, and the
	// overlay's `inset: 0` collapsed to the same near-nothing box).
	'flip-3d': { transform: 'perspective(1000px) rotateY(90deg)', ease: 'cubic-bezier(0.22, 1, 0.36, 1)' },
	// The reference this was modeled on doesn't actually split into two
	// panels either — same single-box scaleX(0→1) from center.
	'curtain-split': { transform: 'scaleX(0)', ease: 'cubic-bezier(0.83, 0, 0.17, 1)' },
	'blur-in': { transform: 'scale(1.1)', filter: 'blur(30px)', ease: 'cubic-bezier(0.22, 1, 0.36, 1)' },
	'rotate-in': { transform: 'rotate(-180deg) scale(0.3)', ease: 'cubic-bezier(0.34, 1.56, 0.64, 1)' },
	'elastic-bounce': { transform: 'scale(0)', ease: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)' },
	none: null,
};

const transitionFor = ( from, duration ) => {
	const ease = from.ease || 'ease';
	const props = [ `opacity ${ duration }s ${ ease }`, `transform ${ duration }s ${ ease }` ];
	if ( from.filter ) props.push( `filter ${ duration }s ${ ease }` );
	return props.join( ', ' );
};

const reduceMotion = () => !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

/**
 * Shared teleport host: one bare `.elementor` wrapper appended to <body>.
 * Same reasoning as AAE Offcanvas's getPortal(): the Panel must escape
 * ancestor container transforms for `position:fixed` to mean "the
 * viewport", but moving it straight onto <body> would drop every atomic
 * style rule (all scoped `.elementor .e-xxx`) — so it's parked inside a
 * transform-free `.elementor` host instead.
 */
const getPortal = () => {
	let portal = document.querySelector( 'body > .aae-video-popup-portal' );
	if ( ! portal ) {
		portal = document.createElement( 'div' );
		portal.className = 'elementor aae-video-popup-portal';
		document.body.appendChild( portal );
	}
	return portal;
};

const FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

let bodyLockCount = 0;
let savedBodyPadR = '';
const lockScroll = () => {
	if ( bodyLockCount === 0 ) {
		const scrollbar = window.innerWidth - document.documentElement.clientWidth;
		savedBodyPadR = document.body.style.paddingRight;
		if ( scrollbar > 0 ) {
			const current = parseFloat( getComputedStyle( document.body ).paddingRight ) || 0;
			document.body.style.paddingRight = ( current + scrollbar ) + 'px';
		}
		document.body.style.overflow = 'hidden';
	}
	bodyLockCount += 1;
};
const unlockScroll = () => {
	bodyLockCount = Math.max( 0, bodyLockCount - 1 );
	if ( bodyLockCount === 0 ) {
		document.body.style.overflow = '';
		document.body.style.paddingRight = savedBodyPadR;
	}
};

const initVideoPopup = ( root ) => {
	if ( root.dataset.aaeVideoPopupInit === 'true' ) return;
	root.dataset.aaeVideoPopupInit = 'true';

	const trigger = root.querySelector( '.aae-video-popup-trigger' )
		|| root.querySelector( '[data-e-type="e-aae-a-video-popup-trigger"]' );
	const overlay = root.querySelector( '.aae-video-popup-overlay' )
		|| root.querySelector( '[data-e-type="e-aae-a-video-popup-overlay"]' );
	const panel = root.querySelector( '.aae-a-video-popup-panel' )
		|| root.querySelector( '[data-element_type="e-aae-a-video-popup-panel"]' );

	if ( ! trigger || ! panel ) return;

	// Prime the frame-fallback poster (if any) right away rather than
	// waiting for the panel's first open — `preload="metadata"` already
	// fetches regardless of the panel's hidden/teleported state, so there's
	// no reason to leave it black until the visitor's first click.
	const framePosterEl = panel.querySelector( '.aae-a-video-popup-poster-frame' );
	if ( framePosterEl ) primeFramePoster( framePosterEl );

	// EDITOR: never teleport or drive the popup here — see initVideoPopupEditor().
	if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) return;

	const closeBtn = panel.querySelector( '.aae-video-popup-close' )
		|| panel.querySelector( '[data-e-type="e-aae-a-video-popup-close"]' );

	const closeOnOverlay = root.dataset.closeOnOverlay !== 'false';
	const closeOnEsc = root.dataset.closeOnEsc !== 'false';
	const animName = root.dataset.popupAnim || 'scale-reveal';
	const duration = ( parseInt( root.dataset.animDuration, 10 ) || 400 ) / 1000;

	// Teleport into a transform-free `.elementor` host so fixed positioning
	// uses the viewport while the Panel keeps its `.elementor`-scoped atomic
	// styles (width/height/background/base).
	const portal = getPortal();
	portal.appendChild( panel );
	if ( overlay ) portal.appendChild( overlay );

	Object.assign( panel.style, {
		position: 'fixed',
		top: '50%',
		left: '50%',
		zIndex: '9999',
		visibility: 'hidden',
		pointerEvents: 'none',
		transform: 'translate(-50%, -50%)',
		transition: 'none',
	} );

	if ( overlay ) {
		Object.assign( overlay.style, {
			position: 'fixed',
			inset: '0',
			zIndex: '9998',
			opacity: '0',
			visibility: 'hidden',
			pointerEvents: 'none',
			transition: 'none',
		} );
	}

	let closeTimer;
	let previouslyFocused = null;
	panel.setAttribute( 'tabindex', '-1' );

	const getFocusable = () =>
		Array.from( panel.querySelectorAll( FOCUSABLE ) ).filter(
			( el ) => el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement
		);

	const trapFocus = ( ev ) => {
		if ( ev.key !== 'Tab' ) return;
		const focusables = getFocusable();
		if ( ! focusables.length ) {
			ev.preventDefault();
			panel.focus();
			return;
		}
		const first = focusables[ 0 ];
		const last = focusables[ focusables.length - 1 ];
		if ( ev.shiftKey && document.activeElement === first ) {
			ev.preventDefault();
			last.focus();
		} else if ( ! ev.shiftKey && document.activeElement === last ) {
			ev.preventDefault();
			first.focus();
		}
	};

	const pauseVideo = () => {
		const video = initVideoInPanel( panel );
		video?.engine.getController()?.pause();
	};

	const playIfAutoplay = () => {
		const cfg = readVideoConfig( panel );
		if ( cfg.autoplay ) {
			const video = initVideoInPanel( panel );
			video?.engine.requestPlay();
		} else {
			initVideoInPanel( panel );
		}
	};

	const open = () => {
		if ( root.classList.contains( 'is-open' ) ) return;
		previouslyFocused = document.activeElement;
		window.clearTimeout( closeTimer );
		panel.style.pointerEvents = 'auto';
		root.classList.add( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'true' );
		lockScroll();
		panel.addEventListener( 'keydown', trapFocus );

		if ( overlay ) {
			overlay.style.visibility = 'visible';
			overlay.style.pointerEvents = 'auto';
			overlay.style.transition = 'none';
			overlay.style.opacity = '0';
			requestAnimationFrame( () => {
				overlay.style.transition = `opacity ${ duration }s ease`;
				overlay.style.opacity = '1';
			} );
		}

		panel.style.visibility = 'visible';
		const from = reduceMotion() ? null : ANIM_FROM[ animName ];
		if ( from ) {
			panel.style.transition = 'none';
			panel.style.opacity = '0';
			panel.style.transform = `translate(-50%, -50%) ${ from.transform }`;
			if ( from.filter ) panel.style.filter = from.filter;
			requestAnimationFrame( () => {
				panel.style.transition = transitionFor( from, duration );
				panel.style.opacity = '1';
				panel.style.transform = 'translate(-50%, -50%)';
				if ( from.filter ) panel.style.filter = 'none';
			} );
		} else {
			panel.style.transition = animName === 'none' || reduceMotion() ? 'none' : `opacity ${ duration }s ease`;
			panel.style.opacity = '1';
		}

		const focusables = getFocusable();
		( focusables[ 0 ] || panel ).focus();

		playIfAutoplay();
	};

	const finishClose = () => {
		panel.style.visibility = 'hidden';
		if ( overlay ) overlay.style.visibility = 'hidden';
	};

	const close = () => {
		if ( ! root.classList.contains( 'is-open' ) ) return;
		root.classList.remove( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		unlockScroll();
		panel.style.pointerEvents = 'none';
		panel.removeEventListener( 'keydown', trapFocus );
		pauseVideo();

		const returnTo = ( previouslyFocused && typeof previouslyFocused.focus === 'function' )
			? previouslyFocused
			: trigger;
		returnTo.focus();
		previouslyFocused = null;

		if ( overlay ) {
			overlay.style.pointerEvents = 'none';
			overlay.style.transition = `opacity ${ duration }s ease`;
			overlay.style.opacity = '0';
		}

		const from = reduceMotion() ? null : ANIM_FROM[ animName ];
		if ( from ) {
			panel.style.transition = transitionFor( from, duration );
			panel.style.opacity = '0';
			panel.style.transform = `translate(-50%, -50%) ${ from.transform }`;
			if ( from.filter ) panel.style.filter = from.filter;
		} else if ( animName !== 'none' && ! reduceMotion() ) {
			panel.style.transition = `opacity ${ duration }s ease`;
			panel.style.opacity = '0';
		}

		closeTimer = window.setTimeout( finishClose, ( from || ( animName !== 'none' && ! reduceMotion() ) ) ? duration * 1000 + 50 : 0 );
	};

	trigger.addEventListener( 'click', open );
	if ( closeBtn ) closeBtn.addEventListener( 'click', close );
	if ( overlay && closeOnOverlay ) overlay.addEventListener( 'click', close );
	if ( closeOnEsc ) {
		document.addEventListener( 'keydown', ( ev ) => {
			if ( ev.key === 'Escape' && root.classList.contains( 'is-open' ) ) close();
		} );
	}
};

// ── Editor-only preview reveal (mirrors AAE Offcanvas's own reconciler) ────
const editorReconcilers = new Map();
// id -> true|false once the trigger/close/overlay has been clicked; wins
// over the polled "Open Popup (Editor)" setting until that setting itself
// actually changes (see reconcile() below) — clicking the trigger in the
// editor should behave like the real popup, not just the switch.
const editorOverrides = new Map();
const editorLastSetting = new Map();

const readEditorOpen = ( id ) => {
	try {
		const editorWindow = window.parent && window.parent !== window ? window.parent : window;
		const container = editorWindow.elementor?.getContainer?.( id );
		let value;
		if ( container?.settings?.get ) value = container.settings.get( 'editor_open' );
		if ( value === undefined && container?.model?.get ) {
			const settings = container.model.get( 'settings' );
			value = settings?.get ? settings.get( 'editor_open' ) : settings?.editor_open;
		}
		return ( value && typeof value === 'object' ) ? !! value.value : !! value;
	} catch ( error ) {
		return false;
	}
};

const EDITOR_PANEL_RESET = [ 'position', 'z-index', 'top', 'left', 'transform', 'visibility' ];

const initVideoPopupEditor = ( container ) => {
	const id = container.getAttribute( 'data-id' );
	if ( ! id ) return;

	const apply = ( open ) => {
		container.classList.toggle( 'is-open', open );

		const panel = container.querySelector( '.aae-a-video-popup-panel' );
		if ( panel ) {
			const isFloating = 'fixed' === panel.style.position;
			if ( open && ! isFloating ) {
				panel.style.removeProperty( 'display' );
				panel.style.setProperty( 'position', 'fixed', 'important' );
				panel.style.setProperty( 'top', '50%', 'important' );
				panel.style.setProperty( 'left', '50%', 'important' );
				panel.style.setProperty( 'transform', 'translate(-50%, -50%)', 'important' );
				panel.style.setProperty( 'z-index', '10000', 'important' );
				panel.style.setProperty( 'visibility', 'visible', 'important' );
				initVideoInPanel( panel );
			} else if ( ! open && ( isFloating || 'none' !== panel.style.display ) ) {
				EDITOR_PANEL_RESET.forEach( ( p ) => panel.style.removeProperty( p ) );
				panel.style.setProperty( 'display', 'none', 'important' );
			}
		}

		const overlay = container.querySelector( '[data-e-type="e-aae-a-video-popup-overlay"]' );
		if ( overlay ) {
			if ( open ) {
				[ [ 'position', 'fixed' ], [ 'inset', '0' ], [ 'z-index', '9999' ],
					[ 'opacity', '1' ], [ 'visibility', 'visible' ], [ 'pointer-events', 'auto' ] ]
					.forEach( ( [ k, v ] ) => overlay.style.setProperty( k, v, 'important' ) );
			} else {
				[ 'position', 'inset', 'z-index', 'opacity', 'visibility', 'pointer-events' ]
					.forEach( ( p ) => overlay.style.removeProperty( p ) );
			}
		}

		if ( '0px' !== container.style.minHeight ) {
			container.style.setProperty( 'min-height', '0', 'important' );
			container.style.setProperty( 'min-block-size', '0', 'important' );
		}
	};

	// Click-to-open/close, same as the real widget — just routed through
	// `apply()` above instead of initVideoPopup()'s teleport-to-body-portal
	// path (moving elements out of Elementor's own tracked DOM tree here
	// would fight its re-render cycle). `dataset.aaeVpEditorBound` stops a
	// later re-render's call to this function from stacking a second
	// listener onto the SAME still-attached node; a node Elementor actually
	// replaced on re-render has no such marker and gets bound fresh.
	const bindOnce = ( el, handler ) => {
		if ( ! el || el.dataset.aaeVpEditorBound === 'true' ) return;
		el.dataset.aaeVpEditorBound = 'true';
		el.addEventListener( 'click', handler );
	};

	const trigger = container.querySelector( '.aae-video-popup-trigger' )
		|| container.querySelector( '[data-e-type="e-aae-a-video-popup-trigger"]' );
	bindOnce( trigger, ( e ) => {
		e.preventDefault();
		editorOverrides.set( id, true );
		apply( true );
	} );

	const panelEl = container.querySelector( '.aae-a-video-popup-panel' );
	const closeBtn = panelEl && ( panelEl.querySelector( '.aae-video-popup-close' )
		|| panelEl.querySelector( '[data-e-type="e-aae-a-video-popup-close"]' ) );
	bindOnce( closeBtn, ( e ) => {
		e.preventDefault();
		editorOverrides.set( id, false );
		apply( false );
	} );

	const overlayEl = container.querySelector( '[data-e-type="e-aae-a-video-popup-overlay"]' );
	bindOnce( overlayEl, () => {
		editorOverrides.set( id, false );
		apply( false );
	} );

	const reconcile = () => {
		if ( ! document.body.contains( container ) ) {
			window.clearInterval( editorReconcilers.get( id ) );
			editorReconcilers.delete( id );
			editorOverrides.delete( id );
			editorLastSetting.delete( id );
			return;
		}

		const settingValue = readEditorOpen( id );
		// The ACTUAL "Open Popup (Editor)" switch changing always wins over
		// a stale click-override — otherwise closing via that switch after
		// a manual open click would silently do nothing.
		if ( editorLastSetting.has( id ) && editorLastSetting.get( id ) !== settingValue ) {
			editorOverrides.delete( id );
		}
		editorLastSetting.set( id, settingValue );

		apply( editorOverrides.has( id ) ? editorOverrides.get( id ) : settingValue );
	};

	if ( editorReconcilers.has( id ) ) window.clearInterval( editorReconcilers.get( id ) );
	reconcile();
	editorReconcilers.set( id, window.setInterval( reconcile, 250 ) );
};

register( {
	elementType: 'e-aae-a-video-popup',
	id: 'aae-a-video-popup-handler',
	callback: ( { element } ) => {
		if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
			initVideoPopupEditor( element );
		} else {
			initVideoPopup( element );
		}
	},
} );
