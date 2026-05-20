/* eslint-env browser */
import { valueEq, plainBool } from '../../responsive-section/helpers';

export function showPlayButton(settings) {
	return plainBool(settings, 'aae_cursor_hover_enable_editor');
}
