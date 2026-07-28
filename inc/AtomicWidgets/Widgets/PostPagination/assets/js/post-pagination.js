/* eslint-env browser */

/**
 * AAE Post Pagination — frontend runtime.
 *
 * The Prev/Next buttons are server-rendered real `<a href>` anchors — they
 * work with zero JS. This file layers optional enhancements on top, each
 * independently toggleable per instance via data-aae-pp-config:
 *
 *   - Prefetch on hover     : <link rel=prefetch> the target on first hover/touch.
 *   - Keyboard arrow nav    : Left/Right navigates prev/next (first bound
 *                             instance with it enabled only — see initKeyboard).
 *   - Mobile swipe gestures : swipe left/right on the page navigates next/prev
 *                             (same single-instance scoping as keyboard).
 *   - Sticky/side-arrow reveal: toggles a class once the page has scrolled
 *                             past the configured offset (sticky_bar /
 *                             side_arrows display modes only).
 *   - Hover Preview Card    : shows a real, user-customizable nested element
 *                             (see initHoverPreview below) on hover/focus.
 */

(function () {
	'use strict';

	function parseConfig(el) {
		try {
			return JSON.parse(el.getAttribute('data-aae-pp-config') || '{}');
		} catch (e) {
			return {};
		}
	}

	function isTypingTarget(el) {
		if (!el) {
			return false;
		}
		var tag = el.tagName ? el.tagName.toLowerCase() : '';
		return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
	}

	/* ---------------------------------------------------------------------
	 * Prefetch on hover
	 * ------------------------------------------------------------------- */

	var prefetched = Object.create(null);

	function prefetch(url) {
		if (!url || prefetched[url]) {
			return;
		}
		prefetched[url] = true;
		var link = document.createElement('link');
		link.rel = 'prefetch';
		link.href = url;
		document.head.appendChild(link);
	}

	function initPrefetch(root, cfg) {
		if (!cfg.prefetch) {
			return;
		}
		var links = root.querySelectorAll('[data-aae-nav]:not(.aae-pp-disabled)');
		Array.prototype.forEach.call(links, function (a) {
			var url = a.getAttribute('data-aae-url');
			if (!url) {
				return;
			}
			a.addEventListener('mouseenter', function () { prefetch(url); }, { passive: true });
			a.addEventListener('touchstart', function () { prefetch(url); }, { passive: true });
			a.addEventListener('focus', function () { prefetch(url); });
		});
	}

	/* ---------------------------------------------------------------------
	 * Keyboard arrow nav + swipe — scoped to ONE primary instance (the first
	 * bound one requesting it). Multiple Post Pagination widgets on one page would
	 * otherwise fight over which one's prev/next a bare arrow-key means.
	 * ------------------------------------------------------------------- */

	var primaryKeyboard = null;
	var primarySwipe = null;

	function navigateTo(url) {
		if (url) {
			window.location.href = url;
		}
	}

	function initKeyboard(cfg) {
		if (!cfg.keyboardNav || primaryKeyboard) {
			return;
		}
		primaryKeyboard = cfg;
		document.addEventListener('keydown', function (e) {
			if (isTypingTarget(e.target)) {
				return;
			}
			if (e.key === 'ArrowLeft' && primaryKeyboard.prev) {
				navigateTo(primaryKeyboard.prev.url);
			} else if (e.key === 'ArrowRight' && primaryKeyboard.next) {
				navigateTo(primaryKeyboard.next.url);
			}
		});
	}

	function initSwipe(cfg) {
		if (!cfg.swipe || primarySwipe) {
			return;
		}
		primarySwipe = cfg;

		var startX = 0;
		var startY = 0;
		var tracking = false;
		var THRESHOLD = 60;

		document.body.addEventListener('touchstart', function (e) {
			if (!e.touches || e.touches.length !== 1 || isTypingTarget(e.target)) {
				tracking = false;
				return;
			}
			tracking = true;
			startX = e.touches[0].clientX;
			startY = e.touches[0].clientY;
		}, { passive: true });

		document.body.addEventListener('touchend', function (e) {
			if (!tracking) {
				return;
			}
			tracking = false;
			var touch = e.changedTouches && e.changedTouches[0];
			if (!touch) {
				return;
			}
			var dx = touch.clientX - startX;
			var dy = touch.clientY - startY;
			if (Math.abs(dx) < THRESHOLD || Math.abs(dx) < Math.abs(dy) * 1.5) {
				return; // too short, or more vertical than horizontal (scrolling).
			}
			if (dx < 0 && primarySwipe.next) {
				navigateTo(primarySwipe.next.url);
			} else if (dx > 0 && primarySwipe.prev) {
				navigateTo(primarySwipe.prev.url);
			}
		}, { passive: true });
	}

	/* ---------------------------------------------------------------------
	 * Hover Preview Card — a REAL, user-customizable atomic element tree
	 * (AAE_A_Post_Pagination_Preview + its Thumbnail/Category/Title/Date/
	 * Author/Excerpt children — see class-aae-a-post-pagination-preview*.php)
	 * nested INSIDE each Prev/Next link and already server-rendered with
	 * that side's real post data. This is pure show/position/hide — no
	 * templating, no escaping, no "which fields are on" logic, since the
	 * user's own child-element choices already decided all of that.
	 *
	 * Being a DESCENDANT of the `<a>`, the card stays part of the link's
	 * hover chain even once positioned elsewhere on screen via `position:
	 * fixed` — CSS :hover follows DOM containment, not visual position.
	 *
	 * ------------------------------------------------------------------- */

	function positionPreviewCard(card, anchor) {
		var rect = anchor.getBoundingClientRect();
		var cardRect = card.getBoundingClientRect();
		var margin = 10;

		var top = rect.top - cardRect.height - margin;
		if (top < margin) {
			top = rect.bottom + margin; // Not enough room above — place below instead.
		}

		var left = rect.left + (rect.width / 2) - (cardRect.width / 2);
		left = Math.max(margin, Math.min(left, window.innerWidth - cardRect.width - margin));
		top = Math.max(margin, Math.min(top, window.innerHeight - cardRect.height - margin));

		// `top`/`left` above are computed relative to the VIEWPORT (that's
		// what window.innerWidth/innerHeight clamping assumes). But a plain
		// `position: fixed` element is only viewport-relative when NO
		// ancestor has a transform/filter/perspective/will-change:transform —
		// any one of those on ANY ancestor (very common: theme entrance
		// animations, sticky headers, Elementor's own section effects)
		// hijacks the containing block, silently turning our "fixed" card
		// into something positioned relative to THAT ancestor instead —
		// still visually correct-looking in isolation, but wildly off once
		// the page has any such ancestor. `offsetParent` reveals exactly
		// that ancestor when it happens (null when there isn't one, i.e.
		// genuinely viewport-relative), so converting into ITS coordinate
		// space here keeps the math correct either way.
		var containingBox = card.offsetParent
			? card.offsetParent.getBoundingClientRect()
			: { top: 0, left: 0 };

		card.style.top = (top - containingBox.top) + 'px';
		card.style.left = (left - containingBox.left) + 'px';
	}

	function initHoverPreview(root, cfg) {
		if (!cfg.hoverPreviewEnabled) {
			return;
		}
		// Hover cards are a desktop affordance — coarse-pointer/no-hover
		// devices (touch) skip entirely rather than fighting tap-to-navigate.
		if (window.matchMedia && !window.matchMedia('(hover: hover)').matches) {
			return;
		}

		var links = root.querySelectorAll('[data-aae-nav]:not(.aae-pp-disabled)');
		var hideTimer = null;

		function show(link, card) {
			window.clearTimeout(hideTimer);
			card.classList.add('aae-pp-preview-visible');
			positionPreviewCard(card, link);
			// Re-position next frame too: the thumbnail <img> can change the
			// card's height once it decodes, shifting where "above" should be.
			window.requestAnimationFrame(function () { positionPreviewCard(card, link); });
		}

		function hide(card) {
			hideTimer = window.setTimeout(function () {
				card.classList.remove('aae-pp-preview-visible');
			}, 80);
		}

		Array.prototype.forEach.call(links, function (link) {
			var card = link.querySelector('.aae-a-post-pagination-preview');
			if (!card) {
				return; // Piece deleted by the user, or a pre-existing page saved before this element existed.
			}

			link.addEventListener('mouseenter', function () { show(link, card); }, { passive: true });
			link.addEventListener('mouseleave', function () { hide(card); }, { passive: true });
			link.addEventListener('focus', function () { show(link, card); });
			link.addEventListener('blur', function () { hide(card); });
		});
	}

	/* ---------------------------------------------------------------------
	 * Sticky bar / side arrows — reveal on scroll
	 * ------------------------------------------------------------------- */

	function initScrollReveal(root, cfg) {
		if (cfg.displayMode === 'inline') {
			return null;
		}
		root.classList.add('aae-pp-reveal');
		var offset = cfg.revealOffset || 0;

		function check() {
			var visible = window.scrollY > offset;
			root.classList.toggle('aae-pp-visible', visible);
		}

		check();
		return check;
	}

	/* ---------------------------------------------------------------------
	 * Bind + global scroll loop
	 * ------------------------------------------------------------------- */

	var scrollHooks = [];
	var scrollTicking = false;

	function onScroll() {
		if (scrollTicking) {
			return;
		}
		scrollTicking = true;
		window.requestAnimationFrame(function () {
			scrollTicking = false;
			scrollHooks.forEach(function (fn) { fn(); });
		});
	}

	function bindInstance(root) {
		if (root.__aaePpBound) {
			return;
		}
		root.__aaePpBound = true;

		var cfg = parseConfig(root);

		initPrefetch(root, cfg);
		initKeyboard(cfg);
		initSwipe(cfg);
		initHoverPreview(root, cfg);

		var revealCheck = initScrollReveal(root, cfg);
		if (revealCheck) {
			scrollHooks.push(revealCheck);
		}
	}

	function init() {
		var nodes = document.querySelectorAll('.aae-a-post-pagination[data-aae-post-pagination]');
		Array.prototype.forEach.call(nodes, bindInstance);

		if (scrollHooks.length) {
			window.addEventListener('scroll', onScroll, { passive: true });
			window.addEventListener('resize', onScroll, { passive: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
