import { register } from '@elementor/frontend-handlers';
import '../scss/search.scss';

const initSearchForm = ( container, signal ) => {
	const opts = signal ? { signal } : {};

	// ── Toggle (dropdown / full-screen presets) ──────────────────────────────

	const toggleOpen  = container.querySelector( '.toggle--open' );
	const toggleClose = container.querySelectorAll( '.toggle--close' );

	toggleOpen?.addEventListener( 'click', () => container.classList.add( 'search-visible' ), opts );
	toggleClose.forEach( btn => btn.addEventListener( 'click', () => {
		container.classList.remove( 'search-visible' );
		resetSelections();
	}, opts ) );

	// ── Focus ring ───────────────────────────────────────────────────────────

	const form = container.querySelector( '.wcf-search-form' );
	container.querySelectorAll( 'input' ).forEach( input => {
		input.addEventListener( 'focus', () => form?.classList.add( 'wcf-search-form--focus' ), opts );
		input.addEventListener( 'blur',  () => form?.classList.remove( 'wcf-search-form--focus' ), opts );
	} );

	// ── Date filter ──────────────────────────────────────────────────────────

	const dateCon  = container.querySelector( '.date-container' );
	const catCon   = container.querySelector( '.category-container' );
	const fromDate = dateCon?.querySelector( '.from-date' );
	const toDate   = dateCon?.querySelector( '.to-date' );

	dateCon?.querySelector( '.date-toggle' )?.addEventListener( 'click', e => {
		e.preventDefault();
		dateCon.classList.toggle( 'active' );
		catCon?.classList.remove( 'active' );
	}, opts );

	dateCon?.querySelectorAll( '.preset-options li' ).forEach( li => {
		li.addEventListener( 'click', () => {
			const preset = li.dataset.preset;
			const now    = new Date();
			let from, to;
			switch ( preset ) {
				case 'today':     from = to = new Date(); break;
				case 'yesterday': from = to = new Date( now.setDate( now.getDate() - 1 ) ); break;
				case 'week':      from = new Date( now.setDate( now.getDate() - 6 ) ); to = new Date(); break;
				case 'month':     from = new Date( now.getFullYear(), now.getMonth(), 1 ); to = new Date(); break;
			}
			if ( fromDate && from ) fromDate.value = from.toISOString().split( 'T' )[ 0 ];
			if ( toDate   && to   ) toDate.value   = to.toISOString().split( 'T' )[ 0 ];
			li.classList.toggle( 'selected' );
			li.parentElement.querySelectorAll( 'li' ).forEach( s => { if ( s !== li ) s.classList.remove( 'selected' ); } );
		}, opts );
	} );

	dateCon?.querySelector( '.clear-btn' )?.addEventListener( 'click', () => {
		if ( fromDate ) fromDate.value = '';
		if ( toDate )   toDate.value   = '';
		dateCon.querySelectorAll( '.preset-options li' ).forEach( li => li.classList.remove( 'selected' ) );
	}, opts );

	dateCon?.querySelector( '.apply-btn' )?.addEventListener( 'click', () => {
		dateCon.classList.remove( 'active' );
		runAjaxSearch();
	}, opts );

	// ── Category filter ──────────────────────────────────────────────────────

	const selectedCats = [];
	const catItems     = catCon?.querySelectorAll( '.category-list li' ) ?? [];

	catCon?.querySelector( '.category-toggle' )?.addEventListener( 'click', e => {
		e.preventDefault();
		catCon.classList.toggle( 'active' );
		dateCon?.classList.remove( 'active' );
	}, opts );

	catItems.forEach( li => {
		li.addEventListener( 'click', () => {
			const value = li.dataset.value;
			if ( value ) {
				const idx = selectedCats.findIndex( c => c.value === value );
				if ( idx === -1 ) { selectedCats.push( { value, label: li.textContent.trim() } ); li.classList.add( 'selected' ); }
				else               { selectedCats.splice( idx, 1 ); li.classList.remove( 'selected' ); }
				catItems.forEach( s => { if ( ! s.dataset.value ) s.classList.remove( 'selected' ); } );
			} else {
				selectedCats.length = 0;
				catItems.forEach( li => li.classList.remove( 'selected' ) );
				li.classList.add( 'selected' );
			}
			syncCatDisplay();
		}, opts );
	} );

	catCon?.querySelector( '.clear-cat-btn' )?.addEventListener( 'click', e => {
		e.preventDefault();
		selectedCats.length = 0;
		catItems.forEach( li => li.classList.remove( 'selected' ) );
		catItems.forEach( li => { if ( ! li.dataset.value ) li.classList.add( 'selected' ); } );
		syncCatDisplay();
	}, opts );

	catCon?.querySelector( '.apply-cat-btn' )?.addEventListener( 'click', e => {
		e.preventDefault();
		catCon.classList.remove( 'active' );
		runAjaxSearch();
	}, opts );

	// Close filter dropdowns on outside click
	document.addEventListener( 'click', e => {
		if ( ! container.contains( e.target ) ) {
			dateCon?.classList.remove( 'active' );
			catCon?.classList.remove( 'active' );
		}
	}, opts );

	// ── Helpers ──────────────────────────────────────────────────────────────

	const syncCatDisplay = () => {
		const display = container.querySelector( '.selected-category-display' );
		if ( ! display ) return;
		display.innerHTML = selectedCats.length
			? selectedCats.map( c => `<span class="category-pill">${ c.label }</span>` ).join( ', ' )
			: '<span class="category-pill">All Categories</span>';
	};

	const resetSelections = () => {
		container.querySelector( '.selected-category-display' )?.replaceChildren();
		dateCon?.querySelectorAll( '.preset-options li' ).forEach( li => li.classList.remove( 'selected' ) );
		catItems.forEach( li => li.classList.remove( 'selected' ) );
		if ( fromDate ) fromDate.value = '';
		if ( toDate )   toDate.value   = '';
	};

	// ── Ajax live search ─────────────────────────────────────────────────────

	const searchInput = container.querySelector( '.search-field' );
	const results     = container.querySelector( '.aae--live-search-results' );
	const fsCon       = container.querySelector( '.wcf-search-container' );

	let debounceTimer;

	const runAjaxSearch = () => {
		if ( ! searchInput || ! results ) return;
		const keyword = searchInput.value.trim();
		const from    = fromDate?.value;
		const to      = toDate?.value;

		if ( ! keyword && ! from && ! to && ! selectedCats.length ) {
			results.style.display = 'none';
			return;
		}

		const body = new URLSearchParams( {
			action:  'live_search',
			keyword,
			nonce:   window.WCF_ADDONS_JS?._wpnonce ?? '',
		} );
		if ( from && to ) { body.append( 'from_date', from ); body.append( 'to_date', to ); }
		selectedCats.forEach( c => body.append( 'category[]', c.value ) );

		fetch( window.WCF_ADDONS_JS?.ajaxUrl ?? '/wp-admin/admin-ajax.php', {
			method: 'POST',
			body,
			signal: signal ?? undefined,
		} )
			.then( r => r.text() )
			.then( html => {
				fsCon?.classList.add( 'ajax-fs-wrap' );
				results.innerHTML     = html;
				results.style.display = 'grid';
				results.querySelectorAll( '.toggle--close' ).forEach( btn =>
					btn.addEventListener( 'click', () => {
						results.style.display = 'none';
						fsCon?.classList.remove( 'ajax-fs-wrap' );
					}, opts )
				);
			} )
			.catch( () => {} );
	};

	searchInput?.addEventListener( 'input', () => {
		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( runAjaxSearch, 500 );
	}, opts );
};

register( {
	elementType: 'e-aae-a-search-form',
	id:          'aae-a-search-form-handler',
	callback: ( { element, signal } ) => {
		const container = element.classList.contains( 'aae-a-search-form' )
			? element
			: element.querySelector( '.aae-a-search-form' );
		if ( container ) initSearchForm( container, signal );
	},
} );
