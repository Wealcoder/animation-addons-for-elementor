/* eslint-env browser */

/**
 * DrawPlayControl — the "Play Animation" element-control for the AAE DrawSVG widget.
 *
 * Registered under the type id 'aae-draw-play' (see ./index.js) and placed by the
 * PHP side (AAE_A_Draw_Play_Control) inside the widget's Animation section.
 *
 * This is an ACTION control — it carries no stored value. On click it calls
 * window.AAEDrawSvg.replay( elementId ) inside the preview iframe, where the
 * widget's frontend runtime (draw-svg.js) restarts the draw animation for the
 * selected element.
 */

import * as React from "react";
import { useElement } from "@elementor/editor-editing-panel";
import { Button, Stack } from "@elementor/ui";

function getPreviewWindow() {
	try {
		const iframe = document.querySelector( "#elementor-preview-iframe" );
		return iframe && iframe.contentWindow ? iframe.contentWindow : null;
	} catch ( e ) {
		return null;
	}
}

export function DrawPlayControl( { label } ) {
	const { element } = useElement();
	const elementId = element.id;

	const onPlay = () => {
		const win = getPreviewWindow();
		if ( win && win.AAEDrawSvg && typeof win.AAEDrawSvg.replay === "function" ) {
			win.AAEDrawSvg.replay( elementId );
		}
	};

	return (
		<Stack gap={ 1 }>
			<Button
				size="small"
				variant="contained"
				color="primary"
				fullWidth
				onClick={ onPlay }
			>
				{ label || "Play Animation" }
			</Button>
		</Stack>
	);
}
