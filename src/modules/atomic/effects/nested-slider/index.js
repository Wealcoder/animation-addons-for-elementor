const { configFor, pickConfigResponsive } = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_NS';

const getSliderElementById = (id) => {
	if (!id) return null;

	return (
		document.querySelector(`[data-id="${id}"]`) ||
		document.querySelector(`[data-element-id="${id}"]`) ||
		document.getElementById(id)
	);
};

function read(el) {
	if (!el.classList.contains('aae-a-slider')) return null;
	const cfg = configFor(el, MAP);

	return cfg || {};
}

function bind(container, config) {
	unbind(container);
	if (!config) return;

	const track = container.querySelector('.aae-slider-track');
	if (!track) return;

	// Real slides only. Match the slide class positively (rather than excluding
	// known noise) so editor artifacts — element overlays, the empty-view
	// placeholder, drag placeholders, injected <style>/<script> — are never
	// miscounted. Loop clones are cloneNode(true) copies, so they keep the
	// 'aae-a-slide' class and are still included on the frontend.
	const getSlides = () =>
		Array.from(track.children).filter((el) =>
			el.classList && el.classList.contains('aae-a-slide')
		);

	if (!getSlides().length) {
		const retryTimeout = setTimeout(() => bind(container, config), 100);
		container._aaeSliderInitRetry = retryTimeout;
		return;
	}

	const sliderDiv = container;

	const r = (key, fallback) => {
		const val = pickConfigResponsive(config, key);
		return (val === undefined || val === null || val === '') ? fallback : val;
	};

	// Configuration getters
	const getEffect = () => r('effect', 'slide');
	const getPeek = () => parseInt(r('peek', 8));
	const getSlidesPerView = () => {
		const effect = getEffect();
		let spv = parseFloat(r('slidesPerView', 3));
		const peek = getPeek();
		// Peek shrinks each slide to reveal neighbours. When the user asks for a
		// single centered slide it must fill the viewport and sit dead-center, so
		// skip the peek widening in that case (otherwise the slide is narrower
		// than the viewport and a sliver of the previous slide shows on the left).
		const singleCentered = isCenterMode() && spv <= 1;
		if (peek > 0 && !singleCentered) {
			spv += (peek / 100) * 2;
		}
		const count = getSlides().length;
		if (count > 0 && spv > count && !isCenterMode() && !['coverflow', 'card', 'perspective'].includes(effect)) {
			spv = count;
		}
		return spv;
	};
	const getCfRotate = () => parseInt(r('cfRotate', 45));
	const getCfDepth = () => parseInt(r('cfDepth', 100));
	const getGap = () => parseInt(r('gap', 0));
	const getSlideHeight = () => Math.max(0, parseInt(r('slideHeight', 0)) || 0); // 0 = auto (aspect ratio)
	const getEasing = () => r('easing', 'power');
	const isCenterMode = () => r('centerMode', false) === true || String(r('centerMode', false)) === 'true';
	const getCenterScale = () => parseFloat(r('centerScale', 0.85));
	const getLoop = () => r('loop', false) === true || String(r('loop', false)) === 'true';
	const getTransitionSpeed = () => parseInt(r('transitionSpeed', 680));
	const getCardScale = () => parseFloat(r('cardScale', 0.88));
	const getCardTilt = () => parseInt(r('cardTilt', 20));
	const getPerspectiveRotate = () => parseInt(r('perspectiveRotate', 50));
	const getPerspectiveDepth = () => parseInt(r('perspectiveDepth', 250));
	const getPerspectiveOffset = () => parseFloat(r('perspectiveOffset', 18));
	const getPerspective = () => parseInt(r('perspective', 1200));
	const getAutoplay = () => {
		if (document.body.classList.contains('elementor-editor-active')) return false;
		return getEffect() !== 'marquee' && (r('autoplay', false) === true || String(r('autoplay', false)) === 'true');
	};
	const getAutoplaySpeed = () => parseInt(r('autoplaySpeed', 3000));
	const getAutoplayDelay = () => Math.max(0, parseInt(r('autoplayDelay', 0)) || 0);
	const getAutoplayDirection = () => r('autoplayDirection', 'right') === 'left' ? -1 : 1;
	const getPauseOnHover = () => r('pauseOnHover', true) !== false && String(r('pauseOnHover', true)) !== 'false';

	const easingsMap = {
		power1: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
		power2: 'cubic-bezier(0.25, 1, 0.5, 1)',
		power3: 'cubic-bezier(0.165, 0.84, 0.44, 1)',
		power4: 'cubic-bezier(0.23, 1, 0.32, 1)',
		power: 'cubic-bezier(0.25, 1, 0.5, 1)',
		elastic: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
		snappy: 'cubic-bezier(0.23, 1, 0.32, 1)',
		cinematic: 'cubic-bezier(0.16, 1, 0.3, 1)',
		smooth: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
		back: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
		bounce: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
		expo: 'cubic-bezier(0.16, 1, 0.3, 1)',
		circ: 'cubic-bezier(0, 0.55, 0.45, 1)'
	};

	// Accessibility initialization
	sliderDiv.setAttribute('role', 'region');
	sliderDiv.setAttribute('aria-roledescription', 'carousel');
	sliderDiv.setAttribute('tabindex', '0');
	if (!sliderDiv.getAttribute('aria-label')) {
		sliderDiv.setAttribute('aria-label', 'Slider Carousel');
	}
	track.setAttribute('aria-live', getAutoplay() ? 'off' : 'polite');

	const setupSlidesA11y = () => {
		const slides = getSlides();
		slides.forEach((slide, i) => {
			slide.setAttribute('role', 'group');
			slide.setAttribute('aria-roledescription', 'slide');
			slide.setAttribute('aria-label', `${i + 1} of ${slides.length}`);
		});
	};
	setupSlidesA11y();

	// Apply layout custom properties
	const applyLayout = () => {
		sliderDiv.style.setProperty('--aae-slides-per-view', getSlidesPerView());
		sliderDiv.style.setProperty('--aae-gap', `${getGap()}px`);
		sliderDiv.style.setProperty('--aae-slide-gap', `${getGap()}px`);
		sliderDiv.style.setProperty('--aae-perspective', `${getPerspective()}px`);
		sliderDiv.style.setProperty('--aae-slide-speed', `${getTransitionSpeed()}ms`);
		sliderDiv.style.setProperty('--aae-side-peek', `${getPeek()}%`);
		// Fixed slide height keeps the slide/image from shrinking as more slides
		// fit per view. 0 = auto (content/aspect-ratio drives height, the default).
		const slideH = getSlideHeight();
		sliderDiv.style.setProperty('--aae-slide-height', slideH > 0 ? `${slideH}px` : 'auto');
		sliderDiv.style.setProperty('--aae-easing', easingsMap[getEasing()] || 'cubic-bezier(0.25, 1, 0.5, 1)');

		// coverflow and perspective both use per-slide perspective() — no container perspective
		if (['perspective', 'coverflow'].includes(getEffect())) {
			sliderDiv.style.perspective = 'none';
			sliderDiv.style.transformStyle = 'flat';
		} else {
			sliderDiv.style.perspective = `${getPerspective()}px`;
			sliderDiv.style.transformStyle = 'preserve-3d';
		}
		track.style.perspective = 'none';
		track.style.transformStyle = 'preserve-3d';

		// Dynamic overflow control to prevent 3D flattening
		sliderDiv.style.overflow = '';
	};

	applyLayout();

	// Clean up previous classes and set active effect class
	const cleanEffectClasses = () => {
		sliderDiv.classList.remove(
			'aae-slider--coverflow',
			'aae-slider--card',
			'aae-slider--perspective',
			'aae-slider--slide',
			'aae-slider--marquee'
		);
	};
	cleanEffectClasses();
	sliderDiv.classList.add(`aae-slider--${getEffect()}`);

	let currentIndex = 0;
	let currentSlidesPerView = getSlidesPerView();
	let isPausedState = false;
	let autoplayStartTime = null;
	let autoplayProgressFrame = null;
	let autoplayFirstStep = true;
	let marqueeFrame = null;
	let marqueeX = 0;
	let lastTimestamp = null;

	const localController = new AbortController();

	sliderDiv._aaeSliderCleanup = () => {
		localController.abort();
		stopAutoplay();
		stopMarquee();
		clearTimeout(container._aaeSliderInitRetry);
		clearTimeout(container._aaeSliderInitTimeout);
		if (sliderDiv._aaeSliderResizeCleanup) {
			sliderDiv._aaeSliderResizeCleanup();
			delete sliderDiv._aaeSliderResizeCleanup;
		}
		sliderDiv.style.perspective = '';
		sliderDiv.style.transformStyle = '';
		sliderDiv.style.overflow = '';
		track.style.perspective = '';
		track.style.transformOrigin = '';
		track.style.height = '';
		track.style.width = '';
		track.style.position = '';
		getSlides().forEach(slide => {
			slide.style.transformOrigin = '';
		});
		track.querySelectorAll('.aae-slide-clone').forEach(el => el.remove());
	};

	const evtOpts = { signal: localController.signal };

	const getSlideWidth = () => getSlides()[0]?.offsetWidth || 0;
	const getActualGap = () => parseFloat(window.getComputedStyle(track).gap) || 0;
	// Centering reference must be the exact box the slides are laid out in: the
	// track's CONTENT width (clientWidth minus its own horizontal padding).
	// Slides are `100% / slidesPerView` of that content box, so this width and
	// the slide width agree — making the centering offset land symmetrically.
	// Using offsetWidth here would include the track's padding (and using the
	// slider would add its padding too), making the reference wider than the
	// slides and pushing the centered slide off to one side.
	const getViewportWidth = () => {
		const vp = sliderDiv.querySelector('.aae-slider__viewport');
		if (vp) return vp.getBoundingClientRect().width || vp.offsetWidth || 1000;
		// Sub-pixel content width: the rect width minus the track's own padding.
		// Using the fractional rect (not integer clientWidth) lets the centering
		// offset cancel exactly against the slide's fractional width.
		const cs = window.getComputedStyle(track);
		const padX = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
		return (track.getBoundingClientRect().width - padX) || track.offsetWidth || 1000;
	};

	const effectCenters = () => {
		const e = getEffect();
		return isCenterMode() || e === 'coverflow' || e === 'card' || e === 'perspective';
	};

	// THE canonical track position for a given slide index. Single source of
	// truth shared by applyTransitions() and the drag handler so they can never
	// drift apart. Positions from the slide's real layout geometry (offsetLeft /
	// computed width) — transform-independent, so center mode's scale() and the
	// 3D effects don't skew it, and exact for any slidesPerView / gap / peek.
	// Read the track's current translateX from its transform matrix.
	const getCurrentTranslateX = () => {
		const t = window.getComputedStyle(track).transform;
		if (!t || t === 'none') return 0;
		// matrix(a,b,c,d,tx,ty) or matrix3d(...,tx,...) — tx is index 4 / 12.
		const m = t.match(/matrix3d\((.+)\)/);
		if (m) return parseFloat(m[1].split(',')[12]) || 0;
		const m2 = t.match(/matrix\((.+)\)/);
		if (m2) return parseFloat(m2[1].split(',')[4]) || 0;
		return 0;
	};

	const computeTargetX = (idx) => {
		const slide = getSlides()[idx];
		if (!slide) {
			return -(idx * (getSlideWidth() + getActualGap()));
		}
		// Work entirely in rendered viewport pixels and self-correct from the
		// slide's CURRENT on-screen position. This is immune to the track's vs
		// slider's padding/coordinate differences (offsetLeft is padding-box
		// relative while content widths are not — mixing them left a constant
		// ~20px skew). We compute the delta from where the slide is now to where
		// we want it, then fold it into the current translate.
		const sRect = slide.getBoundingClientRect();
		const vRect = sliderDiv.getBoundingClientRect();
		const csV = window.getComputedStyle(sliderDiv);
		const padL = parseFloat(csV.paddingLeft) || 0;
		const padR = parseFloat(csV.paddingRight) || 0;
		// Slider CONTENT box edges in viewport space.
		const contentLeft = vRect.left + padL;
		const contentRight = vRect.right - padR;
		const curTx = getCurrentTranslateX();

		let desiredCenter;
		if (effectCenters()) {
			// Active slide centred in the slider content box.
			desiredCenter = (contentLeft + contentRight) / 2;
			const curCenter = sRect.left + sRect.width / 2;
			return curTx + (desiredCenter - curCenter);
		}
		// Left-align: active slide's left edge at the content box's left edge.
		return curTx + (contentLeft - sRect.left);
	};

	const originalSlides = getSlides();
	const originalSlidesCount = originalSlides.length;
	const isEditorMode = document.body.classList.contains('elementor-editor-active');
	const hasSeamlessLoop = getLoop() && getEffect() !== 'marquee' && !isEditorMode;

	const getMaxIndex = () => {
		const slides = getSlides();
		const effect = getEffect();
		if (hasSeamlessLoop) {
			return originalSlidesCount - 1;
		}
		if (isCenterMode() || ['coverflow', 'card', 'perspective'].includes(effect)) {
			return Math.max(0, slides.length - 1);
		}
		return Math.max(0, slides.length - Math.ceil(getSlidesPerView()));
	};

	let maxIndex = getMaxIndex();

	const viewportWidth = getViewportWidth();
	const spv = getSlidesPerView();
	const gapVal = getGap();
	const estimatedSlideWidth = (viewportWidth - (gapVal * (spv - 1))) / spv;
	let originalWidth = 0;
	originalSlides.forEach(slide => {
		originalWidth += slide.getBoundingClientRect().width + getActualGap();
	});
	const actualOriginalWidth = originalWidth || (originalSlides.length * (estimatedSlideWidth + getActualGap()));

	let middleSetStart = 0;
	if (!isEditorMode && (getEffect() === 'marquee' || hasSeamlessLoop)) {
		let clonesMultiplier = 1;
		if (['coverflow', 'card', 'perspective'].includes(getEffect())) {
			clonesMultiplier = 3;
		}
		const minClonesNeeded = (Math.max(1, Math.ceil(viewportWidth / actualOriginalWidth)) || 1) * clonesMultiplier;

		if (hasSeamlessLoop) {
			// Prepend
			for (let c = 0; c < minClonesNeeded; c++) {
				const fragment = document.createDocumentFragment();
				originalSlides.forEach(slide => {
					const clone = slide.cloneNode(true);
					clone.classList.add('aae-slide-clone');
					clone.removeAttribute('id');
					clone.removeAttribute('data-id');
					clone.setAttribute('aria-hidden', 'true');
					clone.setAttribute('tabindex', '-1');
					clone.querySelectorAll('a, button, select, input, textarea, [tabindex]').forEach(el => {
						el.setAttribute('tabindex', '-1');
					});
					fragment.appendChild(clone);
				});
				track.insertBefore(fragment, track.firstChild);
			}
			// Append
			for (let c = 0; c < minClonesNeeded; c++) {
				originalSlides.forEach(slide => {
					const clone = slide.cloneNode(true);
					clone.classList.add('aae-slide-clone');
					clone.removeAttribute('id');
					clone.removeAttribute('data-id');
					clone.setAttribute('aria-hidden', 'true');
					clone.setAttribute('tabindex', '-1');
					clone.querySelectorAll('a, button, select, input, textarea, [tabindex]').forEach(el => {
						el.setAttribute('tabindex', '-1');
					});
					track.appendChild(clone);
				});
			}
			middleSetStart = minClonesNeeded * originalSlidesCount;
			currentIndex = middleSetStart;
		} else {
			const cloneCount = Math.max(1, Math.ceil((viewportWidth * 2) / actualOriginalWidth));
			for (let c = 0; c < cloneCount; c++) {
				originalSlides.forEach(slide => {
					const clone = slide.cloneNode(true);
					clone.classList.add('aae-slide-clone');
					clone.removeAttribute('id');
					clone.removeAttribute('data-id');
					clone.setAttribute('aria-hidden', 'true');
					clone.setAttribute('tabindex', '-1');
					clone.querySelectorAll('a, button, select, input, textarea, [tabindex]').forEach(el => {
						el.setAttribute('tabindex', '-1');
					});
					track.appendChild(clone);
				});
			}
		}
	}

	// Editor center-mode fix: centering the active slide leaves a half-viewport
	// gap on its left when we sit on slide 0 (no slide exists before it). The
	// frontend hides this with seamless-loop clones, but those are off in the
	// editor. Since the user duplicates slides for the editor preview, the
	// natural resting slide is the second one so there is content filling the
	// left of the centered slide. getRestIndex() is the single source of truth
	// for this resting position — used both at init and by the Play Now round
	// so they never fight each other.
	const centersActiveSlide = () =>
		isCenterMode() || ['coverflow', 'card', 'perspective'].includes(getEffect());
	const getRestIndex = () => {
		if (hasSeamlessLoop) return middleSetStart;
		if (isEditorMode && centersActiveSlide() && getSlides().length > 1) return 1;
		return 0;
	};
	// In the editor a re-bind happens whenever a slide re-renders (e.g. you drop
	// content into a slide). Resetting to getRestIndex() would jump the slider
	// away from the slide you're editing — and make the next drop target the
	// wrong slide. Preserve the slide you were on if it's still valid.
	const stashedIndex = sliderDiv._aaeSliderLastIndex;
	if (
		isEditorMode &&
		typeof stashedIndex === 'number' &&
		stashedIndex >= 0 &&
		stashedIndex < getSlides().length
	) {
		currentIndex = stashedIndex;
	} else {
		currentIndex = getRestIndex();
	}

	// Selector elements
	const prevBtns = sliderDiv.querySelectorAll('.aae-a-navigator-prev');
	const nextBtns = sliderDiv.querySelectorAll('.aae-a-navigator-next');

	// Navigation Buttons Accessibility setup
	const trackId = track.id || `aae-slider-track-${Math.random().toString(36).substr(2, 9)}`;
	if (!track.id) track.id = trackId;
	prevBtns.forEach(btn => {
		btn.setAttribute('role', 'button');
		btn.setAttribute('aria-label', 'Previous slide');
		btn.setAttribute('aria-controls', trackId);
	});
	nextBtns.forEach(btn => {
		btn.setAttribute('role', 'button');
		btn.setAttribute('aria-label', 'Next slide');
		btn.setAttribute('aria-controls', trackId);
	});

	const dotsContainer = sliderDiv.querySelector('.js-aae-dots');
	if (dotsContainer) {
		dotsContainer.setAttribute('role', 'tablist');
		dotsContainer.setAttribute('aria-label', 'Slides');
	}
	const fractionContainer = sliderDiv.querySelector('.js-aae-fraction');
	const currentNumberEl = sliderDiv.querySelector('.js-aae-current-number');
	const totalNumberEl = sliderDiv.querySelector('.js-aae-total-number');
	const percentageContainer = sliderDiv.querySelector('.js-aae-percentage');
	const progressFill = sliderDiv.querySelector('.js-aae-progress-fill');
	const scrollbarContainer = sliderDiv.querySelector('.js-aae-scrollbar');
	const scrollbarDrag = sliderDiv.querySelector('.js-aae-scrollbar-drag');
	const autoplayBarFill = sliderDiv.querySelector('.js-aae-autoplay-bar');
	const circleFill = sliderDiv.querySelector('.js-aae-circle-fill');
	const circleText = sliderDiv.querySelector('.js-aae-circle-text');
	const playPauseBtn = sliderDiv.querySelector('.js-aae-play-pause');


	// Dynamic pagination dots — use the atomic dot element as a template and clone it per slide.
	// In editor mode the single template dot is shown as-is for design purposes.
	let dots = [];
	if (dotsContainer) {
		const dotTemplate = dotsContainer.querySelector('.js-aae-dot');
		if (isEditorMode) {
			// Editor: keep the single template dot visible so the user can style it.
			if (dotTemplate) dots.push(dotTemplate);
		} else if (dotTemplate) {
			// Frontend: clone the template N times, one per slide.
			const baseClone = dotTemplate.cloneNode(true);
			dotsContainer.innerHTML = '';
			for (let i = 0; i <= maxIndex; i++) {
				const dot = baseClone.cloneNode(true);
				// Strip Elementor editor attributes so they don't conflict.
				dot.removeAttribute('data-id');
				dot.removeAttribute('data-element_type');
				dot.removeAttribute('data-e-type');
				dot.removeAttribute('data-interaction-id');
				dot.setAttribute('role', 'tab');
				dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
				dot.setAttribute('aria-selected', 'false');
				dot.addEventListener(
					'click',
					() => {
						goToSlide(hasSeamlessLoop ? middleSetStart + i : i);
						resumeSlider();
					},
					evtOpts
				);
				dotsContainer.appendChild(dot);
				dots.push(dot);
			}
		}
	}

	const updateNavigationIndicators = (rawIndex) => {
		const index = hasSeamlessLoop ? rawIndex % originalSlidesCount : rawIndex;
		// Active dot: darken via brightness filter so it stands out at any color the user
		// configures via the Elementor panel. Inactive falls back to atomic base opacity.
		dots.forEach((dot, i) => {
			if (i === index) {
				dot.classList.add('aae-dot--active');
				dot.setAttribute('aria-selected', 'true');
				dot.style.opacity = '1';
				dot.style.filter = 'brightness(0.4)';
			} else {
				dot.classList.remove('aae-dot--active');
				dot.setAttribute('aria-selected', 'false');
				dot.style.opacity = '';
				dot.style.filter = '';
			}
		});

		// Dynamic update for visible/invisible slides (aria-hidden and tabindex)
		const updateSlidesA11y = (activeIndex) => {
			const slides = getSlides();
			const effect = getEffect();
			const spv = Math.ceil(getSlidesPerView());

			slides.forEach((slide, i) => {
				let isVisible = false;
				if (effect === 'card') {
					isVisible = (i === activeIndex);
				} else {
					isVisible = (i >= activeIndex && i < activeIndex + spv);
				}

				if (isVisible) {
					slide.setAttribute('aria-hidden', 'false');
					slide.removeAttribute('tabindex');
					slide.querySelectorAll('a, button, select, input, textarea, [tabindex]').forEach(el => {
						el.removeAttribute('tabindex');
					});
				} else {
					slide.setAttribute('aria-hidden', 'true');
					slide.setAttribute('tabindex', '-1');
					slide.querySelectorAll('a, button, select, input, textarea, [tabindex]').forEach(el => {
						el.setAttribute('tabindex', '-1');
					});
				}
			});
		};
		updateSlidesA11y(index);

		const displayTotal = hasSeamlessLoop ? originalSlidesCount : getSlides().length;
		const displayIndex = hasSeamlessLoop ? (index % originalSlidesCount) : index;
		const displayMaxIndex = hasSeamlessLoop ? originalSlidesCount - 1 : maxIndex;

		// Update individual current / total number widgets
		if (currentNumberEl) currentNumberEl.textContent = displayIndex + 1;
		if (totalNumberEl) totalNumberEl.textContent = displayTotal;

		// Update fraction UI (e.g. 1 / 6)
		if (fractionContainer) {
			fractionContainer.textContent = `${displayIndex + 1} / ${displayTotal}`;
		}

		// Update percentage UI — (index+1)/total so slide 1 shows minimum, not 0%.
		if (percentageContainer) {
			const pct = Math.round(((displayIndex + 1) / displayTotal) * 100);
			percentageContainer.textContent = `${pct}%`;
		}

		// Update progress bar fill — always show at least 1/total on the first slide.
		if (progressFill) {
			const pct = (displayIndex + 1) / (displayMaxIndex + 1);
			progressFill.style.transform = `scaleX(${pct})`;
		}

		// Update scrollbar drag X position
		if (scrollbarDrag && scrollbarContainer) {
			const totalWidth = scrollbarContainer.offsetWidth;
			const dragWidth = totalWidth / (displayMaxIndex + 1);
			scrollbarDrag.style.width = `${dragWidth}px`;
			const left = displayMaxIndex > 0 ? (displayIndex / displayMaxIndex) * (totalWidth - dragWidth) : 0;
			scrollbarDrag.style.transform = `translateX(${left}px)`;
		}
	};

	// 3D & Transition Math Engines
	const applyTransitions = (index, useTransition = true) => {
		const slides = getSlides();
		const effect = getEffect();
		const speedSeconds = getTransitionSpeed() / 1000;
		const ease = easingsMap[getEasing()] || 'cubic-bezier(0.25, 1, 0.5, 1)';

		// Reset transition inline states and clear styling of previous effects
		track.style.transition = useTransition ? `transform ${speedSeconds}s ${ease}` : 'none';
		track.style.transformOrigin = '';
		track.style.position = '';
		track.style.width = '';
		track.style.height = '';
		track.style.display = 'flex';
		slides.forEach((slide) => {
			slide.style.transition = useTransition ? `transform ${speedSeconds}s ${ease}, opacity ${speedSeconds}s ${ease}` : 'none';
			slide.style.position = '';
			slide.style.top = '';
			slide.style.left = '';
			slide.style.width = '';
			slide.style.height = '';
			slide.style.opacity = '';
			slide.style.pointerEvents = '';
			slide.style.backfaceVisibility = '';
			slide.style.webkitBackfaceVisibility = '';
			slide.style.transformOrigin = '';
			slide.style.gridArea = '';
			slide.style.alignSelf = '';
			slide.style.transform = '';
		});

		// Apply transformations relative to active slide
		const cfRotate = getCfRotate();
		const cfDepth = getCfDepth();
		const cardScale = getCardScale();
		const cardTilt = getCardTilt();

		slides.forEach((slide, i) => {
			const offset = i - index;
			const absOffset = Math.abs(offset);

			// z-index only matters when slides physically overlap (center / 3D
			// effects). For the flat 'slide' effect they sit side by side, so a
			// stacking order here does nothing useful — and in the editor it makes
			// the active slide sit on top, which can steal a drop meant for the
			// slide under the cursor (this is why a drop sometimes landed in the
			// last slide). Keep it for overlapping effects; clear it otherwise.
			if (effectCenters()) {
				slide.style.zIndex = slides.length - absOffset;
			} else {
				slide.style.zIndex = '';
			}

			// Editor-only: in centered/3D effects slides physically overlap, and a
			// drop is hit-tested by stacking — so a dropped widget can land in the
			// wrong (top-most / last) slide instead of the active one. Make only the
			// active slide accept pointer/drop events. Skipped for the flat 'slide'
			// effect where slides sit side by side and every visible slide must stay
			// droppable; and never on the frontend (links inside non-active slides
			// must keep working as the slider rotates).
			if (isEditorMode && effectCenters()) {
				slide.style.pointerEvents = offset === 0 ? 'auto' : 'none';
			}

			if (effect === 'coverflow') {
				const p = getPerspective();
				if (offset === 0) {
					slide.style.transform = `perspective(${p}px) translateZ(0px) scale(1) rotateY(0deg)`;
				} else {
					const dir = offset > 0 ? 1 : -1;
					const rotate = -dir * cfRotate;
					const depth = -cfDepth;
					slide.style.transform = `perspective(${p}px) translateZ(${depth}px) scale(${getCenterScale()}) rotateY(${rotate}deg)`;
				}
			} else if (effect === 'card') {
				if (offset === 0) {
					slide.style.transform = 'scale(1) rotate(0deg)';
				} else {
					const dir = offset > 0 ? 1 : -1;
					slide.style.transform = `scale(${cardScale}) rotate(${dir * cardTilt}deg) translate3d(0px, 0, -50px)`;
				}
			} else if (effect === 'perspective') {
				if (offset === 0) {
					slide.style.transform = `translate3d(0, 0, 0) perspective(${getPerspective()}px) translateZ(0px) rotateY(0deg) scale(1)`;
				} else {
					const dir = offset > 0 ? 1 : -1;
					const absOffset = Math.abs(offset);
					const z = Math.round(-absOffset * getPerspectiveDepth());
					const rotate = dir * getPerspectiveRotate();
					// Apply local perspective to prevent outer slides from crossing the vanishing point and flipping (ulte jay)
					// Apply tx offset as a percentage to pull rotated slides inward, compensating for 3D foreshortening
					const tx = -dir * (getPerspectiveOffset() * absOffset);
					slide.style.transform = `translate3d(${tx}%, 0, 0) perspective(${getPerspective()}px) translateZ(${z}px) rotateY(${rotate}deg) scale(1)`;
				}
			} else {
				// Standard horizontal slide logic
				if (isCenterMode()) {
					if (offset === 0) {
						slide.style.transform = 'scale(1)';
						slide.style.opacity = '1';
					} else {
						slide.style.transform = `scale(${getCenterScale()})`;
						slide.style.opacity = '0.5';
					}
				} else {
					slide.style.transform = '';
					slide.style.opacity = '1';
				}
			}
		});

		// Position the track from the active slide's real layout geometry.
		// offsetParent must be the track for offsetLeft (used by computeTargetX)
		// to be track-relative.
		track.style.position = 'relative';
		const targetX = computeTargetX(index);
		track.style.transform = `translate3d(${targetX}px, 0, 0)`;

		if (hasSeamlessLoop) {
			if (useTransition) {
				const snapBack = (e) => {
					if (e.target === track && e.propertyName.includes('transform')) {
						track.removeEventListener('transitionend', snapBack);
						if (currentIndex < middleSetStart || currentIndex >= middleSetStart + originalSlidesCount) {
							const offset = currentIndex % originalSlidesCount;
							currentIndex = middleSetStart + offset;
							applyTransitions(currentIndex, false);
							void track.offsetHeight; // Force synchronous reflow to prevent flash
						}
					}
				};
				track.addEventListener('transitionend', snapBack);
			} else {
				requestAnimationFrame(() => {
					if (currentIndex < middleSetStart || currentIndex >= middleSetStart + originalSlidesCount) {
						const offset = currentIndex % originalSlidesCount;
						currentIndex = middleSetStart + offset;
						applyTransitions(currentIndex, false);
						void track.offsetHeight; // Force synchronous reflow to prevent flash
					}
				});
			}
		}
	};

	const goToSlide = (index, useTransition = true) => {
		if (getEffect() === 'marquee') return;
		const slides = getSlides();
		if (!slides.length) return;

		maxIndex = getMaxIndex();

		if (hasSeamlessLoop) {
			if (index < 0) index = 0;
			if (index >= slides.length) index = slides.length - 1;
		} else if (getLoop()) {
			if (index < 0) index = maxIndex;
			if (index > maxIndex) index = 0;
		} else {
			if (index < 0) index = 0;
			if (index > maxIndex) index = maxIndex;
		}

		currentIndex = index;
		sliderDiv._aaeSliderLastIndex = index; // survive editor re-binds
		updateNavigationIndicators(index);
		applyTransitions(index, useTransition);
	};

	// Editor-only: bring a specific slide fully into view for EDITING. Unlike
	// goToSlide(), this bypasses the navigation maxIndex clamp — in a flat
	// multi-slidesPerView layout goToSlide(lastIndex) would clamp short (you
	// can't scroll past the end), leaving the last slide half-off-screen and
	// undroppable. computeTargetX() left-aligns the requested slide, so even the
	// last one is fully revealed (a little blank space on the right is fine in
	// the editor). Counter/indicators follow the real index so they stay in sync.
	const revealSlide = (index) => {
		if (getEffect() === 'marquee') return;
		const slides = getSlides();
		if (!slides.length) return;
		index = Math.max(0, Math.min(index, slides.length - 1));
		currentIndex = index;
		sliderDiv._aaeSliderLastIndex = index; // survive editor re-binds
		updateNavigationIndicators(index);
		applyTransitions(index, true);
	};

	// Autoplay Anim Loop
	const tickAutoplay = (timestamp) => {
		if (!autoplayStartTime) autoplayStartTime = timestamp;
		const elapsed = timestamp - autoplayStartTime;
		// First step waits the configured Start Delay (0 = immediate); every
		// step after that uses the normal autoplay speed.
		const duration = autoplayFirstStep ? getAutoplayDelay() : getAutoplaySpeed();

		// A zero-duration first step means "advance immediately" — skip the
		// progress math (which would divide by zero) and move on this frame.
		if (duration <= 0) {
			autoplayFirstStep = false;
			goToSlide(currentIndex + getAutoplayDirection());
			startAutoplay();
			return;
		}

		const pct = Math.min(1, elapsed / duration);

		if (autoplayBarFill) {
			autoplayBarFill.style.transform = `scaleX(${pct})`;
		}

		if (circleFill) {
			const circumference = 125.6; // 2 * PI * 20
			circleFill.style.strokeDashoffset = circumference * (1 - pct);
		}
		if (circleText) {
			const remainingSec = Math.ceil((duration - elapsed) / 1000);
			circleText.textContent = `${remainingSec}s`;
		}

		if (pct < 1) {
			autoplayProgressFrame = requestAnimationFrame(tickAutoplay);
		} else {
			autoplayFirstStep = false;
			goToSlide(currentIndex + getAutoplayDirection());
			startAutoplay();
		}
	};

	const startAutoplay = () => {
		// Never auto-advance inside the editor: the slide would move out from
		// under the user while they're placing/editing content (this is what made
		// "the slider runs when I drop something" happen on every re-bind).
		if (isEditorMode) return;
		if (!getAutoplay() || isPausedState) return;
		stopAutoplay();
		autoplayStartTime = null;
		autoplayProgressFrame = requestAnimationFrame(tickAutoplay);
	};

	const stopAutoplay = () => {
		cancelAnimationFrame(autoplayProgressFrame);
		if (autoplayBarFill) autoplayBarFill.style.transform = 'scaleX(0)';
		if (circleFill) circleFill.style.strokeDashoffset = '125.6';
	};

	const getMarqueeSpeed = () => {
		const slideWidth = getSlideWidth() || estimatedSlideWidth || 300;
		const duration = Math.max(100, getTransitionSpeed());
		return slideWidth / duration; // pixels per millisecond
	};

	const startMarquee = () => {
		if (getEffect() !== 'marquee' || isPausedState) return;
		if (document.body.classList.contains('elementor-editor-active')) return;
		stopMarquee();
		lastTimestamp = null;
		const direction = getAutoplayDirection();
		if (direction === -1 && marqueeX === 0) {
			marqueeX = -actualOriginalWidth;
		}
		const tickMarquee = (timestamp) => {
			if (!lastTimestamp) {
				lastTimestamp = timestamp;
				marqueeFrame = requestAnimationFrame(tickMarquee);
				return;
			}
			const delta = timestamp - lastTimestamp;
			lastTimestamp = timestamp;

			const speed = getMarqueeSpeed();
			const step = speed * delta;

			if (direction === 1) {
				marqueeX -= step;
				if (marqueeX <= -actualOriginalWidth) {
					marqueeX += actualOriginalWidth;
				}
			} else {
				marqueeX += step;
				if (marqueeX >= 0) {
					marqueeX -= actualOriginalWidth;
				}
			}

			track.style.transform = `translate3d(${marqueeX}px, 0, 0)`;
			marqueeFrame = requestAnimationFrame(tickMarquee);
		};
		marqueeFrame = requestAnimationFrame(tickMarquee);
	};

	const stopMarquee = () => {
		cancelAnimationFrame(marqueeFrame);
	};

	const pauseSlider = () => {
		if (getEffect() === 'marquee') {
			stopMarquee();
		} else {
			stopAutoplay();
		}
	};

	const resumeSlider = () => {
		if (getEffect() === 'marquee') {
			startMarquee();
		} else {
			startAutoplay();
		}
	};

	// Play Pause Toggle
	if (playPauseBtn) {
		playPauseBtn.addEventListener('click', () => {
			isPausedState = !isPausedState;
			if (isPausedState) {
				playPauseBtn.classList.add('is-paused');
				pauseSlider();
			} else {
				playPauseBtn.classList.remove('is-paused');
				resumeSlider();
			}
		}, evtOpts);
	}

	// Keyboard controls
	sliderDiv.addEventListener('keydown', (e) => {
		if (e.key === 'ArrowLeft') {
			goToSlide(currentIndex - 1);
			resumeSlider();
		} else if (e.key === 'ArrowRight') {
			goToSlide(currentIndex + 1);
			resumeSlider();
		}
	}, evtOpts);

	// Autoplay mouse & focus actions for accessibility
	sliderDiv.addEventListener(
		'mouseenter',
		() => {
			if (getPauseOnHover()) pauseSlider();
		},
		evtOpts
	);

	sliderDiv.addEventListener(
		'mouseleave',
		() => {
			if (getPauseOnHover()) resumeSlider();
		},
		evtOpts
	);

	sliderDiv.addEventListener(
		'focusin',
		() => {
			if (getPauseOnHover()) pauseSlider();
		},
		evtOpts
	);

	sliderDiv.addEventListener(
		'focusout',
		() => {
			if (getPauseOnHover()) resumeSlider();
		},
		evtOpts
	);

	// Navigation click binds
	prevBtns.forEach(btn => {
		btn.addEventListener('click', () => {
			goToSlide(currentIndex - 1);
			resumeSlider();
		}, evtOpts);
	});

	nextBtns.forEach(btn => {
		btn.addEventListener('click', () => {
			goToSlide(currentIndex + 1);
			resumeSlider();
		}, evtOpts);
	});

	// Scrollbar track click-to-jump
	if (scrollbarContainer) {
		scrollbarContainer.addEventListener('click', (e) => {
			const rect = scrollbarContainer.getBoundingClientRect();
			const clickX = e.clientX - rect.left;
			const pct = clickX / rect.width;
			const targetIdx = Math.round(pct * maxIndex);
			goToSlide(targetIdx);
			resumeSlider();
		}, evtOpts);
	}

	// Pointer Drag Gestures
	let isDragging = false;
	let startDragX = 0;
	let currentDragX = 0;
	const dragThreshold = 50;

	const onPointerDown = (e) => {
		// Prevent native HTML drag-and-drop / selection from hijacking the event
		if (e.type === 'mousedown') {
			e.preventDefault();
		}

		isDragging = true;
		startDragX = e.clientX || e.touches?.[0]?.clientX || 0;
		currentDragX = startDragX;

		if (getEffect() === 'marquee') {
			stopMarquee();
		} else {
			track.style.transition = 'none';
			getSlides().forEach(slide => {
				slide.style.transition = 'none';
			});
		}
	};

	const onPointerMove = (e) => {
		if (!isDragging) return;
		currentDragX = e.clientX || e.touches?.[0]?.clientX || 0;
		const diff = currentDragX - startDragX;

		if (getEffect() === 'marquee') {
			let newX = marqueeX + diff;
			if (actualOriginalWidth > 0) {
				while (newX <= -actualOriginalWidth) {
					newX += actualOriginalWidth;
				}
				while (newX >= 0) {
					newX -= actualOriginalWidth;
				}
			}
			track.style.transform = `translate3d(${newX}px, 0, 0)`;
		} else if (['slide', 'coverflow', 'card', 'perspective'].includes(getEffect())) {
			// Drag bounds come from the SAME canonical positioner as the snapped
			// layout, so dragging never jumps relative to where slides settle.
			let baseTargetX = computeTargetX(currentIndex);
			const minScrollX = computeTargetX(0);
			const maxScrollX = computeTargetX(maxIndex);

			let newX = baseTargetX + diff;

			if (hasSeamlessLoop) {
				// One full clone set is N equal-width slides — an exact step delta.
				const singleSetWidth = originalSlidesCount * (getSlideWidth() + getActualGap());
				const middleMinScrollX = computeTargetX(middleSetStart);
				const middleMaxScrollX = computeTargetX(middleSetStart + originalSlidesCount - 1);

				if (newX > middleMinScrollX) {
					currentIndex += originalSlidesCount;
					startDragX += singleSetWidth;
					applyTransitions(currentIndex, false);
					baseTargetX -= singleSetWidth;
					newX -= singleSetWidth;
				} else if (newX < middleMaxScrollX) {
					currentIndex -= originalSlidesCount;
					startDragX -= singleSetWidth;
					applyTransitions(currentIndex, false);
					baseTargetX += singleSetWidth;
					newX += singleSetWidth;
				}
			} else if (!getLoop()) {
				if (newX > minScrollX) {
					newX = minScrollX + (newX - minScrollX) * 0.2;
				} else if (newX < maxScrollX) {
					newX = maxScrollX + (newX - maxScrollX) * 0.2;
				}
			}

			track.style.transform = `translate3d(${newX}px, 0, 0)`;
		}
	};

	const onPointerUp = () => {
		if (!isDragging) return;
		isDragging = false;
		const diff = currentDragX - startDragX;

		if (getEffect() === 'marquee') {
			marqueeX = marqueeX + diff;
			if (actualOriginalWidth > 0) {
				while (marqueeX <= -actualOriginalWidth) {
					marqueeX += actualOriginalWidth;
				}
				while (marqueeX >= 0) {
					marqueeX -= actualOriginalWidth;
				}
			}
			resumeSlider();
		} else {
			if (Math.abs(diff) > dragThreshold) {
				if (diff > 0) {
					goToSlide(currentIndex - 1);
				} else {
					goToSlide(currentIndex + 1);
				}
			} else {
				goToSlide(currentIndex);
			}
			resumeSlider();
		}
	};

	track.addEventListener('mousedown', onPointerDown, evtOpts);
	track.addEventListener('mousemove', onPointerMove, evtOpts);
	window.addEventListener('mouseup', onPointerUp, evtOpts);

	track.addEventListener('touchstart', onPointerDown, evtOpts);
	track.addEventListener('touchmove', onPointerMove, evtOpts);
	window.addEventListener('touchend', onPointerUp, evtOpts);

	// Window resize handler
	window.addEventListener(
		'resize',
		() => {
			applyLayout();
			const newSlidesPerView = getSlidesPerView();
			if (newSlidesPerView !== currentSlidesPerView) {
				currentSlidesPerView = newSlidesPerView;
				bind(container, config);
				return;
			}
			goToSlide(currentIndex, false);
		},
		evtOpts
	);

	// Re-center when the track's box actually changes size. Elementor can apply
	// the slide/.e-con padding and final width a tick or two after render, which
	// would otherwise leave the first paint off-center until the user navigates.
	// Transform changes don't alter size, so re-centering here can't loop.
	if (typeof ResizeObserver !== 'undefined' && getEffect() !== 'marquee') {
		let lastTrackW = track.clientWidth;
		const ro = new ResizeObserver(() => {
			if (isDragging) return;
			const w = track.clientWidth;
			if (w !== lastTrackW) {
				lastTrackW = w;
				goToSlide(currentIndex, false);
			}
		});
		ro.observe(track);
		sliderDiv._aaeSliderResizeCleanup = () => ro.disconnect();
	}

	// Mutation Observer for added/removed slides in Editor
	const isEditMode = document.body.classList.contains('elementor-editor-active');
	if (isEditMode) {
		let debounceTimer = null;
		const reInit = () => {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(() => {
				bind(container, config);
			}, 150);
		};

		const observer = new MutationObserver((mutations) => {
			const shouldRefresh = mutations.some(
				(mutation) => mutation.type === 'childList' &&
				[...mutation.addedNodes, ...mutation.removedNodes].some(
					(n) => n.nodeType === 1 && !n.classList.contains('aae-slide-clone')
				)
			);
			if (shouldRefresh) reInit();
		});

		// Watch only the track's direct children (real slide add/remove).
		// Avoids Elementor editor overlays (.elementor-element-overlay) inside
		// slide content triggering a reinit on every select/deselect.
		observer.observe(track, { childList: true });

		sliderDiv._aaeSliderObserverCleanup = () => {
			clearTimeout(debounceTimer);
			observer.disconnect();
		};
	}

	// Editor affordance: the panel's "Slides" list calls this to bring a slide
	// into view when the user clicks a row (so the selected slide is reachable
	// for editing). Uses revealSlide() — NOT goToSlide() — so the last slide in
	// a multi-slidesPerView layout is fully shown instead of clamped short.
	// Seamless loop is always off in the editor, so no middle-set remap here.
	sliderDiv._aaeGoTo = (i) => {
		if (getEffect() === 'marquee') return;
		revealSlide(i);
	};

	// Belt-and-braces: the panel also broadcasts this event in case the node
	// reference is stale (e.g. just after a re-render). Same effect as _aaeGoTo.
	const onEditSlide = (e) => {
		if (!e.detail || e.detail.sliderId !== (sliderDiv.dataset.id || sliderDiv.id)) return;
		if (typeof sliderDiv._aaeGoTo === 'function') sliderDiv._aaeGoTo(e.detail.index);
	};
	window.addEventListener('aae/slider/edit-slide', onEditSlide, evtOpts);
	const prevEditCleanup = sliderDiv._aaeSliderEditCleanup;
	sliderDiv._aaeSliderEditCleanup = () => {
		window.removeEventListener('aae/slider/edit-slide', onEditSlide, evtOpts);
		delete sliderDiv._aaeGoTo;
	};
	if (typeof prevEditCleanup === 'function') prevEditCleanup();

	if (getEffect() === 'marquee') {
		track.style.transition = 'none';
		track.style.transform = 'translate3d(0, 0, 0)';
		getSlides().forEach(slide => {
			slide.style.transition = 'none';
			slide.style.transform = '';
		});
		startMarquee();
	} else {
		// First paint: place immediately, then re-place once layout settles.
		// In the editor the track's padding / .e-con sizing isn't final on the
		// synchronous pass, so getViewportWidth() (track content width) can read
		// stale and the centered slide lands off — fixed only after the first
		// nav. Re-run after a double rAF (post-layout) and again at 50ms so the
		// initial centering matches the post-interaction centering.
		goToSlide(currentIndex, false);
		requestAnimationFrame(() => {
			requestAnimationFrame(() => {
				goToSlide(currentIndex, false);
			});
		});
		const initTimeout = setTimeout(() => {
			goToSlide(currentIndex, false);
		}, 50);
		container._aaeSliderInitTimeout = initTimeout;
		startAutoplay();
	}
}

