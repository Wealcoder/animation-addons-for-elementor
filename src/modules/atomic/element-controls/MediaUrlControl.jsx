/* eslint-env browser */

/**
 * AAE Media URL — a URL text field with a WordPress Media Library picker.
 *
 * Elementor 4.x ships media controls for IMAGE, SVG and VIDEO only
 * (image-control / svg-control / video-control, all bound to their own object
 * prop types). There is no generic file picker, so a widget that needs a
 * non-media asset — a Lottie `.json`, a subtitle track, a GLTF model — has
 * nothing to bind to but a bare text field.
 *
 * This fills that gap WITHOUT inventing a new prop type: it is prop-bound to a
 * plain String prop (registered with `stringPropTypeUtil`), so the stored value
 * is just the URL. A widget can swap a Text_Control for this one and back again
 * with no schema change and no migration of saved documents.
 *
 * PHP side: any Atomic_Control_Base subclass whose get_type() is
 * 'aae-media-url'. Its get_props() drives the frame — see
 * AAE_A_Media_Url_Control in the Pro plugin's Lottie widget.
 *
 * ── Uploading a type WordPress blocks by default ──────────────────────────
 * `.json` is not in WP's allowed mimes. Elementor's Uploads_Manager re-adds the
 * blocked-but-supported types during an upload that (a) carries the request
 * flag `uploadTypeCaller=elementor-wp-media-upload` and (b) happens while
 * "Enable Unfiltered File Uploads" is on (Elementor → Settings → Advanced).
 * This control sets that flag exactly the way Elementor's own frame does
 * (`setTypeCaller` in @elementor/wp-media) and swaps plupload's extension
 * filter for the duration of the frame, restoring it on close. When the setting
 * is off the picker still browses and still accepts a pasted URL — only the
 * upload is refused, by WordPress, so the control says so up front rather than
 * letting the user hit an opaque "file type not permitted" error.
 */

import * as React from 'react';
import { useEffect, useRef, useState } from 'react';
import { useBoundProp } from '@elementor/editor-controls';
import { stringPropTypeUtil } from '@elementor/editor-props';
import { Box, Button, Stack, TextField, Typography } from '@elementor/ui';

const COMMIT_DEBOUNCE_MS = 300;

// Mirrors handleExtensions() in @elementor/wp-media: plupload's filter decides
// what the browser's file dialog offers and what the uploader will send, and it
// is GLOBAL — always restore it, or every later upload in this editor session
// is stuck on our list.
function useUploadExtensions() {
	const previous = useRef( null );

	const apply = ( extensions ) => {
		const uploader = window.wp && window.wp.Uploader;
		if ( ! uploader || ! extensions ) {
			return;
		}
		const filters = uploader.defaults && uploader.defaults.filters;
		if ( ! filters ) {
			return;
		}
		previous.current = filters.mime_types;
		filters.mime_types = [ { extensions } ];
	};

	const restore = () => {
		const uploader = window.wp && window.wp.Uploader;
		const filters = uploader && uploader.defaults && uploader.defaults.filters;
		if ( ! filters || null === previous.current ) {
			return;
		}
		filters.mime_types = previous.current;
		previous.current = null;
	};

	// A frame left open when the panel unmounts would never fire 'close'.
	useEffect( () => restore, [] );

	return { apply, restore };
}

export const MediaUrlControl = ( props ) => {
	const {
		mimeTypes = [],
		extensions = '',
		title = 'Select File',
		buttonText = 'Select',
		placeholder = 'https://…',
		unfilteredUploads = true,
		settingsUrl = '',
		uploadHint = '',
	} = props || {};

	const { value, setValue, disabled } = useBoundProp( stringPropTypeUtil );
	const [ draft, setDraft ] = useState( value || '' );
	const timerRef = useRef( null );
	const frameRef = useRef( null );
	const uploadExtensions = useUploadExtensions();

	// Follow the prop when it changes from anywhere but this input (undo, a
	// preset apply, the picker below) without fighting the user mid-keystroke.
	useEffect( () => {
		if ( ! timerRef.current ) {
			setDraft( value || '' );
		}
	}, [ value ] );

	useEffect( () => {
		return () => {
			if ( timerRef.current ) {
				clearTimeout( timerRef.current );
			}
			if ( frameRef.current ) {
				frameRef.current.detach();
				frameRef.current.remove();
			}
		};
	}, [] );

	const commit = ( next ) => {
		setDraft( next );
		if ( timerRef.current ) {
			clearTimeout( timerRef.current );
		}
		timerRef.current = setTimeout( () => {
			timerRef.current = null;
			setValue( next );
		}, COMMIT_DEBOUNCE_MS );
	};

	const commitNow = ( next ) => {
		if ( timerRef.current ) {
			clearTimeout( timerRef.current );
			timerRef.current = null;
		}
		setDraft( next );
		setValue( next );
	};

	const openLibrary = () => {
		const wp = window.wp;
		if ( ! wp || ! wp.media ) {
			return;
		}

		if ( frameRef.current ) {
			frameRef.current.detach();
			frameRef.current.remove();
		}

		const frame = wp.media( {
			title,
			multiple: false,
			library: mimeTypes.length ? { type: mimeTypes } : {},
			button: { text: buttonText },
		} );
		frameRef.current = frame;

		frame.on( 'open', () => {
			// The flag Elementor's Uploads_Manager looks for before re-adding the
			// blocked-but-supported mime types to WordPress' allowed list.
			try {
				frame.uploader.uploader.param( 'uploadTypeCaller', 'elementor-wp-media-upload' );
			} catch ( _e ) {
				// No uploader region (e.g. the browse-only state) — nothing to flag.
			}
			uploadExtensions.apply( extensions );
		} );

		frame.on( 'close', () => uploadExtensions.restore() );

		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first();
			const url = attachment && attachment.toJSON ? attachment.toJSON().url : '';
			if ( url ) {
				commitNow( url );
			}
		} );

		frame.open();
	};

	return (
		<Stack gap={ 1 }>
			<TextField
				fullWidth
				size="tiny"
				disabled={ disabled }
				value={ draft }
				placeholder={ placeholder }
				onChange={ ( event ) => commit( event.target.value ) }
				onBlur={ () => commitNow( draft ) }
			/>

			<Stack direction="row" gap={ 1 }>
				<Button
					fullWidth
					size="tiny"
					variant="outlined"
					color="secondary"
					disabled={ disabled }
					onClick={ openLibrary }
				>
					{ buttonText }
				</Button>
				{ draft ? (
					<Button
						size="tiny"
						variant="text"
						color="secondary"
						disabled={ disabled }
						onClick={ () => commitNow( '' ) }
					>
						{ 'Clear' }
					</Button>
				) : null }
			</Stack>

			{ ! unfilteredUploads && uploadHint ? (
				<Box>
					<Typography variant="caption" color="text.secondary">
						{ uploadHint }{ ' ' }
						{ settingsUrl ? (
							<a href={ settingsUrl } target="_blank" rel="noopener noreferrer">
								{ 'Open settings' }
							</a>
						) : null }
					</Typography>
				</Box>
			) : null }
		</Stack>
	);
};
