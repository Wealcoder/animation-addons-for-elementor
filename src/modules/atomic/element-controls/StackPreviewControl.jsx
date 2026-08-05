/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * StackPreviewControl — the "Preview Animation" element-control for Stack Cards.
 *
 * Registered under the type id 'aae-stack-preview' (see ./index.js) and placed
 * by the PHP side (AAE_A_Stack_Preview_Control) at the top of the widget's
 * Stack Cards section, directly above the Animation picker.
 *
 * ACTION control — carries no stored value. It drives window.AAEStackCards
 * inside the preview iframe, the same cross-frame pattern DrawPlayControl uses
 * for window.AAEDrawSvg. The deck's runtime builds the REAL animation timeline
 * (ScrollTrigger is attached separately on the frontend, so the timeline itself
 * is reusable) and this either plays it through once or scrubs it by hand.
 *
 * Why a scrub slider and not just Play: with ten animations to choose between,
 * being able to stop halfway and look at the pose is the difference between
 * picking one and guessing.
 */

import * as React from 'react';
import { useElement } from '@elementor/editor-editing-panel';
import { Box, Button, Stack, Tooltip, Typography } from '@elementor/ui';

function getPreviewWindow() {
	try {
		const iframe = document.querySelector( '#elementor-preview-iframe' );
		return iframe && iframe.contentWindow ? iframe.contentWindow : null;
	} catch ( e ) {
		return null;
	}
}

/** The deck runtime's cross-frame API, or null before the preview has booted. */
function getApi() {
	const win = getPreviewWindow();
	return win && win.AAEStackCards ? win.AAEStackCards : null;
}

export function StackPreviewControl( { label } ) {
	const { element } = useElement();
	const elementId = element.id;

	const [ scrubbing, setScrubbing ] = React.useState( false );
	const [ progress, setProgress ] = React.useState( 0 );

	// Leaving the widget (or the panel) must not strand the canvas mid-pose —
	// the runtime's stop() restores the flat editing list.
	React.useEffect( () => {
		return () => {
			const api = getApi();
			if ( api && typeof api.stop === 'function' ) {
				api.stop( elementId );
			}
		};
	}, [ elementId ] );

	const handlePlay = () => {
		const api = getApi();
		if ( ! api || typeof api.play !== 'function' ) {
			return;
		}
		setScrubbing( false );
		setProgress( 0 );
		api.play( elementId );
	};

	const handleScrub = ( event ) => {
		const api = getApi();
		const pct = Number( event.target.value );
		setScrubbing( true );
		setProgress( pct );
		if ( api && typeof api.scrub === 'function' ) {
			api.scrub( elementId, pct );
		}
	};

	const handleReset = () => {
		const api = getApi();
		setScrubbing( false );
		setProgress( 0 );
		if ( api && typeof api.stop === 'function' ) {
			api.stop( elementId );
		}
	};

	return (
		<Stack gap={ 1 }>
			<Stack direction="row" gap={ 0.5 }>
				<Tooltip title="Play the real animation on the canvas">
					<Button
						size="small"
						variant="contained"
						color="primary"
						fullWidth
						onClick={ handlePlay }
					>
						{ label || 'Preview Animation' }
					</Button>
				</Tooltip>
				{ scrubbing && (
					<Tooltip title="Back to editing">
						<Button size="small" variant="outlined" onClick={ handleReset }>
							{ '✕' }
						</Button>
					</Tooltip>
				) }
			</Stack>

			<Box>
				<Typography variant="caption" sx={ { color: 'text.secondary' } }>
					{ `Scrub — ${ Math.round( progress ) }%` }
				</Typography>
				{ /* Native range, deliberately. @elementor/ui is a runtime external
				     (window.elementorV2.ui) whose export list can't be checked at
				     build time, and no other AAE control uses its Slider — an
				     undefined component would crash the whole panel (React #130). */ }
				<Box
					component="input"
					type="range"
					min={ 0 }
					max={ 100 }
					step={ 1 }
					value={ progress }
					onChange={ handleScrub }
					aria-label="Scrub animation"
					sx={ { width: '100%', display: 'block', mt: 0.5, cursor: 'pointer', accentColor: 'primary.main' } }
				/>
			</Box>
		</Stack>
	);
}
