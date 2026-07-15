/* eslint-env browser */

/**
 * Container-level lightbox discovery.
 *
 * A container marked with a config in window.AAE_INTERACTIONS_LBC[<interactionId>]
 * turns every eligible child image into a grouped trigger. Nothing is bound per
 * child: the single delegated document listener (index.js) resolves a click to
 * its nearest lightbox container, and this module discovers + reads the child
 * images straight from the live DOM — so Loop Grid / AJAX / infinite-scroll /
 * nested content all work with zero re-initialization.
 *
 * Slide source resolution (per child), best → fallback:
 *   1. data-lb-src        (widget-provided full-size URL)
 *   2. <a href>           (when the child is/contains an image link)
 *   3. <img src>          (the rendered image, may be a thumbnail)
 */

import { isVideoUrl } from './content-types/video';

const { configFor } = window.AAEADDON;
export const LBC_MAP = 'AAE_INTERACTIONS_LBC';

/** interaction-id → config lookup, walking up to the nearest LBC container. */
export function containerFor(node) {
	let el = node;
	while (el && el.nodeType === 1) {
		if (el.dataset && el.dataset.interactionId) {
			const cfg = configFor(el, LBC_MAP);
			if (cfg) return { el, cfg };
		}
		el = el.parentElement;
	}
	return null;
}

/** Is this node inside any lightbox container? (cheap check for delegation.) */
export function isInContainer(node) {
	return !!containerFor(node);
}

function fullUrl(el) {
	// el is the matched child (img or a). Prefer an explicit full-size attr.
	if (el.dataset && el.dataset.lbSrc) return el.dataset.lbSrc;

	if (el.tagName === 'A' && el.getAttribute('href')) {
		return el.getAttribute('href');
	}
	if (el.tagName === 'IMG') {
		// A wrapping <a href> (image link) beats the <img src> thumbnail.
		const link = el.closest('a[href]');
		if (link && /\.(jpe?g|png|webp|gif|avif|svg)(\?.*)?$/i.test(link.getAttribute('href'))) {
			return link.getAttribute('href');
		}
		return el.currentSrc || el.getAttribute('src') || '';
	}
	// Container/link that holds an <img>.
	const img = el.querySelector && el.querySelector('img');
	if (img) return img.currentSrc || img.getAttribute('src') || '';
	return '';
}

function captionFor(el, source) {
	if (!source || source === 'none') return '';
	const img = el.tagName === 'IMG' ? el : (el.querySelector && el.querySelector('img'));
	if (!img) return '';
	if (source === 'alt') return img.getAttribute('alt') || '';
	if (source === 'title' || source === 'caption') {
		return img.getAttribute('title') || img.getAttribute('alt') || '';
	}
	return '';
}

const PLACEHOLDER = /placeholder(-v\d+)?\.(svg|png|gif)(\?.*)?$/i;

/**
 * Direct children of the container (skipping our own injected nodes and empty
 * text nodes). In content mode a selector may narrow this to specific children.
 */
function directChildren(containerEl, selector) {
	const kids = Array.prototype.slice.call(containerEl.children).filter(
		(el) => el.nodeType === 1 && !el.classList.contains('aae-children-injector')
	);
	if (!selector) return kids;
	// A selector in content mode restricts which direct children qualify.
	return kids.filter((el) => el.matches(selector));
}

/**
 * Resolve a video URL for a content-mode child, best → fallback:
 *   1. data-lb-video          (explicit, on the child or a descendant)
 *   2. <a href> to a video    (YouTube / Vimeo / mp4 …)
 *   3. nested <iframe>/<video> src
 * Returns '' when the child isn't a video.
 */
