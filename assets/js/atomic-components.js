/**
 * AAE Atomic Components Editor Logic
 *
 * Handles:
 * 1. Topbar Inserter Interface
 * 2. Context Menu (Create Component, Edit Component, Detach Component)
 *
 * Runs inside the Elementor editor frame.
 * Modern ES6 Vanilla JS
 */
(() => {
	'use strict';

	let isInitialized = false;

	/* ------------------------------------------------------------------
	 *  Helpers
	 * ---------------------------------------------------------------- */

	const showToast = (msg, type = 'info') => {
		if (window.elementor?.notifications) {
			window.elementor.notifications.showToast({ message: msg, type });
		}
	};

	const getAjaxUrl = () => window.aaeAtomicEditor?.ajax_url || window.ajaxurl || '';
	const getNonce = () => window.aaeAtomicEditor?.nonce || '';

	const postAjax = async (action, data) => {
		const body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', getNonce());
		
		for (const [key, val] of Object.entries(data)) {
			body.append(key, typeof val === 'object' ? JSON.stringify(val) : val);
		}

		const response = await fetch(getAjaxUrl(), {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body
		});
		return response.json();
	};

	const getAjax = async (action, params = {}) => {
		const url = new URL(getAjaxUrl());
		url.searchParams.append('action', action);
		url.searchParams.append('nonce', getNonce());
		
		for (const [key, val] of Object.entries(params)) {
			url.searchParams.append(key, val);
		}
		
		const response = await fetch(url.toString());
		return response.json();
	};

	/* ------------------------------------------------------------------
	 *  Element Manipulation
	 * ---------------------------------------------------------------- */

	const replaceElementWithComponent = (elementView, newTemplateId) => {
		const container = elementView.getContainer();
		const parent = container.parent;
		const index = parent.children.indexOf(container);

		if (window.$e?.run) {
			window.$e.run('document/elements/delete', { containers: [container] });
		} else {
			container.model.destroy();
		}

		const newModel = {
			elType: 'widget',
			widgetType: 'aae-a-component',
			settings: {
				template_id: String(newTemplateId)
			}
		};

		if (window.$e?.run) {
			window.$e.run('document/elements/create', {
				container: parent,
				model: newModel,
				options: { at: index }
			});
		}
	};

	const replaceComponentWithRawData = (elementView, rawElements) => {
		const container = elementView.getContainer();
		const parent = container.parent;
		const index = parent.children.indexOf(container);

		if (window.$e?.run) {
			window.$e.run('document/elements/delete', { containers: [container] });
		} else {
			container.model.destroy();
		}

		if (Array.isArray(rawElements)) {
			rawElements.forEach((elModel, i) => {
				if (window.$e?.run) {
					window.$e.run('document/elements/create', {
						container: parent,
						model: elModel,
						options: { at: index + i }
					});
				}
			});
		}
	};

	/* ------------------------------------------------------------------
	 *  Context Menu Callbacks
	 * ---------------------------------------------------------------- */

	const actionCreateComponent = async (elementView) => {
		const name = prompt('Enter a name for this new component:', 'New Component');
		if (!name) return;

		const elementData = elementView.model.toJSON();
		showToast('Creating component...', 'info');

		try {
			const res = await postAjax('aae_create_component_from_element', {
				element_data: JSON.stringify(elementData),
				component_name: name
			});

			if (res.success && res.data?.template_id) {
				showToast('Component created! Replacing...', 'success');
				replaceElementWithComponent(elementView, res.data.template_id);
			} else {
				showToast(res.data || 'Failed to create component.', 'error');
			}
		} catch (err) {
			console.error(err);
			showToast('An error occurred.', 'error');
		}
	};

	const actionEditComponent = (elementView) => {
		const templateId = elementView.model.get('settings').get('template_id');
		if (!templateId) {
			showToast('No component selected.', 'error');
			return;
		}

		const editUrl = window.elementor.config.document.urls.edit.replace(/post=[0-9]+/, `post=${templateId}`);
		window.open(editUrl, '_blank');
	};

	const actionDetachComponent = async (elementView) => {
		const templateId = elementView.model.get('settings').get('template_id');
		if (!templateId) {
			showToast('No component selected.', 'error');
			return;
		}

		showToast('Fetching component data...', 'info');

		try {
			const res = await getAjax('aae_get_component_data', { template_id: templateId });
			if (res.success && res.data?.element_data) {
				showToast('Detached! Inserting raw layout.', 'success');
				replaceComponentWithRawData(elementView, res.data.element_data);
			} else {
				showToast(res.data || 'Failed to detach.', 'error');
			}
		} catch (err) {
			console.error(err);
			showToast('An error occurred.', 'error');
		}
	};

	/* ------------------------------------------------------------------
	 *  Hook Context Menus
	 * ---------------------------------------------------------------- */

	const addContextMenuGroups = (groups, elementView) => {
		try {
			const widgetType = elementView?.model?.get('widgetType') || '';
			
			// Only support Atomic widgets
			if (!widgetType.startsWith('aae-')) {
				return groups;
			}

			const isAAEComponent = widgetType === 'aae-a-component';

			let targetGroup = groups.find(g => g.name === 'save');
			if (!targetGroup) {
				targetGroup = groups.find(g => g.name === 'general');
			}
			if (!targetGroup) {
				targetGroup = { name: 'aae_group', actions: [] };
				groups.push(targetGroup);
			}

			if (isAAEComponent) {
				targetGroup.actions.push({
					name: 'aae_edit_component',
					title: 'AAE Edit Component',
					icon: 'eicon-edit',
					callback: () => actionEditComponent(elementView)
				});
				targetGroup.actions.push({
					name: 'aae_detach_component',
					title: 'AAE Detach Component',
					icon: 'eicon-chain-broken',
					callback: () => actionDetachComponent(elementView)
				});
			} else {
				targetGroup.actions.push({
					name: 'aae_create_component',
					title: 'AAE Create Component',
					icon: 'eicon-component',
					callback: () => actionCreateComponent(elementView)
				});
			}
		} catch (e) {
			console.error('AAE Context Menu Error:', e);
		}
		return groups;
	};

	/* ------------------------------------------------------------------
	 *  Topbar Inserter
	 * ---------------------------------------------------------------- */

	const getActiveContainer = () => {
		if (window.elementor?.getPreviewContainer) {
			return window.elementor.getPreviewContainer();
		}
		return null;
	};

	const insertComponentWidget = (templateId) => {
		const activeContainer = getActiveContainer();

		const newModel = {
			elType: 'widget',
			widgetType: 'aae-a-component',
			settings: {
				template_id: String(templateId)
			}
		};

		if (window.$e?.run) {
			window.$e.run('document/elements/create', {
				container: activeContainer,
				model: newModel
			});
			showToast('Component Inserted!', 'success');
		} else {
			showToast('Could not insert component. Editor API not ready.', 'error');
		}
	};

	const attemptTopbarInsertion = () => {
		if (document.getElementById('aae-topbar-components-btn')) return true;

		// Aggressively look for the Design System, History, or Settings icon using class, title, or aria attributes
		const targetIcon = document.querySelector(
			'.eicon-theme-style, .eicon-history, .eicon-cog, ' +
			'[title="Design System"], [aria-label="Design System"], [data-tooltip="Design System"], ' +
			'[title="History"], [aria-label="History"], [data-tooltip="History"]'
		);

		if (!targetIcon) return false;

		const targetBtn = targetIcon.closest('button') || targetIcon.closest('div');
		if (!targetBtn || !targetBtn.parentElement) return false;

		const wrapper = document.createElement('div');
		wrapper.id = 'aae-topbar-components-btn';
		Object.assign(wrapper.style, {
			position: 'relative',
			display: 'inline-flex',
			alignItems: 'center',
			justifyContent: 'center',
			marginLeft: '5px'
		});

		const btn = document.createElement('button');
		btn.className = targetBtn.className || 'elementor-header-button';
		btn.innerHTML = `<i class="eicon-component" aria-hidden="true" title="Components"></i>`;
		Object.assign(btn.style, {
			cursor: 'pointer',
			background: 'transparent',
			border: 'none',
			color: window.getComputedStyle(targetIcon).color || '#a4afb7',
			fontSize: window.getComputedStyle(targetIcon).fontSize || '16px',
			padding: '0 8px'
		});

		const dropdown = document.createElement('div');
		Object.assign(dropdown.style, {
			position: 'absolute',
			top: '100%',
			right: '0',
			marginTop: '10px',
			background: '#fff',
			boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
			borderRadius: '4px',
			width: '200px',
			maxHeight: '300px',
			overflowY: 'auto',
			display: 'none',
			zIndex: '9999',
			color: '#333',
			fontFamily: 'Roboto, Arial, Helvetica, sans-serif'
		});

		btn.addEventListener('click', async (e) => {
			e.stopPropagation();
			if (dropdown.style.display === 'block') {
				dropdown.style.display = 'none';
				return;
			}
			
			dropdown.innerHTML = `<div style="padding:10px;text-align:center;">Loading...</div>`;
			dropdown.style.display = 'block';

			try {
				const res = await getAjax('aae_get_components_list');
				if (res.success && res.data?.components?.length) {
					dropdown.innerHTML = res.data.components.map(comp => `
						<div class="aae-comp-item" data-id="${comp.id}" style="padding:10px;border-bottom:1px solid #eee;cursor:pointer;font-size:12px;display:flex;align-items:center;gap:8px;line-height:1.5;">
							<i class="eicon-component" style="color:#888;"></i> ${comp.title}
						</div>
					`).join('');

					dropdown.querySelectorAll('.aae-comp-item').forEach(item => {
						item.addEventListener('click', (e2) => {
							e2.stopPropagation();
							insertComponentWidget(item.dataset.id);
							dropdown.style.display = 'none';
						});
					});
				} else {
					dropdown.innerHTML = `<div style="padding:10px;text-align:center;font-size:12px;color:#888;">No components found.</div>`;
				}
			} catch (err) {
				console.error(err);
				dropdown.innerHTML = `<div style="padding:10px;text-align:center;font-size:12px;color:red;">Error loading components.</div>`;
			}
		});

		document.addEventListener('click', (e) => {
			if (!wrapper.contains(e.target)) {
				dropdown.style.display = 'none';
			}
		});

		wrapper.append(btn, dropdown);
		
		// Insert exactly after the target button
		targetBtn.after(wrapper);
		return true;
	};

	const initTopbarInserter = () => {
		const poll = () => {
			if (!attemptTopbarInsertion()) {
				setTimeout(poll, 1000);
			}
		};
		poll();
	};

	/* ------------------------------------------------------------------
	 *  Init
	 * ---------------------------------------------------------------- */

	const init = () => {
		if (isInitialized) return;
		isInitialized = true;

		console.log('AAE Components Init (Modern ES6)');

		if (window.elementor?.hooks) {
			const hooks = ['widget','e-flexbox'];
			hooks.forEach(hook => {
				window.elementor.hooks.addFilter(`elements/${hook}/contextMenuGroups`, addContextMenuGroups);
			});
		}

		initTopbarInserter();
	};

	window.addEventListener('elementor/init', init);

	// Fallback in case elementor/init already fired
	if (window.elementor?.hooks) {
		init();
	}

})();
