const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};

const g = () => window.gsap;

/* Per-nav AbortControllers — abort stale document listeners on re-init */
const navControllers = new Map();
const editorNavControllers = new Map();
const editorCompanionControllers = new Map();
const editorCompanionNodes = new Map();
const mobileSubPanels = new WeakMap();
const mobileDrillSurfaces = new WeakMap();

function isEditor() {
	if ( document.body?.classList.contains( 'elementor-editor-active' ) ||
		window.elementorFrontend?.isEditMode?.() === true ) {
		return true;
	}

	/* In the v4 canvas the frontend API/body marker can become ready after this
	 * bundle. The same-origin parent editor is the stable signal. */
	try {
		return window.parent !== window && !! window.parent?.elementor;
	} catch ( error ) {
		return false;
	}
}

function getEditorDeviceMode() {
	try {
		return window.elementor?.channels?.deviceMode?.request?.( 'currentMode' ) ||
			window.parent?.elementor?.channels?.deviceMode?.request?.( 'currentMode' ) ||
			window.elementorFrontend?.getCurrentDeviceMode?.() || '';
	} catch ( error ) {
		return window.elementorFrontend?.getCurrentDeviceMode?.() || '';
	}
}

function isEditorMobileMode( breakpoint ) {
	const mode = String( getEditorDeviceMode() ).toLowerCase();
	const widthMatches = window.innerWidth <= breakpoint;
	if ( mode.includes( 'mobile' ) ) return true;
	if ( mode.includes( 'tablet' ) && breakpoint >= 1024 ) return true;

	/* DevTools responsive mode can resize the preview iframe while Elementor's
	 * toolbar channel still reports `desktop`. Either signal is sufficient. */
	return widthMatches;
}

/* Dropdown content = the first direct child of the nav-item that isn't the
 * label span/anchor. Typically an Elementor Flexbox the user styles themselves. */
function getSub( item ) {
	if ( mobileSubPanels.has( item ) ) return mobileSubPanels.get( item );
	return item.querySelector(
		':scope > .aae-a-nav-dropdown, :scope > .e-flexbox-base, :scope > .e-con'
	);
}

/* Older/broken editor data can contain widgets and nested nav-items directly
 * under a nav-item. The interaction code requires one panel, so repair the
 * rendered DOM defensively. The editor control performs the same repair in the
 * saved model; this fallback also fixes already-published pages immediately. */
function normalizeRenderedDropdowns( nav ) {
	nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
		let sub = getSub( item );
		const stray = [ ...item.children ].filter( child =>
			! child.classList.contains( 'aae-a-nav-item-label' ) &&
			! child.classList.contains( 'aae-mobile-submenu-toggle' ) &&
			child !== sub
		);

		if ( ! sub && stray.length ) {
			sub = document.createElement( 'div' );
			sub.className = 'aae-a-nav-dropdown aae-a-nav-runtime-dropdown';
			item.appendChild( sub );
		}
		if ( ! sub ) return;

		sub.classList.add( 'aae-a-nav-dropdown' );
		stray.forEach( child => sub.appendChild( child ) );
	} );
}

function openEditorDropdownChain( item ) {
	let current = item;
	while ( current ) {
		current.classList.add( 'aae-editor-dropdown-open' );
		const sub = getSub( current );
		if ( sub ) {
			sub.classList.add( 'aae-a-nav-dropdown' );
			sub.style.visibility = 'visible';
			sub.style.opacity = '1';
			sub.style.pointerEvents = 'auto';
		}
		const parentItem = current.parentElement?.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
		current = parentItem;
	}
}

function selectEditorElementById( id ) {
	if ( ! id ) return;
	try {
		const editorWindow = window.parent && window.parent !== window ? window.parent : window;
		const container = editorWindow.elementor?.getContainer?.( id );
		if ( ! container ) return;
		editorWindow.$e?.run?.( 'document/elements/select', {
			container: typeof container.lookup === 'function' ? container.lookup() : container,
			append: false,
		} );
	} catch ( error ) {
		/* Best effort only: editor internals may not be ready during render. */
	}
}

function initEditorDropdownUX( nav ) {
	const navId = nav.getAttribute( 'data-id' );
	if ( ! navId || nav.dataset.aaeEditorDropdownUx === 'true' ) return;
	nav.dataset.aaeEditorDropdownUx = 'true';

	editorNavControllers.get( navId )?.abort();
	const ctrl = new AbortController();
	editorNavControllers.set( navId, ctrl );
	const sig = ctrl.signal;

	const sync = () => {
		normalizeRenderedDropdowns( nav );
		nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
			const sub = getSub( item );
			if ( sub ) {
				sub.classList.add( 'aae-a-nav-dropdown' );
				sub.setAttribute( 'data-aae-dropdown-for', item.getAttribute( 'data-id' ) || '' );
			}
			if ( item.classList.contains( 'elementor-element-selected' ) ||
				item.querySelector( '.elementor-element-selected' ) ) {
				openEditorDropdownChain( item );
			}
		} );
	};

	let raf = null;
	const schedule = () => {
		if ( raf ) return;
		raf = window.requestAnimationFrame( () => {
			raf = null;
			sync();
		} );
	};

	nav.addEventListener( 'pointerdown', e => {
		const dropdown = e.target.closest( '.aae-a-nav-dropdown' );
		if ( ! dropdown || ! nav.contains( dropdown ) ) return;
		const owner = dropdown.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
		if ( owner ) openEditorDropdownChain( owner );
		selectEditorElementById( dropdown.getAttribute( 'data-id' ) );
	}, { capture: true, signal: sig } );

	nav.addEventListener( 'click', e => {
		const item = e.target.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
		if ( ! item || ! nav.contains( item ) ) return;
		openEditorDropdownChain( item );
	}, { capture: true, signal: sig } );

	const observer = new MutationObserver( schedule );
	observer.observe( nav, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class', 'data-has-dropdown' ] } );
	sig.addEventListener( 'abort', () => observer.disconnect(), { once: true } );
	sync();
}

