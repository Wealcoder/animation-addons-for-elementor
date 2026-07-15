/* eslint-env browser */

/**
 * The single global Lightbox overlay. Built lazily on first open and reused for
 * the lifetime of the page. Exposes openLightbox(slides, startIndex, opts).
 *
 * Responsibilities:
 *   - Build/own the overlay DOM (backdrop, stage, toolbar, nav, caption, thumbs)
 *   - ARIA dialog semantics + focus trap + restore focus on close
 *   - Navigate / loop / slideshow across the slide list
 *   - Delegate the stage content to the resolved content-type
 *   - Attach controllers (keyboard/wheel/touch/hash/zoom) on open, detach on close
 *   - Emit aae:lightbox:open / :change / :close CustomEvents
 */

import { resolveType, customToolbarButtons } from './registry';
import {
	attachKeyboard, attachWheel, attachTouch, attachHash, attachZoom,
} from './controllers';

const ICONS = {
	close: '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>',
	prev: '<svg viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></svg>',
	next: '<svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>',
	zoom: '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/></svg>',
	full: '<svg viewBox="0 0 24 24"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>',
	download: '<svg viewBox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>',
	play: '<svg viewBox="0 0 24 24"><path d="M7 4l12 8-12 8z"/></svg>',
	pause: '<svg viewBox="0 0 24 24"><path d="M8 5v14M16 5v14"/></svg>',
};

let ui = null;      // built DOM refs
let state = null;   // per-open session state

function buildUi() {
	if (ui) return ui;

	const root = document.createElement('div');
	root.className = 'aae-lb-root';
	root.setAttribute('role', 'dialog');
	root.setAttribute('aria-modal', 'true');
	root.setAttribute('aria-label', 'Media viewer');

	root.innerHTML = `
		<div class="aae-lb-backdrop" data-lb-close></div>
		<div class="aae-lb-counter" hidden></div>
		<div class="aae-lb-toolbar"></div>
		<button class="aae-lb-btn aae-lb-nav aae-lb-prev" aria-label="Previous">${ICONS.prev}</button>
		<div class="aae-lb-stage" aria-live="polite"></div>
		<button class="aae-lb-btn aae-lb-nav aae-lb-next" aria-label="Next">${ICONS.next}</button>
		<div class="aae-lb-caption" hidden>
			<p class="aae-lb-title"></p>
			<p class="aae-lb-desc"></p>
		</div>
		<div class="aae-lb-thumbs" hidden></div>
	`;
	document.body.appendChild(root);

	ui = {
		root,
		backdrop: root.querySelector('.aae-lb-backdrop'),
		counter: root.querySelector('.aae-lb-counter'),
		toolbar: root.querySelector('.aae-lb-toolbar'),
		prev: root.querySelector('.aae-lb-prev'),
		next: root.querySelector('.aae-lb-next'),
		stage: root.querySelector('.aae-lb-stage'),
		caption: root.querySelector('.aae-lb-caption'),
		title: root.querySelector('.aae-lb-title'),
		desc: root.querySelector('.aae-lb-desc'),
		thumbs: root.querySelector('.aae-lb-thumbs'),
	};

	// Static wiring (persists across opens — the delegated close/nav handlers
	// read the live `state`, so they're safe to bind once).
	ui.prev.addEventListener('click', () => state && state.api.prev());
	ui.next.addEventListener('click', () => state && state.api.next());
	root.addEventListener('click', (e) => {
		if (e.target.closest('[data-lb-close]')) state && state.api.close();
	});

	return ui;
}

function emit(name, detail) {
	if (ui) ui.root.dispatchEvent(new CustomEvent('aae:lightbox:' + name, { detail, bubbles: true }));
}

