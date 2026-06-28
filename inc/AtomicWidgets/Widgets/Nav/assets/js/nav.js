import { register } from '@elementor/frontend-handlers';

const g = () => window.gsap;

/* Per-nav AbortControllers — abort stale document listeners on re-init */
const navControllers = new Map();

function getSub( item ) {
	return item.querySelector( ':scope > .aae-a-nav-sub' );
}

function isNested( item ) {
	return !! item.parentElement?.closest( '.aae-a-nav-sub' );
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

		/* Abort stale document listeners from a previous render of this nav */
		navControllers.get( navId )?.abort();
		const ctrl = new AbortController();
		navControllers.set( navId, ctrl );
		const sig = ctrl.signal;

		const closeClickItems = () => {
			nav.querySelectorAll( '.aae-a-nav-item[data-trigger="click"].is-open' )
				.forEach( item => closeItem( item ) );
		};

		/* Direct element listeners — guarded so they don't stack on same node */
		if ( ! nav.dataset.navInit ) {
			nav.dataset.navInit = 'true';

			// Click trigger — event delegation
			nav.addEventListener( 'click', ( e ) => {
				const item = e.target.closest( '.aae-a-nav-item[data-has-dropdown="true"][data-trigger="click"]' );
				if ( ! item || ! nav.contains( item ) ) {
					closeClickItems();
					return;
				}
				const wasOpen = item.classList.contains( 'is-open' );
				closeClickItems();
				if ( ! wasOpen ) openItem( item );
				e.stopPropagation();
			} );

			// Hover trigger — direct listeners per item
			nav.querySelectorAll( '.aae-a-nav-item[data-has-dropdown="true"][data-trigger="hover"]' )
				.forEach( item => {
					item.addEventListener( 'mouseenter', () => openItem( item ) );
					item.addEventListener( 'mouseleave', () => closeItem( item ) );
				} );

			// ── Mobile offcanvas ──
			const hamburger  = nav.querySelector( '.aae-a-nav-hamburger' );
			const closeBtn   = nav.querySelector( '.aae-a-nav-close' );
			const overlay    = nav.querySelector( '.aae-a-nav-overlay' );
			const mobileList = nav.querySelector( '.aae-a-nav-mobile-list' );

			if ( hamburger && mobileList ) {
				function buildMobileMenu() {
					if ( mobileList.children.length ) return;
					nav.querySelectorAll( ':scope > .aae-a-nav-item' ).forEach( item => {
						const clone = item.cloneNode( true );
						clone.removeAttribute( 'data-id' );
						if ( clone.querySelector( ':scope > .aae-a-nav-sub' ) ) {
							clone.addEventListener( 'click', ( e ) => {
								e.stopPropagation();
								clone.classList.toggle( 'is-mobile-open' );
							} );
						}
						mobileList.appendChild( clone );
					} );
				}

				function openOffcanvas() {
					buildMobileMenu();
					nav.classList.add( 'offcanvas-is-open' );
					document.body.style.overflow = 'hidden';
					hamburger.setAttribute( 'aria-expanded', 'true' );
				}

				function closeOffcanvas() {
					nav.classList.remove( 'offcanvas-is-open' );
					document.body.style.overflow = '';
					hamburger.setAttribute( 'aria-expanded', 'false' );
				}

				hamburger.addEventListener( 'click', ( e ) => {
					e.stopPropagation();
					nav.classList.contains( 'offcanvas-is-open' ) ? closeOffcanvas() : openOffcanvas();
				} );
				closeBtn?.addEventListener( 'click', closeOffcanvas );
				overlay?.addEventListener( 'click', closeOffcanvas );
			}
		}

		/* Document-level listeners — always re-registered with fresh signal */
		document.addEventListener( 'click', closeClickItems, { signal: sig } );
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' && nav.classList.contains( 'offcanvas-is-open' ) ) {
				nav.classList.remove( 'offcanvas-is-open' );
				document.body.style.overflow = '';
				nav.querySelector( '.aae-a-nav-hamburger' )?.setAttribute( 'aria-expanded', 'false' );
			}
		}, { signal: sig } );
	},
} );
