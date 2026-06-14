import { register } from '@elementor/frontend-handlers';

const initMenu = (container) => {
	const nav = container.querySelector('.aae-a-menu-nav');
	const toggle = container.querySelector('.aae-a-menu-toggle');
	if (!nav) return;

	const isHamburger = container.getAttribute('data-hamburger') === 'true';
	const initDropdowns = () => {
		const menuList = nav.querySelector('.aae-a-menu-list');
		if (!menuList) return;

		// GSAP Hover animations for dropdowns
		const menuItemsWithChildren = menuList.querySelectorAll('.menu-item-has-children');
		
		menuItemsWithChildren.forEach(item => {
			const subMenu = item.querySelector('.sub-menu');
			if (!subMenu) return;

			// Initial state
			if (typeof window.gsap !== 'undefined') {
				window.gsap.set(subMenu, { autoAlpha: 0, y: 15, display: 'none' });
			}

		let hoverIntentTimeout;

		const openMenu = () => {
			clearTimeout(hoverIntentTimeout);
			if (typeof window.gsap !== 'undefined') {
				window.gsap.to(subMenu, {
					autoAlpha: 1,
					y: 0,
					display: 'block',
					duration: 0.3,
					ease: "power2.out",
					overwrite: true
				});
			} else {
				subMenu.style.display = 'block';
			}
		};

		const closeMenu = () => {
			hoverIntentTimeout = setTimeout(() => {
				if (typeof window.gsap !== 'undefined') {
					window.gsap.to(subMenu, {
						autoAlpha: 0,
						y: 15,
						display: 'none',
						duration: 0.2,
						ease: "power2.in",
						overwrite: true
					});
				} else {
					subMenu.style.display = 'none';
				}
			}, 100);
		};

		item.addEventListener('mouseenter', openMenu);
		item.addEventListener('mouseleave', closeMenu);
		
		// Accessibility / touch focus
			const link = item.querySelector('a');
			if (link) {
				link.addEventListener('focus', openMenu);
				link.addEventListener('blur', closeMenu);
			}
		});
	};

	const placeholder = nav.querySelector('.aae-a-menu-placeholder');
	if (placeholder && typeof window.elementorFrontend !== 'undefined' && window.elementorFrontend.isEditMode()) {
		const slug = nav.getAttribute('data-menu-slug');
		if (slug) {
			const ajaxUrl = elementorFrontend.config.ajaxurl || (window.ajaxurl ? window.ajaxurl : '/wp-admin/admin-ajax.php');
			fetch(`${ajaxUrl}?action=aae_get_menu_html&menu=${encodeURIComponent(slug)}`)
				.then(res => res.json())
				.then(data => {
					if (data.success && data.data) {
						nav.innerHTML = data.data;
						initDropdowns();
					}
				})
				.catch(err => console.error(err));
		}
	} else {
		initDropdowns();
	}

	// Mobile Hamburger Toggle
	if (isHamburger && toggle && !container.dataset.hamburgerInit) {
		container.dataset.hamburgerInit = 'true';
		let isOpen = false;
		
		// Initial state for mobile
		const checkMobile = () => window.innerWidth <= 768;

		const setMobileInitialState = () => {
			if (checkMobile()) {
				if (typeof window.gsap !== 'undefined') {
					window.gsap.set(nav, { height: 0, overflow: 'hidden', autoAlpha: 0 });
				} else {
					nav.style.display = 'none';
				}
			} else {
				// Reset for desktop
				if (typeof window.gsap !== 'undefined') {
					window.gsap.set(nav, { height: 'auto', overflow: 'visible', autoAlpha: 1 });
				} else {
					nav.style.display = 'block';
				}
				isOpen = false;
				toggle.classList.remove('aae-a-menu-active');
				toggle.setAttribute('aria-expanded', 'false');
			}
		};

		setMobileInitialState();
		window.addEventListener('resize', setMobileInitialState);

		toggle.addEventListener('click', (e) => {
			e.preventDefault();
			if (!checkMobile()) return;

			isOpen = !isOpen;
			toggle.classList.toggle('aae-a-menu-active', isOpen);
			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

			if (typeof window.gsap !== 'undefined') {
				if (isOpen) {
					window.gsap.to(nav, {
						height: 'auto',
						autoAlpha: 1,
						duration: 0.4,
						ease: "power3.out"
					});
				} else {
					window.gsap.to(nav, {
						height: 0,
						autoAlpha: 0,
						duration: 0.3,
						ease: "power3.in"
					});
				}
			} else {
				nav.style.display = isOpen ? 'block' : 'none';
			}
		});
	}
};

register({
	elementType: 'e-aae-a-menu',
	id: 'aae-a-menu-handler',
	callback: ( { element } ) => {
		const container = element.classList.contains('aae-a-menu') ? element : element.querySelector('.aae-a-menu');
		if (container) {
			initMenu(container);
		}
	}
});
