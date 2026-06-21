import { register } from '@elementor/frontend-handlers';

const sliderRegistry = new Map();

const configAttributes = [
	'data-slides-per-view',
	'data-center-mode',
	'data-enable-3d',
	'data-autoplay',
	'data-autoplay-speed',
	'data-transition-speed',
	'data-loop',
	'data-center-scale',
	'data-pause-on-hover',
	'data-autoplay-direction',
];

const getElementId = (element) => {
	return (
		element?.getAttribute?.('data-id') ||
		element?.getAttribute?.('data-element-id') ||
		element?.id ||
		null
	);
};

const getSliderElementById = (id) => {
	if (!id) return null;

	return (
		document.querySelector(`[data-id="${id}"]`) ||
		document.querySelector(`[data-element-id="${id}"]`) ||
		document.getElementById(id)
	);
};

const initSlider = (container, signal) => {
	const track = container.querySelector('.aae-slider-track');
	if (!track) return;

	const getSlides = () =>
		Array.from(track.children).filter(
			(el) => el.tagName !== 'STYLE' && el.tagName !== 'SCRIPT'
		);

	if (!getSlides().length) {
		const retryTimeout = setTimeout(() => initSlider(container, signal), 100);

		if (signal) {
			signal.addEventListener('abort', () => clearTimeout(retryTimeout), {
				once: true,
			});
		}

		return;
	}

	const sliderDiv = container.classList.contains('aae-a-slider')
		? container
		: container.querySelector('.aae-a-slider');

	if (!sliderDiv) return;

	// Safely clean up previous instance listeners/timers if initSlider is called again.
	if (sliderDiv._aaeSliderCleanup) {
		sliderDiv._aaeSliderCleanup();
	}

	// Configuration getters
	const getSlidesPerView = () =>
		parseInt(sliderDiv.getAttribute('data-slides-per-view')) || 2;

	const isCenterMode = () =>
		sliderDiv.getAttribute('data-center-mode') === 'true';

	const is3DEnabled = () =>
		sliderDiv.getAttribute('data-enable-3d') === 'true';

	const getAutoplay = () =>
		sliderDiv.getAttribute('data-autoplay') === 'true';

	const getAutoplaySpeed = () =>
		parseInt(sliderDiv.getAttribute('data-autoplay-speed')) || 3000;

	const getTransitionSpeed = () =>
		parseInt(sliderDiv.getAttribute('data-transition-speed')) || 500;

	const getLoop = () => sliderDiv.getAttribute('data-loop') === 'true';

	const getCenterScale = () =>
		parseFloat(sliderDiv.getAttribute('data-center-scale')) || 0.85;

	const getPauseOnHover = () =>
		sliderDiv.getAttribute('data-pause-on-hover') !== 'false';

	const getAutoplayDirection = () =>
		sliderDiv.getAttribute('data-autoplay-direction') === 'left' ? -1 : 1;

	let currentIndex = 0;
	let autoplayTimer = null;

	const localController = new AbortController();

	sliderDiv._aaeSliderCleanup = () => {
		localController.abort();
		clearInterval(autoplayTimer);
	};

	if (signal) {
		signal.addEventListener('abort', sliderDiv._aaeSliderCleanup, {
			once: true,
		});
	}

	const evtOpts = { signal: localController.signal };


	const getSlideWidth = () => getSlides()[0]?.offsetWidth || 0;
	const getGap = () => parseFloat(window.getComputedStyle(track).gap) || 0;

	let maxIndex = Math.max(0, getSlides().length - getSlidesPerView());

	const paginationContainer = sliderDiv.querySelector(
		'.e-aae-a-slider-pagination'
	);

	let dots = [];

	if (paginationContainer) {
		paginationContainer.innerHTML = '';

		for (let i = 0; i <= maxIndex; i++) {
			const dot = document.createElement('div');
			dot.className = 'aae-a-pagination-dot';

			dot.addEventListener(
				'click',
				() => {
					goToSlide(i);
					startAutoplay();
				},
				evtOpts
			);

			paginationContainer.appendChild(dot);
			dots.push(dot);
		}
	}

	const updatePagination = (index) => {
		dots.forEach((dot, i) => {
			if (i === index) {
				dot.classList.add('e-state-active');
			} else {
				dot.classList.remove('e-state-active');
			}
		});
	};

	const applyCenterStyles = () => {
		const slides = getSlides();

		if (!isCenterMode() && !is3DEnabled()) {
			slides.forEach((slide) => {
				slide.style.transform = '';
			});

			return;
		}

		const trackX = parseFloat(track.dataset.currentX || 0);
		const containerCenter = sliderDiv.offsetWidth / 2;
		const centerScale = getCenterScale();

		slides.forEach((slide) => {
			const slideCenter = slide.offsetLeft + trackX + slide.offsetWidth / 2;
			const distanceFromCenter = slideCenter - containerCenter;
			const normalizedDistance = distanceFromCenter / containerCenter;

			const scale = Math.max(
				centerScale,
				1 - Math.abs(normalizedDistance) * (1 - centerScale)
			);

			if (is3DEnabled()) {
				const rotateY = normalizedDistance * -35;
				const translateZ = -Math.abs(normalizedDistance * 150);

				slide.style.transformOrigin = 'center center';
				slide.style.transform = `translateZ(${translateZ}px) rotateY(${rotateY}deg) scale(${scale})`;
			} else if (isCenterMode()) {
				slide.style.transformOrigin = 'center center';
				slide.style.transform = `scale(${scale})`;
			}
		});
	};

	const goToSlide = (index, useTransition = true) => {
		const slides = getSlides();

		if (!slides.length) return;

		maxIndex = Math.max(0, slides.length - getSlidesPerView());

		if (getLoop()) {
			if (index < 0) index = maxIndex;
			if (index > maxIndex) index = 0;
		} else {
			if (index < 0) index = 0;
			if (index > maxIndex) index = maxIndex;
		}

		currentIndex = index;
		updatePagination(index);

		const step = getSlideWidth() + getGap();
		let targetX = -(index * step);

		if (isCenterMode()) {
			const trackWidth = sliderDiv.offsetWidth;
			const offset = (trackWidth - getSlideWidth()) / 2;
			targetX += offset;
		}

		if (useTransition) {
			const speedSeconds = getTransitionSpeed() / 1000;
			track.style.transition = `transform ${speedSeconds}s ease-out`;
			slides.forEach((slide) => {
				slide.style.transition = `transform ${speedSeconds}s ease-out`;
			});
		} else {
			track.style.transition = 'none';
			slides.forEach((slide) => {
				slide.style.transition = 'none';
			});
		}

		track.style.transform = `translateX(${targetX}px)`;
		track.dataset.currentX = targetX;

		applyCenterStyles();
	};

	const stopAutoplay = () => {
		clearInterval(autoplayTimer);
	};

	const startAutoplay = () => {
		if (!getAutoplay()) return;

		stopAutoplay();

		autoplayTimer = setInterval(() => {
			goToSlide(currentIndex + getAutoplayDirection());
		}, getAutoplaySpeed());
	};

	goToSlide(0, false);
	startAutoplay();

	sliderDiv.addEventListener(
		'mouseenter',
		() => {
			if (getPauseOnHover()) stopAutoplay();
		},
		evtOpts
	);

	sliderDiv.addEventListener(
		'mouseleave',
		() => {
			if (getPauseOnHover()) startAutoplay();
		},
		evtOpts
	);

	const prevBtn = sliderDiv.querySelector('.aae-a-navigator-prev');
	const nextBtn = sliderDiv.querySelector('.aae-a-navigator-next');

	if (prevBtn) {
		prevBtn.addEventListener(
			'click',
			() => {
				goToSlide(currentIndex - 1);
				startAutoplay();
			},
			evtOpts
		);
	}

	if (nextBtn) {
		nextBtn.addEventListener(
			'click',
			() => {
				goToSlide(currentIndex + 1);
				startAutoplay();
			},
			evtOpts
		);
	}

	window.addEventListener(
		'resize',
		() => {
			goToSlide(currentIndex, false);
		},
		evtOpts
	);

	console.log('slider render', {
		id: getElementId(container),
		container,
	});
};