function buildToolbar() {
	// Behaviour flags come from the slides (uniform across a gallery). Default
	// to on for zoom, off for download when a slide omits them.
	const opt = state.slides[0] || {};
	const wantZoom = opt.zoom !== false;
	const wantDownload = opt.download === true;

	const buttons = [];
	if (wantZoom) {
		buttons.push({ id: 'zoom', icon: ICONS.zoom, title: 'Zoom', onClick: () => state.zoom.toggle() });
	}
	buttons.push({ id: 'fullscreen', icon: ICONS.full, title: 'Fullscreen', onClick: toggleFullscreen });
	if (wantDownload) {
		buttons.push({ id: 'download', icon: ICONS.download, title: 'Download', onClick: downloadCurrent });
	}
	if (state.slides.length > 1) {
		buttons.push({ id: 'slideshow', icon: ICONS.play, title: 'Slideshow', onClick: toggleSlideshow });
	}
	customToolbarButtons().forEach((b) => buttons.push(b));
	buttons.push({ id: 'close', icon: ICONS.close, title: 'Close', onClick: () => state.api.close() });

	ui.toolbar.innerHTML = '';
	buttons.forEach((b) => {
		const el = document.createElement('button');
		el.type = 'button';
		el.className = 'aae-lb-btn';
		el.dataset.lbAction = b.id;
		el.setAttribute('aria-label', b.title || b.id);
		el.title = b.title || b.id;
		el.innerHTML = b.icon || b.id;
		el.addEventListener('click', () => b.onClick(state.api, state.slides[state.i]));
		ui.toolbar.appendChild(el);
	});
}

function toggleFullscreen() {
	const el = ui.root;
	if (!document.fullscreenElement) {
		if (el.requestFullscreen) el.requestFullscreen().catch(() => {});
	} else if (document.exitFullscreen) {
		document.exitFullscreen().catch(() => {});
	}
}

function downloadCurrent() {
	const slide = state.slides[state.i];
	if (!slide || !slide.src) return;
	const a = document.createElement('a');
	a.href = slide.src;
	a.download = (slide.title || 'image').replace(/[^a-z0-9_-]+/gi, '-');
	a.target = '_blank';
	a.rel = 'noopener';
	document.body.appendChild(a);
	a.click();
	document.body.removeChild(a);
}

function toggleSlideshow() {
	if (state.timer) {
		clearInterval(state.timer);
		state.timer = null;
		setActionIcon('slideshow', ICONS.play);
	} else {
		state.timer = setInterval(() => state.api.next(), 3200);
		setActionIcon('slideshow', ICONS.pause);
	}
}

function setActionIcon(action, icon) {
	const btn = ui.toolbar.querySelector(`[data-lb-action="${action}"]`);
	if (btn) btn.innerHTML = icon;
}

function buildThumbs() {
	if (state.slides.length <= 1) {
		ui.thumbs.hidden = true;
		return;
	}
	ui.thumbs.hidden = false;
	ui.thumbs.innerHTML = '';
	state.slides.forEach((slide, idx) => {
		const t = document.createElement('img');
		t.className = 'aae-lb-thumb';
		t.src = slide.thumb || slide.src;
		t.alt = '';
		t.loading = 'lazy';
		t.addEventListener('click', () => state.api.goTo(idx));
		ui.thumbs.appendChild(t);
	});
}

function renderSlide() {
	const slide = state.slides[state.i];

	// Tear down previous content + zoom.
	if (state.content && state.content.destroy) state.content.destroy();
	if (state.zoom) state.zoom.reset();

	const type = resolveType(slide);
	state.content = type.render(slide, ui.stage);

	// Caption / title.
	const hasText = !!(slide.title || slide.caption);
	ui.caption.hidden = !hasText;
	ui.title.textContent = slide.title || '';
	ui.title.hidden = !slide.title;
	ui.desc.textContent = slide.caption || slide.desc || '';
	ui.desc.hidden = !(slide.caption || slide.desc);

	// Counter (honors the per-gallery `counter` flag; default on).
	const multi = state.slides.length > 1;
	const showCounter = multi && slide.counter !== false;
	ui.counter.hidden = !showCounter;
	if (showCounter) ui.counter.textContent = `${state.i + 1} / ${state.slides.length}`;

	// Nav visibility (hidden for single, always shown for loop).
	ui.prev.hidden = !multi;
	ui.next.hidden = !multi;

	// Active thumb.
	const thumbs = ui.thumbs.querySelectorAll('.aae-lb-thumb');
	thumbs.forEach((t, idx) => t.classList.toggle('is-active', idx === state.i));

	// Preload neighbours.
	preload(state.i + 1);
	preload(state.i - 1);

	emit('change', { index: state.i, slide });
}

function preload(idx) {
	const n = state.slides.length;
	const wrapped = ((idx % n) + n) % n;
	const slide = state.slides[wrapped];
	if (slide && (!slide.type || slide.type === 'image')) {
		const im = new Image();
		im.src = slide.src;
	}
}

