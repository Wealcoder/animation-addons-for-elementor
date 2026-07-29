import { register } from '@elementor/frontend-handlers';

/* AAE Atomic Table of Content.
 *
 * Native Atomic 4 port of the Pro `wcf--table-of-contents` frontend handler
 * (animation-addons-for-elementor-pro/assets/js/table-of-content.js). Reads
 * every behavioural setting off the root element's data-aae-toc-* attributes
 * (emitted by aae-a-table-of-contents.html.twig), scans the page for headings,
 * builds a nested/flat anchor list into .toc__body, wires active-heading
 * highlighting (GSAP ScrollTrigger), smooth scroll (GSAP ScrollToPlugin) and
 * the collapse/expand + responsive-minimize behaviour.
 *
 * No jQuery / elementorModules dependency — plain DOM, mirroring the other
 * atomic widget bundles (counter.js, accordion.js).
 */

const CLASSES = {
	anchor: 'elementor-menu-anchor',
	listWrapper: 'toc__list-wrapper',
	listItem: 'toc__list-item',
	listTextWrapper: 'toc__list-item-text-wrapper',
	firstLevelListItem: 'toc__top-level',
	listItemText: 'toc__list-item-text',
	activeItem: 'elementor-item-active',
	headingAnchor: 'toc__heading-anchor',
	collapsed: 'toc--collapsed',
};

// Breakpoint ceilings (px) mirroring Elementor's default active breakpoints.
// "Minimized On X" means: collapse when the current viewport is <= X's ceiling.
const BREAKPOINT_MAX = {
	mobile: 767,
	mobile_extra: 880,
	tablet: 1024,
	tablet_extra: 1200,
	laptop: 1366,
	desktop: Infinity,
};

const gsapReady = () => typeof window.gsap !== 'undefined';

const readConfig = ( el ) => {
	const tags = ( el.getAttribute( 'data-aae-toc-tags' ) || 'h2,h3,h4,h5,h6' )
		.split( ',' )
		.map( ( t ) => t.trim() )
		.filter( Boolean );

	return {
		tags,
		container: ( el.getAttribute( 'data-aae-toc-container' ) || '' ).trim(),
		exclude: ( el.getAttribute( 'data-aae-toc-exclude' ) || '' ).trim(),
		markerView: el.getAttribute( 'data-aae-toc-marker' ) || 'numbers',
		markerIcon: el.getAttribute( 'data-aae-toc-marker-icon' ) || '',
		minimize: el.getAttribute( 'data-aae-toc-minimize' ) === 'true',
		minimizedOn: el.getAttribute( 'data-aae-toc-minimized-on' ) || 'tablet',
		hierarchical: el.getAttribute( 'data-aae-toc-hierarchical' ) === 'true',
		collapseSubitems: el.getAttribute( 'data-aae-toc-collapse-subitems' ) === 'true',
	};
};

const isEditor = ( el ) => {
	const body = ( el.ownerDocument || document ).body;
	return !! ( body && ( body.classList.contains( 'elementor-editor-active' ) || body.classList.contains( 'elementor-editor-preview' ) ) );
};

// Resolve the DOM subtree to scan for headings.
//
// Order of preference:
//   1. An explicit user-supplied container selector.
//   2. The .elementor document wrapper the TOC itself lives in — this is the
//      post/page (or popup) the widget belongs to, and the one whose headings
//      the user means. Crucially it does NOT rely on data-elementor-type to
//      exclude header/footer/single-template parts: with theme builder, ALL
//      wrappers report data-elementor-type="wp-post" (not "header"/"footer"),
//      so a document-wide `.elementor:not([type=header])…` query returns the
//      FIRST wrapper on the page (often the site header) — which has no content
//      headings and yields a false "No headings found". Scoping to the TOC's
//      own wrapper is both correct and robust to that.
//   3. Fallbacks: the main post-content wrapper, else the whole document.
const getScanContainer = ( el, config ) => {
	const doc = el.ownerDocument || document;

	if ( config.container ) {
		try {
			const custom = doc.querySelector( config.container );
			if ( custom ) {
				return custom;
			}
		} catch ( e ) {
			// invalid selector — fall through to defaults
		}
	}

	// The Elementor document wrapper the TOC lives in (post, page or popup).
	const wrapper = el.closest( '.elementor' );
	if ( wrapper ) {
		return wrapper;
	}

	// Fallbacks (TOC not inside a recognised .elementor wrapper): prefer a
	// non-header/footer/popup content wrapper, else scan the whole document.
	const postContent = doc.querySelector(
		'.elementor:not([data-elementor-type="header"]):not([data-elementor-type="footer"]):not([data-elementor-type="popup"])'
	);
	return postContent || doc.body;
};

