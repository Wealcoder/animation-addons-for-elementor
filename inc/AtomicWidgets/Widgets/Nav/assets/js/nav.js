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
	const canRepairDom = ! isEditor();

	nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
		let sub = getSub( item );
		const stray = [ ...item.children ].filter( child =>
			! child.classList.contains( 'aae-a-nav-item-label' ) &&
			! child.classList.contains( 'aae-mobile-submenu-toggle' ) &&
			child !== sub
		);

		if ( canRepairDom && ! sub && stray.length ) {
			sub = document.createElement( 'div' );
			sub.className = 'aae-a-nav-dropdown aae-a-nav-runtime-dropdown';
			item.appendChild( sub );
		}
		if ( ! sub ) return;

		sub.classList.add( 'aae-a-nav-dropdown' );
		if ( canRepairDom ) {
			stray.forEach( child => sub.appendChild( child ) );
		}
	} );
}

function getEditorDropdownChain( item ) {
	const chain = new Set();
	let current = item;
	while ( current ) {
		chain.add( current );
		current = current.parentElement?.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
	}
	return chain;
}

function hideEditorDropdown( item ) {
	item.classList.remove( 'aae-editor-dropdown-open' );
	const sub = getSub( item );
	if ( ! sub ) return;
	sub.style.removeProperty( 'visibility' );
	sub.style.removeProperty( 'opacity' );
	sub.style.removeProperty( 'pointer-events' );
}

function closeInactiveEditorDropdowns( nav, activeItem = null ) {
	const activeChain = activeItem ? getEditorDropdownChain( activeItem ) : new Set();
	nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"].aae-editor-dropdown-open' ).forEach( item => {
		if ( ! activeChain.has( item ) ) {
			hideEditorDropdown( item );
		}
	} );
}

function getDirectDropdownOwner( dropdown ) {
	if ( ! dropdown?.classList ) return null;
	const owner = dropdown.parentElement?.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
	if ( ! owner || getSub( owner ) !== dropdown ) return null;
	if (
		dropdown.classList.contains( 'aae-a-nav-dropdown' ) ||
		dropdown.classList.contains( 'e-flexbox-base' ) ||
		dropdown.classList.contains( 'e-con' ) ||
		dropdown.hasAttribute( 'data-aae-dropdown-for' )
	) {
		return owner;
	}
	return null;
}

function openEditorDropdownChain( item ) {
	let current = item;
	while ( current ) {
		current.classList.add( 'aae-editor-dropdown-open' );
		const sub = getSub( current );
		if ( sub ) {
			sub.classList.add( 'aae-a-nav-dropdown' );
			sub.setAttribute( 'data-aae-dropdown-for', current.getAttribute( 'data-id' ) || '' );
			sub.style.visibility = 'visible';
			sub.style.opacity = '1';
			sub.style.pointerEvents = 'auto';
		}
		const parentItem = current.parentElement?.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
		current = parentItem;
	}
}

function getActiveEditorDropdownItem( nav ) {
	const selected = nav.querySelector( '.elementor-element-selected' );
	const selectedDropdownOwner = getDirectDropdownOwner( selected );
	if ( selectedDropdownOwner ) {
		return selectedDropdownOwner;
	}

	const selectedItem = selected?.closest?.( '.aae-a-nav-item[data-has-dropdown="true"]' );
	if ( selectedItem && nav.contains( selectedItem ) ) {
		return selectedItem;
	}

	let activeItem = null;
	nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
		const itemSelected = item.classList.contains( 'elementor-element-selected' );

		if ( itemSelected ) {
			activeItem = item;
		}
	} );
	return activeItem;
}

function isEditorModalOrPopoverActive() {
	try {
		const editorWindow = window.parent && window.parent !== window ? window.parent : window;
		const editorDocument = editorWindow.document;
		const active = editorDocument?.activeElement;

		return !! (
			editorDocument?.querySelector?.( '.MuiPopover-root, .MuiModal-root, [role="presentation"][id*="popover"]' ) ||
			active?.closest?.( '.MuiPopover-root, .MuiModal-root, [role="presentation"][id*="popover"]' )
		);
	} catch ( error ) {
		return false;
	}
}