/* ---------- focus trap ---------- */
function trapFocus(e) {
	if (e.key !== 'Tab') return;
	const focusable = ui.root.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])');
	if (!focusable.length) return;
	const first = focusable[0];
	const last = focusable[focusable.length - 1];
	if (e.shiftKey && document.activeElement === first) {
		last.focus();
		e.preventDefault();
	} else if (!e.shiftKey && document.activeElement === last) {
		first.focus();
		e.preventDefault();
	}
}

export function openLightbox(slides, startIndex, opts = {}) {
	if (!slides || !slides.length) return;
	buildUi();

	const restoreFocus = document.activeElement;

	state = {
		slides,
		i: Math.max(0, Math.min(startIndex || 0, slides.length - 1)),
		groupId: opts.groupId || '',
		content: null,
		zoom: null,
		timer: null,
		disposers: [],
		changeSubs: [],
		restoreFocus,
	};

	// The API handed to controllers + toolbar buttons + custom code.
	state.api = {
		next() { state.api.goTo(state.i + 1); },
		prev() { state.api.goTo(state.i - 1); },
		goTo(idx) {
			const n = state.slides.length;
			// Honor the per-gallery `loop` flag: wrap when looping (default),
			// clamp to [0, n-1] when loop is disabled.
			const loop = (state.slides[0] || {}).loop !== false;
			state.i = loop
				? ((idx % n) + n) % n
				: Math.max(0, Math.min(idx, n - 1));
			renderSlide();
			state.changeSubs.forEach((fn) => { try { fn(); } catch (_) { /* ignore */ } });
		},
		index() { return state.i; },
		count() { return state.slides.length; },
		groupId() { return state.groupId; },
		onChange(fn) {
			state.changeSubs.push(fn);
			return () => {
				state.changeSubs = state.changeSubs.filter((f) => f !== fn);
			};
		},
		close(fromHistory) { closeLightbox(fromHistory); },
	};

	// Animation flavor from the first slide's config.
	ui.root.classList.remove('anim-fade', 'anim-slide', 'anim-zoom');
	const anim = (slides[state.i] && slides[state.i].anim) || 'zoom';
	ui.root.classList.add('anim-' + anim);

	state.zoom = attachZoom(() => (state.content ? state.content.el : null));

	buildToolbar();
	buildThumbs();
	renderSlide();

	// Attach controllers.
	state.disposers.push(attachKeyboard(state.api));
	state.disposers.push(attachWheel(state.api, ui.root));
	state.disposers.push(attachTouch(state.api, ui.root));
	state.disposers.push(attachHash(state.api));
	ui.root.addEventListener('keydown', trapFocus);

	// Lock scroll + open.
	state.prevOverflow = document.documentElement.style.overflow;
	document.documentElement.style.overflow = 'hidden';
	requestAnimationFrame(() => {
		ui.root.classList.add('is-open');
		const closeBtn = ui.toolbar.querySelector('[data-lb-action="close"]');
		if (closeBtn) closeBtn.focus();
	});

	emit('open', { index: state.i, count: slides.length, groupId: state.groupId });
}

function closeLightbox(fromHistory) {
	if (!state) return;

	if (state.timer) clearInterval(state.timer);
	state.disposers.forEach((d) => { try { d(); } catch (_) { /* ignore */ } });
	if (state.zoom) state.zoom.dispose();
	ui.root.removeEventListener('keydown', trapFocus);

	// popstate-driven close must not re-manipulate history (attachHash's
	// disposer already handles hash cleanup for click/Esc closes).
	if (fromHistory) state._skipHash = true;

	ui.root.classList.remove('is-open');
	document.documentElement.style.overflow = state.prevOverflow || '';

	if (document.fullscreenElement && document.exitFullscreen) {
		document.exitFullscreen().catch(() => {});
	}

	const finish = () => {
		if (state && state.content && state.content.destroy) state.content.destroy();
		ui.stage.innerHTML = '';
		emit('close', {});
		const rf = state && state.restoreFocus;
		if (rf && typeof rf.focus === 'function') { try { rf.focus(); } catch (_) { /* ignore */ } }
		state = null;
	};

	// Wait for the fade-out (matches CSS 280ms); guard if reduced-motion.
	const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (reduce) finish();
	else setTimeout(finish, 300);
}