const collectHeadings = ( el, config, container ) => {
	if ( ! container ) {
		return [];
	}

	const selector = config.tags.join( ',' );
	let nodes = Array.prototype.slice.call( container.querySelectorAll( selector ) );

	// Never treat the TOC's own header title as a scannable heading.
	nodes = nodes.filter( ( h ) => ! h.classList.contains( 'toc__header-title' ) && ! el.contains( h ) );

	// Exclusions.
	if ( config.exclude ) {
		nodes = nodes.filter( ( h ) => {
			try {
				return ! h.closest( config.exclude );
			} catch ( e ) {
				return true;
			}
		} );
	}

	return nodes;
};

const buildHeadingsData = ( el, headings ) => {
	const data = [];

	headings.forEach( ( heading, index ) => {
		const headingID = heading.id;
		const widget = heading.closest( '.elementor-widget, .e-con, [data-id]' );
		const wrapperID = widget ? widget.id : '';
		let anchorLink = '';
		let hasOwnID = false;

		if ( headingID ) {
			anchorLink = headingID;
			hasOwnID = true;
		} else if ( wrapperID ) {
			anchorLink = wrapperID;
			hasOwnID = true;
		} else {
			anchorLink = `${ CLASSES.headingAnchor }-${ index }`;
		}

		data.push( {
			tag: parseInt( heading.nodeName.slice( 1 ), 10 ),
			text: heading.textContent.trim(),
			anchorLink,
			hasOwnID,
			el: heading,
			level: 0,
		} );
	} );

	return data;
};

// Insert an empty anchor span before headings that have no ID of their own,
// so the anchor links have a scroll target.
const addAnchors = ( headingsData ) => {
	headingsData.forEach( ( item ) => {
		if ( item.hasOwnID ) {
			return;
		}
		const span = item.el.ownerDocument.createElement( 'span' );
		span.id = item.anchorLink;
		span.className = CLASSES.anchor;
		item.el.parentNode.insertBefore( span, item.el );
	} );
};

const markerIconMarkup = ( config ) => {
	if ( config.markerView !== 'bullets' ) {
		return '';
	}
	if ( config.markerIcon ) {
		return `<img class="toc__marker-icon" src="${ config.markerIcon }" alt="" aria-hidden="true">`;
	}
	// Built-in dot fallback.
	return '<svg class="toc__marker-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="6"/></svg>';
};

// Recursive nested-list builder — a direct port of the Pro widget's
// getNestedLevel(), tracking a shared pointer over headingsData.
const buildList = ( state, level ) => {
	const { config, headingsData, listTag } = state;
	const marker = markerIconMarkup( config );

	let html = `<${ listTag } class="${ CLASSES.listWrapper }">`;

	while ( state.pointer < headingsData.length ) {
		const current = headingsData[ state.pointer ];
		let textClasses = CLASSES.listItemText;
		if ( current.level === 0 ) {
			textClasses += ` ${ CLASSES.firstLevelListItem }`;
		}
		if ( state.itemExtraClass ) {
			textClasses += ` ${ state.itemExtraClass }`;
		}

		if ( level !== undefined && level > current.level ) {
			break;
		}

		if ( level === undefined || level === current.level ) {
			html += `<li class="${ CLASSES.listItem }">`;
			html += `<div class="${ CLASSES.listTextWrapper }">`;
			let liContent = `<a href="#${ current.anchorLink }" class="${ textClasses }">${ current.text }</a>`;
			if ( config.markerView === 'bullets' && marker ) {
				liContent = marker + liContent;
			}
			html += liContent;
			html += '</div>';
			state.pointer++;

			const next = headingsData[ state.pointer ];
			if ( next && ( level === undefined ? false : level < next.level ) ) {
				html += buildList( state, next.level );
			}
			html += '</li>';
		} else {
			break;
		}
	}

	html += `</${ listTag }>`;
	return html;
};

const assignLevels = ( headingsData ) => {
	headingsData.forEach( ( heading, index ) => {
		heading.level = 0;
		for ( let i = index - 1; i >= 0; i-- ) {
			const prev = headingsData[ i ];
			if ( prev.tag <= heading.tag ) {
				heading.level = prev.level;
				if ( prev.tag < heading.tag ) {
					heading.level++;
				}
				break;
			}
		}
	} );
};