function selectEditorElementById( id ) {
	if ( ! id ) return;
	try {
		const editorWindow = window.parent && window.parent !== window ? window.parent : window;
		if ( isEditorModalOrPopoverActive() ) return;
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

/* Editor WYSIWYG fix. In edit-mode the v4 canvas renders atomic children INSIDE
 * our custom nav element WITHOUT their generated style class (`e-<id>-<styleid>`),
 * so Style-tab styling (background, etc.) doesn't preview in the editor — even
 * though it renders correctly on the frontend (the model IS saved). Re-apply each
 * element's saved style-class ids from the model onto the preview DOM so the
 * editor canvas matches the frontend. */
function applyEditorStyleClasses( nav ) {
	let editorWindow;
	try {
		editorWindow = window.parent && window.parent !== window ? window.parent : window;
	} catch ( error ) {
		return;
	}
	const getContainer = editorWindow.elementor?.getContainer;
	if ( typeof getContainer !== 'function' ) return;

	nav.querySelectorAll( '[data-id]' ).forEach( el => {
		const id = el.getAttribute( 'data-id' );
		if ( ! id ) return;
		let styles;
		try {
			styles = getContainer( id )?.model?.get?.( 'styles' );
		} catch ( error ) {
			return;
		}
		if ( ! styles || typeof styles !== 'object' ) return;
		Object.keys( styles ).forEach( styleId => {
			if ( styleId && ! el.classList.contains( styleId ) ) {
				el.classList.add( styleId );
			}
		} );
	} );
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
		/* Safe DOM-only op (adds style classes to the PREVIEW iframe; fires no
		 * command and no panel re-render). Must run BEFORE the popover guard —
		 * the Style-tab colour picker keeps a .MuiPopover-root mounted exactly
		 * while the user is styling, which is precisely when the injected classes
		 * are needed. */
		applyEditorStyleClasses( nav );
		if ( isEditorModalOrPopoverActive() ) return;
		normalizeRenderedDropdowns( nav );
		nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
			const sub = getSub( item );
			if ( sub ) {
				sub.classList.add( 'aae-a-nav-dropdown' );
				sub.setAttribute( 'data-aae-dropdown-for', item.getAttribute( 'data-id' ) || '' );
			}
		} );

		const activeItem = getActiveEditorDropdownItem( nav );
		if ( activeItem ) {
			closeInactiveEditorDropdowns( nav, activeItem );
			openEditorDropdownChain( activeItem );
		}
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
		if ( isEditorModalOrPopoverActive() ) return;
		const owner = e.target.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
		if ( ! owner || ! nav.contains( owner ) ) return;
		const dropdown = getSub( owner );
		if ( ! dropdown || ( ! dropdown.contains( e.target ) && e.target !== dropdown ) ) return;
		dropdown.classList.add( 'aae-a-nav-dropdown' );
		dropdown.setAttribute( 'data-aae-dropdown-for', owner.getAttribute( 'data-id' ) || '' );
		if ( owner ) {
			closeInactiveEditorDropdowns( nav, owner );
			openEditorDropdownChain( owner );
		}
		selectEditorElementById( dropdown.getAttribute( 'data-id' ) );
	}, { capture: true, signal: sig } );

	nav.addEventListener( 'click', e => {
		if ( isEditorModalOrPopoverActive() ) return;
		const item = e.target.closest( '.aae-a-nav-item[data-has-dropdown="true"]' );
		if ( ! item || ! nav.contains( item ) ) return;
		closeInactiveEditorDropdowns( nav, item );
		openEditorDropdownChain( item );
	}, { capture: true, signal: sig } );

	const observer = new MutationObserver( schedule );
	observer.observe( nav, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class', 'data-has-dropdown' ] } );
	sig.addEventListener( 'abort', () => observer.disconnect(), { once: true } );
	const interval = window.setInterval( schedule, 1500 );
	sig.addEventListener( 'abort', () => window.clearInterval( interval ), { once: true } );
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
 * so each panel can be restored to its exact original position on desktop. */
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
	} );
	const sub  = getSub( item );
	const anim = getAnim( item );

	if ( anim === 'gsap' && sub ) {
		gsapClose( sub, item, isNested( item ) );
	} else {
		item.classList.remove( 'is-open' );
	}
}

/* The editor never MOVES the real Nav into the drawer (that would corrupt
 * Elementor's editing model). Instead it renders a display-only CLONE of the
 * source Nav's DOM inside the drawer's menu-area so the user can preview the
 * mobile menu. The clone is inert: all Elementor data-* / editable hooks are
 * stripped so it is never mistaken for a real element. */
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
			/* The source Nav is hidden in-place while mobile (see render); the
			 * clone must NOT inherit that hide class or the drawer shows empty. */
			'aae-nav-editor-mobile-hidden'
		);
	} );
	clone.querySelectorAll( '.elementor-empty-view' ).forEach( node => node.remove() );
	clone.setAttribute( 'aria-hidden', 'true' );
	clone.classList.add( 'aae-nav-mobile-mounted', 'aae-mobile-editor-clone' );
}

/* Add accordion toggle arrows to the clone's dropdown items. Preview-only: the
 * legacy `.is-mobile-submenu-open` accordion CSS (retained in nav.scss) drives
 * the reveal; no drill-panel flattening is needed just to preview in editor. */