function isNested( item ) {
	return !! item.parentElement?.closest(
		'.aae-a-nav-item[data-has-dropdown="true"]'
	);
}

function getAnim( item ) {
	return item.dataset.dropdownAnim || 'gsap';
}

function getDrillSurface( item ) {
	const sub = getSub( item );
	return sub ? ( mobileDrillSurfaces.get( sub ) || sub ) : null;
}

function resetScroll( ...nodes ) {
	nodes.forEach( node => {
		if ( node ) node.scrollTop = 0;
	} );
}

/* Drill panels cannot remain DOM descendants of their parent menu item: hiding
 * a previous level would also hide the current child level. Move every panel
 * beside the root nav items while mobile is mounted, keeping comment anchors
 * so the exact authored tree can be restored on desktop. */
function flattenDrillPanels( nav ) {
	const records = [];
	[ ...nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ) ].forEach( item => {
		const sub = getSub( item );
		if ( ! sub || sub.parentElement === nav ) return;
		const anchor = document.createComment( `aae-drill-panel-${ item.dataset.id || '' }` );
		sub.parentNode.insertBefore( anchor, sub );
		const surface = document.createElement( 'div' );
		surface.className = 'aae-mobile-drill-panel';
		surface.hidden = true;
		mobileSubPanels.set( item, sub );
		mobileDrillSurfaces.set( sub, surface );
		records.push( { item, sub, anchor, surface } );
		sub.hidden = false;
		surface.appendChild( sub );
		nav.appendChild( surface );
	} );
	return records;
}

function restoreDrillPanels( records ) {
	[ ...records ].reverse().forEach( ( { item, sub, anchor, surface } ) => {
		if ( anchor.parentNode ) anchor.parentNode.insertBefore( sub, anchor );
		anchor.remove();
		surface.remove();
		mobileSubPanels.delete( item );
		mobileDrillSurfaces.delete( sub );
		sub.removeAttribute( 'hidden' );
	} );
}

function getItemTitle( item ) {
	return item.querySelector( ':scope > .aae-a-nav-item-label' )?.textContent?.trim() || 'Menu';
}

function setBackLabel( back, item ) {
	const label = back?.querySelector( '.aae-mobile-nav-back-label' );
	if ( label ) label.textContent = item ? `← ${ getItemTitle( item ) }` : '← Back';
}

/* Force CSS animation to restart (needed on re-open) */
function resetCssAnim( sub, anim ) {
	sub.style.animation = 'none';
	void sub.offsetWidth;
	sub.style.animation = '';

	if ( anim === 'slide-items' || anim === 'rotate-items' ) {
		[ ...sub.children ].forEach( child => {
			child.style.animation = 'none';
			void child.offsetWidth;
			child.style.animation = '';
		} );
	}
}

/* GSAP open */
function gsapOpen( sub, nested ) {
	if ( ! g() ) return;
	g().killTweensOf( [ sub, sub.children ] );
	g().set( sub, {
		display: 'flex',
		flexDirection: 'column',
		transformOrigin: nested ? 'left top' : 'top center',
	} );
	if ( nested ) {
		g().fromTo( sub,
			{ opacity: 0, x: -14, filter: 'blur(6px)' },
			{ opacity: 1, x: 0, filter: 'blur(0px)', duration: 0.32, ease: 'back.out(1.3)' }
		);
		const kids = [ ...sub.children ];
		if ( kids.length ) {
			g().fromTo( kids,
				{ opacity: 0, x: -10 },
				{ opacity: 1, x: 0, duration: 0.24, stagger: 0.05, ease: 'power2.out', delay: 0.06 }
			);
		}
	} else {
		g().fromTo( sub,
			{ opacity: 0, scale: 0.85, y: -14, filter: 'blur(8px)' },
			{ opacity: 1, scale: 1, y: 0, filter: 'blur(0px)', duration: 0.38, ease: 'back.out(1.4)' }
		);
	}
}

/* GSAP close */
function gsapClose( sub, item, nested ) {
	if ( ! g() ) {
		item.classList.remove( 'is-open' );
		return;
	}
	g().killTweensOf( [ sub, sub.children ] );
	g().to( sub, {
		opacity: 0,
		scale: nested ? 0.92 : 0.88,
		filter: 'blur(6px)',
		...( nested ? { x: -8 } : { y: -8 } ),
		duration: 0.22,
		ease: 'power3.in',
		onComplete: () => {
			item.classList.remove( 'is-open' );
			g().set( sub, { clearProps: 'all' } );
		},
	} );
}

function openItem( item ) {
	/* When the nav is mounted in the mobile drawer, dropdowns are accordions
	 * driven ONLY by the submenu toggle button (click/tap). The desktop
	 * hover/click listeners stay attached to the moved nav, so bail here — no
	 * hover-open, no desktop overlay dropdown in mobile. */
	if ( item.closest( '.aae-nav-mobile-mounted' ) ) return;
	const sub = getSub( item );
	if ( ! sub ) return;
	const anim = getAnim( item );

	if ( anim === 'gsap' ) {
		item.classList.add( 'is-open' );
		gsapOpen( sub, isNested( item ) );
	} else {
		resetCssAnim( sub, anim );
		item.classList.add( 'is-open' );
	}
	navLabel( item )?.setAttribute( 'aria-expanded', 'true' );
}

function closeItem( item ) {
	if ( item.closest( '.aae-nav-mobile-mounted' ) ) return;
	item.querySelectorAll( '.aae-a-nav-item.is-open' ).forEach( descendant => {
		const descendantSub = getSub( descendant );
		if ( descendantSub && g() ) {
			g().killTweensOf( [ descendantSub, descendantSub.children ] );
			g().set( descendantSub, { clearProps: 'all' } );
		}
		descendant.classList.remove( 'is-open' );
		navLabel( descendant )?.setAttribute( 'aria-expanded', 'false' );
	} );
	const sub  = getSub( item );
	const anim = getAnim( item );

	if ( anim === 'gsap' && sub ) {
		gsapClose( sub, item, isNested( item ) );
	} else {
		item.classList.remove( 'is-open' );
	}
	navLabel( item )?.setAttribute( 'aria-expanded', 'false' );
}

