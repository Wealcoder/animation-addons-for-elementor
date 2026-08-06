import { register } from '@elementor/frontend-handlers';

/* AAE Atomic Menu — vanilla JS. CSS handles transitions for desktop;
   mobile sub-menu uses Web Animations API for guaranteed visible effect. */

const DROPDOWN_EFFECT_KEYFRAMES = {
	slide:        [{ opacity: 0, transform: 'translateY(-18px) scale(0.95)' }, { opacity: 1, transform: 'translateY(0) scale(1)' }],
	fade:         [{ opacity: 0 }, { opacity: 1 }],
	'slide-fade': [{ opacity: 0, transform: 'translateY(-40px)' }, { opacity: 1, transform: 'translateY(0)' }],
	scale:        [{ opacity: 0, transform: 'scale(0.55)' }, { opacity: 1, transform: 'scale(1)' }],
	zoom:         [{ opacity: 0, transform: 'scale(0.2)' }, { opacity: 1, transform: 'scale(1)' }],
	flip:         [{ opacity: 0, transform: 'perspective(800px) rotateX(-90deg)' }, { opacity: 1, transform: 'perspective(800px) rotateX(0deg)' }],
};

/* Drawer effects — applied as inline styles on the root + .aae-a-menu-nav.
   from/to drive the CSS transition via the --aae-drawer-*-transform custom
   props; navStyle handles per-effect anchor / dimensions (slide-top is full
   width pinned to top, fade/zoom-in are centered, etc.). */
const DRAWER_EFFECTS = {
	'slide-left':   { from: 'translateX(-100%)', to: 'translateX(0)' },
	'slide-right':  { from: 'translateX(100%)',  to: 'translateX(0)',  navStyle: { left: 'auto', right: '0', boxShadow: '-4px 0 32px rgba(0,0,0,0.18)' } },
	'slide-top':    { from: 'translateY(-100%)', to: 'translateY(0)',  navStyle: { left: '0', right: '0', top: '0', bottom: 'auto', width: '100%', maxWidth: '100vw', height: 'auto', maxHeight: '85vh', boxShadow: '0 4px 32px rgba(0,0,0,0.18)' } },
	'slide-bottom': { from: 'translateY(100%)',  to: 'translateY(0)',  navStyle: { left: '0', right: '0', top: 'auto', bottom: '0', width: '100%', maxWidth: '100vw', height: 'auto', maxHeight: '85vh', boxShadow: '0 -4px 32px rgba(0,0,0,0.18)' } },
	fade:           { from: 'none', to: 'none', navStyle: { left: '50%', right: 'auto', top: '50%', bottom: 'auto', width: 'min(var(--aae-drawer-w),92vw)', height: 'min(80vh,600px)', margin: 'calc(-1 * min(80vh,600px) / 2) 0 0 calc(-1 * min(var(--aae-drawer-w),92vw) / 2)', borderRadius: 'var(--aae-dd-r)', transformOrigin: 'center center' } },
	scale:          { from: 'scale(0.7)', to: 'scale(1)', navStyle: { transformOrigin: 'left center' } },
	'zoom-in':      { from: 'scale(0)',   to: 'scale(1)', navStyle: { left: '50%', right: 'auto', top: '50%', bottom: 'auto', width: 'min(var(--aae-drawer-w),92vw)', height: 'min(80vh,600px)', margin: 'calc(-1 * min(80vh,600px) / 2) 0 0 calc(-1 * min(var(--aae-drawer-w),92vw) / 2)', borderRadius: 'var(--aae-dd-r)', transformOrigin: 'center center' } },
	flip:           { from: 'perspective(1200px) rotateY(-90deg)', to: 'perspective(1200px) rotateY(0deg)', navStyle: { transformOrigin: 'left center' } },
};

// Inline-style keys we may write on the drawer nav per effect — used to
// fully reset before applying a new effect (so switching effects in the
// editor doesn't leave stale inline styles behind).
const DRAWER_NAV_RESET_KEYS = ['left','right','top','bottom','width','maxWidth','height','maxHeight','margin','borderRadius','transformOrigin','boxShadow'];

