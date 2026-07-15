/* eslint-env browser */

/**
 * Video content-type. Plays YouTube, Vimeo, and self-hosted (mp4/webm/ogv)
 * sources inside the lightbox stage.
 *
 * A slide qualifies when `slide.type === 'video'`. `slide.src` is the raw URL;
 * this module normalizes it into an autoplaying embed (YouTube/Vimeo) or a
 * native <video> element (self-hosted).
 *
 * Contract (shared with every content-type):
 *   { name, match(slide), render(slide, stage) -> { el, destroy } }
 *
 * destroy() removes the element, which stops YouTube/Vimeo playback (the iframe
 * is torn down) and pauses/frees a native <video>. Called by overlay.js on
 * slide change and on close.
 */

const YT = /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/i;
const VIMEO = /vimeo\.com\/(?:video\/)?(\d+)/i;
const FILE = /\.(mp4|webm|ogv|ogg|mov)(\?.*)?$/i;

export function isVideoUrl(url) {
	if (!url) return false;
	return YT.test(url) || VIMEO.test(url) || FILE.test(url);
}

function youtubeId(url) {
	const m = url.match(YT);
	return m ? m[1] : '';
}

function vimeoId(url) {
	const m = url.match(VIMEO);
	return m ? m[1] : '';
}

export const videoType = {
	name: 'video',

	match(slide) {
		return slide.type === 'video';
	},

	render(slide, stage) {
		const src = slide.src || '';
		const wrap = document.createElement('div');
		wrap.className = 'aae-lb-video';

		let media = null;

		const yt = youtubeId(src);
		const vm = vimeoId(src);

		if (yt) {
			media = document.createElement('iframe');
			media.className = 'aae-lb-video-frame';
			media.src = `https://www.youtube-nocookie.com/embed/${yt}?autoplay=1&rel=0&playsinline=1`;
			media.allow = 'autoplay; fullscreen; encrypted-media; picture-in-picture';
			media.setAttribute('allowfullscreen', '');
			media.setAttribute('frameborder', '0');
		} else if (vm) {
			media = document.createElement('iframe');
			media.className = 'aae-lb-video-frame';
			media.src = `https://player.vimeo.com/video/${vm}?autoplay=1`;
			media.allow = 'autoplay; fullscreen; picture-in-picture';
			media.setAttribute('allowfullscreen', '');
			media.setAttribute('frameborder', '0');
		} else {
			// Self-hosted / direct file.
			media = document.createElement('video');
			media.className = 'aae-lb-video-el';
			media.src = src;
			media.controls = true;
			media.autoplay = true;
			media.playsInline = true;
			media.setAttribute('playsinline', '');
		}

		wrap.appendChild(media);
		stage.appendChild(wrap);

		return {
			el: wrap,
			destroy() {
				// Pause native video before removal so audio stops immediately.
				if (media && media.tagName === 'VIDEO') {
					try { media.pause(); media.removeAttribute('src'); media.load(); } catch (_) { /* ignore */ }
				}
				if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
			},
		};
	},
};
