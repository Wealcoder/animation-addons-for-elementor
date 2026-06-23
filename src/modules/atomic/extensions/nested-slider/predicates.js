/* eslint-env browser */

import { valueAt, valueEq, valueIn } from '../../responsive-section/helpers';

export function isCoverflow(s, bp) {
	return valueEq(s, 'aae_ns_effect', bp, 'coverflow');
}

export function isCard(s, bp) {
	return valueEq(s, 'aae_ns_effect', bp, 'card');
}

export function showPerspectiveOffset(s, bp) {
	return hasPerspective(s, bp);
}

export function hasPerspective(s, bp) {
	return valueIn(s, 'aae_ns_effect', bp, ['coverflow', 'perspective']);
}

export function isPerspective(s, bp) {
	return valueEq(s, 'aae_ns_effect', bp, 'perspective');
}

export function showPeek() {
	return true;
}

export function isAutoplayEnabled(s, bp) {
	const autoplay = valueAt(s, 'aae_ns_autoplay', bp);
	return autoplay === true || autoplay === 'yes' || autoplay === '1' || autoplay === 1;
}

export function showSlidesPerView() {
	return true;
}

export function showGap() {
	return true;
}

export function showCenterMode(s, bp) {
	const effect = valueAt(s, 'aae_ns_effect', bp) || 'slide';
	return effect === 'slide';
}

export function showCenterScale(s, bp) {
	if (!showCenterMode(s, bp)) return false;
	const cm = valueAt(s, 'aae_ns_center_mode', bp);
	return cm === true || cm === 'yes' || cm === '1' || cm === 1 || String(cm) === 'true';
}

export function showLoop(s, bp) {
	const effect = valueAt(s, 'aae_ns_effect', bp) || 'slide';
	return effect !== 'marquee';
}

export function showAutoplay(s, bp) {
	const effect = valueAt(s, 'aae_ns_effect', bp) || 'slide';
	return effect !== 'marquee';
}

export function showEasing(s, bp) {
	const effect = valueAt(s, 'aae_ns_effect', bp) || 'slide';
	return effect !== 'marquee';
}

export function showAutoplaySpeed(s, bp) {
	const effect = valueAt(s, 'aae_ns_effect', bp) || 'slide';
	if (effect === 'marquee') {
		return false;
	}
	return isAutoplayEnabled(s, bp);
}

export function showAutoplaySettings(s, bp) {
	const effect = valueAt(s, 'aae_ns_effect', bp) || 'slide';
	if (effect === 'marquee') {
		return true;
	}
	return isAutoplayEnabled(s, bp);
}