function sanitizeEditorClone( clone ) {
	[ clone, ...clone.querySelectorAll( '*' ) ].forEach( node => {
		node.removeAttribute?.( 'data-id' );
		node.removeAttribute?.( 'data-element_type' );
		node.removeAttribute?.( 'data-e-type' );
		node.removeAttribute?.( 'data-interaction-id' );
		node.removeAttribute?.( 'contenteditable' );
		node.classList?.remove(
			'elementor-element-editable',
			'elementor-element-selected',
			'elementor-element-empty',
			'aae-editor-dropdown-open',
			/* Source Nav carries this while mobile preview is active; the clone
			 * is made from it, so strip it or the drawer menu is hidden too. */
			'aae-nav-editor-mobile-hidden'
		);
	} );
	clone.querySelectorAll( '.elementor-empty-view' ).forEach( node => node.remove() );
	clone.setAttribute( 'aria-hidden', 'true' );
	clone.classList.add( 'aae-nav-mobile-mounted', 'aae-mobile-editor-clone' );
}

function addEditorCloneArrows( nav, arrowTemplate ) {
	nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
		const sub = getSub( item );
		if ( ! sub ) return;
		sub.hidden = true;
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'aae-mobile-submenu-toggle';
		button.setAttribute( 'aria-label', 'Toggle submenu preview' );
		button.setAttribute( 'aria-expanded', 'false' );
		const icon = arrowTemplate?.cloneNode( true );
		if ( icon ) {
			const sourceId = arrowTemplate.getAttribute( 'data-id' );
			[ icon, ...icon.querySelectorAll( '*' ) ].forEach( node => {
				node.removeAttribute?.( 'data-id' );
				node.removeAttribute?.( 'data-element_type' );
				node.removeAttribute?.( 'data-e-type' );
			} );
			icon.classList.remove( 'aae-mobile-nav-arrow-template' );
			icon.classList.add( 'aae-mobile-submenu-icon' );
			if ( sourceId ) button.dataset.editorSourceId = sourceId;
			button.appendChild( icon );
		}
		item.insertBefore( button, sub );
	} );
}

