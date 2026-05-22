export function isEnabled(settings) {
	const enable = settings?.aae_wrapper_link_enable;
	const enableVal = (enable && typeof enable === 'object' && '$$type' in enable) ? enable.value : enable;
	return !!enableVal;
}
