/* eslint-env browser */

/**
 * Widget-picker card branding (logo badge, top-left).
 *
 * The classic Marionette "Add Element" panel (#elementor-panel-elements)
 * renders every widget as:
 *   <button class="elementor-element" data-library-element-type="e-aae-a-…">
 *     <i class="eicon-atomic"></i>            (Elementor core marker, top-right)
 *     <div class="icon"><i class="eicon-…"></i></div>
 *     <div class="title-wrapper"><div class="title">AAE …</div></div>
 *   </button>
 *
 * "AAE " title prefix stays as-is for now (removed in a later pass) — this
 * only adds a small wcf-logo badge to the top-left corner, mirroring where
 * eicon-atomic already sits top-right, so every AAE widget card reads as
 * branded without touching the existing icon/title markup.
 *
 * Same MutationObserver pattern as ../responsive-section/section-branding.js:
 * cards render/re-render outside our control (search filtering, category
 * expand/collapse), so watch the whole panel and brand on sight instead of
 * hooking one render path.
 */

let started = false;
let scanRafId = null;
let isScanning = false;

function brandCard(card) {
	if (!card || card.dataset.aaeCardBranded) return;
	card.dataset.aaeCardBranded = '1';

	const badge = document.createElement('i');
	badge.className = 'wcf-logo';
	badge.style.cssText = 'position:absolute;inset-block-start:5px;inset-inline-start:5px;';
	card.appendChild(badge);
}

function scan() {
	const root = document.getElementById('elementor-panel') || document;
	root
		.querySelectorAll('button.elementor-element[data-library-element-type^="e-aae-a-"]:not([data-aae-card-branded])')
		.forEach(brandCard);
}

function scheduleScan() {
	if (isScanning || scanRafId) return;
	scanRafId = requestAnimationFrame(() => {
		scanRafId = null;
		isScanning = true;
		try {
			scan();
		} finally {
			isScanning = false;
		}
	});
}

export function startCardBranding() {
	if (started) return;
	started = true;

	const run = () => {
		scheduleScan();
		const target = document.getElementById('elementor-panel') || document.body;
		const observer = new MutationObserver((mutations) => {
			if (isScanning) return;
			const relevant = mutations.some((m) => {
				if (m.type !== 'childList') return false;
				const nodes = [...m.addedNodes];
				return nodes.some((n) => n.nodeType === 1 && (
					n.classList?.contains('elementor-element') ||
					(n.querySelector && n.querySelector('button.elementor-element'))
				));
			});
			if (relevant) {
				scheduleScan();
			}
		});
		observer.observe(target, { childList: true, subtree: true });
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run, { once: true });
	} else {
		run();
	}
}
