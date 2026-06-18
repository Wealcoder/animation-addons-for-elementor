import { register } from '@elementor/frontend-handlers';

/* AAE Atomic Counter (single-class composite).
   - Start value + duration come from the parent's data-counter-* attrs
     (set by the panel's Start Number / Duration controls).
   - End value is read from the Number child's text content — whatever
     the user types into the Number element on canvas is the target.
     This makes the editable child the single source of truth for the
     end value and removes the panel-vs-child conflict. */

const initCounter = (parent, numberEl) => {
	if (numberEl.classList.contains('aae-counter-initialized')) return;
	numberEl.classList.add('aae-counter-initialized');

	// Capture the user-typed target BEFORE GSAP overwrites innerHTML.
	const typed = parseFloat((numberEl.textContent || '').trim());
	const to    = Number.isFinite(typed) ? typed : 100;

	const from        = parseFloat(parent.getAttribute('data-counter-from')) || 0;
	const durationMs  = parseFloat(parent.getAttribute('data-counter-duration')) || 2000;
	const durationSec = durationMs / 1000;

	if (typeof window.gsap === 'undefined') {
		numberEl.innerHTML = to;
		return;
	}

	const tweenConfig = {
		innerHTML: to,
		duration: durationSec,
		snap: { innerHTML: 1 },
		ease: 'power1.out',
	};

	if (window.gsap.ScrollTrigger) {
		tweenConfig.scrollTrigger = {
			trigger: parent,
			start: 'top 85%',
			toggleActions: 'play none none none',
		};
	}

	window.gsap.fromTo(numberEl, { innerHTML: from }, tweenConfig);
};

register({
	elementType: 'e-aae-a-counter',
	id: 'e-aae-a-counter-handler',
	callback: ({ element }) => {
		const numberEl = element.querySelector('.aae-a-counter-number');
		if (!numberEl) return;
		// Clear init flag so editor re-renders re-run the animation.
		numberEl.classList.remove('aae-counter-initialized');
		initCounter(element, numberEl);
	},
});
