/* eslint-env browser */

/**
 * AAE Loop Grid — frontend pagination runtime.
 *
 * The grid + first page are server-rendered. This wires the Pagination widget's
 * Prev / Page-Numbers / Next / Load-More controls to either:
 *   - AJAX   : fetch the requested page's loop-item cells (full HTML, styles
 *              intact) via `aae_loop_grid_page` and swap/append them, updating
 *              the URL (?aae_page=N) without a reload; or
 *   - Reload : navigate to ?aae_page=N and let the server render it.
 *
 * All config (ajax url, nonce, grid id, post id, current/total pages, method,
 * query) travels inline on the pagination wrapper's data-aae-config. There is
 * no pagination "type" — the runtime is DOM-driven and wires whichever pieces
 * (Prev/Next, Numbers, Load More) are present + visible.
 */

(function () {
	'use strict';

	var FADE_MS = 200;

	function parseConfig(el) {
		try {
			return JSON.parse(el.getAttribute('data-aae-config') || '{}');
		} catch (e) {
			return {};
		}
	}

	/** The grid container (.aae-a-loop-grid) that this pagination controls. */
	function findGrid(pagination) {
		var wrap = pagination.closest('.aae-a-loop-grid-wrap');
		if (!wrap) {
			return null;
		}
		return wrap.querySelector('.aae-a-loop-grid') || wrap;
	}

	function setUrlPage(paged) {
		try {
			var url = new URL(window.location.href);
			if (paged > 1) {
				url.searchParams.set('aae_page', String(paged));
			} else {
				url.searchParams.delete('aae_page');
			}
			window.history.pushState({ aaePage: paged }, '', url.toString());
		} catch (e) { /* no-op */ }
	}

	function request(cfg, paged) {
		var body = new window.FormData();
		body.append('action', 'aae_loop_grid_page');
		body.append('nonce', cfg.nonce);
		body.append('post_id', cfg.postId);
		body.append('grid_id', cfg.grid);
		body.append('paged', String(paged));
		// Current Query source: the archive's query vars were captured into the
		// config at render time — post them back so the AJAX request (which has
		// no archive context) rebuilds the same query.
		if (cfg.query && cfg.query.qv && Object.keys(cfg.query.qv).length) {
			body.append('qv', JSON.stringify(cfg.query.qv));
		}
		return window.fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); });
	}

	function fadeReplace(grid, html) {
		grid.style.transition = 'opacity ' + FADE_MS + 'ms ease';
		grid.style.opacity = '0';
		return new Promise(function (resolve) {
			window.setTimeout(function () {
				grid.innerHTML = html;
				// force reflow then fade back in
				void grid.offsetHeight;
				grid.style.opacity = '1';
				resolve();
			}, FADE_MS);
		});
	}

	function appendCells(grid, html) {
		var tmp = document.createElement('div');
		tmp.innerHTML = html;
		var frag = document.createDocumentFragment();
		while (tmp.firstChild) {
			var node = tmp.firstChild;
			if (node.nodeType === 1) {
				node.style.opacity = '0';
				node.style.transition = 'opacity ' + FADE_MS + 'ms ease';
			}
			frag.appendChild(node);
		}
		grid.appendChild(frag);
		// fade the just-added element nodes in
		window.requestAnimationFrame(function () {
			Array.prototype.forEach.call(grid.children, function (c) {
				if (c.style && c.style.opacity === '0') {
					c.style.opacity = '1';
				}
			});
		});
	}

	/** Re-render the numbers list (smart-truncate) client-side after an AJAX page change. */
	function smartPages(current, total) {
		if (total <= 7) {
			var all = [];
			for (var i = 1; i <= Math.max(1, total); i++) {
				all.push(i);
			}
			return all;
		}
		var pages = [1];
		var start = Math.max(2, current - 1);
		var end = Math.min(total - 1, current + 1);
		if (start > 2) {
			pages.push('...');
		}
		for (var p = start; p <= end; p++) {
			pages.push(p);
		}
		if (end < total - 1) {
			pages.push('...');
		}
		pages.push(total);
		return pages;
	}

	function pageUrl(page) {
		var url = new URL(window.location.href);
		if (page > 1) {
			url.searchParams.set('aae_page', String(page));
		} else {
			url.searchParams.delete('aae_page');
		}
		return url.toString();
	}

	/**
	 * The page numbers are a repeat of ONE authored atomic template (server-side,
	 * AAE_A_Loop_Number::print_content), so every link carries the same classes
	 * and styling. To keep that styling after an AJAX page change we don't build
	 * markup from scratch \u2014 we clone the FIRST rendered number as the template and
	 * stamp each smart-truncated page onto a fresh clone. The template is cached on
	 * the container the first time we see it (before any rebuild mutates the list).
	 */
	function numberTemplate(numbersEl) {
		if (numbersEl.__aaeNumTpl) {
			return numbersEl.__aaeNumTpl;
		}
		var first = numbersEl.querySelector('.aae-a-loop-number');
		if (!first) {
			return null;
		}
		var tpl = first.cloneNode(true);
		// Neutralize per-instance state so the cached template is a clean base.
		tpl.classList.remove('is-active', 'e--selected', 'is-gap', 'aae-a-loop-num-gap');
		tpl.removeAttribute('aria-current');
		tpl.removeAttribute('aria-hidden');
		tpl.removeAttribute('tabindex');
		tpl.removeAttribute('href');
		tpl.removeAttribute('data-aae-page');
		numbersEl.__aaeNumTpl = tpl;
		return tpl;
	}

	function makeNumber(tpl, item, current) {
		var el = tpl.cloneNode(true);
		var isGap = item === '...';
		var isCurrent = item === current;

		el.classList.toggle('is-gap', isGap);
		el.classList.toggle('aae-a-loop-num-gap', isGap);
		el.classList.toggle('is-active', isCurrent);
		el.classList.toggle('e--selected', isCurrent);

		if (isGap) {
			el.textContent = '\u2026';
			el.removeAttribute('href');
			el.removeAttribute('data-aae-page');
			el.removeAttribute('aria-current');
			el.setAttribute('aria-hidden', 'true');
			el.setAttribute('tabindex', '-1');
			return el;
		}

		el.textContent = String(item);
		el.href = pageUrl(item);
		el.setAttribute('data-aae-page', String(item));
		el.setAttribute('aria-label', 'Go to page ' + item);
		el.removeAttribute('aria-hidden');
		el.removeAttribute('tabindex');
		if (isCurrent) {
			el.setAttribute('aria-current', 'page');
		} else {
			el.removeAttribute('aria-current');
		}
		return el;
	}

	/** Rebuild the numbers list (smart-truncated) after an AJAX page change. */
	function rebuildNumbers(numbersEl, current, total) {
		if (!numbersEl) {
			return;
		}
		var tpl = numberTemplate(numbersEl);
		if (!tpl) {
			return;
		}
		var items = smartPages(current, total);
		var frag = document.createDocumentFragment();
		items.forEach(function (item) {
			frag.appendChild(makeNumber(tpl, item, current));
		});
		// Replace only the number links; leave any other children untouched.
		numbersEl.querySelectorAll('.aae-a-loop-number').forEach(function (n) {
			n.remove();
		});
		numbersEl.appendChild(frag);
	}

	function updatePrevNextState(pagination, current, total) {
		var prev = pagination.querySelector('[data-aae-nav="prev"]');
		var next = pagination.querySelector('[data-aae-nav="next"]');
		if (prev) {
			prev.classList.toggle('is-disabled', current <= 1);
		}
		if (next) {
			next.classList.toggle('is-disabled', current >= total);
		}
		// Edge-state classes: CSS hides Prev on the first page and Next /
		// Load More on the last (frontend only — the stylesheet skips the
		// editor). The twig stamps the initial state server-side; this keeps
		// it in sync across AJAX page changes.
		pagination.classList.toggle('aae-pg-first', current <= 1);
		pagination.classList.toggle('aae-pg-last', current >= total);
	}

	function goToPage(ctx, paged) {
		var cfg = ctx.cfg;
		if (paged < 1 || paged > ctx.total || paged === ctx.current || ctx.busy) {
			return;
		}

		// Page reload mode.
		if (cfg.method === 'reload') {
			var url = new URL(window.location.href);
			if (paged > 1) {
				url.searchParams.set('aae_page', String(paged));
			} else {
				url.searchParams.delete('aae_page');
			}
			window.location.href = url.toString();
			return;
		}

		// AJAX mode.
		ctx.busy = true;
		ctx.pagination.classList.add('is-loading');
		request(cfg, paged).then(function (res) {
			if (res && res.success && res.data) {
				fadeReplace(ctx.grid, res.data.html).then(function () {
					ctx.current = res.data.paged;
					ctx.total = res.data.max_pages;
					rebuildNumbers(ctx.numbersEl, ctx.current, ctx.total);
					updatePrevNextState(ctx.pagination, ctx.current, ctx.total);
					setUrlPage(ctx.current);
				});
			}
		}).catch(function () { /* no-op */ }).then(function () {
			ctx.busy = false;
			ctx.pagination.classList.remove('is-loading');
		});
	}

	function loadMore(ctx) {
		var cfg = ctx.cfg;
		if (ctx.busy || ctx.current >= ctx.total) {
			return;
		}
		var nextPage = ctx.current + 1;
		ctx.busy = true;
		ctx.pagination.classList.add('is-loading');
		if (ctx.loadMoreEl) {
			ctx.loadMoreEl.classList.add('is-loading');
		}
		request(cfg, nextPage).then(function (res) {
			if (res && res.success && res.data) {
				appendCells(ctx.grid, res.data.html);
				ctx.current = res.data.paged;
				ctx.total = res.data.max_pages;
				if (ctx.current >= ctx.total && ctx.loadMoreEl) {
					ctx.loadMoreEl.style.display = 'none';
				}
				// Keep Prev/Next + the edge-state classes in sync too —
				// reaching the last chunk must hide Next the same way paged
				// navigation does.
				rebuildNumbers(ctx.numbersEl, ctx.current, ctx.total);
				updatePrevNextState(ctx.pagination, ctx.current, ctx.total);
			}
		}).catch(function () { /* no-op */ }).then(function () {
			ctx.busy = false;
			ctx.pagination.classList.remove('is-loading');
			if (ctx.loadMoreEl) {
				ctx.loadMoreEl.classList.remove('is-loading');
			}
		});
	}

	function initPagination(pagination) {
		if (pagination.__aaeBound) {
			return;
		}
		pagination.__aaeBound = true;

		var cfg = parseConfig(pagination);
		var current = cfg.current || 1;
		var total = cfg.total || 1;
		var numbersEl = pagination.querySelector('.aae-a-loop-numbers');

		// The server already rendered the numbers for the current page. Just cache
		// the template now (from that pristine markup, before any AJAX rebuild
		// mutates the list) so later rebuilds keep the authored styling. Don't
		// rebuild here — that would needlessly re-generate identical DOM.
		if (numbersEl && !document.body.classList.contains('elementor-editor-active')) {
			numberTemplate(numbersEl);
		}

		var grid = findGrid(pagination);
		if (!grid || !cfg.ajaxUrl) {
			return;
		}

		var ctx = {
			pagination: pagination,
			grid: grid,
			cfg: cfg,
			current: current,
			total: total,
			busy: false,
			numbersEl: numbersEl,
			loadMoreEl: pagination.querySelector('[data-aae-loadmore]'),
		};

		updatePrevNextState(pagination, ctx.current, ctx.total);

		pagination.addEventListener('click', function (e) {
			// Number link.
			var num = e.target.closest('[data-aae-page]');
			if (num && pagination.contains(num)) {
				e.preventDefault();
				goToPage(ctx, parseInt(num.getAttribute('data-aae-page'), 10));
				return;
			}
			// Prev / Next.
			var nav = e.target.closest('[data-aae-nav]');
			if (nav && pagination.contains(nav)) {
				e.preventDefault();
				if (nav.classList.contains('is-disabled')) {
					return;
				}
				var role = nav.getAttribute('data-aae-nav');
				goToPage(ctx, role === 'prev' ? ctx.current - 1 : ctx.current + 1);
				return;
			}
			// Load More.
			var lm = e.target.closest('[data-aae-loadmore]');
			if (lm && pagination.contains(lm)) {
				e.preventDefault();
				loadMore(ctx);
			}
		});
	}

	function init() {
		// DOM-driven: bind every pagination bar. Behaviour follows whichever
		// pieces are present + visible (Prev/Next / Numbers / Load More) — there
		// is no "type" to filter on. The click handler dispatches by the clicked
		// element's data-attr, so a bar with no visible controls simply does
		// nothing.
		var nodes = document.querySelectorAll('.aae-a-loop-pagination[data-aae-pagination]');
		Array.prototype.forEach.call(nodes, initPagination);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
