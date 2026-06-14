import { register } from '@elementor/frontend-handlers';

const initSlider = (container) => {
	const track = container.querySelector('.aae-slider-track');
	if (!track) return;

	const getSlides = () => Array.from(track.children).filter(el => el.tagName !== 'STYLE' && el.tagName !== 'SCRIPT');
	if (!getSlides().length) {
		// In the editor, slides might be injected slightly after the container renders.
		setTimeout(() => initSlider(container), 100);
		return;
	}

	const sliderDiv = container.classList.contains('aae-a-slider') ? container : container.querySelector('.aae-a-slider');
	if (!sliderDiv) return;

	const getSlidesPerView = () => parseInt(sliderDiv.getAttribute('data-slides-per-view')) || 3;
	const isCenterMode = () => sliderDiv.getAttribute('data-center-mode') === 'true';
	const is3DEnabled = () => sliderDiv.getAttribute('data-enable-3d') === 'true';
	
	let currentIndex = 0;
	
	// Helpers for dimensions
	const getSlideWidth = () => getSlides()[0]?.offsetWidth || 0;
	const getGap = () => parseFloat(window.getComputedStyle(track).gap) || 0;

	const goToSlide = (index) => {
		const slides = getSlides();
		if (!slides.length) return;
		
		if (index < 0) index = 0;
		const maxIndex = slides.length - getSlidesPerView();
		if (index > maxIndex) index = Math.max(0, maxIndex);
		
		currentIndex = index;
		const step = getSlideWidth() + getGap();
		let targetX = -(index * step);

		// If center mode, offset it so the active slide sits in the middle
		if (isCenterMode()) {
			const trackWidth = sliderDiv.offsetWidth;
			const offset = (trackWidth - getSlideWidth()) / 2;
			targetX += offset;
		}

		if (typeof window.gsap !== 'undefined') {
			window.gsap.to(track, {
				x: targetX,
				duration: 0.5,
				ease: "power2.out",
				onUpdate: apply3DPerspective
			});
		} else {
			track.style.transform = `translateX(${targetX}px)`;
		}
	};

	const apply3DPerspective = () => {
		if (typeof window.gsap === 'undefined') return;
		
		const slides = getSlides();
		if (!is3DEnabled()) {
			window.gsap.set(slides, { rotationY: 0, scale: 1, z: 0 });
			return;
		}

		const trackX = window.gsap.getProperty(track, "x") || 0;
		const containerCenter = sliderDiv.offsetWidth / 2;

		slides.forEach(slide => {
			// Find slide's absolute center position relative to the container
			const slideCenter = slide.offsetLeft + trackX + (slide.offsetWidth / 2);
			const distanceFromCenter = slideCenter - containerCenter;
			const normalizedDistance = distanceFromCenter / containerCenter; // roughly -1 to 1

			// 3D Math: Rotate Y based on distance from center (max 35 deg)
			const rotateY = normalizedDistance * -35; 
			// Scale down outer slides slightly
			const scale = Math.max(0.75, 1 - Math.abs(normalizedDistance) * 0.15);

			window.gsap.set(slide, {
				rotationY: rotateY,
				scale: scale,
				z: -Math.abs(normalizedDistance * 150), // Push back into Z space
				transformOrigin: "center center"
			});
		});
	};

	// Prevent GSAP Draggable from interfering with Elementor Editor native drag & drop!
	const isEditMode = document.body.classList.contains('elementor-editor-active');

	// Initialize positions
	if (is3DEnabled()) apply3DPerspective();
	goToSlide(0);

	// Initialize GSAP Draggable
	if (typeof window.Draggable !== 'undefined' && typeof window.gsap !== 'undefined') {
		const step = getSlideWidth() + getGap();
		
		// If InertiaPlugin is available (it's premium, user might have it), we can use it.
		// Otherwise we manual snap onDragEnd.
		const hasInertia = typeof window.InertiaPlugin !== 'undefined';
		
		window.Draggable.create(track, {
			type: "x",
			inertia: hasInertia,
			dragClickables: true, 
			snap: hasInertia ? { x: function(endValue) { return Math.round(endValue / step) * step; } } : undefined,
			onDrag: apply3DPerspective,
			onThrowUpdate: hasInertia ? apply3DPerspective : undefined,
			onDragEnd: function() {
				if (!hasInertia) {
					// Fallback: Calculate which slide we are closest to based on current drag position (this.x)
					const currentStep = getSlideWidth() + getGap();
					let rawIndex = -this.x / currentStep;
					
					if (isCenterMode()) {
						const trackWidth = sliderDiv.offsetWidth;
						const offset = (trackWidth - getSlideWidth()) / 2;
						rawIndex = -(this.x - offset) / currentStep;
					}

					const targetIndex = Math.round(rawIndex);
					goToSlide(targetIndex);
				}
			}
		});
	} else {
		console.warn('GSAP Draggable plugin is not loaded. Slider drag is disabled. Please enqueue Draggable.js.');
	}

	// Handle window resize dynamically
	window.addEventListener('resize', () => {
		goToSlide(currentIndex);
		apply3DPerspective();
	});
};

register({
	elementType: 'e-aae-a-slider',
	id: 'aae-a-slider-handler',
	callback: ( { element } ) => {
		initSlider(element);
	}
});
