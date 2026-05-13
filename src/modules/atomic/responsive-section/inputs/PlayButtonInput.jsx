/* eslint-env browser */

import * as React from 'react';
import { useState } from 'react';
import { Button } from '@elementor/ui';

import { getPreviewWindow, getSelectedContainer } from '../../editor-bridge/helpers';
import { featuresFor } from '../../editor-bridge/features';
import { applySettingsToDom, replayInPreview } from '../../editor-bridge/settings-bridge';

/**
 * "Play Now" button inside a <ResponsiveSection>. Unlike the other inputs
 * in this folder, this one has no underlying prop value to read/write — it
 * just triggers a replay of the animation on the currently-selected element
 * in the preview iframe. The Play Animation switch prop (TEXT_PLAY_TOKEN /
 * ANIM_PLAY_TOKEN) is no longer needed; the row's visibility predicate
 * gates this button instead.
 *
 * Click flow:
 *   1. Resolve the selected container + its feature config.
 *   2. Mirror current settings into the preview-iframe DOM data-attrs
 *      (applySettingsToDom) so the animation reads the latest values.
 *   3. Find the iframe target node and call replayInPreview.
 *   4. Flash "Played" briefly so the user gets visual feedback.
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

		const result = applySettingsToDom(container);
		if (!result?.target) {
			// eslint-disable-next-line no-console
			console.warn('[AAE] Play: target element not found in preview.');
			return;
		}

		if (!replayInPreview(result.target)) {
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
