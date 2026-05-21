
/* eslint-env browser */

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

export function showPlayButton(s, bp) {
	return plainBool(s, 'aae_ih_enable_editor');
}
