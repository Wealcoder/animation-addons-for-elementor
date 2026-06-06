import { valueAt } from '../../responsive-section/helpers';

export function isEnabled(s, bp) {
	return !!valueAt(s, 'aae_custom_css_enable', bp);
}