function unbind(el) {
	// Disconnect the MutationObserver FIRST so clone removal in _aaeSliderCleanup
	// doesn't fire reInit on the outgoing observer before it is torn down.
	if (el._aaeSliderObserverCleanup) {
		el._aaeSliderObserverCleanup();
		delete el._aaeSliderObserverCleanup;
	}
	if (el._aaeSliderCleanup) {
		el._aaeSliderCleanup();
		delete el._aaeSliderCleanup;
	}
	if (el._aaeSliderEditCleanup) {
		el._aaeSliderEditCleanup();
		delete el._aaeSliderEditCleanup;
	}
	clearTimeout(el._aaeSliderInitRetry);
	clearTimeout(el._aaeSliderInitTimeout);
}

// Invoked by the editor live-change dispatcher (play_group "aae_ns_") whenever a
// slider setting changes. A slider is an ongoing interactive component, not a
// one-shot entrance animation, so there's no "play once" — we just re-bind so
// the new settings take effect (and autoplay/marquee resume via bind()).
function play(el, config) {
	unbind(el);
	bind(el, config);
}

const refreshSliderById = (id, reason = 'manual') => {
	const element = getSliderElementById(id);

	if (!element) {
		console.warn('AAE slider refresh failed: element not found', { id, reason });
		return;
	}

	requestAnimationFrame(() => {
		requestAnimationFrame(() => {
			const cfg = configFor(element, MAP) || {};
			bind(element, cfg);
		});
	});
};

/**
 * Custom bridge event from parent editor window.
 */
window.addEventListener('aae:slider:refresh', (event) => {
	const id = event.detail?.id;
	const reason = event.detail?.reason || 'custom-event';
	refreshSliderById(id, reason);
});

window.AAEADDON.register({
	name: 'nested-slider',
	mapName: MAP,
	boundFlag: 'aae-nested-slider-bound',
	read,
	play,
	bind,
	unbind,
	reset: unbind,
});

