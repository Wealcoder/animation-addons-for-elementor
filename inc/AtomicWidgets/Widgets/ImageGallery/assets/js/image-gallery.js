/* eslint-env browser */

/**
 * AAE Image Gallery — frontend lightbox runtime.
 *
 * Atomic (V4) port of the legacy wcf--image-gallery lightbox. Registers on the
 * `e-aae-a-image-gallery` element via @elementor/frontend-handlers (same
 * pattern as the Accordion widget); when the gallery has data-lightbox="true",
 * clicking any item opens a full-size lightbox with prev/next, counter,
 * fullscreen, keyboard and swipe. Image sources are read straight from the
 * rendered <img> elements (their natural/full src), so no server-side image
 * list is needed. DOM/classes match image-gallery.scss (.aae-lightbox*).
 */

import { register } from '@elementor/frontend-handlers';

// Text fallbacks used only if the server-rendered Atomic_Svg icon node for a
// role is missing (should not happen — the widget always seeds all four).
const ICON_FALLBACK = {
	close: '&times;',
	prev: '&#8249;',
	next: '&#8250;',
	fullscreen: '&#x26F6;',
};

/**
 * Clone the native Atomic_Svg icon element the widget rendered for `role`
 * (a locked `.aae-lb-icon-<role>` child of the gallery) into `button`. Falls
 * back to a text glyph only if absent. This is why the lightbox uses zero
 * hardcoded SVG markup — the icons ARE atomic elements the user can swap and
 * style like any other Atomic Icon.
 */
function fillIconButton( gallery, button, role ) {
	const source = gallery.querySelector( '.aae-lb-icon-' + role );
	if ( source ) {
		const clone = source.cloneNode( true );
		clone.removeAttribute( 'data-interaction-id' );
		clone.setAttribute( 'aria-hidden', 'true' );
		button.appendChild( clone );
	} else {
		button.innerHTML = ICON_FALLBACK[ role ] || '';
	}
}