const setActiveLink = ( el, headingsData, activeId ) => {
	headingsData.forEach( ( item ) => {
		const link = el.querySelector( `[href="#${ CSS.escape( item.anchorLink ) }"]` );
		if ( link ) {
			link.classList.toggle( CLASSES.activeItem, item.anchorLink === activeId );
		}
	} );
};

const bindScrollSpy = ( el, headingsData ) => {
	if ( ! gsapReady() || ! window.gsap.ScrollTrigger ) {
		return;
	}
	window.gsap.registerPlugin( window.gsap.ScrollTrigger );

	headingsData.forEach( ( item ) => {
		window.gsap.ScrollTrigger.create( {
			trigger: item.el,
			start: 'top center',
			end: 'bottom center',
			onEnter: () => setActiveLink( el, headingsData, item.anchorLink ),
			onEnterBack: () => setActiveLink( el, headingsData, item.anchorLink ),
		} );
	} );
};

const bindSmoothScroll = ( el ) => {
	const links = el.querySelectorAll( `.${ CLASSES.listItemText }` );
	const canGsap = gsapReady() && window.gsap.ScrollToPlugin;
	if ( canGsap ) {
		window.gsap.registerPlugin( window.gsap.ScrollToPlugin );
	}

	links.forEach( ( link ) => {
		link.addEventListener( 'click', ( e ) => {
			const targetId = link.getAttribute( 'href' );
			if ( ! targetId || targetId === '#' ) {
				return;
			}
			const target = el.ownerDocument.querySelector( targetId );
			if ( ! target ) {
				return;
			}
			e.preventDefault();
			if ( canGsap ) {
				window.gsap.to( ( el.ownerDocument.defaultView || window ), {
					duration: 0.6,
					scrollTo: targetId,
					ease: 'power2.inOut',
				} );
			} else {
				target.scrollIntoView( { behavior: 'smooth' } );
			}
		} );
	} );
};

const collapseBox = ( el, changeFocus = true ) => {
	el.classList.add( CLASSES.collapsed );
	const body = el.querySelector( '.toc__body' );
	if ( body ) {
		body.setAttribute( 'aria-expanded', 'false' );
	}
	if ( changeFocus ) {
		const expandBtn = el.querySelector( '.toc__toggle-button--expand' );
		if ( expandBtn ) {
			expandBtn.focus();
		}
	}
};

const expandBox = ( el, changeFocus = true ) => {
	el.classList.remove( CLASSES.collapsed );
	const body = el.querySelector( '.toc__body' );
	if ( body ) {
		body.setAttribute( 'aria-expanded', 'true' );
	}
	if ( changeFocus ) {
		const collapseBtn = el.querySelector( '.toc__toggle-button--collapse' );
		if ( collapseBtn ) {
			collapseBtn.focus();
		}
	}
};

const applyResponsiveMinimize = ( el, config ) => {
	const width = ( el.ownerDocument.defaultView || window ).innerWidth;
	const ceiling = BREAKPOINT_MAX[ config.minimizedOn ] !== undefined ? BREAKPOINT_MAX[ config.minimizedOn ] : BREAKPOINT_MAX.tablet;
	const shouldCollapse = width <= ceiling;
	const isCollapsed = el.classList.contains( CLASSES.collapsed );

	if ( shouldCollapse && ! isCollapsed ) {
		collapseBox( el, false );
	} else if ( ! shouldCollapse && isCollapsed ) {
		expandBox( el, false );
	}
};

const bindToggle = ( el, config ) => {
	if ( ! config.minimize ) {
		return;
	}

	const expandBtn = el.querySelector( '.toc__toggle-button--expand' );
	const collapseBtn = el.querySelector( '.toc__toggle-button--collapse' );

	const onKey = ( event, handler ) => {
		if ( event.key === 'Enter' || event.key === ' ' || event.keyCode === 13 || event.keyCode === 32 ) {
			event.preventDefault();
			handler();
		}
	};

	if ( expandBtn ) {
		expandBtn.addEventListener( 'click', () => expandBox( el ) );
		expandBtn.addEventListener( 'keyup', ( e ) => onKey( e, () => expandBox( el ) ) );
	}
	if ( collapseBtn ) {
		collapseBtn.addEventListener( 'click', () => collapseBox( el ) );
		collapseBtn.addEventListener( 'keyup', ( e ) => onKey( e, () => collapseBox( el ) ) );
	}

	// Initial + resize responsive state.
	applyResponsiveMinimize( el, config );
	let raf;
	( el.ownerDocument.defaultView || window ).addEventListener( 'resize', () => {
		( el.ownerDocument.defaultView || window ).cancelAnimationFrame( raf );
		raf = ( el.ownerDocument.defaultView || window ).requestAnimationFrame( () => applyResponsiveMinimize( el, config ) );
	} );
};

