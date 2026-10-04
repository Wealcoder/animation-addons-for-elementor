/* eslint-env browser */

/**
 * The fixed palette every preset surface paints on.
 *
 * These are deliberately NOT the editor theme's `background.paper` / `text.*`
 * tokens. Those follow the editor's Dark/Light preference, and the preset
 * dialogs are full-bleed galleries of light-background thumbnails — on a dark
 * editor the sheet came out near-black while the artwork stayed white. So the
 * sheet is fixed and everything drawn on it takes its ink from here.
 *
 * It lives in its own module because there are now two dialogs (the picker and
 * the requirements sheet it opens) and a second copy of these values is how the
 * two would end up disagreeing about what white is.
 *
 * The one thing that does NOT use this palette is the trigger button in the
 * panel: that sits in the editor's own chrome and follows its theme, so its
 * disabled tint is expressed as a translucent wash instead (see
 * PresetPickerControl.jsx).
 */

/** This plugin's established brand/upgrade colour — see inc/admin/row-actions.php. */
export const BRAND = "#ff7a00";
export const BRAND_DARK = "#e35f00";

export const SHEET = "#ffffff";
export const SHEET_BORDER = "#e6e8eb";
export const INK = "#17181a";
export const INK_MUTED = "#6b7280";
export const THUMB_BG = "#f1f2f4";

/**
 * The requirement states, and they are three colours because they are three
 * different jobs for the person reading them: nothing to do, one switch to
 * flip, something to fetch.
 */
export const OK_INK = "#1a7f4b";
export const WARN_INK = "#8a5a00";
export const WARN_BG = "#fdf4e3";
export const WARN_BORDER = "#f5d9a8";
