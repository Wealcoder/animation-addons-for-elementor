/* eslint-env browser */

/**
 * Widget-picker card branding (corner markers).
 *
 * The classic Marionette "Add Element" panel (#elementor-panel-elements)
 * renders every widget as:
 *   <button class="elementor-element" data-library-element-type="e-aae-a-…">
 *     <div class="icon"><i class="eicon-…"></i></div>
 *     <div class="title-wrapper"><div class="title">AAE …</div></div>
 *   </button>
 *
 * "AAE " title prefix stays as-is for now (removed in a later pass) — this
 * only adds two corner markers, without touching the icon/title markup:
 *
 *   [wcf-logo]                    [eicon-atomic]
 *    top-left                        top-right
 *
 * BOTH are ours to draw. Elementor prints eicon-atomic itself, but only for a
 * widget whose categories include 'v4-elements' / 'atomic-form' (see
 * includes/editor-templates/panel-elements.php) — AAE atomic widgets register
 * their own panel categories, so core never marks them even though they ARE
 * atomic. Injecting it here is what makes an AAE card read the same as a
 * native Div block / Flexbox card.
 *
 * The atom glyph is left UNPOSITIONED on purpose: core CSS already pins
 * .eicon-atomic to the top-right corner (editor.css `.elementor-panel
 * .elementor-element .eicon-atomic`), so it lands in the identical spot on an
 * AAE card and a native one, and follows core if that offset ever moves. Our
 * own badge takes the opposite corner, so the two can never collide and
 * neither offset has to be derived from the other's rendered width.
 *
 * Same MutationObserver pattern as ../responsive-section/section-branding.js:
 * cards render/re-render outside our control (search filtering, category
 * expand/collapse), so watch the whole panel and brand on sight instead of
 * hooking one render path.
 */

/**
 * Elementor's own corner markers, at most one per card and mutually exclusive
 * in the template. None renders on an AAE card today; if one ever does it
 * already owns the top-right corner, so we must not add a second glyph there.
 */
const CORE_CORNER_MARKERS = ':scope > .eicon-atomic, :scope > .eicon-plug, :scope > .eicon-lock, :scope > .eicon-upgrade-crown-full';

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

	if (!card.querySelector(CORE_CORNER_MARKERS)) {
		const atomic = document.createElement('i');
		atomic.className = 'eicon-atomic';
		card.appendChild(atomic);
	}
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
