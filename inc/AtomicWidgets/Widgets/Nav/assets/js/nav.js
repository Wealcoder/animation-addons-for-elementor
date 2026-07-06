const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};

const g = () => window.gsap;

/* Per-nav AbortControllers — abort stale document listeners on re-init */
const navControllers = new Map();
const editorCompanionControllers = new Map();
const editorCompanionNodes = new Map();

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
	return item.querySelector( ':scope > :not(.aae-a-nav-item-label):not(.aae-mobile-submenu-toggle)' );
}

function isNested( item ) {
	return !! item.parentElement?.closest(
		'.aae-a-nav-item[data-has-dropdown="true"]'
	);
}

function getAnim( item ) {
	return item.dataset.dropdownAnim || 'gsap';
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
}

function closeItem( item ) {
	const sub  = getSub( item );
	const anim = getAnim( item );

	if ( anim === 'gsap' && sub ) {
		gsapClose( sub, item, isNested( item ) );
	} else {
		item.classList.remove( 'is-open' );
	}
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

			clone.addEventListener( 'click', e => {
				const button = e.target.closest( '.aae-mobile-submenu-toggle' );
				if ( ! button ) return;
				e.preventDefault();
				const item = button.closest( '.aae-a-nav-item' );
				const open = ! item.classList.contains( 'is-mobile-submenu-open' );
				item.parentElement?.querySelectorAll( ':scope > .aae-a-nav-item.is-mobile-submenu-open' )
					.forEach( sibling => {
						sibling.classList.remove( 'is-mobile-submenu-open' );
						const siblingSub = getSub( sibling );
						if ( siblingSub ) siblingSub.hidden = true;
					} );
				item.classList.toggle( 'is-mobile-submenu-open', open );
				const sub = getSub( item );
				if ( sub ) sub.hidden = ! open;
				button.setAttribute( 'aria-expanded', String( open ) );
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
		const staleMode = companion.classList.contains( 'is-mobile' ) !== mobile;
		const missingPreview = mobile && menuArea &&
			! menuArea.querySelector( ':scope > .aae-mobile-editor-clone' );
		if ( staleMode || missingPreview ) render();
	}, 250 );
	sig.addEventListener( 'abort', () => {
		window.clearInterval( healthCheck );
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
		 * Editor: do NOTHING here. Any DOM mutation (classList changes,
		 * data-attr writes) triggers Elementor's MutationObserver, which
		 * re-renders the widget — and rapid re-renders on drop/device-switch
		 * cause the editor to hang. (See Countdown's `isEditMode` skip for
		 * the same reason.) Visibility is handled by the always-show CSS
		 * rule in aae-a-nav.html.twig.
		 */
		if ( isEditor() ) return;

		if ( nav.dataset.navInit === 'true' ) return;
		nav.dataset.navInit = 'true';

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
		const overlay = companion.querySelector( '.aae-mobile-nav-overlay' );
		const drawer = companion.querySelector( '.aae-mobile-nav-drawer' );
		const arrowTemplate = companion.querySelector( '.aae-mobile-nav-arrow-template' );
		if ( ! nav || ! mount || ! toggle || ! close || ! overlay || ! drawer ) return;
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
	drawer.setAttribute( 'role', 'dialog' );
	drawer.setAttribute( 'aria-modal', 'true' );
	drawer.setAttribute( 'aria-hidden', 'true' );

		const setSubmenu = ( item, open ) => {
			const button = item.querySelector( ':scope > .aae-mobile-submenu-toggle' );
			const sub = getSub( item );
			item.classList.toggle( 'is-mobile-submenu-open', open );
			if ( sub ) sub.hidden = ! open;
			button?.setAttribute( 'aria-expanded', String( open ) );
		};

		const closeSubmenus = () => nav.querySelectorAll( '.is-mobile-submenu-open' )
			.forEach( item => setSubmenu( item, false ) );

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
			closeSubmenus();
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
			mounted = true;
		};

		const leaveMobile = () => {
			if ( ! mounted ) return;
			closeDrawer( false );
			removeArrows();
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

		nav.addEventListener( 'click', e => {
			if ( ! mounted ) return;
			const button = e.target.closest( '.aae-mobile-submenu-toggle' );
			if ( button ) {
				e.preventDefault();
				e.stopImmediatePropagation();
				const item = button.closest( '.aae-a-nav-item' );
				const opening = ! item.classList.contains( 'is-mobile-submenu-open' );
				item.parentElement?.querySelectorAll( ':scope > .aae-a-nav-item.is-mobile-submenu-open' )
					.forEach( sibling => { if ( sibling !== item ) setSubmenu( sibling, false ); } );
				setSubmenu( item, opening );
				return;
			}
			if ( companion.dataset.closeOnLink === 'true' && e.target.closest( 'a' ) && ! e.target.closest( '[data-has-dropdown="true"] > .aae-a-nav-item-label' ) ) {
				closeDrawer();
			}
		}, { capture: true, signal: sig } );

		document.addEventListener( 'keydown', e => {
			if ( e.key === 'Escape' && companion.classList.contains( 'is-open' ) ) closeDrawer();
			if ( e.key !== 'Tab' || ! companion.classList.contains( 'is-open' ) ) return;
			const focusable = [ close, ...drawer.querySelectorAll( 'a[href], button, [tabindex="0"]' ) ];
			if ( ! focusable.length ) return;
			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
			else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
		}, { signal: sig } );

		syncMode();
	},
} );