const bindCollapseSubitems = ( el ) => {
	// Toggle a nested list's visibility when hovering/clicking its parent item.
	el.querySelectorAll( `.${ CLASSES.listItem }` ).forEach( ( item ) => {
		const nested = item.querySelector( `:scope > .${ CLASSES.listWrapper }` );
		if ( ! nested ) {
			return;
		}
		item.addEventListener( 'mouseenter', () => nested.classList.add( 'toc__subitems--open' ) );
		item.addEventListener( 'mouseleave', () => nested.classList.remove( 'toc__subitems--open' ) );
	} );
};

const populate = ( el, config, headingsData ) => {
	const body = el.querySelector( '.toc__body' );
	if ( ! body ) {
		return;
	}

	const listTag = config.markerView === 'numbers' ? 'ol' : 'ul';
	const state = {
		config,
		headingsData,
		listTag,
		pointer: 0,
		itemExtraClass: el.getAttribute( 'data-aae-toc-item-class' ) || '',
	};

	if ( config.hierarchical ) {
		assignLevels( headingsData );
		body.innerHTML = buildList( state, 0 );
	} else {
		body.innerHTML = buildList( state, undefined );
	}
};

// Build the list + wire behaviour from a non-empty heading set.
const buildFromHeadings = ( el, config, headings ) => {
	const headingsData = buildHeadingsData( el, headings );

	if ( ! isEditor( el ) ) {
		addAnchors( headingsData );
	}

	populate( el, config, headingsData );

	if ( config.collapseSubitems ) {
		bindCollapseSubitems( el );
	}

	if ( ! isEditor( el ) ) {
		bindSmoothScroll( el );
		bindScrollSpy( el, headingsData );
	}
};

const MAX_SCAN_ATTEMPTS = 20; // ~ up to 20 animation frames before giving up.

const initToc = ( el ) => {
	if ( ! el || el.dataset.aaeTocReady === 'true' ) {
		return;
	}
	el.dataset.aaeTocReady = 'true';

	const config = readConfig( el );
	const body = el.querySelector( '.toc__body' );
	if ( ! body ) {
		return;
	}

	// The header toggle must work regardless of whether headings exist yet.
	bindToggle( el, config );

	const win = el.ownerDocument.defaultView || window;
	let attempts = 0;
	let observer = null;

	const stopObserver = () => {
		if ( observer ) {
			observer.disconnect();
			observer = null;
		}
	};

	// Try to scan; retry while nothing is found yet, because sibling atomic
	// widgets (e-heading etc.) hydrate independently and may not be in the DOM
	// on the first pass. Only surface "No headings" once the DOM has settled.
	const tryScan = () => {
		const container = getScanContainer( el, config );
		const headings = collectHeadings( el, config, container );

		if ( headings.length ) {
			stopObserver();
			buildFromHeadings( el, config, headings );
			return true;
		}
		return false;
	};

	if ( tryScan() ) {
		return;
	}

	// Retry on subsequent frames.
	const pump = () => {
		attempts++;
		if ( tryScan() ) {
			return;
		}
		if ( attempts < MAX_SCAN_ATTEMPTS ) {
			win.requestAnimationFrame( pump );
		} else {
			stopObserver();
			body.innerHTML = '<div class="toc__no-headings">' + 'No headings were found on this page.' + '</div>';
		}
	};

	// Also react to late DOM insertions within the scan container.
	const container = getScanContainer( el, config );
	if ( win.MutationObserver && container ) {
		observer = new win.MutationObserver( () => {
			tryScan();
		} );
		observer.observe( container, { childList: true, subtree: true } );
		// Safety: stop observing after the retry window closes.
		win.setTimeout( stopObserver, 3000 );
	}

	win.requestAnimationFrame( pump );
};

// Re-init on editor re-renders: the v4 editor repaints a widget's DOM on any
// settings change, which resets our aaeTocReady flag with a fresh node — so a
// plain register() callback is enough, but we clear the flag defensively in
// case the same node is reused.
register( {
	elementType: 'e-aae-a-toc',
	id: 'e-aae-a-toc-handler',
	callback: ( { element } ) => {
		const el = element.classList.contains( 'aae-a-toc' )
			? element
			: element.querySelector( '.aae-a-toc' );
		if ( el ) {
			delete el.dataset.aaeTocReady;
			initToc( el );
		}
	},
} );
