/**
 * AAE — Atomic Heading Animation, editor live preview bridge.
 *
 * Subscribes to v4 settings updates and rewrites a single <style> block in the
 * preview iframe head. The block survives React re-renders because it lives in
 * <head>, not in the widget DOM.
 *
 * Run `window.aaeDebug()` in console to dump diagnostic state.
 */
( function ( $ ) {
	'use strict';

	var TAG         = '[AAE]';
	var STYLE_ID    = 'aae-atomic-anim';
	var WIDGET_TYPE = 'e-heading';
	var PROP_KEY    = 'aae_animation';
	var ALLOWED     = [ 'fade-in', 'slide-up', 'scale-in' ];

	function getPreviewDoc() {
		var p = window.elementor && window.elementor.$preview && window.elementor.$preview[ 0 ];
		return p ? p.contentDocument : null;
	}

	function readValue( container ) {
		if ( ! container ) { return ''; }

		var v;
		if ( container.settings && typeof container.settings.get === 'function' ) {
			try { v = container.settings.get( PROP_KEY ); } catch ( e ) {}
		}
		if ( typeof v === 'undefined' && container.settings && container.settings.attributes ) {
			v = container.settings.attributes[ PROP_KEY ];
		}
		if ( typeof v === 'undefined' && container.model && typeof container.model.get === 'function' ) {
			try {
				var s = container.model.get( 'settings' );
				if ( s && typeof s.get === 'function' ) { v = s.get( PROP_KEY ); }
				else if ( s && typeof s === 'object' ) { v = s[ PROP_KEY ]; }
			} catch ( e ) {}
		}

		if ( v && typeof v === 'object' && 'value' in v ) {
			v = v.value;
		}
		return ( typeof v === 'string' ) ? v.trim() : '';
	}

	function isHeading( c ) {
		if ( ! c ) { return false; }
		var t;
		if ( c.model && typeof c.model.get === 'function' ) {
			t = c.model.get( 'widgetType' ) || c.model.get( 'elType' );
		}
		if ( ! t && c.type ) { t = c.type; }
		return t === WIDGET_TYPE;
	}

	function walk( c, fn ) {
		if ( ! c ) { return; }
		fn( c );
		if ( c.children && c.children.forEach ) {
			c.children.forEach( function ( x ) { walk( x, fn ); } );
		}
	}

	function rebuild() {
		var doc = getPreviewDoc();
		if ( ! doc || ! doc.head ) {
			console.log( TAG, 'rebuild aborted: no preview doc/head' );
			return;
		}

		var current = window.elementor && window.elementor.documents && window.elementor.documents.getCurrent();
		if ( ! current || ! current.container ) {
			console.log( TAG, 'rebuild aborted: no current document container' );
			return;
		}

		var rules    = [];
		var headings = 0;
		walk( current.container, function ( c ) {
			if ( ! isHeading( c ) ) { return; }
			headings++;
			var v = readValue( c );
			if ( ALLOWED.indexOf( v ) !== -1 ) {
				rules.push( '[data-interaction-id="' + c.id + '"]{animation:aae-' + v + ' 0.6s ease-out both;}' );
			}
		} );

		var styleEl = doc.getElementById( STYLE_ID );
		if ( ! styleEl ) {
			styleEl = doc.createElement( 'style' );
			styleEl.id = STYLE_ID;
			doc.head.appendChild( styleEl );
		}
		styleEl.textContent = rules.join( '' );

		console.log( TAG, 'rebuild done. headings=', headings, 'rules=', rules.length );
	}

	function bind() {
		if ( ! window.elementor ) {
			return setTimeout( bind, 250 );
		}
		console.log( TAG, 'bind ready. elementor=', !!window.elementor, '$e=', !!window.$e );

		if ( typeof window.elementor.on === 'function' ) {
			window.elementor.on( 'preview:loaded', function () {
				console.log( TAG, 'event preview:loaded' );
				setTimeout( rebuild, 200 );
			} );
		}

		if ( window.elementor.channels && window.elementor.channels.editor ) {
			window.elementor.channels.editor.on( 'change', function () {
				console.log( TAG, 'event channels.editor change' );
				rebuild();
			} );
		}

		if ( window.$e && window.$e.commands && typeof window.$e.commands.on === 'function' ) {
			window.$e.commands.on( 'run:after', function ( _component, command ) {
				if ( command && command.indexOf( 'document/elements/settings' ) !== -1 ) {
					console.log( TAG, 'event $e cmd', command );
					rebuild();
				}
			} );
		}

		setTimeout( rebuild, 1000 );

		var observerInstalled = false;
		function installObserver() {
			if ( observerInstalled ) { return; }
			var doc = getPreviewDoc();
			if ( ! doc || ! doc.body || typeof MutationObserver === 'undefined' ) {
				return setTimeout( installObserver, 500 );
			}
			var debounce;
			var mo = new MutationObserver( function () {
				clearTimeout( debounce );
				debounce = setTimeout( function () {
					console.log( TAG, 'event MutationObserver' );
					rebuild();
				}, 50 );
			} );
			mo.observe( doc.body, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'data-interaction-id', 'class' ] } );
			observerInstalled = true;
			console.log( TAG, 'MutationObserver installed on preview body' );
		}
		setTimeout( installObserver, 500 );
		if ( typeof window.elementor.on === 'function' ) {
			window.elementor.on( 'preview:loaded', installObserver );
		}

		console.log( TAG, 'subscribed. Type aaeDebug() to inspect state.' );
	}

	window.aaeDebug = function () {
		var out = {
			elementor:     !! window.elementor,
			$e_commands:   !! ( window.$e && window.$e.commands ),
			channels:      !! ( window.elementor && window.elementor.channels && window.elementor.channels.editor ),
			previewDoc:    !! getPreviewDoc(),
			document:      null,
			rootContainer: null,
			headings:      [],
		};

		var current = window.elementor && window.elementor.documents && window.elementor.documents.getCurrent();
		out.document = current ? current.id : null;
		out.rootContainer = ( current && current.container ) ? 'present' : null;

		if ( current && current.container ) {
			walk( current.container, function ( c ) {
				if ( ! isHeading( c ) ) { return; }
				out.headings.push( {
					id:    c.id,
					value: readValue( c ),
					settingsType: c.settings ? typeof c.settings : 'none',
					hasGet: !! ( c.settings && typeof c.settings.get === 'function' ),
				} );
			} );
		}

		console.table( out.headings );
		console.log( out );
		console.log( 'Manual trigger: window.aaeRebuild()' );
		return out;
	};
	window.aaeRebuild = rebuild;

	$( bind );
} )( jQuery );