function initEditorMobilePreview( companion ) {
	const companionId = companion.getAttribute( 'data-id' );
	if ( ! companionId ) return;
	/* Editor-only marker: scopes the "hamburger is a static preview" CSS
	 * (pointer-events:none, so hovering it doesn't stack Elementor edit
	 * toolbars) to the editor. This handler never runs on the frontend, so the
	 * real hamburger there stays clickable. */
	companion.classList.add( 'aae-mobile-nav-in-editor' );
	editorCompanionControllers.get( companionId )?.abort();
	const ctrl = new AbortController();
	editorCompanionControllers.set( companionId, ctrl );
	editorCompanionNodes.set( companionId, companion );
	const sig = ctrl.signal;
	let timer = null;
	let editorClone = null;
	let editorDrillStack = [];
	let observedSource = null;
	const sourceObserver = new MutationObserver( () => render() );
	const observeSource = source => {
		if ( source === observedSource ) return;
		sourceObserver.disconnect();
		observedSource = source;
		if ( source ) sourceObserver.observe( source, { childList: true, subtree: true } );
	};

	const render = () => {
		window.clearTimeout( timer );
		timer = window.setTimeout( () => {
			const breakpoint = Math.max( 320, Number.parseInt( companion.dataset.breakpoint || '767', 10 ) );
			const mobile = isEditorMobileMode( breakpoint ) && companion.dataset.enabled === 'true';
			const drawer = companion.querySelector( '.aae-mobile-nav-drawer' );
			const menuArea = companion.querySelector( '.aae-mobile-nav-menu-area' ) || drawer;
			if ( ! drawer ) return;

			/* Orphaned companion (its source Nav was deleted but this sibling
			 * lingers): bail BEFORE mutating so we don't drive an observer/render
			 * loop against a missing source. healthCheck tears it down shortly. */
			const sourceId = companion.dataset.sourceNavId;
			const source = sourceId ? document.querySelector( `.aae-a-nav[data-id="${ sourceId }"]` ) : null;
			observeSource( source );
			if ( sourceId && ! source && companion.dataset.enabled === 'true' && isEditorMobileMode( breakpoint ) ) {
				return;
			}

			menuArea?.querySelector( ':scope > .aae-mobile-editor-clone' )?.remove();
			drawer.querySelector( ':scope > .aae-mobile-editor-close' )?.remove();
			companion.classList.toggle( 'is-mobile', mobile );
			companion.classList.toggle(
				'is-open',
				mobile && companion.dataset.editorPreviewClosed !== 'true'
			);
			/* Match the frontend: below the breakpoint the desktop Nav belongs in
			 * the drawer, not the header. The frontend MOVES it there; in the
			 * editor we keep the model stable by cloning into the drawer instead,
			 * so the original must be hidden while mobile preview is active (and
			 * restored on desktop). Runtime-only class — never saved to the model. */
			if ( source ) source.classList.toggle( 'aae-nav-editor-mobile-hidden', mobile );
			if ( ! mobile ) return;

			if ( ! source ) return;
			const clone = source.cloneNode( true );
			sanitizeEditorClone( clone );
			addEditorCloneArrows( clone, companion.querySelector( '.aae-mobile-nav-arrow-template' ) );
			flattenDrillPanels( clone );
			const sourceClose = companion.querySelector( '.aae-mobile-nav-close' );
			if ( sourceClose && sourceClose.parentElement !== drawer && ! companion.querySelector( '.aae-mobile-nav-header' ) ) {
				const sourceCloseId = sourceClose.getAttribute( 'data-id' );
				const closeClone = sourceClose.cloneNode( true );
				[ closeClone, ...closeClone.querySelectorAll( '*' ) ].forEach( node => {
					node.removeAttribute?.( 'data-id' );
					node.removeAttribute?.( 'data-element_type' );
					node.removeAttribute?.( 'data-e-type' );
				} );
				closeClone.classList.add( 'aae-mobile-editor-close' );
				if ( sourceCloseId ) closeClone.dataset.editorSourceId = sourceCloseId;
				drawer.appendChild( closeClone );
			}
			menuArea.appendChild( clone );
			editorClone = clone;
			editorDrillStack = [];
			clone.dataset.drillDepth = '0';
			const editorBack = companion.querySelector( '.aae-mobile-nav-back' );
			editorBack?.classList.remove( 'is-visible' );

			clone.addEventListener( 'click', e => {
				const button = e.target.closest( '.aae-mobile-submenu-toggle' );
				if ( ! button ) return;
				e.preventDefault();
				const item = button.closest( '.aae-a-nav-item' );
				const sub = getSub( item );
				if ( ! sub ) return;
				const surface = getDrillSurface( item );
				if ( ! surface ) return;
				const previous = editorDrillStack.at( -1 );
				if ( previous ) {
					previous.surface.classList.remove( 'is-active' );
					previous.surface.hidden = true;
				}
				surface.hidden = false;
				surface.classList.remove( 'is-active' );
				surface.style.zIndex = String( 5 + editorDrillStack.length );
				resetScroll( clone, surface, surface.firstElementChild );
				/* Paint the translated start position before activating so forward
				 * navigation animates instead of jumping directly to the panel. */
				void surface.offsetWidth;
				surface.classList.add( 'is-active' );
				item.classList.add( 'is-mobile-submenu-open' );
				button.setAttribute( 'aria-expanded', 'true' );
				editorDrillStack.push( { item, sub, surface, button } );
				clone.dataset.drillDepth = String( editorDrillStack.length );
				if ( editorBack ) {
					editorBack.classList.add( 'is-visible' );
					setBackLabel( editorBack, item );
				}
			} );
		}, 60 );
	};

	window.addEventListener( 'resize', render, { signal: sig } );
	/* Elementor can change its responsive canvas without consistently firing a
	 * resize in the preview document. Recover only when state/preview is stale;
	 * do not continuously rebuild a healthy clone. */
	const healthCheck = window.setInterval( () => {
		if ( ! companion.isConnected ) {
			ctrl.abort();
			return;
		}
		const breakpoint = Math.max( 320, Number.parseInt( companion.dataset.breakpoint || '767', 10 ) );
		const mobile = isEditorMobileMode( breakpoint ) && companion.dataset.enabled === 'true';
		const menuArea = companion.querySelector( '.aae-mobile-nav-menu-area' ) ||
			companion.querySelector( '.aae-mobile-nav-drawer' );
		const sourceId = companion.dataset.sourceNavId;
		const currentSource = sourceId ? document.querySelector( `.aae-a-nav[data-id="${ sourceId }"]` ) : null;
		const replacedSource = currentSource !== observedSource;
		const staleMode = companion.classList.contains( 'is-mobile' ) !== mobile;
		const missingPreview = mobile && menuArea &&
			! menuArea.querySelector( ':scope > .aae-mobile-editor-clone' );
		if ( staleMode || missingPreview || replacedSource ) render();
	}, 250 );
	sig.addEventListener( 'abort', () => {
		window.clearInterval( healthCheck );
		sourceObserver.disconnect();
		if ( editorCompanionNodes.get( companionId ) === companion ) {
			editorCompanionNodes.delete( companionId );
		}
	}, { once: true } );
	const previewObserver = new MutationObserver( () => {
		const breakpoint = Math.max( 320, Number.parseInt( companion.dataset.breakpoint || '767', 10 ) );
		if ( ! isEditorMobileMode( breakpoint ) || companion.dataset.enabled !== 'true' ) return;
		const currentMenuArea = companion.querySelector( '.aae-mobile-nav-menu-area' ) ||
			companion.querySelector( '.aae-mobile-nav-drawer' );
		if ( currentMenuArea && ! currentMenuArea.querySelector( ':scope > .aae-mobile-editor-clone' ) ) {
			render();
		}
	} );
	previewObserver.observe( companion, { childList: true, subtree: true } );
	sig.addEventListener( 'abort', () => previewObserver.disconnect(), { once: true } );
	companion.addEventListener( 'pointerdown', e => {
		if ( e.target.closest( '.aae-mobile-nav-close' ) ) {
			companion.dataset.editorPreviewClosed = 'true';
			companion.classList.remove( 'is-open' );
		}
		const proxy = e.target.closest( '[data-editor-source-id]' );
		if ( ! proxy ) return;
		const original = document.querySelector( `[data-id="${ proxy.dataset.editorSourceId }"]` );
		if ( ! original ) return;
		original.dispatchEvent( new MouseEvent( 'mousedown', { bubbles: true, cancelable: true, view: window } ) );
		original.dispatchEvent( new MouseEvent( 'click', { bubbles: true, cancelable: true, view: window } ) );
	}, { capture: true, signal: sig } );
	companion.addEventListener( 'click', e => {
		if ( e.target.closest( '.aae-mobile-nav-back' ) ) {
			e.preventDefault();
			const current = editorDrillStack.pop();
			if ( current ) {
				current.surface.classList.remove( 'is-active' );
				current.surface.style.removeProperty( 'z-index' );
				current.surface.hidden = true;
				resetScroll( current.surface );
				current.item.classList.remove( 'is-mobile-submenu-open' );
				current.button.setAttribute( 'aria-expanded', 'false' );
			}
			if ( editorClone ) editorClone.dataset.drillDepth = String( editorDrillStack.length );
			const back = companion.querySelector( '.aae-mobile-nav-back' );
			const parent = editorDrillStack.at( -1 );
			if ( parent ) {
				parent.surface.hidden = false;
				resetScroll( parent.surface, parent.surface.firstElementChild );
				void parent.surface.offsetWidth;
				parent.surface.classList.add( 'is-active' );
			} else {
				resetScroll( editorClone, companion.querySelector( '.aae-mobile-nav-menu-area' ), companion.querySelector( '.aae-mobile-nav-drawer' ) );
			}
			back?.classList.toggle( 'is-visible', !! parent );
			setBackLabel( back, parent?.item || null );
			return;
		}
		if ( e.target.closest( '.aae-mobile-nav-close' ) ) {
			e.preventDefault();
			companion.dataset.editorPreviewClosed = 'true';
			companion.classList.remove( 'is-open' );
			return;
		}
		if ( e.target.closest( '.aae-mobile-nav-toggle' ) ) {
			e.preventDefault();
			companion.dataset.editorPreviewClosed = 'false';
			companion.classList.add( 'is-open' );
		}
	}, { signal: sig } );
	[ 0, 150, 400 ].forEach( delay => window.setTimeout( render, delay ) );
}

