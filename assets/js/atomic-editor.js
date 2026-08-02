/**
 * AAE Atomic Editor — Loop Grid Template Management
 *
 * Runs inside the Elementor V4 iframe editor.
 * - Injects "Create / Edit Template" buttons into the panel
 * - Live AJAX preview with smooth shimmer → fade-in transitions
 * - Active-document flow for template editing
 *
 * Pure JS — no jQuery.
 */
(function () {
	'use strict';

	/* ------------------------------------------------------------------
	 *  Constants
	 * ---------------------------------------------------------------- */
	var PANEL_SELECTOR     = '#elementor-panel';
	var CONTROL_LABEL_TEXT = 'Choose Template';
	var BUTTON_WRAPPER_CLS = 'aae-atomic-tpl-actions';
	var WIDGET_SELECTOR    = '.aa-gc4a';

	/* ------------------------------------------------------------------
	 *  Helpers
	 * ---------------------------------------------------------------- */

	function qs (sel, root) { return (root || document).querySelector(sel); }
	function qsa (sel, root) { return (root || document).querySelectorAll(sel); }

	function findTemplateControlWrapper () {
		var labels = qsa('#elementor-panel label');
		for (var i = 0; i < labels.length; i++) {
			if (labels[i].textContent.trim() === CONTROL_LABEL_TEXT) {
				return labels[i].closest('.elementor-control') || labels[i].parentElement;
			}
		}
		return null;
	}

	function getPanelValue (labelText, tag) {
		var labels = qsa('#elementor-panel label');
		for (var i = 0; i < labels.length; i++) {
			if (labels[i].textContent.trim() !== labelText) continue;
			var wrap = labels[i].closest('.elementor-control') || labels[i].parentElement;
			if (!wrap) return '';
			var el = wrap.querySelector(tag || 'select, input');
			return el ? el.value : '';
		}
		return '';
	}

	function getCurrentSettings () {
		return {
			template_id:    getPanelValue('Choose Template', 'select'),
			post_type:      getPanelValue('Source', 'select'),
			posts_per_page: getPanelValue('Posts Per Page', 'input') || '6',
			order_by:       getPanelValue('Order By', 'select') || 'date',
			columns:        getPanelValue('Columns', 'input') || '3',
		};
	}

	function postForm (url, data) {
		var body = new URLSearchParams();
		for (var k in data) body.append(k, data[k]);
		return fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body,
		}).then(function (r) { return r.json(); });
	}

	/* ------------------------------------------------------------------
	 *  Preview iframe helpers
	 * ---------------------------------------------------------------- */

	function getPreviewDoc () {
		var iframe = qs('#elementor-preview-iframe');
		return iframe && iframe.contentDocument ? iframe.contentDocument : null;
	}

	function getPreviewGrid () {
		var doc = getPreviewDoc();
		return doc ? qs(WIDGET_SELECTOR, doc) : null;
	}

	/**
	 * Build shimmer placeholder HTML matching the current column count.
	 */
	function shimmerHTML (cols) {
		var n = Math.min(parseInt(cols, 10) || 3, 6);
		var blocks = '';
		for (var i = 0; i < n; i++) blocks += '<div class="aae-lg-shimmer"></div>';
		return '<div class="aae-lg-loading">' + blocks + '</div>';
	}

	/* ------------------------------------------------------------------
	 *  Button injection
	 * ---------------------------------------------------------------- */

	function injectButtons () {
		var wrapper = findTemplateControlWrapper();
		if (!wrapper || wrapper.querySelector('.' + BUTTON_WRAPPER_CLS)) return;

		var select = wrapper.querySelector('select');
		var hasTpl = select && select.value;

		var container = document.createElement('div');
		container.className = BUTTON_WRAPPER_CLS;
		Object.assign(container.style, {
			display: 'flex', gap: '8px', marginTop: '10px', padding: '0 10px 10px',
		});

		var createBtn = document.createElement('button');
		createBtn.type = 'button';
		createBtn.className = 'aae-atomic-create-tpl elementor-button elementor-button-success';
		Object.assign(createBtn.style, {
			flex: '1', display: 'flex', alignItems: 'center', justifyContent: 'center',
			gap: '4px', fontSize: '12px',
		});
		createBtn.innerHTML = '<i class="eicon-plus"></i> Create Template';
		createBtn.addEventListener('click', onCreateTemplate);

		var editBtn = document.createElement('button');
		editBtn.type = 'button';
		editBtn.className = 'aae-atomic-edit-tpl elementor-button elementor-button-default';
		Object.assign(editBtn.style, {
			flex: '1', display: hasTpl ? 'flex' : 'none', alignItems: 'center',
			justifyContent: 'center', gap: '4px', fontSize: '12px',
		});
		editBtn.innerHTML = '<i class="eicon-edit"></i> Edit Template';
		editBtn.addEventListener('click', onEditTemplate);

		container.appendChild(createBtn);
		container.appendChild(editBtn);
		wrapper.appendChild(container);

		if (select) {
			select.addEventListener('change', function () {
				editBtn.style.display = this.value ? 'flex' : 'none';
				refreshLoopPreview();
			});
		}

		bindQueryControlListeners();
	}

	function bindQueryControlListeners () {
		var watchLabels = ['Source', 'Posts Per Page', 'Order By', 'Columns'];
		var labels = qsa('#elementor-panel label');

		for (var i = 0; i < labels.length; i++) {
			var text = labels[i].textContent.trim();
			if (watchLabels.indexOf(text) === -1) continue;

			var wrap = labels[i].closest('.elementor-control') || labels[i].parentElement;
			if (!wrap || wrap.dataset.aaeLoopBound) continue;
			wrap.dataset.aaeLoopBound = '1';

			var inputs = wrap.querySelectorAll('select, input');
			for (var j = 0; j < inputs.length; j++) {
				inputs[j].addEventListener('change', debounceRefresh);
				inputs[j].addEventListener('input', debounceRefresh);
			}
		}
	}

	var _timer = null;
	function debounceRefresh () {
		clearTimeout(_timer);
		_timer = setTimeout(refreshLoopPreview, 400);
	}

	/* ------------------------------------------------------------------
	 *  Live preview via AJAX  (smooth transitions)
	 * ---------------------------------------------------------------- */

	var _fetchController = null; // AbortController for cancelling stale requests

	function refreshLoopPreview () {
		var s = getCurrentSettings();
		if (!s.template_id) return;

		var grid = getPreviewGrid();
		if (!grid) return;

		// Cancel any in-flight request
		if (_fetchController) _fetchController.abort();
		_fetchController = new AbortController();

		// 1. Fade out current content
		grid.classList.add('aae-fading');
		grid.classList.remove('aae-loaded');
		grid.dataset.columns = s.columns;

		// 2. After fade-out, show shimmer
		setTimeout(function () {
			grid.innerHTML = shimmerHTML(s.columns);

			var url = aaeAtomicEditor.ajax_url +
				'?action=aae_render_loop_items' +
				'&template_id='    + encodeURIComponent(s.template_id) +
				'&post_type='      + encodeURIComponent(s.post_type) +
				'&posts_per_page=' + encodeURIComponent(s.posts_per_page) +
				'&order_by='       + encodeURIComponent(s.order_by);

			fetch(url, { signal: _fetchController.signal })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					var g = getPreviewGrid();
					if (!g) return;

					g.classList.remove('aae-fading');

					if (res.success && res.data && res.data.items && res.data.items.length) {
						var html = '';
						for (var i = 0; i < res.data.items.length; i++) {
							html += '<article class="e-loop-item aae-li-c4a">' + res.data.items[i] + '</article>';
						}
						g.innerHTML = html;

						// Trigger smooth stagger reveal via CSS
						requestAnimationFrame(function () {
							g.classList.add('aae-loaded');
						});
					} else {
						g.innerHTML =
							'<div class="aae-lge-c4a">' +
							'<i class="eicon-warning" style="font-size:28px;margin-bottom:8px;display:block;opacity:.5;"></i>' +
							'No posts found matching the query.</div>';
					}
				})
				.catch(function (err) {
					if (err.name === 'AbortError') return; // cancelled, ignore
					var g = getPreviewGrid();
					if (g) {
						g.classList.remove('aae-fading');
						g.innerHTML =
							'<div class="aae-lge-c4a" style="color:#c00;">' +
							'Preview failed to load.</div>';
					}
				});
		}, 200); // wait for fade-out
	}

	/* ------------------------------------------------------------------
	 *  Template creation
	 * ---------------------------------------------------------------- */

	function onCreateTemplate () {
		var modal = elementorCommon.dialogsManager.createWidget('confirm', {
			id: 'aae-atomic-create-tpl-modal',
			headerMessage: 'Create Loop Template',
			message: 'A new loop builder template will be created and the editor will switch to it. Continue?',
			position: { my: 'center center', at: 'center center' },
			strings: { confirm: 'Create & Edit', cancel: 'Cancel' },
			onConfirm: doCreateTemplate,
		});
		modal.show();
	}

	function doCreateTemplate () {
		postForm(aaeAtomicEditor.ajax_url, {
			action:        'create_loop_template',
			template_name: 'Loop Template ' + Date.now(),
			source_type:   'post',
			template_type: 'loop-builder',
			nonce:         aaeAtomicEditor.nonce,
		}).then(function (res) {
			if (res.success && res.data && res.data.template_id) {
				enterActiveDocument(res.data.template_id);
			} else {
				showToast((res.data && res.data.message) || 'Failed to create template.', 'error');
			}
		}).catch(function () {
			showToast('AJAX request failed.', 'error');
		});
	}

	/* ------------------------------------------------------------------
	 *  Edit existing template
	 * ---------------------------------------------------------------- */

	function onEditTemplate () {
		var tplId = getPanelValue('Choose Template', 'select');
		if (!tplId) { showToast('Please select a template first.', 'info'); return; }
		enterActiveDocument(tplId);
	}

	/* ------------------------------------------------------------------
	 *  Active-document switching
	 * ---------------------------------------------------------------- */

	function enterActiveDocument (templateId) {
		var saveProm = (window.$e && $e.run)
			? $e.run('document/save/default').catch(function () {})
			: Promise.resolve();

		saveProm.then(function () {
			var url = new URL(window.location.href);
			url.searchParams.set('active-document', String(templateId));
			window.location.href = url.toString();
		});
	}

	function handleActiveDocumentMode () {
		var params    = new URLSearchParams(window.location.search);
		var activeDoc = params.get('active-document');
		if (!activeDoc) return;

		waitForEditor(function () {
			injectSaveBackBar(params.get('post'));
			lockBaseElements();
		});
	}

	function waitForEditor (cb) {
		if (typeof elementor !== 'undefined' && elementor.config && elementor.config.document) { cb(); }
		else { setTimeout(function () { waitForEditor(cb); }, 200); }
	}

	/* ------------------------------------------------------------------
	 *  Save & Back bar
	 * ---------------------------------------------------------------- */

	function injectSaveBackBar (basePostId) {
		if (qs('#aae-atomic-save-back-bar')) return;

		var bar = document.createElement('div');
		bar.id = 'aae-atomic-save-back-bar';
		Object.assign(bar.style, {
			position: 'fixed', top: '0', left: '0', right: '0', zIndex: '100000',
			display: 'flex', alignItems: 'center', justifyContent: 'center',
			gap: '12px', padding: '8px 16px',
			background: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
			color: '#fff', fontSize: '13px',
			fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
			boxShadow: '0 2px 12px rgba(0,0,0,0.3)',
		});

		var label = document.createElement('span');
		Object.assign(label.style, { display: 'flex', alignItems: 'center', gap: '6px' });
		label.innerHTML = '<i class="eicon-edit" style="color:#93b4ff;"></i> Editing Loop Template';

		var btn = document.createElement('button');
		btn.type = 'button';
		Object.assign(btn.style, {
			background: 'linear-gradient(225deg, #F12529 10%, #FFA030 90%)',
			color: '#fff', border: '0', borderRadius: '4px',
			padding: '6px 16px', fontSize: '12px', fontWeight: '600',
			cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px',
		});
		btn.innerHTML = '<i class="eicon-save"></i> Save & Back';
		btn.addEventListener('click', function () { saveAndGoBack(basePostId); });

		bar.appendChild(label);
		bar.appendChild(btn);
		document.body.appendChild(bar);
	}

	function saveAndGoBack (basePostId) {
		showToast('Saving template…', 'info');

		var saveProm = (window.$e && $e.run)
			? $e.run('document/save/default').catch(function () {})
			: Promise.resolve();

		saveProm.then(function () {
			showToast('Saved! Returning…', 'success');
			setTimeout(function () {
				if (basePostId) {
					window.location.href = window.location.origin +
						'/wp-admin/post.php?post=' + basePostId + '&action=elementor';
				} else {
					var url = new URL(window.location.href);
					url.searchParams.delete('active-document');
					window.location.href = url.toString();
				}
			}, 600);
		});
	}

	/* ------------------------------------------------------------------
	 *  Lock base document elements
	 * ---------------------------------------------------------------- */

	function lockBaseElements () {
		function tryLock () {
			var doc = getPreviewDoc();
			if (!doc) { setTimeout(tryLock, 500); return; }
			if (doc.getElementById('aae-atomic-lock-base')) return;

			var s = doc.createElement('style');
			s.id = 'aae-atomic-lock-base';
			s.textContent =
				'.elementor-element:not(.e-loop-item):not(.e-loop-item *){pointer-events:none!important}' +
				'.e-loop-item,.e-loop-item *,.e-loop-item .elementor-element{pointer-events:auto!important}';
			doc.head.appendChild(s);
		}
		tryLock();
	}

	/* ------------------------------------------------------------------
	 *  Toast
	 * ---------------------------------------------------------------- */

	function showToast (msg, type) {
		if (typeof elementor !== 'undefined' && elementor.notifications && elementor.notifications.showToast) {
			elementor.notifications.showToast({ message: msg, type: type || 'info' });
		}
	}

	/* ------------------------------------------------------------------
	 *  Panel observer
	 * ---------------------------------------------------------------- */

	function startPanelObserver () {
		var panel = qs(PANEL_SELECTOR);
		if (!panel) { setTimeout(startPanelObserver, 500); return; }

		var debounce = null;
		new MutationObserver(function () {
			if (debounce) return;
			debounce = setTimeout(function () { debounce = null; injectButtons(); }, 150);
		}).observe(panel, { childList: true, subtree: true });
	}

	/* ------------------------------------------------------------------
	 *  Bootstrap
	 * ---------------------------------------------------------------- */

	document.addEventListener('DOMContentLoaded', function () {
		handleActiveDocumentMode();
		if (typeof elementor !== 'undefined') startPanelObserver();
		else window.addEventListener('elementor:init', startPanelObserver);
	});

})();