const refreshSliderById = (id, reason = 'manual') => {
	const element = getSliderElementById(id);

	if (!element) {
		console.warn('AAE slider refresh failed: element not found', {
			id,
			reason,
		});

		return;
	}

	const entry = sliderRegistry.get(id);
	const signal = entry?.signal;

	requestAnimationFrame(() => {
		requestAnimationFrame(() => {
			console.log('AAE slider refresh by id', {
				id,
				reason,
				element,
			});

			initSlider(element, signal);
		});
	});
};

/**
 * Expose refresh API inside preview iframe.
 * Parent editor JS can call this through iframe.contentWindow.
 */
window.AAEAtomicSlider = {
	refreshById: refreshSliderById,
};

/**
 * Custom bridge event from parent editor window.
 */
window.addEventListener('aae:slider:refresh', (event) => {
	const id = event.detail?.id;
	const reason = event.detail?.reason || 'custom-event';

	refreshSliderById(id, reason);
});

register({
	elementType: 'e-aae-a-slider',
	id: 'aae-a-slider-handler',
	callback: ({ element, signal }) => {
		const id = getElementId(element);
		console.log("Slider run");
		if (id) {
			sliderRegistry.set(id, {
				element,
				signal,
			});
		}

		initSlider(element, signal);

		const isEditMode = document.body.classList.contains(
			'elementor-editor-active'
		);

		if (!isEditMode) {
			return;
		}

		let debounceTimer = null;

		const reInit = (reason = 'observer') => {
			clearTimeout(debounceTimer);

			debounceTimer = setTimeout(() => {
				initSlider(element, signal);
			}, 150);

			console.log('AAE slider reinit queued from iframe', {
				id,				
				element
			});
		};

		/**
		 * This catches attribute changes and DOM child changes inside the slider.
		 * Important for slide add/delete/duplicate after Elementor updates preview DOM.
		 */
		const observer = new MutationObserver((mutations) => {
			const shouldRefresh = mutations.some((mutation) => {
				if (mutation.type === 'attributes') {
					return configAttributes.includes(mutation.attributeName);
				}

				if (mutation.type === 'childList') {
					return true;
				}

				return false;
			});

			if (shouldRefresh) {
				reInit('mutation-observer');
			}
		});

		observer.observe(element, {
			attributes: true,
			attributeFilter: configAttributes,
			childList: true,
			subtree: true,
		});

		if (signal) {
			signal.addEventListener(
				'abort',
				() => {
					clearTimeout(debounceTimer);
					observer.disconnect();

					if (id) {
						sliderRegistry.delete(id);
					}
				},
				{ once: true }
			);
		}
	},
});