/* Atomic child/style updates may replace the Mobile Nav root without invoking
 * its frontend handler again. Observe replacements and initialize each actual
 * DOM node once; the node map prevents observer-driven reinitialization loops. */
function ensureEditorMobilePreviews() {
	if ( ! isEditor() ) return;
	document.querySelectorAll( '.aae-a-mobile-nav[data-id]' ).forEach( companion => {
		const id = companion.getAttribute( 'data-id' );
		if ( editorCompanionNodes.get( id ) !== companion ) {
			initEditorMobilePreview( companion );
		}
	} );
}

const editorMobileBootstrapObserver = new MutationObserver( ensureEditorMobilePreviews );
editorMobileBootstrapObserver.observe( document.documentElement, { childList: true, subtree: true } );
window.addEventListener( 'load', ensureEditorMobilePreviews );
/* Covers the no-mutation race: editor readiness may change after both module
 * evaluation and `load`. Node identity checks make healthy scans a no-op. */
window.setInterval( ensureEditorMobilePreviews, 500 );

/* Normalize a URL for current-page comparison: resolve to absolute, drop the
 * hash, and strip a trailing slash from the path so `/about` and `/about/`
 * match. Returns null for unparseable/anchor-only hrefs. */
function normalizeNavUrl( href ) {
	if ( ! href || href === '#' || href.charAt( 0 ) === '#' ) return null;
	try {
		const url = new URL( href, window.location.origin );
		if ( url.origin !== window.location.origin ) return null;
		const path = url.pathname.replace( /\/+$/, '' ) || '/';
		return path + url.search;
	} catch ( error ) {
		return null;
	}
}

/* DESIGN-LESS current-page highlight. Runs on the FRONTEND only (the nav lives
 * in a cached header/theme-builder template shared across pages, so this must
 * be computed per page in the browser, not baked into the server render). Adds
 * structural hooks the user styles themselves via the Style tab:
 *   .aae-a-nav-item-active   — the item whose link is the current page
 *   .aae-a-nav-item-ancestor — every dropdown parent on the active item's trail
 *   aria-current="page"      — on the matching <a> (accessibility)
 * No visual styling is injected. */
function markActiveNavItems( nav ) {
	const current = normalizeNavUrl( window.location.href );
	if ( ! current ) return;
	nav.querySelectorAll( 'a.aae-a-nav-item-label[href]' ).forEach( anchor => {
		if ( normalizeNavUrl( anchor.href ) !== current ) return;
		const item = anchor.closest( '.aae-a-nav-item' );
		if ( ! item ) return;
		item.classList.add( 'aae-a-nav-item-active' );
		anchor.setAttribute( 'aria-current', 'page' );
		let ancestor = item.parentElement?.closest( '.aae-a-nav-item' );
		while ( ancestor ) {
			ancestor.classList.add( 'aae-a-nav-item-ancestor' );
			ancestor = ancestor.parentElement?.closest( '.aae-a-nav-item' );
		}
	} );
}

/* ---- Desktop keyboard + ARIA menubar model ---------------------------------
 * Everything below runs on the FRONTEND desktop path only (never mobile — the
 * drawer has its own focus-trap/keyboard handling). It layers a standard WAI-
 * ARIA menubar interaction onto the existing click/hover dropdowns without
 * touching their visual behaviour. */

/* The item's own label element (the focusable <a>/<span>), not a descendant's. */
function navLabel( item ) {
	return item?.querySelector( ':scope > .aae-a-nav-item-label' ) || null;
}

/* Direct child nav-items of a container (the nav root or a dropdown flexbox). */
function childNavItems( container ) {
	return container ? [ ...container.querySelectorAll( ':scope > .aae-a-nav-item' ) ] : [];
}

/* The nav-items that live inside this item's dropdown panel. */
function subNavItems( item ) {
	return childNavItems( getSub( item ) );
}

function parentNavItem( item ) {
	return item?.parentElement?.closest( '.aae-a-nav-item' ) || null;
}

function hasNavDropdown( item ) {
	return item?.dataset.hasDropdown === 'true';
}