const applyDrawerEffect = (root, nav, effectName) => {
	const eff = DRAWER_EFFECTS[effectName] || DRAWER_EFFECTS['slide-left'];
	root.style.setProperty('--aae-drawer-from-transform', eff.from);
	root.style.setProperty('--aae-drawer-to-transform', eff.to);
	DRAWER_NAV_RESET_KEYS.forEach((k) => { nav.style[k] = ''; });
	if (eff.navStyle) Object.assign(nav.style, eff.navStyle);
};

const playSubMenuEffect = (subMenu, effectName, durationMs, direction, onFinish) => {
	const done = (cancelled) => { if (typeof onFinish === 'function') onFinish(cancelled === true); };
	if (!subMenu || typeof subMenu.animate !== 'function') { done(); return; }
	const baseFrames = DROPDOWN_EFFECT_KEYFRAMES[effectName] || DROPDOWN_EFFECT_KEYFRAMES.slide;
	const frames = direction === 'close' ? [baseFrames[1], baseFrames[0]] : baseFrames;
	const easing = direction === 'close' ? 'cubic-bezier(.4, 0, .2, 1)' : 'cubic-bezier(.2, .8, .2, 1)';

	// Cancel any in-flight animation on this sub-menu so the new one starts clean
	if (typeof subMenu.getAnimations === 'function') {
		subMenu.getAnimations().forEach((a) => { try { a.cancel(); } catch (e) {} });
	}

	subMenu.style.transformOrigin = 'top center';
	subMenu.style.willChange = 'transform, opacity';

	// Wait one frame so display:none → display:flex has been committed and the
	// element has computed layout before the animation starts.
	requestAnimationFrame(() => {
		try {
			const anim = subMenu.animate(frames, {
				duration: durationMs,
				easing: easing,
				fill: 'both',
			});
			const cleanup = (cancelled) => {
				subMenu.style.willChange = '';
				done(cancelled);
			};
			if (anim && anim.finished && typeof anim.finished.then === 'function') {
				anim.finished.then(() => cleanup(false), () => cleanup(true));
			} else if (anim && typeof anim.addEventListener === 'function') {
				anim.addEventListener('finish', () => cleanup(false));
				anim.addEventListener('cancel', () => cleanup(true));
			} else {
				done();
			}
		} catch (e) { done(); }
	});
};

