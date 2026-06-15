import { register } from '@elementor/frontend-handlers';

const initPosts = (grid) => {
	const cards = grid.querySelectorAll('.aae-a-post-card');

	if (!cards.length) return;

	if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
		cards.forEach(card => {
			card.style.opacity = '1';
			card.style.transform = 'translateY(0)';
		});
		return;
	}

	gsap.fromTo(cards, 
		{ 
			opacity: 0, 
			y: 30 
		},
		{
			opacity: 1,
			y: 0,
			duration: 1.2,
			stagger: 0.2,
			ease: "power2.out",
			scrollTrigger: {
				trigger: grid,
				start: "top 85%", // Start animation when grid is 85% in viewport
				toggleActions: "play none none none"
			}
		}
	);
	
};

register({
	elementType: 'e-aae-a-posts',
	id: 'aae-a-posts-handler',
	callback: ( { element } ) => {
		
		// Element is either the grid itself, or a wrapper containing the grid
		const grid = element.classList.contains('aae-a-posts-grid') ? element : element.querySelector('.aae-a-posts-grid');
		if (grid) {
			initPosts(grid);
		}
	}
});