function initDesktopKeyboardNav( nav, sig ) {
	nav.setAttribute( 'aria-orientation', 'horizontal' );

	/* One-time ARIA + roving-tabindex setup for every item. */
	nav.querySelectorAll( '.aae-a-nav-item' ).forEach( item => {
		const label = navLabel( item );
		if ( ! label ) return;
		label.setAttribute( 'tabindex', '-1' );
		if ( hasNavDropdown( item ) ) {
			label.setAttribute( 'aria-haspopup', 'true' );
			label.setAttribute( 'aria-expanded', 'false' );
			const sub = getSub( item );
			if ( sub ) {
				if ( ! sub.id ) {
					sub.id = `aae-nav-sub-${ nav.getAttribute( 'data-id' ) || '' }-${ item.dataset.id || '' }`;
				}
				sub.setAttribute( 'role', 'menu' );
				label.setAttribute( 'aria-controls', sub.id );
			}
		}
	} );
	/* Roving tabindex: exactly one label is Tab-reachable; arrows move within. */
	navLabel( childNavItems( nav )[ 0 ] )?.setAttribute( 'tabindex', '0' );

	const setRoving = ( item ) => {
		nav.querySelectorAll( '.aae-a-nav-item-label[tabindex="0"]' )
			.forEach( l => l.setAttribute( 'tabindex', '-1' ) );
		navLabel( item )?.setAttribute( 'tabindex', '0' );
	};
	const focusItem = ( item ) => {
		if ( ! item ) return;
		setRoving( item );
		navLabel( item )?.focus();
	};
	const openAndEnter = ( item, toLast = false ) => {
		if ( ! hasNavDropdown( item ) ) return false;
		openItem( item );
		const kids = subNavItems( item );
		focusItem( toLast ? kids[ kids.length - 1 ] : kids[ 0 ] );
		return true;
	};
	const closeToParent = ( item ) => {
		const parent = parentNavItem( item );
		if ( parent ) {
			closeItem( parent );
			focusItem( parent );
		}
	};
	const closeAllOpen = () => {
		childNavItems( nav ).forEach( item => {
			if ( item.classList.contains( 'is-open' ) ) closeItem( item );
		} );
	};

	nav.addEventListener( 'keydown', ( e ) => {
		const label = e.target.closest( '.aae-a-nav-item-label' );
		if ( ! label || ! nav.contains( label ) ) return;
		const item = label.closest( '.aae-a-nav-item' );
		if ( ! item ) return;
		const parent = parentNavItem( item );
		const isTop = ! parent;
		const container = isTop ? nav : getSub( parent );
		const siblings = childNavItems( container );
		const idx = siblings.indexOf( item );
		const step = ( delta ) =>
			focusItem( siblings[ ( idx + delta + siblings.length ) % siblings.length ] );

		switch ( e.key ) {
			case 'ArrowRight':
				if ( isTop ) { step( 1 ); e.preventDefault(); }
				else if ( hasNavDropdown( item ) ) { openAndEnter( item ); e.preventDefault(); }
				break;
			case 'ArrowLeft':
				if ( isTop ) { step( -1 ); e.preventDefault(); }
				else { closeToParent( item ); e.preventDefault(); }
				break;
			case 'ArrowDown':
				if ( isTop ) { openAndEnter( item ); e.preventDefault(); }
				else { step( 1 ); e.preventDefault(); }
				break;
			case 'ArrowUp':
				if ( isTop ) { openAndEnter( item, true ); e.preventDefault(); }
				else { step( -1 ); e.preventDefault(); }
				break;
			case 'Home':
				focusItem( siblings[ 0 ] ); e.preventDefault();
				break;
			case 'End':
				focusItem( siblings[ siblings.length - 1 ] ); e.preventDefault();
				break;
			case 'Enter':
			case ' ':
				if ( hasNavDropdown( item ) ) {
					if ( item.classList.contains( 'is-open' ) ) closeItem( item );
					else openAndEnter( item );
					e.preventDefault();
				}
				/* A leaf <a> is left alone so Enter follows the link natively. */
				break;
			case 'Escape':
				if ( ! isTop ) { closeToParent( item ); e.preventDefault(); }
				else if ( item.classList.contains( 'is-open' ) ) { closeItem( item ); e.preventDefault(); }
				break;
			default:
				break;
		}
	}, { signal: sig } );

	/* Leaving the nav entirely (Tab out, click away) collapses open dropdowns. */
	nav.addEventListener( 'focusout', ( e ) => {
		if ( ! nav.contains( e.relatedTarget ) ) closeAllOpen();
	}, { signal: sig } );
}

register( {
	elementType: 'e-aae-a-nav',
	id: 'aae-a-nav-handler',
	callback: ( { element } ) => {
		const nav = element.classList.contains( 'aae-a-nav' )
			? element
			: element.querySelector( '.aae-a-nav' );

		if ( ! nav ) return;

		const navId = nav.getAttribute( 'data-id' );
		if ( ! navId ) return;

		/*
		 * Editor: skip frontend open/close listeners, but install a tiny
		 * idempotent authoring helper so the dropdown Flexbox itself remains
		 * selectable/styleable/drop-target friendly in the canvas.
		 */
		if ( isEditor() ) {
			normalizeRenderedDropdowns( nav );
			initEditorDropdownUX( nav );
			return;
		}
		normalizeRenderedDropdowns( nav );

		if ( nav.dataset.navInit === 'true' ) return;
		nav.dataset.navInit = 'true';

		markActiveNavItems( nav );

		/* Abort stale document listeners from a previous render of this nav */
		navControllers.get( navId )?.abort();
		const ctrl = new AbortController();
		navControllers.set( navId, ctrl );
		const sig = ctrl.signal;

		const closeAllClickItems = () => {
			nav.querySelectorAll( '.aae-a-nav-item[data-trigger="click"].is-open' )
				.forEach( item => closeItem( item ) );
		};

		/* Close siblings of `item` at the SAME nesting level — leave ancestors open. */
		const closeSiblings = ( item ) => {
			const parentList = item.parentElement;
			if ( ! parentList ) return;
			parentList.querySelectorAll( ':scope > .aae-a-nav-item[data-trigger="click"].is-open' )
				.forEach( sib => {
					if ( sib !== item ) closeItem( sib );
				} );
		};

		// Click trigger — event delegation
		nav.addEventListener( 'click', ( e ) => {
			const item = e.target.closest( '.aae-a-nav-item' );
			if ( ! item || ! nav.contains( item ) ) return;

			/* Only handle click-trigger dropdown items; let leaves navigate normally. */
			if ( item.dataset.hasDropdown !== 'true' || item.dataset.trigger !== 'click' ) return;

			const ownSub = getSub( item );
			if ( ownSub && ownSub.contains( e.target ) ) return;

			const wasOpen = item.classList.contains( 'is-open' );
			closeSiblings( item );
			if ( wasOpen ) {
				closeItem( item );
			} else {
				openItem( item );
			}
			e.stopPropagation();
		} );

		// Hover trigger — direct listeners per item
		nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"][data-trigger="hover"]' )
			.forEach( item => {
				item.addEventListener( 'mouseenter', () => openItem( item ) );
				item.addEventListener( 'mouseleave', () => closeItem( item ) );
			} );

		/* Document-level listener — only close when click landed OUTSIDE the nav. */
		document.addEventListener( 'click', ( e ) => {
			if ( ! nav.contains( e.target ) ) closeAllClickItems();
		}, { signal: sig } );

		initDesktopKeyboardNav( nav, sig );
	},
} );