const initMenu = (root) => {
	if (root.dataset.aaeMenuInit === '1') return;
	root.dataset.aaeMenuInit = '1';

	const nav      = root.querySelector('.aae-a-menu-nav');
	const toggle   = root.querySelector('.aae-a-menu-toggle');
	const overlay  = root.querySelector('.aae-a-menu-overlay');
	const closeBtn = root.querySelector('.aae-a-menu-close');
	if (!nav) return;

	const breakpoint     = parseInt(root.getAttribute('data-breakpoint'), 10) || 768;
	const isHamburger    = root.getAttribute('data-hamburger') === 'true';
	const isMobile       = () => window.innerWidth <= breakpoint;
	let   dropdownEffect = root.getAttribute('data-dropdown-effect') || 'slide';
	const transitionMs   = (parseInt(root.style.getPropertyValue('--aae-menu-transition'), 10) || 250);

	// Apply drawer effect (transform vars + per-effect nav positioning) on init,
	// and keep it in sync when the editor changes the data-drawer-effect attribute.
	applyDrawerEffect(root, nav, root.getAttribute('data-drawer-effect') || 'slide-left');
	if (typeof MutationObserver !== 'undefined') {
		new MutationObserver(() => {
			applyDrawerEffect(root, nav, root.getAttribute('data-drawer-effect') || 'slide-left');
			dropdownEffect = root.getAttribute('data-dropdown-effect') || 'slide';
		}).observe(root, { attributes: true, attributeFilter: ['data-drawer-effect', 'data-dropdown-effect'] });
	}

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

				const wasOpen = item.classList.contains('aae-a-menu-item--open');
				const duration = Math.round(transitionMs * 1.4);

				if (wasOpen) {
					// Closing — on mobile, play the reverse effect THEN remove the class
					// so the close transition is visible. On desktop, remove immediately
					// (CSS handles the desktop transition).
					if (isMobile()) {
						arrow.setAttribute('aria-expanded', 'false');
						playSubMenuEffect(subMenu, dropdownEffect, duration, 'close', () => {
							item.classList.remove('aae-a-menu-item--open');
						});
					} else {
						item.classList.remove('aae-a-menu-item--open');
						arrow.setAttribute('aria-expanded', 'false');
					}
					return;
				}

				// Opening
				item.classList.add('aae-a-menu-item--open');
				arrow.setAttribute('aria-expanded', 'true');

				// Close siblings at the same level
				if (item.parentElement) {
					Array.from(item.parentElement.children).forEach((sib) => {
						if (sib !== item && sib.classList && sib.classList.contains('aae-a-menu-item--open')) {
							const sArrow = sib.querySelector(':scope > .aae-a-menu-arrow');
							const sSub   = sib.querySelector(':scope > .sub-menu');
							sib.classList.remove('aae-a-menu-item--open');
							if (sArrow) sArrow.setAttribute('aria-expanded', 'false');
							if (isMobile() && sSub) {
								// Sibling closes simultaneously — just cancel its animation;
								// removing the class above hides it via CSS.
								if (typeof sSub.getAnimations === 'function') {
									sSub.getAnimations().forEach((a) => { try { a.cancel(); } catch (e) {} });
								}
							}
						}
					});
				}

				// Mobile-only: play the picked dropdown effect via Web Animations API.
				if (isMobile()) {
					playSubMenuEffect(subMenu, dropdownEffect, duration, 'open');
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

	/* ---------- Editor preview AJAX fallback ----------
	   The menu markup is built by wp_nav_menu() in get_atomic_settings(), which
	   is PHP — so it only exists on a server render. The editor canvas renders
	   this Twig CLIENT-side, where `settings.rendered_menu` is simply absent and
	   the template falls through to `.aae-a-menu-placeholder`. Fetching the real
	   markup is the only way the builder ever sees their menu.

	   The trigger is the PLACEHOLDER, not an edit-mode test. That is deliberate:
	   `elementorFrontend.isEditMode()` is not dependable inside the v4 canvas
	   (counter.js already pairs it with an `elementor-editor-active` body check
	   for the same reason), and gating on it meant one false negative left the
	   builder staring at "Menu rendered on frontend / preview." forever. The
	   placeholder is the exact, self-scoping signal for "this render has no menu
	   HTML in it" — on the frontend get_atomic_settings() always fills
	   `rendered_menu`, so the placeholder is never present and this never runs. */
	const placeholder = nav.querySelector('.aae-a-menu-placeholder');
	const body = nav.querySelector('.aae-a-menu-nav-body');
	const slug = nav.getAttribute('data-menu-slug');

	if (placeholder && slug) {
		// AAE_MENU_CFG.ajaxUrl is admin_url('admin-ajax.php'), localized onto
		// this handle in the editor preview. It is FIRST because it is the only
		// entry that is right on every install layout — the root-relative last
		// resort points at the NETWORK MAIN SITE on a subdirectory multisite
		// (a subsite's admin is /<site>/wp-admin/), which is why the editor
		// preview worked on a single site and silently failed on a network.
		const ajaxUrl = (window.AAE_MENU_CFG && window.AAE_MENU_CFG.ajaxUrl)
			|| (window.elementorFrontend
			&& window.elementorFrontend.config
			&& window.elementorFrontend.config.ajaxurl)
			|| window.ajaxurl
			|| '/wp-admin/admin-ajax.php';
		fetch(`${ajaxUrl}?action=aae_get_menu_html&menu=${encodeURIComponent(slug)}`, {
			// admin-ajax authenticates by cookie; without this the request is
			// anonymous, the priv-only action never matches and it 400s.
			credentials: 'same-origin',
		})
			.then((r) => r.json())
			.then((data) => {
				if (data && data.success && data.data && body) {
					body.innerHTML = data.data;
					closeAllSubmenus = buildDropdowns();
				}
			})
			.catch(() => {});
	} else if (placeholder && ! slug) {
		// No menu chosen yet. Say so, instead of the frontend/preview line that
		// reads as "this is fine, it'll show later" when in fact nothing will.
		placeholder.textContent = 'Please select a menu';
		closeAllSubmenus = buildDropdowns();
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


