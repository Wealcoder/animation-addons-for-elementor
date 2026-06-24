import { register } from '@elementor/frontend-handlers';

function initPosts( wrapper ) {
	const cards = wrapper.querySelectorAll( '.aae-a-post-card' );
	if ( ! cards.length ) return;

	cards.forEach( ( card, i ) => {
		card.style.opacity   = '0';
		card.style.transform = 'translateY(20px)';
		card.style.transition = `opacity 0.5s ease ${ i * 0.08 }s, transform 0.5s ease ${ i * 0.08 }s`;

		const io = new IntersectionObserver( ( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					card.style.opacity   = '1';
					card.style.transform = 'translateY(0)';
					io.unobserve( card );
				}
			} );
		}, { threshold: 0.1 } );

		io.observe( card );
	} );
}

register( {
	elementType: 'e-aae-a-posts',
	id:          'aae-a-posts-handler',
	callback:    ( { element } ) => initPosts( element ),
} );