function initGallery( gallery ) {
	if ( ! gallery ) {
		return;
	}

	const lightboxOn = gallery.getAttribute( 'data-lightbox' ) === 'true';
	const animation = gallery.getAttribute( 'data-lightbox-animation' ) || 'fade-scale';
	const showCounter = gallery.getAttribute( 'data-lightbox-counter' ) !== 'false';

	const items = Array.from( gallery.querySelectorAll( '.aae-a-gallery-item' ) );

	// Config signature — re-init only when the lightbox settings or item count
	// actually change. In the editor the widget re-renders on every setting
	// toggle (Enable / Animation / Counter), so a plain once-only guard would
	// freeze the first config; comparing the signature keeps it live without
	// rebinding on unrelated mutations.
	const signature = [ lightboxOn ? '1' : '0', animation, showCounter ? '1' : '0', items.length ].join( '|' );
	if ( gallery.dataset.aaeGallerySig === signature ) {
		return;
	}

	// Tear down any previous click bindings before (re)binding.
	if ( gallery.__aaeGalleryCleanup ) {
		gallery.__aaeGalleryCleanup();
		gallery.__aaeGalleryCleanup = null;
	}

	gallery.dataset.aaeGallerySig = signature;

	if ( ! lightboxOn || ! items.length ) {
		return; // Lightbox disabled or nothing to show.
	}

	// Resolve each item's largest image URL.
	const images = items.map( ( item ) => {
		const img = item.querySelector( 'img' );
		if ( ! img ) {
			return '';
		}
		return img.getAttribute( 'data-large-src' ) || img.currentSrc || img.src || '';
	} );

	let currentIndex = 0;
	let lightboxEl = null;
	let isFullscreen = false;
	let isTransitioning = false;

	function createLightbox() {
		const el = document.createElement( 'div' );
		el.className = 'aae-lightbox aae-lightbox--' + animation;
		// Buttons are built empty (no hardcoded icon markup); the native
		// Atomic_Svg icon nodes are cloned in below via fillIconButton().
		el.setAttribute( 'role', 'dialog' );
		el.setAttribute( 'aria-modal', 'true' );
		el.setAttribute( 'aria-label', 'Image lightbox' );
		el.innerHTML =
			'<div class="aae-lightbox__overlay"></div>' +
			'<div class="aae-lightbox__content">' +
				'<div class="aae-lightbox__toolbar">' +
					( showCounter ? '<span class="aae-lightbox__counter" aria-live="polite"></span>' : '' ) +
					'<button class="aae-lightbox__btn aae-lightbox__fullscreen" type="button" aria-label="Toggle fullscreen"></button>' +
					'<button class="aae-lightbox__btn aae-lightbox__close" type="button" aria-label="Close lightbox"></button>' +
				'</div>' +
				'<div class="aae-lightbox__stage">' +
					'<img class="aae-lightbox__image" src="" alt="" />' +
				'</div>' +
				'<button class="aae-lightbox__btn aae-lightbox__prev" type="button" aria-label="Previous image"></button>' +
				'<button class="aae-lightbox__btn aae-lightbox__next" type="button" aria-label="Next image"></button>' +
			'</div>';

		fillIconButton( gallery, el.querySelector( '.aae-lightbox__fullscreen' ), 'fullscreen' );
		fillIconButton( gallery, el.querySelector( '.aae-lightbox__close' ), 'close' );
		fillIconButton( gallery, el.querySelector( '.aae-lightbox__prev' ), 'prev' );
		fillIconButton( gallery, el.querySelector( '.aae-lightbox__next' ), 'next' );

		document.body.appendChild( el );
		return el;
	}

	function showImage( index, direction ) {
		if ( isTransitioning ) {
			return;
		}
		const img = lightboxEl.querySelector( '.aae-lightbox__image' );
		const stage = lightboxEl.querySelector( '.aae-lightbox__stage' );

		if ( animation === 'slide' && typeof direction !== 'undefined' ) {
			isTransitioning = true;
			const outClass = direction === 'next' ? 'aae-slide-out-left' : 'aae-slide-out-right';
			const inClass = direction === 'next' ? 'aae-slide-in-right' : 'aae-slide-in-left';
			img.classList.add( outClass );
			setTimeout( function () {
				img.classList.remove( outClass );
				img.src = images[ index ];
				img.classList.add( inClass );
				setTimeout( function () {
					img.classList.remove( inClass );
					isTransitioning = false;
				}, 350 );
			}, 350 );
		} else if ( lightboxEl.classList.contains( 'aae-lightbox--active' ) ) {
			isTransitioning = true;
			stage.classList.add( 'aae-lightbox__stage--fade' );
			setTimeout( function () {
				img.src = images[ index ];
				stage.classList.remove( 'aae-lightbox__stage--fade' );
				isTransitioning = false;
			}, 250 );
		} else {
			img.src = images[ index ];
		}

		currentIndex = index;
		updateCounter();
		updateNavVisibility();
	}

	function updateCounter() {
		const counter = lightboxEl.querySelector( '.aae-lightbox__counter' );
		if ( counter ) {
			counter.textContent = ( currentIndex + 1 ) + ' / ' + images.length;
		}
	}

	function updateNavVisibility() {
		const prevBtn = lightboxEl.querySelector( '.aae-lightbox__prev' );
		const nextBtn = lightboxEl.querySelector( '.aae-lightbox__next' );
		if ( images.length <= 1 ) {
			prevBtn.style.display = 'none';
			nextBtn.style.display = 'none';
		}
	}

	let lastFocused = null;

	function openLightbox( index ) {
		if ( ! lightboxEl ) {
			lightboxEl = createLightbox();
			bindLightboxEvents();
		}
		// Remember what had focus so we can restore it on close (a11y).
		lastFocused = document.activeElement;
		currentIndex = index;
		showImage( index );
		void lightboxEl.offsetHeight; // force reflow before activating.
		lightboxEl.classList.add( 'aae-lightbox--active' );
		document.body.style.overflow = 'hidden';
		// Move focus into the dialog for keyboard + screen-reader users.
		const closeBtn = lightboxEl.querySelector( '.aae-lightbox__close' );
		if ( closeBtn ) {
			closeBtn.focus();
		}
	}

	function closeLightbox() {
		if ( ! lightboxEl ) {
			return;
		}
		lightboxEl.classList.remove( 'aae-lightbox--active' );
		document.body.style.overflow = '';
		isTransitioning = false;
		if ( isFullscreen ) {
			exitFullscreen();
		}
		// Restore focus to the trigger the user came from.
		if ( lastFocused && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
		}
	}

	function goNext() {
		if ( images.length <= 1 ) {
			return;
		}
		showImage( ( currentIndex + 1 ) % images.length, 'next' );
	}

	function goPrev() {
		if ( images.length <= 1 ) {
			return;
		}
		showImage( ( currentIndex - 1 + images.length ) % images.length, 'prev' );
	}

	function toggleFullscreen() {
		const content = lightboxEl.querySelector( '.aae-lightbox__content' );
		if ( ! isFullscreen ) {
			if ( content.requestFullscreen ) {
				content.requestFullscreen();
			} else if ( content.webkitRequestFullscreen ) {
				content.webkitRequestFullscreen();
			} else if ( content.msRequestFullscreen ) {
				content.msRequestFullscreen();
			}
			isFullscreen = true;
		} else {
			exitFullscreen();
		}
	}

	function exitFullscreen() {
		if ( document.exitFullscreen ) {
			document.exitFullscreen();
		} else if ( document.webkitExitFullscreen ) {
			document.webkitExitFullscreen();
		} else if ( document.msExitFullscreen ) {
			document.msExitFullscreen();
		}
		isFullscreen = false;
	}

	function onKeyDown( e ) {
		if ( ! lightboxEl || ! lightboxEl.classList.contains( 'aae-lightbox--active' ) ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			closeLightbox();
		} else if ( e.key === 'ArrowRight' ) {
			goNext();
		} else if ( e.key === 'ArrowLeft' ) {
			goPrev();
		} else if ( e.key === 'Tab' ) {
			// Trap focus within the dialog.
			const focusable = Array.from(
				lightboxEl.querySelectorAll( '.aae-lightbox__btn' )
			).filter( ( b ) => b.offsetParent !== null );
			if ( ! focusable.length ) {
				return;
			}
			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	}

	function bindLightboxEvents() {
		lightboxEl.querySelector( '.aae-lightbox__close' ).addEventListener( 'click', closeLightbox );
		lightboxEl.querySelector( '.aae-lightbox__overlay' ).addEventListener( 'click', closeLightbox );

		lightboxEl.querySelector( '.aae-lightbox__prev' ).addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			goPrev();
		} );
		lightboxEl.querySelector( '.aae-lightbox__next' ).addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			goNext();
		} );
		lightboxEl.querySelector( '.aae-lightbox__fullscreen' ).addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			toggleFullscreen();
		} );

		document.addEventListener( 'keydown', onKeyDown );
		document.addEventListener( 'fullscreenchange', function () {
			if ( ! document.fullscreenElement ) {
				isFullscreen = false;
			}
		} );

		// Touch / swipe.
		let touchStartX = 0;
		const stage = lightboxEl.querySelector( '.aae-lightbox__stage' );
		stage.addEventListener( 'touchstart', function ( e ) {
			touchStartX = e.changedTouches[ 0 ].screenX;
		}, { passive: true } );
		stage.addEventListener( 'touchend', function ( e ) {
			const diff = touchStartX - e.changedTouches[ 0 ].screenX;
			if ( Math.abs( diff ) > 50 ) {
				if ( diff > 0 ) {
					goNext();
				} else {
					goPrev();
				}
			}
		}, { passive: true } );
	}

	const boundItems = [];
	items.forEach( function ( item, index ) {
		if ( ! images[ index ] ) {
			return;
		}
		const onClick = function ( e ) {
			// Let genuine links inside the item work; the item itself triggers.
			if ( e.target.closest( 'a[href]:not([href="#"])' ) ) {
				return;
			}
			e.preventDefault();
			openLightbox( index );
		};
		const onKey = function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar' ) {
				e.preventDefault();
				openLightbox( index );
			}
		};
		item.addEventListener( 'click', onClick );
		item.addEventListener( 'keydown', onKey );
		// Make the item a keyboard-reachable button for a11y.
		item.style.cursor = 'zoom-in';
		item.setAttribute( 'role', 'button' );
		item.setAttribute( 'tabindex', '0' );
		if ( ! item.getAttribute( 'aria-label' ) ) {
			item.setAttribute( 'aria-label', 'Open image ' + ( index + 1 ) + ' in lightbox' );
		}
		boundItems.push( { item, onClick, onKey } );
	} );

	// Cleanup: unbind handlers, drop a11y attrs, remove the appended lightbox
	// DOM on re-init.
	gallery.__aaeGalleryCleanup = function () {
		boundItems.forEach( function ( b ) {
			b.item.removeEventListener( 'click', b.onClick );
			b.item.removeEventListener( 'keydown', b.onKey );
			b.item.style.cursor = '';
			b.item.removeAttribute( 'role' );
			b.item.removeAttribute( 'tabindex' );
			b.item.removeAttribute( 'aria-label' );
		} );
		document.removeEventListener( 'keydown', onKeyDown );
		if ( lightboxEl && lightboxEl.parentNode ) {
			lightboxEl.parentNode.removeChild( lightboxEl );
		}
		lightboxEl = null;
	};
}

// ── Register with Elementor's atomic frontend-handlers ──────────────────────
register( {
	elementType: 'e-aae-a-image-gallery',
	id: 'aae-a-image-gallery-handler',
	callback: ( { element } ) => {
		const gallery = element.classList.contains( 'aae-a-image-gallery' )
			? element
			: element.querySelector( '.aae-a-image-gallery' );
		initGallery( gallery );
	},
} );

// Fallback bootstrap for the editor preview, where the frontend-handler
// callback may not fire. Idempotent (guarded per gallery via dataset flag).
const bootstrap = ( doc ) => {
	if ( ! doc ) {
		return;
	}
	doc.querySelectorAll( '.aae-a-image-gallery' ).forEach( initGallery );

	if ( doc.__aaeGalleryBootstrapped ) {
		return;
	}
	doc.__aaeGalleryBootstrapped = true;

	const win = doc.defaultView || window;
	const observer = new win.MutationObserver( () => {
		doc.querySelectorAll( '.aae-a-image-gallery' ).forEach( initGallery );
	} );
	observer.observe( doc.documentElement || doc.body, { childList: true, subtree: true } );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', () => bootstrap( document ) );
} else {
	bootstrap( document );
}
