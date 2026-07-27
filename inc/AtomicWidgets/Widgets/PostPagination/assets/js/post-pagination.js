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
 *   - Infinite scroll       : AJAX-appends the next post's title+content when
 *                             the reader nears the bottom, updates the URL via
 *                             pushState, and clones the trigger widget after
 *                             the newly-loaded post so scrolling can keep
 *                             chaining forward.
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
	 * Infinite scroll
	 * ------------------------------------------------------------------- */

	function resolveTarget(root, cfg) {
		if (cfg.infiniteTarget) {
			var custom = document.querySelector(cfg.infiniteTarget);
			if (custom) {
				return custom;
			}
		}
		return root;
	}

	function requestNext(cfg) {
		var body = new window.FormData();
		body.append('action', 'aae_post_pagination_load');
		body.append('nonce', cfg.nonce);
		body.append('post_id', cfg.next.id);
		body.append('settings', JSON.stringify(cfg.settings || {}));
		return window.fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); });
	}

	function buildLoadedPost(data) {
		var article = document.createElement('article');
		article.className = 'aae-pp-loaded-post';

		var h = document.createElement('h2');
		h.className = 'aae-pp-loaded-title';
		h.textContent = data.title;
		article.appendChild(h);

		var body = document.createElement('div');
		body.className = 'aae-pp-loaded-content';
		body.innerHTML = data.content; // phpcs-ignore: server-rendered, same pipeline as the post's own page.
		article.appendChild(body);

		return article;
	}

	function initInfiniteScroll(root, cfg, scrollHooks) {
		if (!cfg.infiniteScroll) {
			return;
		}

		var state = { current: cfg, busy: false, done: !cfg.next };

		function maybeLoad() {
			if (state.busy || state.done) {
				return;
			}
			var threshold = cfg.infiniteThreshold || 600;
			var nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - threshold);
			if (!nearBottom) {
				return;
			}

			state.busy = true;
			requestNext(state.current).then(function (res) {
				if (!res || !res.success || !res.data) {
					state.done = true;
					return;
				}
				var data = res.data;
				var target = resolveTarget(root, cfg);
				var article = buildLoadedPost(data);

				var clone = root.cloneNode(true);
				clone.removeAttribute('data-aae-pp-bound');
				var cloneCfg = Object.assign({}, cfg, { next: data.next, postId: data.post_id });
				clone.setAttribute('data-aae-pp-config', JSON.stringify(cloneCfg));
				article.appendChild(clone);

				if (target.parentNode) {
					target.parentNode.insertBefore(article, target.nextSibling);
				}

				bindInstance(clone);

				try {
					document.title = data.title;
					window.history.pushState({ aaePostPagination: data.post_id }, '', data.permalink);
				} catch (e) { /* no-op */ }

				state.current = cloneCfg;
				state.done = !data.next;

				if (state.done && cfg.noMoreText) {
					var end = document.createElement('div');
					end.className = 'aae-pp-end-of-posts';
					end.textContent = cfg.noMoreText;
					article.appendChild(end);
				}
			}).catch(function () {
				state.done = true;
			}).then(function () {
				state.busy = false;
			});
		}

		scrollHooks.push(maybeLoad);
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

		var revealCheck = initScrollReveal(root, cfg);
		if (revealCheck) {
			scrollHooks.push(revealCheck);
		}

		initInfiniteScroll(root, cfg, scrollHooks);
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
