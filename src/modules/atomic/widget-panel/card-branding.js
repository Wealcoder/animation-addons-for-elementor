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

function brandCard(card) {
	if (!card || card.dataset.aaeCardBranded) return;
	card.dataset.aaeCardBranded = '1';

	const badge = document.createElement('i');
	badge.className = 'wcf-logo';
	badge.style.cssText = 'position:absolute;inset-block-start:5px;inset-inline-start:5px;';
	card.appendChild(badge);
}

function scan() {
	document
		.querySelectorAll('button.elementor-element[data-library-element-type^="e-aae-a-"]:not([data-aae-card-branded])')
		.forEach(brandCard);
}

export function startCardBranding() {
	if (started) return;
	started = true;

	const run = () => {
		scan();
		const observer = new MutationObserver(() => scan());
		observer.observe(document.body, { childList: true, subtree: true });
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run, { once: true });
	} else {
		run();
	}
}
