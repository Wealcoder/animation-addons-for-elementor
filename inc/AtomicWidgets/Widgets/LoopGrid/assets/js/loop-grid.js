/* eslint-env browser */

/**
 * AAE Loop Grid — frontend pagination runtime.
 *
 * The grid + first page are server-rendered. This wires the Pagination widget's
 * Prev / Page-Numbers / Next / Load-More controls to either:
 *   - AJAX   : fetch the requested page's loop-item cells (full HTML, styles
 *              intact) via `aae_loop_grid_page` and swap/append them, updating
 *              the URL (?aae_page=N) without a reload; or
 *   - Reload : navigate to ?aae_page=N and let the server render it.
 *
 * All config (ajax url, nonce, grid id, post id, current/total pages, method,
 * query) travels inline on the pagination wrapper's data-aae-config. There is
 * no pagination "type" — the runtime is DOM-driven and wires whichever pieces
 * (Prev/Next, Numbers, Load More) are present + visible.
 */
( function () {
	'use strict';

	var FADE_MS = 200;

	function parseConfig( el ) {
		try {
			return JSON.parse( el.getAttribute( 'data-aae-config' ) || '{}' );
		} catch ( e ) {
			return {};
		}
	}

	/** The grid container (.aae-a-loop-grid) that this pagination controls. */
	function findGrid( pagination ) {
		var wrap = pagination.closest( '.aae-a-loop-grid-wrap' );
		if ( ! wrap ) {
			return null;
		}
		return wrap.querySelector( '.aae-a-loop-grid' ) || wrap;
	}

	function setUrlPage( paged ) {
		try {
			var url = new URL( window.location.href );
			if ( paged > 1 ) {
				url.searchParams.set( 'aae_page', String( paged ) );
			} else {
				url.searchParams.delete( 'aae_page' );
			}
			window.history.pushState( { aaePage: paged }, '', url.toString() );
		} catch ( e ) { /* no-op */ }
	}

	function request( cfg, paged ) {
		var body = new window.FormData();
		body.append( 'action', 'aae_loop_grid_page' );
		body.append( 'nonce', cfg.nonce );
		body.append( 'post_id', cfg.postId );
		body.append( 'grid_id', cfg.grid );
		body.append( 'paged', String( paged ) );
		return window.fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } );
	}

	function fadeReplace( grid, html ) {
		grid.style.transition = 'opacity ' + FADE_MS + 'ms ease';
		grid.style.opacity = '0';
		return new Promise( function ( resolve ) {
			window.setTimeout( function () {
				grid.innerHTML = html;
				// force reflow then fade back in
				void grid.offsetHeight;
				grid.style.opacity = '1';
				resolve();
			}, FADE_MS );
		} );
	}

	function appendCells( grid, html ) {
		var tmp = document.createElement( 'div' );
		tmp.innerHTML = html;
		var frag = document.createDocumentFragment();
		while ( tmp.firstChild ) {
			var node = tmp.firstChild;
			if ( node.nodeType === 1 ) {
				node.style.opacity = '0';
				node.style.transition = 'opacity ' + FADE_MS + 'ms ease';
			}
			frag.appendChild( node );
		}
		grid.appendChild( frag );
		// fade the just-added element nodes in
		window.requestAnimationFrame( function () {
			Array.prototype.forEach.call( grid.children, function ( c ) {
				if ( c.style && c.style.opacity === '0' ) {
					c.style.opacity = '1';
				}
			} );
		} );
	}

	/** Re-render the numbers list (smart-truncate) client-side after an AJAX page change. */
	function smartPages( current, total ) {
		if ( total <= 7 ) {
			var all = [];
			for ( var i = 1; i <= Math.max( 1, total ); i++ ) {
				all.push( i );
			}
			return all;
		}
		var pages = [ 1 ];
		var start = Math.max( 2, current - 1 );
		var end = Math.min( total - 1, current + 1 );
		if ( start > 2 ) {
			pages.push( '...' );
		}
		for ( var p = start; p <= end; p++ ) {
			pages.push( p );
		}
		if ( end < total - 1 ) {
			pages.push( '...' );
		}
		pages.push( total );
		return pages;
	}

	function rebuildNumbers( numbersEl, current, total ) {
		if ( ! numbersEl ) {
			return;
		}
		var items = smartPages( current, total );
		var out = '';
		items.forEach( function ( p ) {
			if ( p === '...' ) {
				out += '<span class="aae-a-loop-num aae-a-loop-num-gap">…</span>';
			} else {
				out += '<a href="#" class="aae-a-loop-num' + ( p === current ? ' is-active' : '' ) +
					'" data-aae-page="' + p + '">' + p + '</a>';
			}
		} );
		numbersEl.innerHTML = out;
	}

	function updatePrevNextState( pagination, current, total ) {
		var prev = pagination.querySelector( '[data-aae-nav="prev"]' );
		var next = pagination.querySelector( '[data-aae-nav="next"]' );
		if ( prev ) {
			prev.classList.toggle( 'is-disabled', current <= 1 );
		}
		if ( next ) {
			next.classList.toggle( 'is-disabled', current >= total );
		}
	}

	function goToPage( ctx, paged ) {
		var cfg = ctx.cfg;
		if ( paged < 1 || paged > ctx.total || paged === ctx.current || ctx.busy ) {
			return;
		}

		// Page reload mode.
		if ( cfg.method === 'reload' ) {
			var url = new URL( window.location.href );
			if ( paged > 1 ) {
				url.searchParams.set( 'aae_page', String( paged ) );
			} else {
				url.searchParams.delete( 'aae_page' );
			}
			window.location.href = url.toString();
			return;
		}

		// AJAX mode.
		ctx.busy = true;
		ctx.pagination.classList.add( 'is-loading' );
		request( cfg, paged ).then( function ( res ) {
			if ( res && res.success && res.data ) {
				fadeReplace( ctx.grid, res.data.html ).then( function () {
					ctx.current = res.data.paged;
					ctx.total = res.data.max_pages;
					rebuildNumbers( ctx.numbersEl, ctx.current, ctx.total );
					updatePrevNextState( ctx.pagination, ctx.current, ctx.total );
					setUrlPage( ctx.current );
				} );
			}
		} ).catch( function () { /* no-op */ } ).then( function () {
			ctx.busy = false;
			ctx.pagination.classList.remove( 'is-loading' );
		} );
	}

	function loadMore( ctx ) {
		var cfg = ctx.cfg;
		if ( ctx.busy || ctx.current >= ctx.total ) {
			return;
		}
		var nextPage = ctx.current + 1;
		ctx.busy = true;
		ctx.pagination.classList.add( 'is-loading' );
		if ( ctx.loadMoreEl ) {
			ctx.loadMoreEl.classList.add( 'is-loading' );
		}
		request( cfg, nextPage ).then( function ( res ) {
			if ( res && res.success && res.data ) {
				appendCells( ctx.grid, res.data.html );
				ctx.current = res.data.paged;
				ctx.total = res.data.max_pages;
				if ( ctx.current >= ctx.total && ctx.loadMoreEl ) {
					ctx.loadMoreEl.style.display = 'none';
				}
			}
		} ).catch( function () { /* no-op */ } ).then( function () {
			ctx.busy = false;
			ctx.pagination.classList.remove( 'is-loading' );
			if ( ctx.loadMoreEl ) {
				ctx.loadMoreEl.classList.remove( 'is-loading' );
			}
		} );
	}

	function initPagination( pagination ) {
		if ( pagination.__aaeBound ) {
			return;
		}
		pagination.__aaeBound = true;

		var cfg = parseConfig( pagination );
		var grid = findGrid( pagination );
		if ( ! grid || ! cfg.ajaxUrl ) {
			return;
		}

		var ctx = {
			pagination: pagination,
			grid: grid,
			cfg: cfg,
			current: cfg.current || 1,
			total: cfg.total || 1,
			busy: false,
			numbersEl: pagination.querySelector( '.aae-a-loop-numbers' ),
			loadMoreEl: pagination.querySelector( '[data-aae-loadmore]' ),
		};

		updatePrevNextState( pagination, ctx.current, ctx.total );

		pagination.addEventListener( 'click', function ( e ) {
			// Number link.
			var num = e.target.closest( '[data-aae-page]' );
			if ( num && pagination.contains( num ) ) {
				e.preventDefault();
				goToPage( ctx, parseInt( num.getAttribute( 'data-aae-page' ), 10 ) );
				return;
			}
			// Prev / Next.
			var nav = e.target.closest( '[data-aae-nav]' );
			if ( nav && pagination.contains( nav ) ) {
				e.preventDefault();
				if ( nav.classList.contains( 'is-disabled' ) ) {
					return;
				}
				var role = nav.getAttribute( 'data-aae-nav' );
				goToPage( ctx, role === 'prev' ? ctx.current - 1 : ctx.current + 1 );
				return;
			}
			// Load More.
			var lm = e.target.closest( '[data-aae-loadmore]' );
			if ( lm && pagination.contains( lm ) ) {
				e.preventDefault();
				loadMore( ctx );
			}
		} );
	}

	function init() {
		// DOM-driven: bind every pagination bar. Behaviour follows whichever
		// pieces are present + visible (Prev/Next / Numbers / Load More) — there
		// is no "type" to filter on. The click handler dispatches by the clicked
		// element's data-attr, so a bar with no visible controls simply does
		// nothing.
		var nodes = document.querySelectorAll( '.aae-a-loop-pagination[data-aae-pagination]' );
		Array.prototype.forEach.call( nodes, initPagination );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
