import { register } from '@elementor/frontend-handlers';

const CHEVRON = '<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M3.5 5.25 7 8.75l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';

const closeAllSubmenus = (root) => {
	root.querySelectorAll('.aae-a-menu-item--open').forEach((el) => {
		el.classList.remove('aae-a-menu-item--open');
		el.querySelector(':scope > .aae-a-menu-submenu-toggle')?.setAttribute('aria-expanded', 'false');
	});
};

const wireSubmenus = (nav) => {
	nav.querySelectorAll('.menu-item-has-children').forEach((item) => {
		if (item.querySelector(':scope > .aae-a-menu-submenu-toggle')) return;
		const link = item.querySelector(':scope > a');
		if (!link) return;

		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'aae-a-menu-submenu-toggle';
		btn.setAttribute('aria-label', 'Toggle submenu');
		btn.setAttribute('aria-expanded', 'false');
		btn.innerHTML = CHEVRON;
		btn.addEventListener('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			const open = item.classList.toggle('aae-a-menu-item--open');
			btn.setAttribute('aria-expanded', open);
			[...item.parentElement.children].forEach((s) => {
				if (s !== item) {
					s.classList.remove('aae-a-menu-item--open');
					s.querySelector(':scope > .aae-a-menu-submenu-toggle')?.setAttribute('aria-expanded', 'false');
				}
			});
		});
		link.insertAdjacentElement('afterend', btn);
	});
};

const wireDrawer = (container, toggle) => {
	const overlay = container.querySelector('.aae-a-menu-overlay');
	const nav = container.querySelector('.aae-a-menu-nav');
	const close = container.querySelector('.aae-a-menu-close');
	const bp = parseInt(container.dataset.breakpoint, 10) || 768;
	const navHome = nav?.parentElement;
	const overlayHome = overlay?.parentElement;
	let portaled = false;

	const portal = () => {
		if (portaled) return;
		portaled = true;
		// Carry CSS custom properties from container so user style controls still apply
		const inline = container.getAttribute('style') || '';
		if (overlay) {
			overlay.setAttribute('style', inline);
			overlay.classList.add('aae-a-menu-portal');
			document.body.appendChild(overlay);
		}
		if (nav) {
			nav.setAttribute('style', inline);
			nav.classList.add('aae-a-menu-portal');
			document.body.appendChild(nav);
		}
	};
	const unportal = () => {
		if (!portaled) return;
		portaled = false;
		overlay?.removeAttribute('style');
		nav?.removeAttribute('style');
		overlay?.classList.remove('aae-a-menu-portal');
		nav?.classList.remove('aae-a-menu-portal');
		if (overlay && overlayHome) overlayHome.appendChild(overlay);
		if (nav && navHome) navHome.appendChild(nav);
	};

	const set = (state) => {
		if (state) portal();
		container.classList.toggle('aae-a-menu--open', state);
		overlay?.classList.toggle('aae-a-menu--open', state);
		nav?.classList.toggle('aae-a-menu--open', state);
		toggle.classList.toggle('aae-a-menu-active', state);
		toggle.setAttribute('aria-expanded', state);
		document.body.style.overflow = state ? 'hidden' : '';
		if (!state) closeAllSubmenus(container);
	};

	toggle.addEventListener('click', (e) => { e.preventDefault(); set(!container.classList.contains('aae-a-menu--open')); });
	overlay?.addEventListener('click', () => set(false));
	close?.addEventListener('click', () => set(false));
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape') set(false); });
	window.addEventListener('resize', () => {
		if (window.innerWidth > bp) {
			set(false);
			unportal();
		}
	});
};

const initMenu = (container) => {
	const nav = container.querySelector('.aae-a-menu-nav');
	if (!nav) return;
	const toggle = container.querySelector('.aae-a-menu-toggle');
	const isHamburger = container.dataset.hamburger === 'true';

	const setup = () => {
		wireSubmenus(nav);
		if (isHamburger && toggle && !container.dataset.init) {
			container.dataset.init = '1';
			wireDrawer(container, toggle);
		}
	};

	const placeholder = nav.querySelector('.aae-a-menu-placeholder');
	if (placeholder && window.elementorFrontend?.isEditMode?.()) {
		const slug = nav.dataset.menuSlug;
		if (!slug) return setup();
		const url = elementorFrontend.config.ajaxurl || window.ajaxurl || '/wp-admin/admin-ajax.php';
		fetch(`${url}?action=aae_get_menu_html&menu=${encodeURIComponent(slug)}`)
			.then((r) => r.json())
			.then((d) => {
				if (d.success && d.data) (nav.querySelector('.aae-a-menu-nav-body') || nav).innerHTML = d.data;
				setup();
			})
			.catch(() => {});
		return;
	}
	setup();
};

register({
	elementType: 'e-aae-a-menu',
	id: 'aae-a-menu-handler',
	callback: ({ element }) => {
		const c = element.classList.contains('aae-a-menu') ? element : element.querySelector('.aae-a-menu');
		if (c) initMenu(c);
	},
});
