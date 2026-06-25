/* eslint-env browser */

/**
 * Section header branding (logo + lock/unlock or "Try").
 *
 * WHY this is global and not per-section: Elementor v4's <Section> renders its
 * body inside a <Collapse unmountOnExit>. A collapsed section's body is NOT in
 * the DOM, so any branding injected from inside the body (ResponsiveSection's
 * useEffect) only appears once the user expands that section — every other AAE
 * section header stays unbranded until opened.
 *
 * Fix: brand from OUTSIDE the body. A single MutationObserver watches the whole
 * editor panel and brands every AAE section header it can find — matched by the
 * header's label text against the known AAE section titles — regardless of
 * whether that section is expanded. Runs once per editor session.
 */

/* global aaeAtomicBridge */
const IS_PRO = typeof aaeAtomicBridge !== 'undefined' && !!aaeAtomicBridge.is_pro;

// The exact section titles AAE registers (must match the PHP set_label() text
// in inc/Atomic/<Feature>/Controls.php). Used to recognise our headers in the
// panel DOM. Kept here as the single source of truth for branding.
const AAE_SECTION_LABELS = new Set([
	'Animation',
	'Parallax Effect',
	'Text Animation',
	'Image Animation',
	'Image Reveal on Hover',
	'Cursor Hover Effect',
	'Mouse Move Effect',
	'Advance Tooltip',
	'Tilt',
	'ScrollTo',
	'Sticky/Pin Element',
	'Horizontal Scroll',
	'Custom CSS (AAE)',
	'Wrapper Link',
	'Nested Slider',
]);

let started = false;

/** Brand one section-header label element (idempotent via data flag). */
function brandLabel(labelEl) {
	if (!labelEl || labelEl.dataset.aaeBranded) return;

	const text = (labelEl.textContent || '').trim();
	if (!AAE_SECTION_LABELS.has(text)) return;

	labelEl.dataset.aaeBranded = '1';

	// 1. Prepend the wcf-logo icon. Make the label a flex row so the logo and
	// the section name sit vertically centred next to each other. The section
	// name is nudged down 0.5px to optically align with the logo.
	const nameSpan = document.createElement('span');
	nameSpan.style.cssText = 'transform: translateY(0.5px);';
	while (labelEl.firstChild) {
		nameSpan.appendChild(labelEl.firstChild);
	}
	labelEl.appendChild(nameSpan);

	labelEl.style.display = 'inline-flex';
	labelEl.style.alignItems = 'center';
	const logoIcon = document.createElement('i');
	logoIcon.className = 'wcf-logo';
	logoIcon.style.cssText = 'margin-right: 4px; display: inline-flex; align-items: center;';
	labelEl.insertBefore(logoIcon, labelEl.firstChild);

	// 2. Append the lock/unlock indicator to the RIGHT of the header row. We put
	// it after the label's container (MUI ListItemText) with margin-left:auto so
	// it sits at the section's right edge, before the collapse arrow.
	const labelContainer = labelEl.closest('.MuiListItemText-root') || labelEl;

	if (IS_PRO) {
		const unlockIcon = document.createElement('span');
		unlockIcon.className = 'wcfpro_text aae-icon-unlock';
		unlockIcon.style.cssText = 'margin-left: auto; vertical-align: middle;';
		labelContainer.insertAdjacentElement('afterend', unlockIcon);
	} else {
		// "Try" text FIRST, then the lock glyph. Colour comes from the logo (orange).
		const tryLink = document.createElement('a');
		tryLink.href = 'https://try.animation-addons.com';
		tryLink.target = '_blank';
		tryLink.className = 'wcfpro_text';
		tryLink.style.cssText = 'margin-left: auto; font-size: 11px; font-weight: 500; color: #FC6848; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; vertical-align: middle; transition: color .15s;';

		const tryText = document.createElement('span');
		tryText.textContent = 'Try';
		tryText.style.transition = 'text-decoration .15s';
		const lockGlyph = document.createElement('span');
		lockGlyph.className = 'aae-icon-lock';
		tryLink.appendChild(tryText);
		tryLink.appendChild(lockGlyph);

		// On hover, underline "Try" and brighten the colour so it reads as a
		// clickable link.
		tryLink.addEventListener('mouseenter', () => {
			tryText.style.textDecoration = 'underline';
			tryLink.style.color = '#FF8460';
		});
		tryLink.addEventListener('mouseleave', () => {
			tryText.style.textDecoration = 'none';
			tryLink.style.color = '#FC6848';
		});

		tryLink.addEventListener('click', (e) => e.stopPropagation());
		labelContainer.insertAdjacentElement('afterend', tryLink);
	}
}

/** Scan the document for unbranded AAE section header labels and brand them. */
function scan() {
	// Section titles render via MUI ListItemText `secondary`, surfaced as
	// .MuiListItemText-secondary. Fall back to any Typography root for safety.
	const labels = document.querySelectorAll(
		'.MuiListItemText-secondary:not([data-aae-branded]), .MuiListItemText-root .MuiTypography-root:not([data-aae-branded])'
	);
	labels.forEach(brandLabel);
}

/**
 * Start the global branding observer. Idempotent — safe to call from every
 * registerResponsiveSection(); only the first call wires anything up.
 */
export function startSectionBranding() {
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
