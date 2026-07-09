/**
 * AAE Global Preset — Social Wrap hover utilities.
 *
 * Build target: ../../../../../assets/atomic/js/global-preset-social.js
 * Styles live in ../scss/global-preset-social.scss (extracted by webpack).
 *
 * No DOM behavior needed yet — the current templates (minimal, outlined,
 * solid) only need the pure-CSS `.aae-social-pulse` ring from the SCSS
 * above. This file exists so webpack extracts that matching .css bundle
 * (same reasoning as global-preset-free.js's import line). Add JS-driven
 * effects here, following that file's init()+MutationObserver pattern,
 * if a future Social Wrap template needs one.
 */
import '../scss/global-preset-social.scss';