/* Mobile companion: moves the existing Nav DOM at the configured breakpoint,
 * then restores the exact node to its original position on desktop. */
register( {
	elementType: 'e-aae-a-mobile-nav',
	id: 'aae-a-mobile-nav-handler',
	callback: ( { element } ) => {
		const companion = element.classList.contains( 'aae-a-mobile-nav' )
			? element
			: element.querySelector( '.aae-a-mobile-nav' );
		if ( ! companion ) return;
		if ( isEditor() ) {
			initEditorMobilePreview( companion );
			return;
		}
		if ( companion.dataset.enabled !== 'true' ) return;

		const sourceId = companion.dataset.sourceNavId;
		const nav = document.querySelector( `.aae-a-nav[data-id="${ sourceId }"]` );
		const mount = companion.querySelector( '.aae-mobile-nav-menu-area' ) ||
			companion.querySelector( '.aae-mobile-nav-mount' ) ||
			companion.querySelector( '.aae-mobile-nav-drawer' );
		const toggle = companion.querySelector( '.aae-mobile-nav-toggle' );
		const close = companion.querySelector( '.aae-mobile-nav-close' );
		let back = companion.querySelector( '.aae-mobile-nav-back' );
		const overlay = companion.querySelector( '.aae-mobile-nav-overlay' );
		const drawer = companion.querySelector( '.aae-mobile-nav-drawer' );
		const arrowTemplate = companion.querySelector( '.aae-mobile-nav-arrow-template' );
		if ( ! nav || ! mount || ! toggle || ! close || ! overlay || ! drawer ) return;
		if ( ! back ) {
			back = document.createElement( 'button' );
			back.type = 'button';
			back.className = 'aae-mobile-nav-back aae-mobile-nav-back-fallback';
			back.innerHTML = '<span class="aae-mobile-nav-back-label">← Back</span>';
			( companion.querySelector( '.aae-mobile-nav-header' ) || drawer ).prepend( back );
		}
		normalizeRenderedDropdowns( nav );
		if ( close.parentElement !== drawer && ! companion.querySelector( '.aae-mobile-nav-header' ) ) {
			drawer.insertBefore( close, drawer.firstChild );
		}

		const id = companion.getAttribute( 'data-id' );
		const ctrl = new AbortController();
		const sig = ctrl.signal;
		const anchor = document.createComment( `aae-nav-anchor-${ sourceId }` );
		let mounted = false;
		let lastFocus = null;
		let media;
		let drillStack = [];
		let drillPanelRecords = [];

		companion.classList.toggle( 'position-left', companion.dataset.position === 'left' );
	drawer.id = `aae-mobile-nav-drawer-${ id }`;
	toggle.setAttribute( 'role', 'button' );
	toggle.setAttribute( 'tabindex', '0' );
	toggle.setAttribute( 'aria-label', 'Open menu' );
	toggle.setAttribute( 'aria-controls', drawer.id );
	toggle.setAttribute( 'aria-expanded', 'false' );
	close.setAttribute( 'role', 'button' );
	close.setAttribute( 'tabindex', '0' );
	close.setAttribute( 'aria-label', 'Close menu' );
	back.setAttribute( 'aria-label', 'Back to parent menu' );
	back.hidden = true;
	drawer.setAttribute( 'role', 'dialog' );
	drawer.setAttribute( 'aria-modal', 'true' );
	drawer.setAttribute( 'aria-hidden', 'true' );

		const syncDrillState = () => {
			nav.dataset.drillDepth = String( drillStack.length );
			const current = drillStack.at( -1 );
			back.hidden = ! current;
			back.classList.toggle( 'is-visible', !! current );
			setBackLabel( back, current?.item || null );
		};

		const openDrillPanel = ( item, button ) => {
			const sub = getSub( item );
			const surface = getDrillSurface( item );
			if ( ! sub || ! surface ) return;
			const previous = drillStack.at( -1 );
			if ( previous ) {
				previous.surface.classList.remove( 'is-active' );
				previous.surface.hidden = true;
			}
			surface.hidden = false;
			surface.classList.remove( 'is-active' );
			surface.style.zIndex = String( 5 + drillStack.length );
			resetScroll( nav, mount, drawer, surface, surface.firstElementChild );
			void surface.offsetWidth;
			surface.classList.add( 'is-active' );
			item.classList.add( 'is-mobile-submenu-open' );
			button.setAttribute( 'aria-expanded', 'true' );
			drillStack.push( { item, sub, surface, button } );
			syncDrillState();
			window.requestAnimationFrame( () => back.focus?.() );
		};

		const closeDrillPanel = () => {
			const current = drillStack.pop();
			if ( ! current ) return;
			current.surface.classList.remove( 'is-active' );
			current.surface.style.removeProperty( 'z-index' );
			current.surface.hidden = true;
			current.surface.setAttribute( 'hidden', '' );
			resetScroll( current.surface );
			current.item.classList.remove( 'is-mobile-submenu-open' );
			current.button.setAttribute( 'aria-expanded', 'false' );
			const previous = drillStack.at( -1 );
			if ( previous ) {
				previous.surface.hidden = false;
				previous.surface.removeAttribute( 'hidden' );
				resetScroll( previous.surface, previous.surface.firstElementChild );
				void previous.surface.offsetWidth;
				previous.surface.classList.add( 'is-active' );
				previous.item.classList.add( 'is-mobile-submenu-open' );
				previous.button.setAttribute( 'aria-expanded', 'true' );
			} else {
				resetScroll( nav, mount, drawer );
			}
			syncDrillState();
			window.requestAnimationFrame( () => ( previous ? back : close ).focus?.() );
		};

		const resetDrill = () => {
			while ( drillStack.length ) {
				const current = drillStack.pop();
				current.surface.classList.remove( 'is-active' );
				current.surface.style.removeProperty( 'z-index' );
				current.surface.hidden = true;
				current.item.classList.remove( 'is-mobile-submenu-open' );
				current.button.setAttribute( 'aria-expanded', 'false' );
			}
			syncDrillState();
		};

		const addArrows = () => {
			nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
				if ( item.querySelector( ':scope > .aae-mobile-submenu-toggle' ) ) return;
				const sub = getSub( item );
				if ( ! sub ) return;
				sub.hidden = true;
				if ( ! sub.id ) sub.id = `aae-mobile-submenu-${ sourceId }-${ item.dataset.id }`;
				const button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'aae-mobile-submenu-toggle';
				button.setAttribute( 'aria-label', 'Toggle submenu' );
				button.setAttribute( 'aria-haspopup', 'true' );
				button.setAttribute( 'aria-expanded', 'false' );
				button.setAttribute( 'aria-controls', sub.id );
				const icon = arrowTemplate?.cloneNode( true );
				if ( icon ) {
					icon.classList.remove( 'aae-mobile-nav-arrow-template' );
					icon.classList.add( 'aae-mobile-submenu-icon' );
					icon.removeAttribute( 'data-id' );
					button.appendChild( icon );
				} else {
					button.textContent = '⌄';
				}
				item.insertBefore( button, sub );
			} );
		};

		const removeArrows = () => {
			nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
				const sub = getSub( item );
				if ( sub ) sub.removeAttribute( 'hidden' );
			} );
			nav.querySelectorAll( '.aae-mobile-submenu-toggle' ).forEach( button => button.remove() );
		};

		const closeDrawer = ( restoreFocus = true ) => {
			companion.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			drawer.setAttribute( 'aria-hidden', 'true' );
			resetDrill();
			if ( companion.dataset.lockScroll === 'true' ) document.body.classList.remove( 'aae-mobile-nav-scroll-lock' );
			if ( restoreFocus ) lastFocus?.focus?.();
		};

		const openDrawer = () => {
			lastFocus = document.activeElement;
			companion.classList.add( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			drawer.setAttribute( 'aria-hidden', 'false' );
			if ( companion.dataset.lockScroll === 'true' ) document.body.classList.add( 'aae-mobile-nav-scroll-lock' );
			window.requestAnimationFrame( () => close.focus?.() );
		};

		const enterMobile = () => {
			if ( mounted ) return;
			nav.parentNode?.insertBefore( anchor, nav );
			mount.appendChild( nav );
			nav.classList.add( 'aae-nav-mobile-mounted' );
			companion.classList.add( 'is-mobile' );
			addArrows();
			drillPanelRecords = flattenDrillPanels( nav );
			resetDrill();
			mounted = true;
		};

		const leaveMobile = () => {
			if ( ! mounted ) return;
			closeDrawer( false );
			removeArrows();
			restoreDrillPanels( drillPanelRecords );
			drillPanelRecords = [];
			nav.classList.remove( 'aae-nav-mobile-mounted' );
			anchor.parentNode?.insertBefore( nav, anchor );
			anchor.remove();
			companion.classList.remove( 'is-mobile' );
			mounted = false;
		};

		const breakpoint = Math.max( 320, Number.parseInt( companion.dataset.breakpoint || '767', 10 ) );
		media = window.matchMedia( `(max-width: ${ breakpoint }px)` );
		const syncMode = () => media.matches ? enterMobile() : leaveMobile();
		media.addEventListener( 'change', syncMode, { signal: sig } );

		const activate = ( node, action ) => {
			node.addEventListener( 'click', action, { signal: sig } );
			node.addEventListener( 'keydown', e => {
				if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); action(); }
			}, { signal: sig } );
		};
		activate( toggle, openDrawer );
		activate( close, () => closeDrawer() );
		overlay.addEventListener( 'click', () => closeDrawer(), { signal: sig } );

		companion.addEventListener( 'click', e => {
			if ( ! mounted || ! e.target.closest( '.aae-mobile-nav-back' ) ) return;
			e.preventDefault();
			e.stopImmediatePropagation();
			closeDrillPanel();
		}, { capture: true, signal: sig } );

		nav.addEventListener( 'click', e => {
			if ( ! mounted ) return;
			const button = e.target.closest( '.aae-mobile-submenu-toggle' );
			if ( button ) {
				e.preventDefault();
				e.stopImmediatePropagation();
				const item = button.closest( '.aae-a-nav-item' );
				openDrillPanel( item, button );
				return;
			}
			if ( companion.dataset.closeOnLink === 'true' && e.target.closest( 'a' ) && ! e.target.closest( '[data-has-dropdown="true"] > .aae-a-nav-item-label' ) ) {
				closeDrawer();
			}
		}, { capture: true, signal: sig } );

		document.addEventListener( 'keydown', e => {
			if ( e.key === 'Escape' && companion.classList.contains( 'is-open' ) ) closeDrawer();
			if ( e.key !== 'Tab' || ! companion.classList.contains( 'is-open' ) ) return;
			const focusable = [ close, ...drawer.querySelectorAll( 'a[href], button, [tabindex="0"]' ) ]
				.filter( ( node, index, list ) => ! node.hidden && list.indexOf( node ) === index );
			if ( ! focusable.length ) return;
			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
			else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
		}, { signal: sig } );

		syncMode();
	},
} );
