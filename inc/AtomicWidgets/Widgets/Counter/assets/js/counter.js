document.addEventListener('DOMContentLoaded', () => {
	
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
				},
				onUpdate: function() {
					// Add commas if needed, but for minimal JS, we just round it.
					// snap: { innerHTML: 1 } handles the rounding natively in GSAP.
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

	const initAllCounters = () => {
		document.querySelectorAll('.aae-a-counter-number:not(.aae-counter-initialized)').forEach(initCounter);
	};

	// Init on frontend load
	initAllCounters();

	// Observe DOM mutations to initialize counters added via Elementor Editor
	const observer = new MutationObserver((mutations) => {
		let shouldInit = false;
		for (const mutation of mutations) {
			if (mutation.addedNodes.length > 0) {
				shouldInit = true;
				break;
			}
			if (mutation.type === 'attributes' && mutation.target.classList.contains('aae-a-counter-number')) {
				// Re-init if attributes change (e.g. settings updated in editor)
				mutation.target.classList.remove('aae-counter-initialized');
				shouldInit = true;
			}
		}
		if (shouldInit) {
			initAllCounters();
		}
	});

	observer.observe(document.body, {
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: ['data-to', 'data-from', 'data-duration']
	});
});
