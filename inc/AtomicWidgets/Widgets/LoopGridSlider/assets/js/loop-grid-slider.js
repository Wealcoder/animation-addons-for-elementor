/* eslint-env browser */

/**
 * AAE Loop Grid Slider — load-more bridge.
 *
 * The slider MOTION (transform / autoplay / effect / loop) is owned entirely by
 * the shared nested-slider runtime (aae-effect-nested-slider). This file adds
 * ONLY the paging layer: it wires the Pagination child's Load-More / Prev / Next
 * / page-number controls to the existing `aae_loop_grid_page` AJAX endpoint,
 * fetches the next page of loop-item cells (already carrying `.aae-a-slide`),
 * appends them INTO the slider track, and then asks the shared slider runtime to
 * re-bind so it recounts slides, rebuilds loop clones, and recomputes widths.
 *
 * There is no second slider runtime here — the re-bind goes through the single
 * registry surface `window.AAEADDON.rebind(el)` (alias `window.aaeAtomicAnimations`).
 *
 * Design notes:
 *   - Load More is the natural fit for a slider (grow the slide set). Paged
 *     navigation (Prev/Next/numbers) is also supported: it REPLACES the track's
 *     slides with the requested page, then re-binds and resets to the first
 *     slide — useful for very large post sets where you don't want every post in
 *     the DOM at once.
 *   - All config (ajax url, nonce, grid id, post id, current/total pages,
 *     method, query) travels inline on the pagination wrapper's data-aae-config,
 *     identical to the static Loop Grid.
 */

(function () {
	'use strict';

	function parseConfig(el) {
		try {
			return JSON.parse(el.getAttribute('data-aae-config') || '{}');
		} catch (e) {
			return {};
		}
	}

	/** The slider wrapper (.aae-a-slider) this pagination belongs to. */
	function findSlider(pagination) {
		return pagination.closest('.aae-a-loop-grid-slider') || pagination.closest('.aae-a-slider');
	}

	/** The .aae-slider-track inside a slider wrapper. */
	function findTrack(slider) {
		return slider ? slider.querySelector('.aae-slider-track') : null;
	}

	/** Ask the single shared slider runtime to re-bind this element. */
	function rebindSlider(slider) {
		var api = window.AAEADDON || window.aaeAtomicAnimations;
		if (api && typeof api.rebind === 'function') {
			// Defer so appended nodes are laid out before the runtime measures them.
			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(function () {
					try {
						api.rebind(slider);
					} catch (e) { /* never let a rebind failure break paging */ }
				});
			});
		}
	}

	function request(cfg, paged) {
		var body = new window.FormData();
		body.append('action', 'aae_loop_grid_page');
		body.append('nonce', cfg.nonce);
		body.append('post_id', cfg.postId);
		body.append('grid_id', cfg.grid);
		body.append('paged', String(paged));
		if (cfg.query && cfg.query.qv && Object.keys(cfg.query.qv).length) {
			body.append('qv', JSON.stringify(cfg.query.qv));
		}
		return window.fetch(cfg.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
		}).then(function (r) { return r.json(); });
	}

	/** Append the returned cell HTML as new slides into the track. */
	function appendSlides(track, html) {
		var tmp = document.createElement('div');
		tmp.innerHTML = html;
		var frag = document.createDocumentFragment();
		while (tmp.firstChild) {
			frag.appendChild(tmp.firstChild);
		}
		track.appendChild(frag);
	}

	/** Replace all real slides in the track with the returned page's cells. */
	function replaceSlides(track, html) {
		Array.prototype.slice.call(track.querySelectorAll('.aae-a-slide')).forEach(function (s) {
			// Only remove originals, never leave the track empty mid-swap.
			s.parentNode === track && track.removeChild(s);
		});
		appendSlides(track, html);
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
		pagination.classList.toggle('aae-pg-first', current <= 1);
		pagination.classList.toggle('aae-pg-last', current >= total);
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
				appendSlides(ctx.track, res.data.html);
				ctx.current = res.data.paged;
				ctx.total = res.data.max_pages;
				if (ctx.current >= ctx.total && ctx.loadMoreEl) {
					ctx.loadMoreEl.style.display = 'none';
				}
				updatePrevNextState(ctx.pagination, ctx.current, ctx.total);
				rebindSlider(ctx.slider);
			}
		}).catch(function () { /* no-op */ }).then(function () {
			ctx.busy = false;
			ctx.pagination.classList.remove('is-loading');
			if (ctx.loadMoreEl) {
				ctx.loadMoreEl.classList.remove('is-loading');
			}
		});
	}

	function goToPage(ctx, paged) {
		var cfg = ctx.cfg;
		if (paged < 1 || paged > ctx.total || paged === ctx.current || ctx.busy) {
			return;
		}
		ctx.busy = true;
		ctx.pagination.classList.add('is-loading');
		request(cfg, paged).then(function (res) {
			if (res && res.success && res.data) {
				replaceSlides(ctx.track, res.data.html);
				ctx.current = res.data.paged;
				ctx.total = res.data.max_pages;
				updatePrevNextState(ctx.pagination, ctx.current, ctx.total);
				rebindSlider(ctx.slider);
			}
		}).catch(function () { /* no-op */ }).then(function () {
			ctx.busy = false;
			ctx.pagination.classList.remove('is-loading');
		});
	}

	function initPagination(pagination) {
		if (pagination.__aaeSliderBound) {
			return;
		}
		pagination.__aaeSliderBound = true;

		var cfg = parseConfig(pagination);
		var slider = findSlider(pagination);
		var track = findTrack(slider);
		if (!slider || !track || !cfg.ajaxUrl) {
			return;
		}

		var ctx = {
			pagination: pagination,
			slider: slider,
			track: track,
			cfg: cfg,
			current: cfg.current || 1,
			total: cfg.total || 1,
			busy: false,
			loadMoreEl: pagination.querySelector('[data-aae-loadmore]'),
		};

		updatePrevNextState(pagination, ctx.current, ctx.total);

		pagination.addEventListener('click', function (e) {
			var num = e.target.closest('[data-aae-page]');
			if (num && pagination.contains(num)) {
				e.preventDefault();
				goToPage(ctx, parseInt(num.getAttribute('data-aae-page'), 10));
				return;
			}
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
			var lm = e.target.closest('[data-aae-loadmore]');
			if (lm && pagination.contains(lm)) {
				e.preventDefault();
				loadMore(ctx);
			}
		});
	}

	function init() {
		// Only pagination bars that live inside a Loop Grid Slider — the static
		// Loop Grid's own runtime (loop-grid.js) handles the rest.
		var nodes = document.querySelectorAll(
			'.aae-a-loop-grid-slider .aae-a-loop-pagination[data-aae-pagination]'
		);
		Array.prototype.forEach.call(nodes, initPagination);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
