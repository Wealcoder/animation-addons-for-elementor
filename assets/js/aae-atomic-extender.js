/**
 * AAE — Atomic Heading Style, manual-apply via "▶ Re-render" button.
 *
 * Mirrors the PHP prop config (inc/aae-atomic-extender.php). Each entry binds
 * one v4 prop key to one CSS property. Dropdown change does NOT auto-apply —
 * user must click the "▶ Re-render" button injected into the AAE Style
 * section in the editor panel.
 */
( function attach() {
	var frame = window.elementor && window.elementor.$preview && window.elementor.$preview[ 0 ];
	if ( ! frame ) { return setTimeout( attach, 200 ); }

	var WIDGET        = 'e-heading';
	var STYLE_ID      = 'aae-atomic-style';
	var SECTION_LABEL = 'AAE Style';
	var BTN_CLASS     = 'aae-replay-btn';

	// Whitelisted prop → CSS property + allowed values map. Mirrors PHP.
	var STYLE_MAP = [
		{ prop: 'aae_color',      css: 'background-color', allowed: [ '#FF3B30', '#007AFF', '#34C759' ] },
		{ prop: 'aae_text_color', css: 'color',            allowed: [ '#FFFFFF', '#000000', '#FFD60A' ] },
		{ prop: 'aae_border',     css: 'border',           allowed: [ '1px solid #000000', '2px dashed #FF3B30', '3px dotted #007AFF' ] },
		{ prop: 'aae_radius',     css: 'border-radius',    allowed: [ '4px', '12px', '999px' ] },
	];

	function findContainer( root, id ) {
		if ( ! root ) { return null; }
		if ( root.id === id ) { return root; }
		if ( ! root.children || ! root.children.forEach ) { return null; }
		for ( var i = 0; i < root.children.length; i++ ) {
			var f = findContainer( root.children[ i ], id );
			if ( f ) { return f; }
		}
		return null;
	}

	function readSetting( container, propKey ) {
		if ( ! container || ! container.settings ) { return ''; }
		var v = container.settings.get ? container.settings.get( propKey )
			: ( container.settings.attributes && container.settings.attributes[ propKey ] );
		if ( v && typeof v === 'object' && 'value' in v ) { v = v.value; }
		console.log( container, propKey );
		return ( typeof v === 'string' ) ? v.trim() : '';
	}

	function buildRule( id ) {
		var doc = window.elementor.documents.getCurrent();
		if ( ! doc || ! doc.container ) { return null; }
		var found = findContainer( doc.container, id );
		console.log( 'found element',found );
		if ( ! found || ! found.model || found.model.get( 'widgetType' ) !== WIDGET ) { return null; }

		// Debug — log every prop value read from this heading container.
		var debug = { id: id };
		STYLE_MAP.forEach( function ( entry ) {
			debug[ entry.prop ] = readSetting( found, entry.prop ) || '(empty)';
		} );
		console.table( [ debug ] );

		var decls = [ 'padding:8px 16px', 'display:inline-block' ];
		var hasAny = false;
		STYLE_MAP.forEach( function ( entry ) {
			var v = readSetting( found, entry.prop );
			var accepted = !! v && entry.allowed.indexOf( v ) !== -1;
			console.log(
				'[AAE]',
				'prop=' + entry.prop,
				'css=' + entry.css,
				'value=', v || '(empty)',
				accepted ? '✓ accepted' : '✗ rejected (empty or not in allowed list)'
			);
			if ( ! accepted ) { return; }
			decls.push( entry.css + ':' + v );
			hasAny = true;
		} );

		if ( ! hasAny ) { return ''; }
		var rule = '[data-interaction-id="' + id + '"]{' + decls.join( ';' ) + ';}';
		console.log( '[AAE] final CSS rule →', rule );
		return rule;
	}

	function applyTo( id ) {
		var doc = frame.contentDocument;
		if ( ! doc || ! doc.head ) { return; }

		var styleEl = doc.getElementById( STYLE_ID );
		if ( ! styleEl ) {
			styleEl = doc.createElement( 'style' );
			styleEl.id = STYLE_ID;
			doc.head.appendChild( styleEl );
		}

		var rules = {};
		var re = /\[data-interaction-id="([^"]+)"\][^}]+\}/g;
		var m;
		while ( ( m = re.exec( styleEl.textContent || '' ) ) ) { rules[ m[ 1 ] ] = m[ 0 ]; }

		var rule = buildRule( id );
		if ( rule ) { rules[ id ] = rule; } else { delete rules[ id ]; }

		styleEl.textContent = Object.keys( rules ).map( function ( k ) { return rules[ k ]; } ).join( '' );
	}

	function applyAll() {
		var current = window.elementor.documents.getCurrent();
		if ( ! current || ! current.container ) { return; }
		( function walk( c ) {
			if ( ! c ) { return; }
			if ( c.model && c.model.get( 'widgetType' ) === WIDGET && c.id ) {
				applyTo( c.id );
			}
			if ( c.children && c.children.forEach ) { c.children.forEach( walk ); }
		} )( current.container );
	}

	function findLabelElement() {
		var walker = document.createTreeWalker( document.body, NodeFilter.SHOW_TEXT, null, false );
		var node;
		while ( ( node = walker.nextNode() ) ) {
			if ( node.nodeValue && node.nodeValue.trim() === SECTION_LABEL ) {
				return node.parentElement;
			}
		}
		return null;
	}

	function findSectionContainer( labelEl ) {
		var c = labelEl;
		for ( var i = 0; i < 10 && c && c !== document.body; i++ ) {
			if ( c.clientWidth >= 220 && c.clientHeight >= 60 ) { return c; }
			c = c.parentElement;
		}
		return labelEl.parentElement;
	}

	var injectInProgress = false;
	function injectButton() {
		if ( injectInProgress ) { return; }
		if ( document.querySelector( '.' + BTN_CLASS ) ) { return; }

		var labelEl = findLabelElement();
		if ( ! labelEl ) { return; }
		var container = findSectionContainer( labelEl );
		if ( ! container ) { return; }

		injectInProgress = true;
		try {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = BTN_CLASS;
			btn.textContent = '▶ Re-render';
			btn.style.cssText = [
				'display:block', 'width:calc(100% - 32px)', 'margin:12px 16px',
				'padding:10px 12px', 'border:0', 'border-radius:4px',
				'background:#111', 'color:#fff',
				'font:600 12px/1.4 system-ui, sans-serif', 'cursor:pointer',
				'box-shadow:0 2px 6px rgba(0,0,0,.15)',
			].join( ';' );
			btn.addEventListener( 'click', applyAll );
			container.appendChild( btn );
			console.log( '[AAE] Button injected into', container );
		} finally {
			injectInProgress = false;
		}
	}

	var debounce;
	new MutationObserver( function () {
		clearTimeout( debounce );
		debounce = setTimeout( injectButton, 100 );
	} ).observe( document.body, { childList: true, subtree: true } );

	injectButton();

	window.aaeApplyAll = applyAll;
	window.aaeForceInject = injectButton;
	console.log('frontend extender loaded');
} )();
