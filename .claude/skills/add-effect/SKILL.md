---
name: add-effect
description: Add a new animation effect to the V4 atomic-widgets pipeline. Use this when the user asks to port a V3 extension (tilt, pin, parallax, tooltip, mouse-move, etc.) or to add any new data-attr-driven animation. Walks through the 8 files that need touching in the correct order, with verification steps between each.
---

# Add a new atomic animation effect

This skill encodes the full pipeline for adding an animation effect to the
V4 atomic-widgets system. It exists because the effect lives across 7-8
files in 4 layers (Schema, Controls, Render, JS effect, JS editor-bridge,
Webpack, Assets, optional Bootstrap) and missing one layer produces silent
failures (no controls in panel, no data-attrs in DOM, no JS bundle loaded,
no live editor preview).

**Always read `CLAUDE.md` at the project root first** — it has the file map,
the Kind interface, and the conventions this skill assumes.

## Before you start

Confirm these with the user (don't guess):

1. **Effect name** — snake_case (e.g. `tilt`, `mouse_move`, `wrapper_link`).
   Used for prop prefixes (`aae_tilt_*`) and JS bundle handle
   (`aae-effect-tilt`).
2. **Target widget types** — which atomic widgets does it apply to?
   (`e-heading`, `e-paragraph`, `e-button`, `e-image`, `e-svg`,
   `e-flexbox`, `e-div-block`, `e-grid`).
3. **Settings list** — name, type (Boolean / Number / String), default,
   and which settings depend on which.
4. **Trigger model** — does it animate on scroll / on page load / on
   hover / on click / always-on (like tilt)?
5. **Auto-replay in editor?** — should toggling settings replay the
   animation live? If yes, what's the enable-toggle setting name?
6. **V3 source file** to port from (if applicable). Usually under
   `animation-addons-for-elementor-pro/inc/extensions/wcf-*.php` and
   `animation-addons-for-elementor-pro/src/js/`.

If any answer is unclear, ask the user before writing code. Do not invent
defaults — defaults baked into a port that should match V3 will silently
diverge.

## The 8 steps (in order)

Do them in this order. Each step builds on the previous one, and verifying
mid-way catches integration errors early.

### Step 1 — Schema (PHP)

File: `inc/Atomic/TextAnimation/Schema.php`
(Or a sibling subdirectory if the effect is large enough to warrant
its own namespace — but the existing layout puts Animation + TextAnimation
in `TextAnimation/`, so prefer adding to that file unless the effect is
truly unrelated.)

**Add:**
1. Constants for every prop: `const TILT_ENABLE = 'aae_tilt_enable';`
2. In `add_animation_props()`, register each prop with `Boolean_Prop_Type`,
   `Number_Prop_Type`, or `String_Prop_Type` (`make()->default(...)`).
3. Conditional show/hide via `Dependency_Manager::make()->where([...])`.
   The enable toggle is at the top; everything else depends on it being
   true (`dep_eq`) or the effect being chosen (`dep_in` / `dep_ne`).
4. For responsive numerics/strings use the helpers
   `register_responsive_number()` / `register_responsive_string()`.

**Verify:** `php -l inc/Atomic/TextAnimation/Schema.php` (or just save and
reload Elementor editor — atomic widgets crash visibly if the schema is
malformed).

### Step 2 — Controls (PHP)

File: `inc/Atomic/TextAnimation/Controls.php`

**Add:**
1. A new private method `build_tilt_section()` returning `Section::make()`
   with the appropriate `Switch_Control` / `Number_Control` /
   `Select_Control` rows.
2. In `inject_controls()`, call it for the right widget types:
   ```php
   if ( in_array( $type, [ 'e-flexbox', 'e-div-block', 'e-grid' ], true ) ) {
       $controls[] = $this->build_tilt_section();
   }
   ```
3. If a Number_Control should accept decimals, set
   `->set_should_force_int( false )` AND add the label to
   `FLOAT_LABEL_BASES` in `editor-bridge/float-step-fix.js` (step 7).

**Verify:** reload the Elementor editor, select a target widget, the new
section should appear in the panel.

### Step 3 — Render (PHP)

File: `inc/Atomic/TextAnimation/Render.php`

**Add:**
1. A `build_tilt_attrs(array $settings): array` method that returns
   `[ 'data-aae-tilt-enable' => '...', ... ]` if enabled, `[]` if disabled.
2. In `inject_into_html()`, call it and merge into the `$attrs` array
   for matching widget types.
3. **Conditional enqueue** — after the `if ( empty( $attrs ) ) return;`
   guard, but specifically for effects whose attrs were just added:
   ```php
   if ( ! is_admin() && ! empty( $tilt_attrs ) ) {
       wp_enqueue_script( 'aae-effect-tilt' );
   }
   ```
   This is the on-demand load — the effect bundle ships only on pages
   that actually use it.

**Verify:** save a widget with the effect enabled, view the published
frontend page, inspect the widget root — `data-aae-tilt-*` attrs should
be on the first opening tag.

### Step 4 — Effect JS bundle

File: `src/modules/atomic/effects/tilt.js`

Implement three functions and self-register:

```js
import { getGsap, getScrollTrigger } from '../common';

function readTilt(el) {
  if (el.dataset.aaeTiltEnable !== 'true') return null;
  return { /* parsed config */ };
}

function playTilt(el, config) { /* GSAP fromTo or similar */ }
function bindTilt(el, config) { /* wire trigger → play */ }

function registerWhenReady() {
  if (!window.AAERegistry) {
    Promise.resolve().then(registerWhenReady);
    return;
  }
  window.AAERegistry.register({
    name:      'tilt',
    selector:  '[data-aae-tilt-enable="true"]',
    boundFlag: 'aae-tilt-bound',
    playedKey: '__aaeTiltPlayed',
    read: readTilt, play: playTilt, bind: bindTilt,
  });
}
registerWhenReady();
```

**Verify:** the registerWhenReady pattern is mandatory — `common.js` may
not have loaded yet at module top level (Promise microtask retries until
it does).

### Step 5 — Webpack entry

File: `webpack.config.js`

Append to the `entry: {}` block:

```js
"modules/atomic/effects/tilt": "./src/modules/atomic/effects/tilt.js",
```

**Verify:** run `npm run build`, check `assets/build/modules/atomic/effects/tilt.js`
and `tilt.asset.php` were generated.

### Step 6 — PHP bundle registration

File: `inc/Atomic/Assets.php`

Append to the `EFFECT_BUNDLES` constant:

```php
const EFFECT_BUNDLES = [
    'aae-effect-animation' => 'effects/animation.js',
    'aae-effect-tilt'      => 'effects/tilt.js',
];
```

The handle must exactly match what `Render.php` enqueues in step 3.

**Verify:** load a page with the effect on it; `view-source` should show
`<script src="…/effects/tilt.js">`.

### Step 7 — Editor-bridge feature

File: `src/modules/atomic/editor-bridge/features.js`

Push a new entry into `FEATURES`:

```js
{
  name: 'tilt',
  widgetTypes: ['e-flexbox', 'e-div-block', 'e-grid'],
  enableSetting: 'aae_tilt_enable',
  autoReplaySetting: null,  // or 'aae_tilt_enable_editor' if you have one
  attrMap: {
    aae_tilt_enable:      'data-aae-tilt-enable',
    aae_tilt_max:         'data-aae-tilt-max',
    aae_tilt_perspective: 'data-aae-tilt-perspective',
    aae_tilt_scale:       'data-aae-tilt-scale',
    aae_tilt_speed:       'data-aae-tilt-speed',
  },
  findTarget: (doc, id) => doc.querySelector(`[data-id="${id}"]`),
},
```

Notes:
- `enableSetting`: the prop whose truthy value means "effect is on".
- `attrMap`: ONLY the settings that need to be mirrored to data-attrs.
  Skip settings that affect render-time PHP behaviour but aren't read by
  the JS runtime.
- `findTarget`: usually `[data-id="${id}"]`, but text-anim uses
  `[data-interaction-id="${id}"]` — match what `Render.php` actually
  produces.

**Verify:** open Elementor editor, select a flexbox, toggle the tilt
enable switch. Inspect the preview iframe — `data-aae-tilt-enable="1"`
should appear on the node live (no save/reload).

### Step 8 — Responsive registrations (if any)

If the effect has per-breakpoint settings:

1. Add the responsive bases to `AAE_RESPONSIVE_BASES` in
   `editor-bridge/responsive-config.js`.
2. Map each base label → schema key in `LABEL_TO_BASE`.
3. If any controls use select dropdowns, add their option labels to
   `HINT_VALUE_LABELS` so the inherit-hint shows readable text.
4. Decimal-accepting numerics: add their label to `FLOAT_LABEL_BASES` in
   `editor-bridge/float-step-fix.js`.

**Verify:** switch to Tablet mode in the editor toolbar — the tablet
variant rows should appear, desktop rows should hide, and empty tablet
inputs should show the desktop value as an italic placeholder.

## Final verification checklist

After all 8 steps, run through this:

| Check | Where |
|---|---|
| Schema loads without error | Editor opens without React crash |
| Controls appear | Panel shows the new section under matching widgets |
| Settings persist | Save + reload — values stick |
| Data-attrs on frontend | View source — `data-aae-<feature>-*` present |
| JS bundle loads conditionally | Page WITHOUT effect: no bundle. Page WITH effect: bundle present. |
| Animation runs on frontend | Visit published page — animation plays |
| Live preview mirrors | Edit settings in panel — preview iframe updates without save |
| Play Now button (if enable_editor) | Toggle Enable On Editor → "Play Now" button appears |
| Device-mode switching | Toggle Desktop/Tablet/Mobile — rows show/hide correctly |
| Per-breakpoint inherit | Empty tablet field shows desktop value as placeholder |

## When something doesn't work

Refer to the "Common breakage points" table at the bottom of `CLAUDE.md`.
The dispatcher loop is in `src/modules/atomic/common.js` — if effects
register but don't bind, log `KINDS` from the browser console:

```js
window.AAERegistry.scan(document);  // force re-scan
```

If the Play Now button doesn't appear, the PHP `Switch_Control` with
`->set_meta( [ 'aaePlayButton' => true ] )` is missing or the label text
in `scanPanelForPlayButton()` doesn't match.

## Don't

- Don't add `assets => scripts` to controls — atomic widgets ignore them.
- Don't enqueue scripts unconditionally in `Assets::register()` — break
  the on-demand loading.
- Don't modify React-managed DOM (tab `aria-selected`, MUI internals)
  without `!important` overrides or you'll trigger infinite re-render loops.
- Don't add new top-level files when an existing module fits — extending
  `features.js` + adding `effects/<name>.js` should be enough.
- Don't skip the `registerWhenReady()` pattern in effect JS — module
  load order is not guaranteed.