function videoSrcOf(child) {
	const tagEl = child.matches('[data-lb-video]') ? child : child.querySelector('[data-lb-video]');
	if (tagEl) {
		const tagged = tagEl.getAttribute('data-lb-video');
		if (tagged) return tagged;
	}

	const link = child.matches('a[href]') ? child : child.querySelector('a[href]');
	if (link && isVideoUrl(link.getAttribute('href'))) {
		return link.getAttribute('href');
	}

	const frame = child.matches('iframe[src]') ? child : child.querySelector('iframe[src]');
	if (frame && isVideoUrl(frame.getAttribute('src'))) {
		return frame.getAttribute('src');
	}

	const vid = child.matches('video') ? child : child.querySelector('video');
	if (vid) {
		const s = vid.getAttribute('src') || (vid.querySelector('source') || {}).src || '';
		if (s) return s;
	}

	return '';
}

/** First usable (non-placeholder) image URL for a content-mode child. */
function imageSrcOf(child) {
	const src = fullUrl(child);
	return src && !PLACEHOLDER.test(src) ? src : '';
}

/**
 * Content mode: each direct child becomes an IMAGE or VIDEO slide.
 *   - video child (data-lb-video / video link / nested iframe|video) → video
 *   - else, first image inside the child                            → image
 *   - a child with neither is skipped (no HTML/content slides)
 * Clicking anywhere inside a child opens it. Slides stay in DOM order, and the
 * start index tracks the clicked child across skipped ones.
 */
function collectContent(containerEl, cfg, clicked) {
	const kids = directChildren(containerEl, cfg.selector);

	const slides = [];
	let startIndex = 0;

	kids.forEach((child) => {
		const isClicked = child === clicked || child.contains(clicked);

		const videoSrc = videoSrcOf(child);
		if (videoSrc) {
			if (isClicked) startIndex = slides.length;
			slides.push({
				type: 'video',
				src: videoSrc,
				anim: cfg.anim || 'zoom',
				zoom: false,
				loop: cfg.loop !== false,
				download: false,
				counter: cfg.counter !== false,
			});
			return;
		}

		const imgSrc = imageSrcOf(child);
		if (imgSrc) {
			if (isClicked) startIndex = slides.length;
			slides.push({
				type: 'image',
				src: imgSrc,
				thumb: imgSrc,
				caption: captionFor(child, cfg.captionSrc),
				anim: cfg.anim || 'zoom',
				zoom: cfg.zoom !== false,
				loop: cfg.loop !== false,
				download: cfg.download === true,
				counter: cfg.counter !== false,
			});
		}
		// Children with no image and no video are skipped.
	});

	return { slides, startIndex, groupId: cfg.group || '' };
}

/**
 * Build the slide list for a container. Returns slides in DOM order plus the
 * index of the clicked child. Dispatches on cfg.mode.
 *
 * @param {HTMLElement} containerEl
 * @param {object}      cfg          the container's LBC config
 * @param {HTMLElement} clicked      the element that received the click
 * @returns {{ slides: object[], startIndex: number, groupId: string }}
 */
export function collectContainer(containerEl, cfg, clicked) {
	if (cfg.mode === 'content') {
		return collectContent(containerEl, cfg, clicked);
	}
	const selector = cfg.selector || 'img';
	const matches = Array.prototype.slice.call(containerEl.querySelectorAll(selector));

	const slides = [];
	let startIndex = 0;
	let clickedMatch = null;

	// Which matched element does the click belong to? (click may land on a child
	// of the matched node — e.g. the <img> inside a matched <a>.)
	for (let i = 0; i < matches.length; i += 1) {
		if (matches[i] === clicked || matches[i].contains(clicked)) {
			clickedMatch = matches[i];
			break;
		}
	}

	matches.forEach((el) => {
		const src = fullUrl(el);
		if (!src || PLACEHOLDER.test(src)) return;

		if (el === clickedMatch) startIndex = slides.length;

		slides.push({
			type: 'image',
			src,
			thumb: src,
			caption: captionFor(el, cfg.captionSrc),
			anim: cfg.anim || 'zoom',
			zoom: cfg.zoom !== false,
			loop: cfg.loop !== false,
			download: cfg.download === true,
			counter: cfg.counter !== false,
		});
	});

	return { slides, startIndex, groupId: cfg.group || '' };
}
