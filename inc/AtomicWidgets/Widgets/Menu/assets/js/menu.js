import { register } from '@elementor/frontend-handlers';

/* AAE Atomic Menu — minimal vanilla JS. CSS does all transitions. */

const initMenu = (root) => {
	if (root.dataset.aaeMenuInit === '1') return;
	root.dataset.aaeMenuInit = '1';

	const nav      = root.querySelector('.aae-a-menu-nav');
	const toggle   = root.querySelector('.aae-a-menu-toggle');
	const overlay  = root.querySelector('.aae-a-menu-overlay');
	const closeBtn = root.querySelector('.aae-a-menu-close');
	if (!nav) return;

	const breakpoint  = parseInt(root.getAttribute('data-breakpoint'), 10) || 768;
	const isHamburger = root.getAttribute('data-hamburger') === 'true';
	const isMobile    = () => window.innerWidth <= breakpoint;

	/* ---------- Dropdown arrows + click-to-toggle ---------- */
	const buildDropdowns = () => {
		const list = nav.querySelector('.aae-a-menu-list');
		if (!list) return null;

		list.querySelectorAll('.menu-item-has-children').forEach((item) => {
			if (item.querySelector(':scope > .aae-a-menu-arrow')) return;
			const subMenu = item.querySelector(':scope > .sub-menu');
			if (!subMenu) return;

			const arrow = document.createElement('button');
			arrow.type = 'button';
			arrow.className = 'aae-a-menu-arrow';
			arrow.setAttribute('aria-label', 'Toggle submenu');
			arrow.setAttribute('aria-expanded', 'false');
			// Insert BEFORE the sub-menu so DOM order is: link → arrow → sub-menu.
			// (Sub-menu is flex-basis:100% on mobile, so anything after it gets pushed
			// to its own row, which is why arrows were appearing below.)
			item.insertBefore(arrow, subMenu);

			arrow.addEventListener('click', (e) => {
				e.preventDefault();
				e.stopPropagation();
				const open = item.classList.toggle('aae-a-menu-item--open');
				arrow.setAttribute('aria-expanded', open ? 'true' : 'false');

				// Close siblings at the same level
				if (open && item.parentElement) {
					Array.from(item.parentElement.children).forEach((sib) => {
						if (sib !== item && sib.classList && sib.classList.contains('aae-a-menu-item--open')) {
							sib.classList.remove('aae-a-menu-item--open');
							const sArrow = sib.querySelector(':scope > .aae-a-menu-arrow');
							if (sArrow) sArrow.setAttribute('aria-expanded', 'false');
						}
					});
				}
			});
		});

		return () => {
			list.querySelectorAll('.aae-a-menu-item--open').forEach((el) => {
				el.classList.remove('aae-a-menu-item--open');
				const a = el.querySelector(':scope > .aae-a-menu-arrow');
				if (a) a.setAttribute('aria-expanded', 'false');
			});
		};
	};

	let closeAllSubmenus = null;

	/* ---------- Editor preview AJAX fallback ---------- */
	const inEditor = !!(window.elementorFrontend
		&& typeof window.elementorFrontend.isEditMode === 'function'
		&& window.elementorFrontend.isEditMode());
	const placeholder = nav.querySelector('.aae-a-menu-placeholder');

	if (inEditor && placeholder) {
		const slug = nav.getAttribute('data-menu-slug');
		if (slug) {
			const ajaxUrl = (window.elementorFrontend
				&& window.elementorFrontend.config
				&& window.elementorFrontend.config.ajaxurl)
				|| window.ajaxurl
				|| '/wp-admin/admin-ajax.php';
			fetch(`${ajaxUrl}?action=aae_get_menu_html&menu=${encodeURIComponent(slug)}`)
				.then((r) => r.json())
				.then((data) => {
					if (data && data.success && data.data) {
						const body = nav.querySelector('.aae-a-menu-nav-body');
						if (body) body.innerHTML = data.data;
						closeAllSubmenus = buildDropdowns();
					}
				})
				.catch(() => {});
		}
	} else {
		closeAllSubmenus = buildDropdowns();
	}

	/* ---------- Outside-click + Escape closes desktop dropdowns ---------- */
	document.addEventListener('click', (e) => {
		if (isMobile()) return;
		if (!root.contains(e.target) && typeof closeAllSubmenus === 'function') {
			closeAllSubmenus();
		}
	});

	/* ---------- Mobile drawer ---------- */
	if (!isHamburger || !toggle) return;

	const openDrawer = () => {
		root.classList.add('aae-a-menu--open');
		toggle.setAttribute('aria-expanded', 'true');
		document.body.classList.add('aae-a-menu-body-lock');
	};

	const closeDrawer = () => {
		root.classList.remove('aae-a-menu--open');
		toggle.setAttribute('aria-expanded', 'false');
		document.body.classList.remove('aae-a-menu-body-lock');
		if (typeof closeAllSubmenus === 'function') closeAllSubmenus();
	};

	toggle.addEventListener('click', (e) => {
		e.preventDefault();
		if (root.classList.contains('aae-a-menu--open')) closeDrawer();
		else openDrawer();
	});

	if (closeBtn) closeBtn.addEventListener('click', (e) => { e.preventDefault(); closeDrawer(); });
	if (overlay)  overlay.addEventListener('click',  (e) => { e.preventDefault(); closeDrawer(); });

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && root.classList.contains('aae-a-menu--open')) closeDrawer();
	});

	window.addEventListener('resize', () => {
		if (!isMobile() && root.classList.contains('aae-a-menu--open')) closeDrawer();
	});
};

register({
	elementType: 'e-aae-a-menu',
	id: 'aae-a-menu-handler',
	callback: ({ element }) => {
		const root = element.classList.contains('aae-a-menu') ? element : element.querySelector('.aae-a-menu');
		if (root) initMenu(root);
	},
});

/* Fallback: also init on DOMContentLoaded for non-Elementor contexts */
const initAll = () => document.querySelectorAll('.aae-a-menu').forEach(initMenu);
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initAll);
} else {
	initAll();
}
