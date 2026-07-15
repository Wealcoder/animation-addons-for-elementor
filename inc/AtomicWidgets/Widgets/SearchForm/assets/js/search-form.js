/* eslint-env browser */

/**
 * AAE Search Form — frontend runtime.
 *
 * Drives the whole composite: mode behaviour (inline / dropdown / fullscreen),
 * the Ajax live search (POSTs to the shared `live_search` admin-ajax endpoint,
 * config travels inline on the wrapper's data-config) and the category / date
 * filter dropdowns. All interaction state is applied as inline styles / class
 * toggles — NO stylesheet ships. In the editor everything is force-shown so each
 * atomic sub-element stays selectable; the open/close behaviour is frontend-only.
 */

(function () {
	'use strict';

	var DEBOUNCE_MS = 300;

	function isEditor() {
		return document.body.classList.contains('elementor-editor-active');
	}

	function css(el, styles) {
		if (el) { Object.assign(el.style, styles); }
	}

	function each(root, sel, fn) {
		if (root) { Array.prototype.forEach.call(root.querySelectorAll(sel), fn); }
	}

	function parseConfig(el) {
		try {
			return JSON.parse(el.getAttribute('data-config') || '{}');
		} catch (e) {
			return {};
		}
	}

	function debounce(fn, wait) {
		var t;
		return function () {
			var ctx = this;
			var args = arguments;
			window.clearTimeout(t);
			t = window.setTimeout(function () { fn.apply(ctx, args); }, wait);
		};
	}

	/* ---- date presets ------------------------------------------------------ */

	function fmt(d) {
		var m = String(d.getMonth() + 1).padStart(2, '0');
		var day = String(d.getDate()).padStart(2, '0');
		return d.getFullYear() + '-' + m + '-' + day;
	}

	function presetRange(preset) {
		var now = new Date();
		var from = new Date(now);
		var to = new Date(now);
		if (preset === 'yesterday') {
			from.setDate(now.getDate() - 1);
			to.setDate(now.getDate() - 1);
		} else if (preset === 'week') {
			from.setDate(now.getDate() - 6);
		} else if (preset === 'month') {
			from.setDate(now.getDate() - 29);
		}
		return { from: fmt(from), to: fmt(to) };
	}

	/* ---- results ----------------------------------------------------------- */

	function doSearch(ctx) {
		var cfg = ctx.cfg;
		if (!ctx.input || !ctx.results || !cfg.ajaxUrl) {
			return;
		}
		var keyword = ctx.input.value.trim();
		if (keyword.length < 1) {
			ctx.results.style.display = 'none';
			ctx.results.innerHTML = '';
			return;
		}

		var body = new window.FormData();
		body.append('action', cfg.action || 'live_search');
		body.append('nonce', cfg.nonce || '');
		body.append('keyword', keyword);

		var from = ctx.wrapper.querySelector('.from-date');
		var to = ctx.wrapper.querySelector('.to-date');
		if (from && from.value) { body.append('from_date', from.value); }
		if (to && to.value) { body.append('to_date', to.value); }
		var cat = ctx.wrapper.querySelector('.aae-selected-category');
		if (cat && cat.value) { body.append('category[]', cat.value); }

		window.fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.text(); })
			.then(function (html) {
				ctx.results.innerHTML = html;
				styleResultItems(ctx.results);
				ctx.results.style.display = 'flex';
			})
			.catch(function () { /* no-op */ });
	}

	/* ---- filter dropdowns -------------------------------------------------- */

	function bindFilter(ctx) {
		var cfg = ctx.cfg;

		// The Date and Category filters are now two separate atomic elements
		// (each still carries its .date-container / .category-container class).
		var dateC = ctx.wrapper.querySelector('.date-container');
		var catC = ctx.wrapper.querySelector('.category-container');
		if (!dateC && !catC) {
			return;
		}

		// Visibility from the parent flags.
		if (!cfg.showFilter && !isEditor()) {
			if (dateC) { dateC.style.display = 'none'; }
			if (catC) { catC.style.display = 'none'; }
			return;
		}
		if (dateC && !cfg.showDate && !isEditor()) { dateC.style.display = 'none'; }
		if (catC && !cfg.showCat && !isEditor()) { catC.style.display = 'none'; }

		if (isEditor()) {
			return; // keep dropdowns inert & visible for editing
		}

		function openDrop(container, dropSel) {
			var drop = container.querySelector(dropSel);
			var isOpen = container.classList.toggle('active');
			if (drop) { drop.style.display = isOpen ? 'block' : 'none'; }
			// Close the sibling dropdown.
			[dateC, catC].forEach(function (other) {
				if (other && other !== container) {
					other.classList.remove('active');
					var od = other.querySelector('.date-dropdown, .category-dropdown');
					if (od) { od.style.display = 'none'; }
				}
			});
		}

		if (dateC) {
			var dToggle = dateC.querySelector('.date-toggle');
			if (dToggle) {
				dToggle.addEventListener('click', function () { openDrop(dateC, '.date-dropdown'); });
			}
			dateC.querySelectorAll('.preset-options li').forEach(function (li) {
				li.addEventListener('click', function () {
					dateC.querySelectorAll('.preset-options li').forEach(function (n) { n.classList.remove('selected'); });
					li.classList.add('selected');
					var range = presetRange(li.getAttribute('data-preset'));
					var from = dateC.querySelector('.from-date');
					var to = dateC.querySelector('.to-date');
					if (from) { from.value = range.from; }
					if (to) { to.value = range.to; }
				});
			});
			var clearD = dateC.querySelector('.clear-btn');
			if (clearD) {
				clearD.addEventListener('click', function () {
					var from = dateC.querySelector('.from-date');
					var to = dateC.querySelector('.to-date');
					if (from) { from.value = ''; }
					if (to) { to.value = ''; }
					dateC.querySelectorAll('.preset-options li').forEach(function (n) { n.classList.remove('selected'); });
					if (cfg.ajax) { doSearch(ctx); }
				});
			}
			var applyD = dateC.querySelector('.apply-btn');
			if (applyD) {
				applyD.addEventListener('click', function () {
					dateC.classList.remove('active');
					var od = dateC.querySelector('.date-dropdown');
					if (od) { od.style.display = 'none'; }
					if (cfg.ajax) { doSearch(ctx); }
				});
			}
		}

		if (catC) {
			var cToggle = catC.querySelector('.category-toggle');
			if (cToggle) {
				cToggle.addEventListener('click', function () { openDrop(catC, '.category-dropdown'); });
			}
			catC.querySelectorAll('.category-list li').forEach(function (li) {
				li.addEventListener('click', function () {
					catC.querySelectorAll('.category-list li').forEach(function (n) { n.classList.remove('selected'); });
					li.classList.add('selected');
				});
			});
			var applyC = catC.querySelector('.apply-cat-btn');
			if (applyC) {
				applyC.addEventListener('click', function () {
					var sel = catC.querySelector('.category-list li.selected');
					var hidden = catC.querySelector('.aae-selected-category');
					if (hidden) { hidden.value = sel ? (sel.getAttribute('data-value') || '') : ''; }
					catC.classList.remove('active');
					var od = catC.querySelector('.category-dropdown');
					if (od) { od.style.display = 'none'; }
					if (cfg.ajax) { doSearch(ctx); }
				});
			}
			var clearC = catC.querySelector('.clear-cat-btn');
			if (clearC) {
				clearC.addEventListener('click', function () {
					catC.querySelectorAll('.category-list li').forEach(function (n) { n.classList.remove('selected'); });
					var all = catC.querySelector('.category-list li[data-value=""]');
					if (all) { all.classList.add('selected'); }
					var hidden = catC.querySelector('.aae-selected-category');
					if (hidden) { hidden.value = ''; }
					if (cfg.ajax) { doSearch(ctx); }
				});
			}
		}
	}

	/* ---- mode (toggle / panel) -------------------------------------------- */

	function iconState(ctx, open) {
		if (!ctx.toggle) {
			return;
		}
		var o = ctx.toggle.querySelector('.aae-a-search-toggle__open');
		var c = ctx.toggle.querySelector('.aae-a-search-toggle__close');
		if (o) { o.style.display = open ? 'none' : 'inline-flex'; }
		if (c) { c.style.display = open ? 'inline-flex' : 'none'; }
	}

	function openPanel(ctx) {
		var panel = ctx.panel;
		if (!panel) {
			return;
		}
		if (ctx.mode === 'fullscreen') {
			Object.assign(panel.style, {
				position: 'fixed',
				top: '0',
				left: '0',
				right: '0',
				bottom: '0',
				width: '100%',
				height: '100%',
				display: 'flex',
				flexDirection: 'column',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: '99999',
			});
			if (ctx.toggle) {
				Object.assign(ctx.toggle.style, {
					position: 'fixed',
					top: '20px',
					right: '20px',
					zIndex: '100000',
				});
			}
		} else { // dropdown
			Object.assign(panel.style, {
				position: 'absolute',
				top: '100%',
				display: 'flex',
				zIndex: '99',
				minWidth: '300px',
			});
			panel.style[ctx.cfg.position === 'right' ? 'right' : 'left'] = '0';
		}
		ctx.wrapper.classList.add('is-open');
		iconState(ctx, true);
		ctx.open = true;
	}

	function closePanel(ctx) {
		if (!ctx.panel) {
			return;
		}
		ctx.panel.style.display = 'none';
		if (ctx.mode === 'fullscreen' && ctx.toggle) {
			ctx.toggle.style.position = '';
			ctx.toggle.style.top = '';
			ctx.toggle.style.right = '';
			ctx.toggle.style.zIndex = '';
		}
		ctx.wrapper.classList.remove('is-open');
		iconState(ctx, false);
		ctx.open = false;
	}

	function bindMode(ctx) {
		if (isEditor()) {
			// Editor: keep the whole tree visible & selectable, no toggling.
			if (ctx.toggle && ctx.mode === 'inline') { ctx.toggle.style.display = 'none'; }
			return;
		}

		if (ctx.mode === 'inline') {
			if (ctx.toggle) { ctx.toggle.style.display = 'none'; }
			return;
		}

		// dropdown / fullscreen: hide the panel until toggled.
		if (ctx.panel) { ctx.panel.style.display = 'none'; }
		iconState(ctx, false);

		if (ctx.toggle) {
			ctx.toggle.addEventListener('click', function () {
				if (ctx.open) { closePanel(ctx); } else { openPanel(ctx); }
			});
			ctx.toggle.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					if (ctx.open) { closePanel(ctx); } else { openPanel(ctx); }
				}
			});
		}

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && ctx.open) { closePanel(ctx); }
		});

		if (ctx.mode === 'dropdown') {
			document.addEventListener('click', function (e) {
				if (ctx.open && !ctx.wrapper.contains(e.target)) { closePanel(ctx); }
			});
		}
	}

	/* ---- default styling (no CSS file — applied inline at runtime) ---------- *
	 * The dropdown internals (list rows, date inputs, Clear/Apply buttons, the
	 * dropdown card) are functional DOM, not atomic elements, so the Style panel
	 * can't reach them. Give them a clean, neutral default here so they never
	 * render bare. The atomic parts (the Date / Category filter wrappers, the
	 * toggles) are left untouched so they stay panel-styleable.
	 */
	function decorateFilters(ctx) {
		var wrap = ctx.wrapper;

		each(wrap, '.date-dropdown, .category-dropdown', function (d) {
			css(d, {
				backgroundColor: '#ffffff',
				border: '1px solid #e4e4e7',
				borderRadius: '8px',
				padding: '6px',
				boxShadow: '0 10px 30px rgba(0,0,0,0.12)',
				marginTop: '8px',
			});
		});

		each(wrap, '.preset-options', function (u) {
			css(u, { borderBottom: '1px solid #f1f1f4', paddingBottom: '4px', marginBottom: '4px' });
		});

		each(wrap, '.preset-options li, .category-list li', function (li) {
			css(li, { padding: '8px 12px', borderRadius: '6px', cursor: 'pointer', fontSize: '14px', transition: 'background .15s ease', whiteSpace: 'nowrap' });
			if (li.classList.contains('selected')) {
				css(li, { backgroundColor: '#eef0ff', fontWeight: '500' });
			}
			li.addEventListener('mouseenter', function () {
				if (!li.classList.contains('selected')) { li.style.backgroundColor = '#f4f4f5'; }
			});
			li.addEventListener('mouseleave', function () {
				if (!li.classList.contains('selected')) { li.style.backgroundColor = ''; }
			});
		});

		each(wrap, '.category-list', function (u) {
			css(u, { maxHeight: '220px', overflowY: 'auto' });
		});

		each(wrap, '.custom-range', function (cr) {
			css(cr, { display: 'flex', flexDirection: 'column', gap: '10px', padding: '8px 6px 4px' });
		});
		each(wrap, '.custom-range .wrap', function (w) {
			css(w, { display: 'flex', flexDirection: 'column', gap: '4px' });
		});
		each(wrap, '.custom-range label', function (l) {
			css(l, { fontSize: '13px', color: '#71717a' });
		});
		each(wrap, '.custom-range input[type="date"]', function (i) {
			css(i, { padding: '8px 10px', border: '1px solid #e4e4e7', borderRadius: '6px', width: '100%', fontSize: '14px', boxSizing: 'border-box' });
		});

		each(wrap, '.date-buttons, .category-footer', function (f) {
			css(f, { display: 'flex', gap: '8px', justifyContent: 'flex-end', padding: '10px 6px 4px', marginTop: '4px', borderTop: '1px solid #f1f1f4' });
		});
		each(wrap, '.date-buttons button, .category-footer button', function (b) {
			css(b, { padding: '6px 14px', borderRadius: '6px', cursor: 'pointer', fontSize: '13px', lineHeight: '1.4', border: '1px solid transparent' });
			if (b.classList.contains('apply-btn') || b.classList.contains('apply-cat-btn')) {
				css(b, { backgroundColor: '#4f46e5', color: '#ffffff' });
			} else {
				css(b, { backgroundColor: 'transparent', color: '#52525b', border: '1px solid #e4e4e7' });
			}
		});
	}

	/** Style the AJAX result rows (returned HTML, not atomic elements). */
	function styleResultItems(results) {
		each(results, '.search-item', function (it) {
			css(it, { display: 'flex', gap: '12px', alignItems: 'center', padding: '8px', borderRadius: '8px', textDecoration: 'none' });
			it.addEventListener('mouseenter', function () { it.style.backgroundColor = '#f4f4f5'; });
			it.addEventListener('mouseleave', function () { it.style.backgroundColor = ''; });
		});
		each(results, '.search-item .thumb', function (t) {
			css(t, { flex: '0 0 auto', width: '56px', height: '56px', overflow: 'hidden', borderRadius: '6px' });
		});
		each(results, '.search-item .thumb img', function (img) {
			css(img, { width: '100%', height: '100%', objectFit: 'cover' });
		});
		each(results, '.search-item .title', function (a) {
			css(a, { display: 'block', fontSize: '15px', fontWeight: '500', color: 'inherit', textDecoration: 'none' });
		});
		each(results, '.search-item .date', function (d) {
			css(d, { fontSize: '12px', color: '#71717a', marginTop: '2px' });
		});
		each(results, '.search-no-result', function (n) {
			css(n, { padding: '12px', fontSize: '14px', color: '#71717a' });
		});
	}

	/**
	 * Editor-only fallback for the "Show Panel In Editor" switch. The Twig
	 * `.elementor-editor-active` <style> is the primary mechanism; this mirrors it
	 * in case edit-mode stripped the class the CSS relies on.
	 */
	function applyEditorVisibility(ctx) {
		if (!isEditor()) {
			return;
		}
		var mode = ctx.wrapper.getAttribute('data-mode') || 'inline';
		var showPanel = ctx.wrapper.getAttribute('data-editor-show-panel') === 'true';
		var panel = ctx.panel || ctx.wrapper.querySelector('.aae-a-search-panel, [data-e-type="e-aae-a-search-panel"]');
		if (!panel) {
			return;
		}
		panel.style.display = (mode !== 'inline' && !showPanel) ? 'none' : '';
	}

	/* ---- init -------------------------------------------------------------- */

	function initForm(wrapper) {
		if (wrapper.__aaeSearchBound) {
			return;
		}
		wrapper.__aaeSearchBound = true;

		var cfg = parseConfig(wrapper);
		var ctx = {
			wrapper: wrapper,
			cfg: cfg,
			mode: wrapper.getAttribute('data-mode') || cfg.mode || 'inline',
			toggle: wrapper.querySelector('.aae-a-search-toggle'),
			panel: wrapper.querySelector('.aae-a-search-panel'),
			input: wrapper.querySelector('.aae-a-search-input'),
			results: wrapper.querySelector('.aae-a-search-results'),
			open: false,
		};

		bindMode(ctx);
		bindFilter(ctx);
		decorateFilters(ctx);
		applyEditorVisibility(ctx);

		if (cfg.ajax && ctx.input && !isEditor()) {
			ctx.input.addEventListener('input', debounce(function () { doSearch(ctx); }, DEBOUNCE_MS));
			// Hide results when clicking away.
			document.addEventListener('click', function (e) {
				if (ctx.results && !ctx.wrapper.contains(e.target)) {
					ctx.results.style.display = 'none';
				}
			});
		}
	}

	function init() {
		var nodes = document.querySelectorAll('.aae-a-search-form');
		Array.prototype.forEach.call(nodes, initForm);
	}

	function boot() {
		init();
		// In the editor the element subtree is re-rendered on every setting change,
		// which drops our bound handlers. Re-run init (guarded per node) on DOM
		// mutations so freshly rendered widgets get wired + editor visibility re-applied.
		if (isEditor() && window.MutationObserver) {
			var obs = new MutationObserver(debounce(function () { init(); }, 200));
			obs.observe(document.body, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}());
