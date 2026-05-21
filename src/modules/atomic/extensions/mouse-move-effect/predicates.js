import { valueAt } from '../../responsive-section/helpers';



export function isEnabled(s, bp) {
    return !!valueAt(s, 'aae_mouse_move_effect_enable', bp);
}

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}


export function isCustomMovementWrapper(settings,bp) {
    return isEnabled(settings, bp) && valueAt(settings, 'aae_mouse_move_effect_movement_wrapper', bp) === 'custom';
}


export function showPlayButton(s, bp) {
    return isEnabled(s, bp) && plainBool(s, 'aae_mouse_move_effect_enable_editor');
}
 