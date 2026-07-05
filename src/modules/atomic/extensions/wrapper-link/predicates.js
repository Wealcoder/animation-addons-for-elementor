export function isEnabled(settings) {
	const enable = settings?.aae_wrapper_link_enable;
	const enableVal = (enable && typeof enable === 'object' && '$$type' in enable) ? enable.value : enable;
	return !!enableVal;
}

/**
 * The Link URL field only applies in "Custom URL" mode — in "Current Post"
 * mode each loop card resolves its own permalink from data-aae-post-url.
 */
export function isCustomSource(settings) {
	if (!isEnabled(settings)) {
		return false;
	}
	const source = settings?.aae_wrapper_link_source;
	const sourceVal = (source && typeof source === 'object' && '$$type' in source) ? source.value : source;
	return !sourceVal || sourceVal === 'custom';
}
