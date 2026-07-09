/**
 * AAE Social Wrap — hover utilities.
 *
 * Build target: ../../../../../assets/atomic/js/social-wrap.js
 * Styles live in ../scss/social-wrap.scss (extracted by webpack).
 *
 * No DOM behavior needed yet — the current templates (minimal, outlined,
 * solid) only need the pure-CSS `.aae-social-pulse` ring from the SCSS
 * above. This file exists so webpack extracts that matching .css bundle
 * (same reasoning as Btn's/BtnPro's own bundles). It is intentionally
 * NOT registered as a script in class-atomic.php (no has_script/
 * script_handle) since it does nothing at runtime — only the stylesheet
 * loads on-demand. Add JS-driven effects here, following the
 * init()+MutationObserver pattern in Btn's/BtnPro's own bundles, if a
 * future Social Wrap template needs one — and register a script_handle
 * for it at that point.
 */
import '../scss/social-wrap.scss';