function addEditorCloneArrows( nav, arrowTemplate ) {
	nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"]' ).forEach( item => {
		const sub = getSub( item );
		if ( ! sub ) return;
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'aae-mobile-submenu-toggle';
		button.setAttribute( 'aria-label', 'Toggle submenu preview' );
		button.setAttribute( 'aria-expanded', 'false' );
		const icon = arrowTemplate?.cloneNode( true );
		if ( icon ) {
			[ icon, ...icon.querySelectorAll( '*' ) ].forEach( node => {
				node.removeAttribute?.( 'data-id' );
				node.removeAttribute?.( 'data-element_type' );
				node.removeAttribute?.( 'data-e-type' );
			} );
			icon.classList.remove( 'aae-mobile-nav-arrow-template' );
			icon.classList.add( 'aae-mobile-submenu-icon' );
			button.appendChild( icon );
		} else {
			button.textContent = '⌄';
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
			const sourceId = companion.dataset.sourceNavId;
			const source = sourceId ? document.querySelector( `.aae-a-nav[data-id="${ sourceId }"]` ) : null;
			if ( sourceId && ! source && mobile ) {
				return;
			}

			const drawer = companion.querySelector( '.aae-mobile-nav-drawer' );
			const menuArea = companion.querySelector( '.aae-mobile-nav-menu-area' ) || drawer;
			/* Drop any previous clone before re-evaluating; a fresh one is rebuilt
			 * below while mobile so title/link/structure edits stay in sync. */
			menuArea?.querySelector( ':scope > .aae-mobile-editor-clone' )?.remove();

			companion.classList.toggle( 'is-mobile', mobile );
			companion.classList.toggle(
				'is-open',
				mobile && companion.dataset.editorPreviewClosed !== 'true'
			);

			/* Editor parity with the frontend: in mobile mode the real desktop Nav
			 * would otherwise sit in the bar NEXT TO the hamburger. The frontend
			 * hides it by MOVING it into the drawer; the editor can't move it (that
			 * breaks Elementor's model), so hide it in place instead. */
			if ( source ) source.classList.toggle( 'aae-nav-editor-mobile-hidden', mobile );

			if ( ! mobile || ! source || ! menuArea ) return;

			/* Display-only clone of the real Nav so the drawer previews the menu
			 * in the editor (the real Nav is never moved here — see helper note). */
			const clone = source.cloneNode( true );
			sanitizeEditorClone( clone );
			addEditorCloneArrows( clone, companion.querySelector( '.aae-mobile-nav-arrow-template' ) );
			menuArea.appendChild( clone );

			clone.addEventListener( 'click', e => {
				const button = e.target.closest( '.aae-mobile-submenu-toggle' );
				if ( ! button ) return;
				e.preventDefault();
				const item = button.closest( '.aae-a-nav-item' );
				const open = ! item.classList.contains( 'is-mobile-submenu-open' );
				item.parentElement?.querySelectorAll( ':scope > .aae-a-nav-item.is-mobile-submenu-open' )
					.forEach( sibling => sibling.classList.remove( 'is-mobile-submenu-open' ) );
				item.classList.toggle( 'is-mobile-submenu-open', open );
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
		const cloneMissing = mobile && ! companion.querySelector( '.aae-mobile-editor-clone' );
		if ( companion.classList.contains( 'is-mobile' ) !== mobile || cloneMissing ) render();
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
		/* Appending the clone itself mutates the companion; without this guard the
		 * observer would rebuild it every 60ms forever. Only rebuild when the
		 * clone is actually gone (e.g. Elementor re-rendered the drawer). */
		if ( companion.querySelector( '.aae-mobile-editor-clone' ) ) return;
		render();
	} );
	previewObserver.observe( companion, { childList: true, subtree: true } );
	sig.addEventListener( 'abort', () => previewObserver.disconnect(), { once: true } );
	companion.addEventListener( 'pointerdown', e => {
		if ( e.target.closest( '.aae-mobile-nav-close' ) ) {
			companion.dataset.editorPreviewClosed = 'true';
			companion.classList.remove( 'is-open' );
		}
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
			const focusTarget = restoreFocus && lastFocus?.isConnected ? lastFocus : toggle;
			if ( drawer.contains( document.activeElement ) ) {
				focusTarget?.focus?.();
			}
			companion.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			resetDrill();
			drawer.setAttribute( 'aria-hidden', 'true' );
			if ( companion.dataset.lockScroll === 'true' ) document.body.classList.remove( 'aae-mobile-nav-scroll-lock' );
			if ( restoreFocus && ! drawer.contains( document.activeElement ) ) focusTarget?.focus?.();
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
