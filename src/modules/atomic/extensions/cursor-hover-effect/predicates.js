import { valueAt } from '../../responsive-section/helpers';

export function isEnabled(s, bp) {
	return !!valueAt(s, 'aae_cursor_hover_enable', bp);
}

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

export function showPlayButton(s, bp) {
	return isEnabled(s, bp) && plainBool(s, 'aae_cursor_hover_enable_editor');
}
