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

	/* ---- transformed-ancestor relocation ----------------------------------- */

	/**
	 * Nearest ancestor that makes `position: fixed` resolve against IT instead of
	 * the viewport. transform / perspective / filter (and a will-change promising
	 * any of them) all do this. GSAP ScrollSmoother is the common case: it puts a
	 * transform on #smooth-content and translates it as you scroll, so the
	 * fullscreen panel's insets resolve against the whole DOCUMENT — the overlay
	 * opens scrollY pixels ABOVE the viewport and stretches to the document's
	 * height (Elementor's `.e-con { height: var(--height) }` resolves `auto`
	 * between top: 0 and bottom: 0). No CSS escapes a containing block — not
	 * z-index, not !important, not 100dvh, which fixes only the size — so the
	 * panel has to leave the transformed subtree.
	 *
	 * Returns the offending element, or null when `position: fixed` behaves
	 * normally — every ordinary page, which is why the move below is conditional
	 * rather than unconditional. Header markup can also sit OUTSIDE
	 * #smooth-content (the header-smoother option), so this is asked per element
	 * instead of once per page.
	 */
	function fixedContainingBlockAncestor(el) {
		for (var p = el && el.parentElement; p && p !== document.body; p = p.parentElement) {
			var cs = window.getComputedStyle(p);
			if (
				cs.transform !== 'none' ||
				cs.perspective !== 'none' ||
				cs.filter !== 'none' ||
				/transform|perspective|filter/.test(cs.willChange || '')
			) {
				return p;
			}
		}
		return null;
	}

	/**
	 * Move the fullscreen panel — plus the toggle, which openPanel() turns into a
	 * fixed floating close button — to a body-level host, so both are
	 * viewport-fixed again. Called from openPanel(), NOT from init: ScrollSmoother
	 * is usually created after DOMContentLoaded, so at init time the transform
	 * that has to be detected does not exist yet.
	 *
	 * The host carries the `.elementor` scope's class list, because atomic styles
	 * compile as `.elementor .e-<id>-<hash>` — outside that ancestor every
	 * Style-tab value on the panel silently stops applying. It also carries the
	 * form's `data-id`, which the wrapper's inline <style> needs to reach the
	 * input's ::placeholder colour ( `[data-id="..."] input[type="search"]` is a
	 * descendant selector; a <style> element itself keeps working from wherever it
	 * sits). It deliberately does NOT carry the form's own classes: the host would
	 * then answer `.aae-a-search-form` lookups and pick up `.e-con` box styles.
	 *
	 * `display: contents` means the host generates no box at all, so the page's
	 * layout is untouched — nothing is added to the flow at the end of <body>.
	 */
	function detach(ctx) {
		if (ctx.host || !ctx.panel || !fixedContainingBlockAncestor(ctx.panel)) {
			return;
		}
		var scope = ctx.wrapper.closest('.elementor');
		var id = ctx.wrapper.getAttribute('data-id') || '';
		var host = document.createElement('div');
		host.setAttribute('data-aae-search-detached', id);
		if (scope) { host.className = scope.className; }
		if (id) { host.setAttribute('data-id', id); }
		host.style.display = 'contents';
		document.body.appendChild(host);
		/* Comment anchors put both nodes back in their authored position — and
		 * authored order — when the panel closes. */
		ctx.panel.parentNode.insertBefore(ctx.panelAnchor, ctx.panel);
		host.appendChild(ctx.panel);
		if (ctx.toggle) {
			ctx.toggle.parentNode.insertBefore(ctx.toggleAnchor, ctx.toggle);
			host.appendChild(ctx.toggle);
		}
		ctx.host = host;
	}

	function reattach(ctx) {
		if (!ctx.host) {
			return;
		}
		if (ctx.panelAnchor.parentNode) {
			ctx.panelAnchor.parentNode.insertBefore(ctx.panel, ctx.panelAnchor);
			ctx.panelAnchor.remove();
		}
		if (ctx.toggle && ctx.toggleAnchor.parentNode) {
			ctx.toggleAnchor.parentNode.insertBefore(ctx.toggle, ctx.toggleAnchor);
			ctx.toggleAnchor.remove();
		}
		ctx.host.remove();
		ctx.host = null;
	}

	/**
	 * Wrapper-scoped lookups have to keep working while the panel lives in the
	 * host: the filters, the input and the results all travel with it, so a plain
	 * `ctx.wrapper.querySelector()` would return null and the date / category
	 * filters would be silently dropped from the Ajax request.
	 */
	function find(ctx, sel) {
		return ctx.wrapper.querySelector(sel) ||
			(ctx.host ? ctx.host.querySelector(sel) : null);
	}

	/**
	 * Same reason for containment: with the panel outside the wrapper, every click
	 * INSIDE the panel would read as "outside" and close it / hide the results the
	 * moment it opened.
	 */
	function owns(ctx, node) {
		return ctx.wrapper.contains(node) || !!(ctx.host && ctx.host.contains(node));
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

		var from = find(ctx, '.from-date');
		var to = find(ctx, '.to-date');
		if (from && from.value) { body.append('from_date', from.value); }
		if (to && to.value) { body.append('to_date', to.value); }
		var cat = find(ctx, '.aae-selected-category');
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
		var dateC = find(ctx, '.date-container');
		var catC = find(ctx, '.category-container');
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

	/**
	 * Write a mode DEFAULT only if nothing else has set the property.
	 *
	 * Inline styles beat every Style-panel value, so an unconditional write silently
	 * overrides whatever the user chose in the panel — that is exactly how the
	 * Panel's own Height ended up struck through in devtools. Comparing against the
	 * property's INITIAL value is what separates "nobody set this" from "the author
	 * picked something", since a base style or a Style-tab value both resolve to
	 * something other than the initial.
	 */
	function setIfUnset(el, prop, value, initial) {
		if (window.getComputedStyle(el)[prop] === initial) {
			el.style[prop] = value;
		}
	}

	/**
	 * Open the panel, writing ONLY what the mode structurally needs.
	 *
	 * Fullscreen used to write width/height 100% here. Both were redundant — a fixed
	 * element with `inset: 0` already fills the viewport — and both clobbered the
	 * Panel's own Width/Height from the Style tab. Dropping them changes nothing when
	 * the user has set no size, and lets their size win when they have.
	 *
	 * `display` and `flex-direction` are gone for the same reason: clearing display
	 * reverts to the panel's own value instead of forcing flex, and the panel's base
	 * style already sets flex-direction: column.
	 */
	function openPanel(ctx) {
		var panel = ctx.panel;
		if (!panel) {
			return;
		}
		// Clear closePanel()'s display:none FIRST, so the panel falls back to its own
		// base / Style-panel display and the setIfUnset() reads resolve against it.
		panel.style.display = '';

		if (ctx.mode === 'fullscreen') {
			// Every inset below is meaningless inside a transformed subtree, so get
			// the panel out of one first. No-op on a page without one.
			detach(ctx);
			Object.assign(panel.style, {
				position: 'fixed',
				top: '0',
				left: '0',
				right: '0',
				bottom: '0',
				zIndex: '99999',
			});
			// Centring the overlay content is a default, not a requirement.
			setIfUnset(panel, 'alignItems', 'center', 'normal');
			setIfUnset(panel, 'justifyContent', 'center', 'normal');
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
				zIndex: '99',
			});
			panel.style[ctx.cfg.position === 'right' ? 'right' : 'left'] = '0';
			// A floating panel shrinks to its content without this, but a Min Width
			// set in the panel must still win.
			setIfUnset(panel, 'minWidth', '300px', '0px');
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
		reattach(ctx);
		ctx.open = false;
	}

	function bindMode(ctx) {
		if (isEditor()) {
			// Editor: keep the whole tree visible & selectable, no toggling. The
			// panel's editor visibility is the "Show Panel In Editor" preview,
			// owned by the twig's data-editor-show-panel CSS and flipped by the
			// delegated handler in boot() — never from here, so the two can never
			// both fire on one click.
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
				if (ctx.open && !owns(ctx, e.target)) { closePanel(ctx); }
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
	 * The twig's data-editor-show-panel CSS owns preview visibility now.
	 *
	 * This used to write display inline, which beat that CSS: the moment the
	 * toggle-click preview flipped the attribute, the stale inline "none" kept the
	 * panel hidden anyway. So all it does now is clear that inline value and let
	 * the cascade decide.
	 */
	function applyEditorVisibility(ctx) {
		if (!isEditor()) {
			return;
		}
		var panel = ctx.panel || ctx.wrapper.querySelector('.aae-a-search-panel, [data-e-type="e-aae-a-search-panel"]');
		if (panel) {
			panel.style.display = '';
		}
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
			/* Set by detach() only when the panel sits in a transformed subtree. */
			host: null,
			panelAnchor: document.createComment('aae-search-panel-' + (wrapper.getAttribute('data-id') || '')),
			toggleAnchor: document.createComment('aae-search-toggle-' + (wrapper.getAttribute('data-id') || '')),
		};

		bindMode(ctx);
		bindFilter(ctx);
		decorateFilters(ctx);
		applyEditorVisibility(ctx);

		if (cfg.ajax && ctx.input && !isEditor()) {
			ctx.input.addEventListener('input', debounce(function () { doSearch(ctx); }, DEBOUNCE_MS));
			// Hide results when clicking away.
			document.addEventListener('click', function (e) {
				if (ctx.results && !owns(ctx, e.target)) {
					ctx.results.style.display = 'none';
				}
			});
		}
	}

	function init() {
		var nodes = document.querySelectorAll('.aae-a-search-form');
		Array.prototype.forEach.call(nodes, initForm);
	}

	/**
	 * True inside the editor's preview iframe — and, unlike isEditor(), true from
	 * the first line of script execution. Elementor adds
	 * `elementor-editor-active` to the preview body only after this script runs,
	 * so isEditor() is false at boot and cannot gate anything installed there.
	 * On the frontend `frameElement` is null; the try/catch covers a page embedded
	 * cross-origin, where reading it throws.
	 */
	function inEditorPreview() {
		try {
			return isEditor() ||
				!!(window.frameElement && window.frameElement.id === 'elementor-preview-iframe');
		} catch (e) {
			return false;
		}
	}

	/**
	 * Editor-only: click the search icon in the canvas to preview the panel.
	 *
	 * Clicking it used to do nothing at all, so the only way to see a dropdown /
	 * fullscreen panel was to find the "Show Panel In Editor" switch in the
	 * Settings tab. This flips the same data-editor-show-panel attribute that
	 * switch renders, which is what the twig's editor CSS keys off.
	 *
	 * PREVIEW ONLY: the saved setting is never written, so nothing marks the
	 * document dirty and any re-render restores whatever the switch says.
	 *
	 * Delegated from the document so it keeps working across Elementor's
	 * re-renders with nothing bound per node, and capture-phase so it cannot be
	 * swallowed by a stopPropagation() on the way up. It never calls
	 * preventDefault(), so Elementor still selects the element on the same click.
	 */
	function bindEditorPreviewToggle() {
		document.addEventListener('click', function (e) {
			var target = e.target;
			if (!target || !target.closest) {
				return;
			}
			var toggle = target.closest('.aae-a-search-toggle');
			if (!toggle) {
				return;
			}
			var wrapper = toggle.closest('.aae-a-search-form');
			if (!wrapper || (wrapper.getAttribute('data-mode') || 'inline') === 'inline') {
				return;
			}
			var on = wrapper.getAttribute('data-editor-show-panel') === 'true';
			wrapper.setAttribute('data-editor-show-panel', on ? 'false' : 'true');
		}, true);
	}

	function boot() {
		init();
		if (inEditorPreview()) {
			bindEditorPreviewToggle();
		}
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
