/* eslint-env browser */

import * as React from 'react';
import { useState } from 'react';
import { Button } from '@elementor/ui';

import { getPreviewWindow, getSelectedContainer } from '../../editor-bridge/helpers';
import { featuresFor } from '../../editor-bridge/features';
import { replayInPreview } from '../../editor-bridge/settings-bridge';

/**
 * "Play Now" button inside a <ResponsiveSection>.
 *
 * The live bridge already keeps `window.AAE_INTERACTIONS_*[id]` in sync
 * on every settings mutation, so this button doesn't need to re-mirror
 * settings — it just resolves the iframe target and replays.
 */
export function PlayButtonInput() {
	const [played, setPlayed] = useState(false);

	const onClick = (e) => {
		e.preventDefault();
		e.stopPropagation();

		const container = getSelectedContainer();
		const features  = container ? featuresFor(container) : [];
		const win       = getPreviewWindow();

		if (!container || !features.length || !win) {
			// eslint-disable-next-line no-console
			console.warn('[AAE] Play: no selection / unsupported widget / preview not ready.');
			return;
		}

		const target = features[0].findTarget(win.document, container.id);
		if (!target) {
			// eslint-disable-next-line no-console
			console.warn('[AAE] Play: target element not found in preview.');
			return;
		}

		if (!replayInPreview(target)) {
			// eslint-disable-next-line no-console
			console.warn('[AAE] Play: animation runtime (aaeAtomicAnimations) not available in preview. Is GSAP enqueued?');
			return;
		}

		setPlayed(true);
		setTimeout(() => setPlayed(false), 600);
	};

	return (
		<Button
			variant="contained"
			size="small"
			onClick={onClick}
			disabled={played}
			sx={{
				background: '#0c977d',
				color: '#fff',
				'&:hover': { background: '#0b8870' },
				minWidth: 90,
				textTransform: 'none',
			}}
		>
			{played ? 'Played' : 'Play Now'}
		</Button>
	);
}
