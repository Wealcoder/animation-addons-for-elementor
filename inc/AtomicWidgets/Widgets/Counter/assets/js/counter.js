import { register } from '@elementor/frontend-handlers';

const initCounter = (element) => {
	// Prevent double initialization
	if (element.classList.contains('aae-counter-initialized')) return;
	element.classList.add('aae-counter-initialized');

	const from = parseFloat(element.getAttribute('data-from')) || 0;
	const to = parseFloat(element.getAttribute('data-to')) || 100;
	const durationMs = parseFloat(element.getAttribute('data-duration')) || 2000;
	const durationSec = durationMs / 1000;

	if (typeof window.gsap !== 'undefined' && window.gsap.ScrollTrigger) {
		window.gsap.fromTo(element, {
			innerHTML: from
		}, {
			innerHTML: to,
			duration: durationSec,
			snap: { innerHTML: 1 },
			ease: "power1.out",
			scrollTrigger: {
				trigger: element,
				start: "top 85%", // Start when element is 85% down the viewport
				toggleActions: "play none none none"
			}
		});
	} else if (typeof window.gsap !== 'undefined') {
		// Fallback if ScrollTrigger is missing
		window.gsap.fromTo(element, {
			innerHTML: from
		}, {
			innerHTML: to,
			duration: durationSec,
			snap: { innerHTML: 1 },
			ease: "power1.out",
		});
	} else {
		// Fallback if GSAP is not loaded
		element.innerHTML = to;
	}
};

register( {
	elementType: 'e-aae-a-counter',
	id: 'e-aae-a-counter-handler',
	callback: ( { element } ) => {
		
		const numberEl = element.querySelector('.aae-a-counter-number');		
		if (numberEl) {
			// Remove init class so it safely re-animates when settings change in the editor
			numberEl.classList.remove('aae-counter-initialized');
			initCounter(numberEl);
		}
	}
} );

