# Animation Addons for Elementor — Project Memory

This file is the project's working memory for AI assistants and new
contributors. It describes the architecture, the file map, and the exact
steps for the common task: **adding a new animation effect to atomic
widgets**.

If you only read one section, read [Adding a new effect](#adding-a-new-effect).

---

## What this plugin does

Animation Addons (AAE) extends Elementor with scroll/hover/click animations.
The codebase has two parallel implementations:

| Era | Where | Status |
|---|---|---|
| **V3 (legacy)** | `inc/extensions/`, `src/js/` (root) | Maintenance — not extended |
| **V4 (atomic)** | `inc/Atomic/`, `src/modules/atomic/` | Active development |

The V4 path targets Elementor's **Atomic Widgets** (Elementor 4.x):
`e-heading`, `e-paragraph`, `e-button`, `e-image`, `e-svg`, `e-flexbox`,
`e-div-block`, `e-grid`. Atomic widgets have a different controls API and
render pipeline than the V3 `Element_Base` widgets, so the two systems
do not share code.

This document covers the **V4 atomic side only**.

---

## High-level architecture (V4 atomic)

Four cooperating layers:

```
┌────────────────────────────────────────────────────────────────┐
│ 1. SCHEMA           inc/Atomic/.../Schema.php                  │
│    Registers top-level scalar props on atomic widgets via     │
│    elementor/atomic-widgets/props-schema filter.              │
├────────────────────────────────────────────────────────────────┤
│ 2. CONTROLS         inc/Atomic/.../Controls.php                │
│    Builds the panel sections (Section::make()->set_items()).  │
│    Hooks elementor/atomic-widgets/controls.                   │
├────────────────────────────────────────────────────────────────┤
│ 3. RENDER           inc/Atomic/.../Render.php                  │
│    Splices data-aae-* attrs into the rendered widget HTML     │
│    via elementor/widget/render_content. Decides which JS      │
│    bundle to enqueue based on actually-applied settings.      │
├────────────────────────────────────────────────────────────────┤
│ 4. RUNTIME          src/modules/atomic/common.js + effects/   │
│    Browser-side. common.js is the always-loaded core          │
│    (window.AAERegistry). Each effect file in effects/         │
│    self-registers a Kind that the dispatcher binds to         │
│    matching DOM elements.                                     │
└────────────────────────────────────────────────────────────────┘

editor-bridge (separate)    src/modules/atomic/editor-bridge.js
  Mirrors panel settings → preview iframe DOM live, injects
  the "Play Now" button, handles responsive panel UX.
```

### On-demand asset loading

Elementor's atomic widgets do **not** support setting-level conditional
script loading (V3's `assets => scripts` array is broken for them).
We replicate the V3 pattern ourselves:

1. `Assets::register()` registers all JS handles on `wp_enqueue_scripts`
   but does **not** enqueue them.
2. When `Render.php::inject_into_html()` actually adds animation attrs,
   it calls `wp_enqueue_script( 'aae-effect-foo' )`.
3. Effect bundles declare `aae-atomic-common` as a dependency, so the
   core runtime gets pulled in automatically.
4. Editor preview iframe (`elementor/preview/enqueue_scripts`) blanket-
   enqueues everything because the user can toggle effects live.

Net effect: a page with no AAE animations loads zero AAE JavaScript.

---

## File map

```
inc/Atomic/                              PHP (server-side)
├── Bootstrap.php                        Boot — registers all the *.php below
├── Assets.php                           Script registration + on-demand enqueue
└── TextAnimation/
    ├── Schema.php                       Props for both Text and Regular animation
    ├── Controls.php                     Panel sections for both
    └── Render.php                       Data-attr splicing for both

src/modules/atomic/                      JS (browser-side)
├── common.js                            Core runtime — exposes window.AAERegistry
├── presets.js                           Regular-animation GSAP fromTo presets
├── effects/
│   └── animation.js                     Text + Regular animation kind, self-registers
├── editor-bridge.js                     Editor entry — bootstraps the modules below
└── editor-bridge/                       Editor implementation (one module each)
    ├── disposables.js                   Listener teardown registry
    ├── helpers.js                       getPreviewWindow, getSelectedContainer, unwrap
    ├── features.js                      FEATURES table — single source of truth
    ├── responsive-config.js             Breakpoint + responsive metadata
    ├── panel-rows.js                    DOM row utilities (rowFromLabel, isSelectRow…)
    ├── settings-bridge.js               applySettingsToDom, replayInPreview
    ├── live-bridge.js                   Selection + change-event subscription
    ├── preview-pipe.js                  Forward elementor/element/render to runtime
    ├── play-button.js                   "Play Animation" row → "Play Now" button
    ├── responsive-visibility.js         Show/hide rows per device, strip label suffixes
    ├── responsive-placeholders.js       Inherit hints in inputs + selects
    ├── float-step-fix.js                MUI Number_Control step="any" override
    └── responsive-bridge.js             Orchestrates the three responsive modules
```

---

## The Kind interface

Every animation effect plugs into the runtime via this contract:

```js
window.AAERegistry.register({
  name:      'text',                       // unique id, logs only
  selector:  '[data-aae-text-anim]',       // querySelector for matching nodes
  boundFlag: 'aae-text-anim-bound',        // class added to prevent double-bind
  playedKey: '__aaeTextPlayed',            // property on el that caches the tween
  read:  (el) => config | null,            // null = effect off on this element
  play:  (el, config) => void,             // run the GSAP tween
  bind:  (el, config) => void,             // wire trigger → play
});
```

The dispatcher (`common.js`) walks `KINDS` on every scan/rebind/replay,
so kinds added later still work on already-rendered elements (registry
triggers a microtask rescan).

The matching editor-side contract is in `editor-bridge/features.js`:

```js
{
  name:              'text-animation',
  widgetTypes:       ['e-heading', 'e-paragraph'],
  enableSetting:     'aae_text_effect',
  autoReplaySetting: 'aae_text_enable_editor',  // optional Boolean
  attrMap: {
    aae_text_effect:  'data-aae-text-anim',
    aae_text_trigger: 'data-aae-text-trigger',
    // … one per setting that should mirror to a data-attr
  },
  findTarget: (doc, id) => doc.querySelector(`[data-interaction-id="${id}"]`),
}
```

---

## Adding a new effect

Concrete walkthrough: porting a **Tilt** effect.

### 1. Schema (PHP)

Edit `inc/Atomic/TextAnimation/Schema.php` (or create a sibling like
`inc/Atomic/Tilt/Schema.php` if the effect is large enough to warrant its
own namespace):

```php
const TILT_ENABLE     = 'aae_tilt_enable';
const TILT_MAX_TILT   = 'aae_tilt_max';
const TILT_PERSPECTIVE = 'aae_tilt_perspective';
const TILT_SCALE      = 'aae_tilt_scale';
const TILT_SPEED      = 'aae_tilt_speed';
```

In `add_animation_props()`:

```php
$schema[ self::TILT_ENABLE ] = Boolean_Prop_Type::make()->default( false );
$tilt_active = $this->dep_eq( self::TILT_ENABLE, true );

$schema[ self::TILT_MAX_TILT ] = Number_Prop_Type::make()
    ->default( 20 )
    ->set_dependencies( $tilt_active );
// … rest
```

### 2. Controls (PHP)

Edit `inc/Atomic/TextAnimation/Controls.php`. Either add a new section
method (`build_tilt_section()`) and call it from `inject_controls()`, or
extend an existing section if the effect belongs there:

```php
private function build_tilt_section(): Section {
    return Section::make()
        ->set_label( __( 'Tilt', self::TD ) )
        ->set_items( [
            Switch_Control::bind_to( Schema::TILT_ENABLE )
                ->set_label( __( 'Enable', self::TD ) ),
            Number_Control::bind_to( Schema::TILT_MAX_TILT )
                ->set_label( __( 'Max Tilt (deg)', self::TD ) ),
            // …
        ] );
}
```

Then in `inject_controls()`:

```php
if ( in_array( $type, [ 'e-flexbox', 'e-div-block', 'e-grid' ], true ) ) {
    $controls[] = $this->build_tilt_section();
}
```

### 3. Render (PHP)

Edit `inc/Atomic/TextAnimation/Render.php`. Add a `build_tilt_attrs()`
method and call it from `inject_into_html()`:

```php
$is_tilt = in_array( $type, [ 'e-flexbox', 'e-div-block', 'e-grid' ], true );
if ( $is_tilt ) {
    $attrs = array_merge( $attrs, $this->build_tilt_attrs( $settings ) );
}
```

In `inject_into_html()`, when `$attrs` is non-empty, also enqueue the
effect's bundle:

```php
if ( ! is_admin() && ! empty( $tilt_attrs ) ) {
    wp_enqueue_script( 'aae-effect-tilt' );
}
```

### 4. Effect bundle (JS)

Create `src/modules/atomic/effects/tilt.js`:

```js
import { getGsap } from '../common';

function readTilt(el) {
  if (el.dataset.aaeTiltEnable !== 'true') return null;
  return {
    max:         parseFloat(el.dataset.aaeTiltMax)         || 20,
    perspective: parseFloat(el.dataset.aaeTiltPerspective) || 1000,
    scale:       parseFloat(el.dataset.aaeTiltScale)       || 1,
    speed:       parseFloat(el.dataset.aaeTiltSpeed)       || 300,
  };
}

function playTilt(/* el, config */) {
  // No-op: tilt is hover-driven, not a tween. bindTilt() does the work.
}

function bindTilt(el, config) {
  // mousemove handler that applies transform to el. See V3 tilt.js for
  // port reference: animation-addons-for-elementor-pro/src/js/tilt.js
}

function registerWhenReady() {
  if (!window.AAERegistry) {
    Promise.resolve().then(registerWhenReady);
    return;
  }
  window.AAERegistry.register({
    name:      'tilt',
    selector:  '[data-aae-tilt-enable="true"]',
    boundFlag: 'aae-tilt-bound',
    playedKey: '__aaeTiltBound',
    read:      readTilt,
    play:      playTilt,
    bind:      bindTilt,
  });
}
registerWhenReady();
```

### 5. Webpack entry

Edit `webpack.config.js`:

```js
"modules/atomic/effects/tilt": "./src/modules/atomic/effects/tilt.js",
```

### 6. PHP bundle registration

Edit `inc/Atomic/Assets.php`, append to `EFFECT_BUNDLES`:

```php
const EFFECT_BUNDLES = [
    'aae-effect-animation' => 'effects/animation.js',
    'aae-effect-tilt'      => 'effects/tilt.js',
];
```

### 7. Editor-bridge feature entry

Edit `src/modules/atomic/editor-bridge/features.js`, push:

```js
{
  name: 'tilt',
  widgetTypes: ['e-flexbox', 'e-div-block', 'e-grid'],
  enableSetting: 'aae_tilt_enable',
  autoReplaySetting: null,
  attrMap: {
    aae_tilt_enable:     'data-aae-tilt-enable',
    aae_tilt_max:        'data-aae-tilt-max',
    aae_tilt_perspective:'data-aae-tilt-perspective',
    aae_tilt_scale:      'data-aae-tilt-scale',
    aae_tilt_speed:      'data-aae-tilt-speed',
  },
  findTarget: (doc, id) => doc.querySelector(`[data-id="${id}"]`),
},
```

### 8. Build & test

```bash
npm run build
```

Open Elementor editor, select a flexbox container, toggle the Tilt enable
switch. The `data-aae-tilt-*` attrs should appear on the preview node and
the effect should run.

---

## Conventions

### Data-attribute naming

Standardise `data-aae-<feature>-<setting>`. Per-breakpoint variants get
`-<breakpoint>` appended:

```
data-aae-text-delay              ← desktop
data-aae-text-delay-mobile       ← mobile variant
data-aae-text-delay-mobile-extra ← (snake_case kept literal)
```

### Naming

| Layer | Style |
|---|---|
| PHP class names | `PascalCase` |
| PHP constants  | `UPPER_SNAKE_CASE` |
| Atomic prop names | `aae_<feature>_<setting>` (snake_case) |
| JS exports     | `camelCase` |
| Data-attrs     | `data-aae-<feature>-<setting>` (kebab-case) |
| CSS classes for runtime | `aae-<feature>-bound`, `aae-<feature>-played` |

### Float vs integer Number controls

The Number_Control's underlying `<input type="number">` ships with
`step="1"`, which rounds decimals. For decimal-accepting fields:

1. Add the label string to `FLOAT_LABEL_BASES` in
   `editor-bridge/float-step-fix.js`.
2. The MutationObserver rewrites `step="any"` automatically.

### Responsive props

Use `Schema::register_responsive_string()` / `register_responsive_number()`
helpers. They register `<base>` + `<base>_<breakpoint>` for every active
breakpoint and gate variants behind the same dependencies as the base.

`editor-bridge/responsive-config.js` lists which bases are responsive
(`AAE_RESPONSIVE_BASES`) and maps each base label → schema key
(`LABEL_TO_BASE`). Add new responsive settings to both.

### Writing atomic settings from the editor (the `aae-rj` prop shape)

When you set an AAE atomic prop **programmatically** in the editor — e.g. via
`$e.run('document/elements/settings', { container, settings, options })` from an
editor-bridge module — the value must be wrapped in the prop-type's `$$type`
envelope, **not** passed raw. Passing a raw value throws
`Settings validation failed. <prop>: invalid_value` and, worse, leaves the
element's settings corrupted so the whole document fails to **publish**.

The slider's `Responsive_JSON_Prop_Type` (used for every `aae_ns_*` slider
setting — slidesPerView, peek, gap, …) serialises as `$$type: 'aae-rj'` with a
per-breakpoint `value`:

```js
// WRONG — rejected as invalid_value, breaks publish:
settings[ 'aae_ns_slides_per_view' ] = { desktop: 3 };

// RIGHT — the shape a live slider actually stores:
settings[ 'aae_ns_slides_per_view' ] = {
  $$type: 'aae-rj',
  value: { desktop: 3 },
};
```

**How to find the right `$$type` for any prop:** select a real element that has
the prop set and read it back — `container.settings.toJSON()[ propKey ]` prints
the exact envelope (`{ $$type: '…', value: … }`) Elementor expects. Mirror that
shape when writing. (Other prop types have their own `$$type`, e.g. `image-src`,
`dimensions`, `size`, `classes` — never assume `aae-rj`; read it back.)

Real usage: `editor-bridge/auto-preset.js` → `applySliderSettings()` seeds
slider settings on drop using this exact envelope.

### Cleanup

Anything that adds a listener / observer / timer must call `track(fn)` from
`editor-bridge/disposables.js`. `disposeAll()` runs on document switch and
on `beforeunload`.

---

## Build & development

```bash
npm install
npm run start    # webpack --watch
npm run build    # production build
```

Output goes to `assets/build/`. Each entry produces:
- `<name>.js`
- `<name>.asset.php` (deps + version, generated by @wordpress/scripts)
- `<name>.js.map` (in dev)

### `assets/atomic/js/` belongs to WEBPACK — never raw-copy sources into it

Atomic widget frontend JS (`inc/AtomicWidgets/Widgets/*/assets/js/*.js`) is
built ONLY by webpack (`npm run build` / `npm run start`): the
`getAtomicWidgetEntries()` loop in `webpack.config.js` emits each file to
`assets/atomic/js/<basename>.js`, resolving `@elementor/frontend-handlers`
imports via `externals` → `window.elementorV2.frontendHandlers`, inlining
SCSS imports and the Form widget's `lib/` modules, and skipping `-prev`/
`_prev` widgets. `npm run build` then has gulp `minify:atomic-js` minify
that webpack output to `.min.js` (the file served when
`is_dev_environment()` is false).

**Incident 2026-07-20:** a legacy gulp task (`compile:atomic-js`, since
deleted from `gulpfile.js`) flatten-copied the RAW sources over webpack's
bundles in the same directory. Every widget script on the site broke with
two console-error flavors, both from unbundled source served as classic
scripts: `Cannot use import statement outside a module` (files with ES
imports) and `Identifier 'register' has already been declared` (files
using top-level `const { register } = window.elementorV2?… ` — classic
scripts share one global lexical scope, so only the FIRST such script
survives; every later one throws and its widget handler is dead). The raw
copy also collided `Posts/posts.js` with `Posts_prev/posts.js` (gulp
didn't exclude `-prev`) and dumped Form's `lib/` internals as stray
standalone files. **Fix/recovery: `npm run build` regenerates everything;
never reintroduce a gulp task with `gulp.dest('assets/atomic/js')` for
JS.** Diagnose this class of failure fast: `head` the served file in
`assets/atomic/js/` — a healthy bundle starts with webpack boilerplate
(`!function(){"use strict"…`), never a bare `import` or
`const { register }`; the `?ver=` timestamp in the console is the file's
`filemtime`, which tells you exactly when the clobbering write happened.

### Testing on a real page

1. `npm run build`
2. Open Elementor editor on a test page
3. Add a heading, set Text Animation = Character
4. Save, view the published page
5. Inspect — should see `data-aae-text-anim="char"` on the heading
6. View source — `aae-effect-animation` script should be enqueued only on
   pages with at least one AAE effect

### Common breakage points

| Symptom | Likely cause |
|---|---|
| Effect controls not appearing | `Controls::inject_controls()` not adding the section for that widget type |
| Settings don't save | Prop not in Schema, or wrong $$type wrapper |
| `Settings validation failed … invalid_value` on publish (after a programmatic settings write) | Wrote a prop value RAW instead of in its `$$type` envelope. Responsive_JSON props need `{ $$type: 'aae-rj', value: { desktop: N } }`, not `{ desktop: N }`. See [Writing atomic settings from the editor](#writing-atomic-settings-from-the-editor-the-aae-rj-prop-shape). |
| A whole `define_base_styles()` silently does nothing | One invalid key/value fails the entire definition. `width`/`height` are `Size_Prop_Type` — a `String_Prop_Type('fit-content')` is invalid. For CSS keywords/functions use the `custom` unit: `Size_Prop_Type::generate([ 'size' => 'fit-content', 'unit' => 'custom' ])` (transformer emits the `size` verbatim). `auto` → `[ 'size' => 'auto', 'unit' => 'auto' ]`. Another killer: `background` must be `Background_Prop_Type::generate([ 'color' => Color_Prop_Type::generate(…) ])` — a bare `Color_Prop_Type` voids the definition (shipped as a colorless Form submit button). **Full verified key/format tables + shape cookbook: `animation-addons-for-elementor-pro/docs/atomic-v4/atomic-style-schema-reference.md` — check it BEFORE writing any base style.** |
| Base-style change shows in the editor but the frontend is unstyled / wrong | Atomic base styles are generated per-request, but the frontend renders them from a **cached** state; deleting `wp-content/uploads/elementor/` alone leaves it empty until a rebuild. After editing any `define_base_styles()`, **clear Elementor's cache** (wp-admin → Elementor → Tools → "Clear Files & Data", i.e. `#elementor-clear-cache-button`) then reload the frontend. The editor always renders live from PHP, so an editor-only check hides this. (Seen as a nav that's a perfect 44px circle in the editor but a full-width `position:static` block on the frontend.) |
| Slider nav arrow one size in editor, another on frontend (and/or a huge ellipse at slidesPerView=1) | The nav badge USED to be icon-driven (`width/height: fit-content`), so it sized to whatever the SVG happened to be — 20px on a fresh drop (the `aae-a-svg` utility) but 65px on an element saved before that class existed → different footprint per element age, and `auto`/content-driven width could stretch into an ellipse in a single full-width slide. Fix: pin the nav to a **fixed** `width/height: 44px` in `class-aae-a-slider-nav-{prev,next}.php` (constant everywhere, can't stretch) and cap the SVG to fit inside via each slider stylesheet — `.aae-a-navigator-{prev,next} .e-svg-base, svg { max-width/height: 24px }` in BOTH `nestedslider.scss` and `loop-grid-slider.scss` (the two sliders load different CSS; the Loop Grid Slider deliberately does not load the Nested Slider's stylesheet, so the rule is duplicated). |
| A "reveal" class you toggle at runtime (`aae-form-step-active`, etc.) doesn't override a base style's `display:none`, even though the class IS present in the DOM (`classList` has it, but `getComputedStyle().display` still says `none`) | **CSS specificity tie, not a JS/runtime bug.** Atomic base styles compile as `.elementor .e-<widget>-base { display: none; … }` — specificity (0,2,0). A plain `.your-class.active-class { display: flex }` override is ALSO (0,2,0) — a tie, and the base-styles sheet happens to cascade after your own, so the tie silently goes to `display:none`. Fix: add `.elementor` to your override selector so it matches the base rule's specificity (`.elementor .your-class.active-class { display: flex }`), not `!important`. Diagnose by walking `document.styleSheets` for every rule matching the element and setting `display` (see the `e-aae-a-form-step` Multi-Step Forms fix, 2026-07-19, for the exact Playwright snippet) — don't assume the DOM/model layer is broken just because content "isn't showing"; check computed style + winning rule first. |
| Data-attrs not on rendered HTML | `Render::inject_into_html()` not handling the widget type, or `$attrs` empty |
| Animation works in preview but not on frontend | `Render.php` not calling `wp_enqueue_script()` |
| Animation works on frontend but not in editor preview | `editor-bridge/features.js` missing widget type in `widgetTypes` |
| Per-breakpoint values don't preview | `applySettingsToDom()` writing only desktop attrs — check `AAE_RESPONSIVE_BASES` includes the base |
| Number input rounds decimals | Add label to `FLOAT_LABEL_BASES` in `float-step-fix.js` |
| Two tabs visually selected | Don't fight React on `aria-selected` / `Mui-selected` — use `!important` on the indicator, not class strips |
| Console floods with `Cannot use import statement outside a module` + `Identifier 'register' has already been declared` across many widget scripts at once | RAW widget sources got copied over webpack's bundles in `assets/atomic/js/` — see [`assets/atomic/js/` belongs to WEBPACK](#assetsatomicjs-belongs-to-webpack--never-raw-copy-sources-into-it). Recover with `npm run build`. |

---

## V3 reference for porting

When porting a V3 extension to atomic, the source files are at:

```
animation-addons-for-elementor-pro/
├── inc/extensions/wcf-*.php           (PHP control declarations)
└── src/js/                            (frontend runtime)
```

The V3 register pattern uses `elementor/element/<type>/section_*/after_section_end`
with `add_responsive_control`; this maps directly to our `Schema::register_responsive_*`
helpers. Frontend handlers use `elementorFrontend.elementsHandler.addHandler()`
which we replace with our `KINDS` scan/bind dispatcher.

---

## AAE Atomic Form Builder (planned — new widget family)

Spec sources, in order of authority (newer/more implementation-specific wins
on conflict):
1. `C:\Users\UseR\Documents\atomic-form\AAE_Atomic_Form_Builder_Claude_PRD_SSR_Pack\` —
   **primary reference.** `AAE_Atomic_Form_Builder_Complete_PRD_for_Claude.md`
   (product, v2.0) + `AAE_Atomic_Form_Builder_SSR_SRS_for_Claude.md`
   (technical/SRS, v1.0) — both written specifically for coding agents, dated
   July 12 2026, and far more concrete (literal REST routes, DB columns,
   prop names, response-code table) than the doc below.
2. `C:\Users\UseR\Documents\atomic-form\` root — PRD v1.6 + Test Cases v1.1.
   Broader product rationale (pain points, accessibility/i18n detail, exact
   UX copy) not repeated in the SSR pack; still valid where it doesn't
   conflict with #1.

This section is a working distillation for coding purposes — re-read the
source docs for anything not captured here or for product (not architecture)
questions.

This is a **new atomic widget family**, not an animation effect. Do not use
the [Adding a new effect](#adding-a-new-effect) recipe (Schema/Controls/Render
layered onto an *existing* widget) — instead follow the **Widgets/** pattern
used by `Offcanvas`, `NestedSlider`, `FlipBoxMain` (container widget + real child
element widgets registered independently in `class-atomic.php`'s
`widgets_registry`, PHP twig render via `Has_Element_Template`, own JS bundle).
See [File map](#file-map) and `inc/AtomicWidgets/Widgets/` for the shape to
copy.

### Why not use Elementor's native `e-form` instead

Elementor is actively building its own atomic form (`e-form` container +
`e-form-input`/`-label`/`-textarea`/`-checkbox`/`-radio-button`/`-select`/
`-date-picker`/`-time-picker`/`-file-upload`/`-submit-button`, each an
independently-styleable atomic widget — confirmed live in installed
Elementor Pro 4.1.2 behind the hidden, active-by-default `e_pro_atomic_form`
experiment; free core only shows a "Pro feature" paywall placeholder for it).
Investigated 2026-07-12 — see git history in `elementor-src-repo` under
`modules/atomic-widgets/elements/atomic-form/` for how actively it's still
changing (commits within the prior ~30 days touching required-children
behavior, default children, and the free/Pro boundary itself).

**Decided: build AAE's own form independently, not on top of `e-form`.**
Reason: the target client will not run Elementor Pro, so they would only
ever see Pro's paywall placeholder for `e-form` — there is no native form
for AAE to extend or coexist with on this project. This also isn't a loss
relative to what Pro's version offers: confirmed by direct code inspection,
Elementor's native form has no honeypot/CAPTCHA/bot-shield of its own (only
optional Akismet, itself gated behind a further paid tier + a separate
plugin dependency), runs all actions synchronously in one request (no async
queue), has no true save-before-actions guarantee (submission storage is
just another action in the list, skippable), and has no multi-step or
conditional-field logic at all — so every differentiator already planned
below (Bot Shield, async action queue, save-before-actions, schema
versioning, presets, multi-step, conditional logic) remains genuinely
additive, not duplicative. If a future project needs the free/Pro-agnostic
version reconsidered, re-run this investigation rather than trusting this
note blindly — the native feature is pre-GA and changing fast.

### Packaging decision

The SSR doc's stated *preference* is a separate `aae-form-builder` plugin
(namespace `AAEFB\`), but it explicitly allows building inside Animation
Addons with this repo's own conventions instead. **Decided: build inside
`animation-addons-for-elementor`.** Concretely, that means translating the
SSR doc's separate-plugin scaffolding onto this repo's real conventions:

| SSR doc says | Use instead, in this repo |
|---|---|
| Separate plugin `aae-form-builder` | `animation-addons-for-elementor` (this plugin) |
| Namespace `AAEFB\` | `WCF_ADDONS\Forms\` (sibling to `WCF_ADDONS\AtomicWidgets\`, `WCF_ADDONS\Atomic\`) |
| `aae-form-builder.php`, `includes/`, `assets/`, `templates/elementor/elements/` | `inc/AtomicWidgets/Widgets/Form*/` per element (matching `Offcanvas/`, `NestedSlider/`), plus `inc/Forms/` for the non-widget backend (Schema Sync, Submission Core, Queue, Admin) — mirror `inc/Atomic/` vs `inc/AtomicWidgets/` split already used for effects vs. widgets |
| DB tables `aaefb_forms`, `aaefb_form_schemas`, `aaefb_submissions`, `aaefb_submission_values`, `aaefb_action_jobs`, `aaefb_action_logs` | Same columns, prefixed `aae_` to match this plugin's existing option-name convention (`aae_atomic_widgets`, `aae_atomic_extensions`) → `aae_forms`, `aae_form_schemas`, `aae_submissions`, `aae_submission_values`, `aae_action_jobs`, `aae_action_logs` |
| REST namespace `aaefb/v1` | `aae/v1` (or whatever namespace the rest of this plugin already registers under — check before Milestone 5 REST work; grep for `register_rest_route` first) |
| `data-aaefb-ready="true"` ready marker | `data-aae-form-ready="true"`, consistent with this repo's `data-aae-<feature>-<setting>` convention (see [Conventions](#conventions)) |

Everything else below (props, table columns, endpoints, response codes,
milestones) is otherwise a direct port of the SSR doc's content with names
adjusted per this table.

### Core product principles (non-negotiable, PRD §5 + v1.4 security addendum)

1. **Security first, then UI/UX polish.** Public submission must be safe by
   default; the submit pipeline must not depend on Elementor editor classes —
   it must keep working even if the editor layer breaks.
2. **Never lose a lead** — default submission mode is Store + Admin Email.
3. **Save-before-actions, and its inverse, fail-before-save**: a valid
   submission is saved before any email/webhook/Sheets/notification/redirect
   action runs; a request blocked by Bot Shield/validation creates **zero**
   clean submission and **zero** action jobs — it only ever produces a
   spam/security log entry.
4. Backend validation always repeats frontend validation before saving —
   never trust the frontend alone.
5. Actions must be observable: failures need logs and retry where supported.
6. Accessibility, i18n/RTL, privacy, and performance are MVP requirements,
   not later polish.
7. No raw personal data shared with third parties unless the admin enables
   the feature and sees a disclosure.
8. No global frontend bloat — load assets conditionally (reuses
   [On-demand asset loading](#on-demand-asset-loading)).
9. Elementor editor previews must not create real submissions unless the
   admin explicitly uses **Test Submit** / "Test All Actions" mode.
10. Future integrations plug into adapters, not hardcoded one-off code.

### Element family

- `e-aae-a-form` — container element (`Atomic_Element_Base` + `Has_Element_Template`,
  `meta('is_container', true)`, like `AAE_A_Btn_Pro`/`Offcanvas`)
- `e-aae-a-form-field` — child element. Owns its own error-message slot
  render-side (see "Validation message ownership" below) — never a separate
  draggable widget.
- `e-aae-a-form-submit` — child element, normally the last element on the
  final step. **Locked**: deleting it must be blocked or require a restore
  warning — a form must never become silently unsubmittable.
- `e-aae-a-form-step` — **Pro/later** (Multi-Step). Step container holding
  fields, headings, paragraphs, div blocks, and optional review/summary
  content. Don't build this until the Multi-Step milestone.

The form is a container with real child elements, visible in the
Navigator/structure panel — do not build a monolithic repeater-only widget
(i.e. do not model fields as a repeater control on a single widget the way
V3/Elementor Pro forms do). Allowed non-field children (heading/paragraph/
div wrappers) must still be walked in DOM order by the schema builder
regardless of wrapper nesting. **Nested `e-aae-a-form` inside another
`e-aae-a-form` must be rejected or warned at save time.**

### Validation message ownership

Field/form/step error and success messages are **built-in slots owned by the
element itself**, not separate widgets a user can drag away. This is a
deliberate accessibility guard — the panel/Render layer for
`e-aae-a-form-field` must always render its own error container
non-optionally.

### Free vs. Pro split (PRD §24, verbatim boundary)

Follow the split already established by `Btn` (free) / `Btn_Pro` (free
plugin, `inc/AtomicWidgets/Widgets/BtnPro/class-aae-a-btn-pro.php`, type
`e-aae-a-btn-pro`) and by the pro plugin's own extension modules
(`animation-addons-for-elementor-pro/inc/AtomicV4/<Feature>/{Schema,Controls,Renderer,Assets}.php`,
e.g. `FlexboxChildHover`). Gate with `class_exists` guards, not an inline
capability flag inside the free widget. Prefer extending the same
`e-aae-a-form`/`e-aae-a-form-field` family from the pro plugin (matching how
`FlexboxChildHover` extends `e-flexbox`) over forking a second
`e-aae-a-form-pro` container.

**Free:** Basic Form widget; Visual Preset Popup; Contact/Newsletter/Lead/
Support presets; base fields — **Text, Email, Textarea, Phone (basic format
only), URL, Number, Select, Radio, Checkbox, Acceptance, Hidden, Submit**;
AJAX/REST submit; Store Submission; Admin Email (To/CC/BCC/Reply-To); Auto
Reply; basic Submission Dashboard + CSV export; basic Webhook (URL, POST
JSON, all fields, test button); Honeypot + basic reCAPTCHA option;
accessibility/i18n/RTL/privacy/performance foundations.

**Pro:** advanced field types — **Date, Time, Range, Rating** (shipped, see
[Advanced Field Types — Batch 1](#advanced-field-types--batch-1-datetimerangerating-shipped-2026-07-20)),
**File Upload (advanced), Country, Address, Password** (shipped, see
[Advanced Field Types — Batch 2](#advanced-field-types--batch-2-started-country-shipped-2026-07-20)
— Address is a preset + an `autocomplete` prop on Input, not a widget;
Password ships with the PRO Create User action), **Signature, Repeater,
HTML, Calculation, Step** (not yet built); Premium presets; Private Storage +
Google Drive/AWS S3 adapters; Google Sheets (OAuth); Advanced Webhook
(custom headers/auth/mapping/conditions/retry, n8n/Zapier/Make presets);
Action Logs + Retry Queue UI; **Validation Pro** (regex, real phone
validation API, country restriction, disposable email, domain rules);
Telegram/WhatsApp/Slack; **Conditional Display Engine**; **Multi-Step
Forms**; Calculator/Quote forms; Analytics/Lead Management; AI Copilot;
HTML Email Template Builder.

Open/unresolved per the spec (flag to product owner, don't guess): whether
Bot Shield presets are Free or Pro (recommendation only: basic protections
Free, advanced presets/logs Pro).

### Atomic element requirements (SSR §4 — authoritative prop names)

**`e-aae-a-form`** — `Atomic_Element_Base` + `Has_Element_Template`, HTML tag
`form`. Props: `form_key` (hidden), `actions_json` (hidden), `behavior`
(`store_email` | `store` | `email`), `msg_success`, `msg_error`,
`msg_invalid`, `spam_honeypot`, `spam_min_seconds`, `captcha_provider`,
`classes`, `attributes`. Allowed children: `e-aae-a-form-field`,
`e-aae-a-form-submit`, `e-heading`, `e-paragraph`, `e-div-block`. Default
children on drop: Name field, Email field, Message field, Submit button.

**`e-aae-a-form-field`** — one element class, behavior branches on a
`field_type` prop (do not make a separate element per field type). Props:
`field_type`, `field_key`, `label`, `placeholder`, `help`, `required`,
`width`, `default_value`, `autocomplete`, `inputmode`, `options`, `rows`,
`min`, `max`, `step`, `consent_text`, `validation_rules` (later). MVP
`field_type` values: `text`, `email`, `textarea`, `tel`, `url`, `number`,
`select`, `radio`, `checkbox`, `acceptance`, `hidden`.

**`e-aae-a-form-submit`** — props: `label`, `loading_label`, `success_label`
(later), `error_label` (later), `icon`, `alignment`. **Locked by default** —
must not become silently unsubmittable via accidental deletion.

### Editor controls (SSR §5)

- **`aae-form-fields`** control — projects real child field elements into a
  list; add/reorder/duplicate/delete; edits label/type/key/required/
  placeholder/width inline; warns on duplicate `field_key`; warns if
  `field_key` is locked because submissions already reference it.
- **`aae-form-actions`** control — opens a modal that reads/writes the
  hidden `actions_json` prop; configures Store/Email/Auto-Reply/Webhook/
  Sheets(later); validates against an action-registry schema; test
  email/webhook buttons call admin REST routes (see below).
- **Preset picker** — reuse the existing
  [preset picker](#to-add-presets-to-a-native-atomic-widget-e-heading-e-button-)
  pattern: search/filter, apply subtree, **regenerate `form_key`** on apply,
  keep current page context.

### Form identity (SSR §6 — simpler than the older PRD's 5-field identity
table; this is the version to implement)

Every form instance has one **`form_key`** (hidden prop on `e-aae-a-form`,
not user-facing by default), tied to `post_id` + `element_id`.

- On create: if `form_key` is empty, generate one.
- On duplicate: regenerate if the key already exists elsewhere.
- On paste/import: regenerate if a collision is detected.
- On Elementor document save: **server-side reconciliation** re-verifies
  uniqueness as a safety net for programmatic imports/edge cases that bypass
  the editor-bridge regeneration path.
- Do not let duplicated forms share submissions unless a future explicit
  "linked/global form" feature supports it.

(The older root-level PRD additionally describes `form_template_id` /
`form_instance_id` / `runtime_instance_id` as separate concepts — treat that
as design *rationale*, not the schema to implement; the SSR doc's single
`form_key` is the authoritative, simpler version actually meant for coding.)

Field-level: changing a `field_key` after submissions exist against it must
**warn** — it creates a new field identity rather than silently rewriting
history.

### Schema sync (SSR §7 — exact steps, runs on Elementor document save)

```
1. Locate e-aae-a-form elements.
2. Walk the form subtree recursively.
3. Read form-level props.
4. Read field child elements.
5. Read submit element.
6. Validate actions_json.
7. Build canonical schema JSON.
8. Hash schema.
9. If hash changed, create new schema version.
10. Mark latest schema active.
```

Schema JSON fields: form key, form version, fields, actions, messages, spam
settings, submit settings, `source = "elements"`, `schema_format`. Every
submission stores its schema version/snapshot so old submissions keep
rendering correctly after the form is edited (including deleted/renamed
fields, which retain their historical label/value).

### Database (SSR §8 — table names adjusted per the packaging table above:
`aaefb_*` → `aae_*`)

**`aae_forms`**: id, form_key, post_id, element_id, status, created_at, updated_at.

**`aae_form_schemas`**: id, form_id, version, schema_hash, schema_json, source, active, created_at.

**`aae_submissions`**: id, form_id, form_key, schema_version, status, source_url, referrer_url, utm_json, ip_hash, user_agent, created_at.

**`aae_submission_values`**: id, submission_id, field_key, field_label, field_type, field_value, created_at.

**`aae_action_jobs`**: id, submission_id, action_type, status, attempts, next_run_at, payload_json, created_at, updated_at.

**`aae_action_logs`**: id, job_id, submission_id, action_type, status, message, request_snapshot, response_snapshot, created_at.

**`aae_attachments`** — later, Pro (file upload).

Dashboard requirements: pagination, search, filter by form/status/date, CSV
export, bulk delete, single-submission drawer (values, source, metadata,
action logs), find-by-email for DSAR/support. No unbounded row loads —
paginate + index, must not timeout or spike memory at scale.

### REST API (SSR §9–10 — namespace adjusted per packaging table:
`aaefb/v1` → `aae/v1`; confirm no collision with an existing namespace
before Milestone 5 by grepping for `register_rest_route` in this plugin)

**Public:**
```
POST /aae/v1/forms/{form_key}/token   — no-store headers, returns a fresh single-use submit token, itself rate-limited
POST /aae/v1/forms/{form_key}/submit  — validates token, runs Bot Shield, runs server validation, saves submission, creates action jobs, returns success
```

**Admin** (cookie auth + nonce + capability checks required):
```
GET  /aae/v1/submissions
GET  /aae/v1/submissions/{id}
POST /aae/v1/actions/test-email
POST /aae/v1/actions/test-webhook
POST /aae/v1/action-jobs/{id}/retry
GET  /aae/v1/form-health/{form_id}
```

### Response codes (SSR §10 — the complete, authoritative table; supersedes
the partial 403/409/429-only list from the older PRD)

| Code | Meaning |
|---|---|
| 200 | success |
| 400 | malformed request |
| 401 | unauthorized admin request |
| 403 | invalid token / security failure |
| 409 | duplicate/replay submission |
| 422 | validation error |
| 429 | rate limit |
| 500 | server error |

Frontend must handle every one of these distinctly.

### Submit runtime requirements (SSR §11)

Initialize forms once (prevent duplicate init with a ready marker —
`data-aae-form-ready="true"`, see packaging table); must work inside hidden
containers; fetch a fresh token on interaction or submit; prevent page
reload; disable submit button while in flight; show loading state; run
frontend validation; submit to REST; show field + global errors; focus
first invalid field; **reveal parent accordion/tab/popup** if the invalid
field is hidden; keep user input after failure; prevent double submit;
respect `prefers-reduced-motion`.

### Submission architecture — exact 10-step pipeline (PRD §3, v1.4)

```
1. Render form with form_key, active schema_version, timestamp, one-time submit token.
2. Visitor submits via REST endpoint ONLY — public admin-ajax is not allowed.
3. Server verifies token, nonce/session context, form identity, schema version, timestamp.
4. Bot Shield checks: honeypot, minimum time, rate limit, duplicate submit, optional CAPTCHA/Turnstile.
5. Server validates all fields against the active schema snapshot.
6. Security/validation failure → log as spam/security, return a safe generic message, create NO clean submission.
7. Validation passes → save submission FIRST.
8. Create async action jobs (email, webhook, Sheets, notification, cloud storage).
9. Return success to visitor AFTER save, NOT after third-party actions complete.
10. Queue processes actions; stores action logs/retry status for admin review.
```

### Bot Shield (PRD §2/§4/§5, v1.4)

| Layer | Purpose | Tier |
|---|---|---|
| Submit token | single-use, prevents duplicate/replayed submit | MVP |
| Nonce / REST permission | verifies request origin + route validity | MVP |
| Honeypot | silently catches automated fillers | MVP |
| Minimum submit time | blocks unrealistically fast submits | MVP |
| Rate limit | by hashed IP + `form_key` | MVP |
| CAPTCHA/Turnstile escalation | challenges suspicious/high-frequency requests | MVP/Pro |
| Email/domain/keyword blocklist | blocks low-quality patterns | Pro |
| Country restriction | allow/block by policy | Pro |
| Real phone validation adapter | validates phone quality/country match | Pro |
| Spam reason log | shows admin why blocked, never stored as a clean lead | — |

Admin defaults: Protection Mode = **Recommended**; Honeypot On; Minimum
Submit Time = **3 seconds**; Rate Limit per Form On; Rate Limit by Hashed IP
On; Duplicate Submit Token On; CAPTCHA Escalation on when connected (shown
only on suspicion or High mode); Spam Log On; generic Block Message; Admin
Alert on Attack Spike = Pro.

**Protection modes:**
- **Low** — nonce/token, honeypot, basic validation. Small low-risk forms.
- **Recommended** (default) — + min time, rate limit, spam log, optional
  CAPTCHA escalation. Default for most contact/lead forms.
- **High** — + stricter rate limits, CAPTCHA/Turnstile challenge,
  domain/keyword blocking, country restrictions. High-traffic/lead-gen forms.

Rate limiting is scoped by **`form_key` + hashed visitor signal**, never a
permanent raw IP, by default. Disabling raw-IP storage must **not** disable
hashed rate limiting — they're independent switches.

### Duplicate-submit prevention (three overlapping layers)

1. Single-use submit token — reused token → 409-style response + spam/security log.
2. Frontend button-state guard — submit button disables immediately on
   click/while sending, before the token round-trip even starts.
3. **Cache-proofing** — fetch a fresh, `no-store` submit token when the user
   interacts with/begins the form, specifically to defeat CDN/cache-plugin
   stale-token 403s (this is the direct fix for the "cache plugins break
   forms" pain point).

### Async action queue (PRD §12 — exact contract)

States: **Pending, Processing, Success, Failed, Retrying, Cancelled.**

Retry schedule: attempt 1 immediate → attempt 2 after **5 minutes** →
attempt 3 after **30 minutes** → attempt 4 after **2 hours** → final failure
marks **Failed** with a manual **Retry** button in admin.

Job record: provider, action type, submission id, payload summary, status,
attempts, last error, next retry time, created/updated time. Store summaries
and redacted payloads, not raw PII, where possible. Redirect must not wait
for queued actions (explicit future carve-out: payment flows requiring
synchronous confirmation).

**Open/unresolved:** Action Scheduler vs. WP Cron vs. custom queue table vs.
hybrid — not decided by the spec; flag before building this milestone.

### Actions after submit (PRD §10, tiers)

| Action | Tier | Priority |
|---|---|---|
| Store in Database | Free | P0 |
| Admin Email (To/CC/BCC/Reply-To/subject/message/smart tags) | Free | P0 |
| Auto Reply | Free | P1 |
| Redirect | Free | P1 |
| Basic Webhook | Free | P1 |
| Advanced Webhook | Pro | P2 |
| Google Sheets | Pro | P2 |
| Telegram/WhatsApp/Slack | Pro | P2 |
| Google Drive/AWS S3 | Pro | P2 |

Auto-reply only fires when a valid email field exists (with a "Include
submitted copy" option). From-domain mismatch (From email ≠ site domain)
must trigger a UI deliverability warning + SMTP-setup suggestion. Google
Sheets requires OAuth + mapping UI + test row + retry on failure;
expired/revoked OAuth token must fail the job safely, prompt reconnect in
the dashboard, and leave the submission saved.

**Smart tags (SSR §22 — exact MVP syntax, resolved this time, not left to
design):**
```
{{field.name}}  {{field.email}}  {{field.phone}}  {{field.message}}
{{site.title}}  {{site.url}}  {{site.admin_email}}
{{page.title}}  {{page.url}}
{{submission.id}}  {{submission.date}}  {{submission.time}}
```
The parser must sanitize/escape based on output context (HTML email body vs.
plain text vs. webhook JSON).

**Action registry (SSR §19)** — implement actions behind a common interface,
not one-off handlers: `id`, `label`, `settings schema`, `validation`,
`run()` method, `log formatter`, `retry support`. Action types: `store`,
`admin_email`, `auto_reply`, `webhook`, `google_sheets` (later), `telegram`
(later), `whatsapp` (later), `redirect`. All actions run through the async
queue **except** immediate UX actions like `redirect`.

### UX requirements

- AJAX/REST submit, no page reload; loading state on submit.
- Inline validation: validate on blur, not while typing unless already
  interacted; validate everything on submit + optional error summary; map
  backend errors back to field-level; hidden conditional fields don't block
  submit by default.
- Focus first invalid field; **reveal parent accordion/tab/popup before
  focusing a field hidden inside one** ("Reveal-Before-Focus" — a named
  differentiator, not just a nice-to-have).
- Never clear user input after a failed submit.
- Slow-network/offline messaging, exact copy from the spec:
  - Slow: "Your connection seems slow. Please wait a moment."
  - Offline: "You appear to be offline. Please reconnect and try again."
  - Timeout: "We could not submit the form. Your information is still here. Please try again."
  - Duplicate: "This form was already submitted."
  - Rate limit: "Too many attempts. Please wait a moment and try again."
- Retry after a network failure uses a **fresh** submit token.
- Native autocomplete/inputmode: `name`, `email`, `tel`, `organization`,
  `street-address`, `address-level1/2`, `postal-code`, `country-name`, `url`
  where appropriate; mobile keyboard matches field type (email/tel/numeric/
  url); editor should warn on conflicting field-type/autocomplete pairs.
- Accessibility (WCAG 2.1 AA-inspired, MVP not later polish): every input
  has an accessible label; required state announced; errors linked via
  accessible descriptions; ARIA live/status for success/error messages;
  full keyboard navigation; focus moves to first invalid field after failed
  submit; usable inside Elementor popups; presets meet contrast requirements.
- i18n/RTL: all strings translatable, consistent text domain, RTL-safe
  layout, WPML/Polylang/TranslatePress compatibility, submissions store
  locale metadata.

### Motion policy

MVP motion scope only: error reveal, success fade/slide, button spinner,
conditional field show/hide transition, preset popup open/close. **No GSAP
dependency for core form submit** — this is repeated emphatically throughout
the spec and is a hard MVP constraint, distinct from the rest of this plugin
where GSAP is already used for animation effects. Respect
`prefers-reduced-motion`; validation clarity must never depend on animation.

**Multi-Step Forms' step-change transition (fade/slide/fade-slide, per-step
selectable) is a pure CSS `transform`/`opacity` transition** — see the
"Step-change animation" entry under
[Multi-Step Forms](#multi-step-forms--shipped-2026-07-19-free-plugin-not-pro-gated-in-code)
above. It briefly used GSAP (2026-07-20, at the user's request) as a
deliberate scoped exception, then was rebuilt CSS-only the same day (also
user-requested, after a residual visual issue) — so the "no GSAP" rule now
holds without exception across the entire Form Builder, including this
transition.

### Milestone order (SSR §33 — authoritative; supersedes the older PRD's
v1.4 sprint reprioritization, which grouped things slightly differently)

Implement and get one milestone reviewed before starting the next; do not
build the whole feature in one pass.

1. **Atomic skeleton** — register `e-aae-a-form`, `-form-field`,
   `-form-submit`; add default children (Name/Email/Message/Submit); render
   basic form; no submit logic yet.
2. **Fields control** — `aae-form-fields` list control; add/reorder/
   duplicate/delete; edit field settings.
3. **Preset popup** — preset registry; popup UI; apply subtree; regenerate
   `form_key` on apply.
4. **Identity and schema sync** — generate `form_key`; regenerate on
   duplicate/paste/import; schema walker; versioning.
5. **REST submit runtime** — frontend submit intercept; token fetch; REST
   submit; UI states (loading/success/error/rate-limited/offline).
6. **Submission DB** — create the six `aae_*` tables; save submission +
   values.
7. **Email action** — admin email; auto reply; smart tags; test email.
8. **Bot Shield** — honeypot; min submit time; token replay prevention;
   rate limit.
9. **Dashboard and health check** — submissions UI; single view; Form
   Health Check.
10. **Basic webhook and action logs** — webhook action; action jobs; logs.

**Note on ordering vs. the older PRD:** the root-level PRD's v1.4 addendum
argues for moving Bot Shield earlier (position 2, right after the skeleton)
because security should precede UI polish. The SSR doc — newer, and written
explicitly as the implementation milestone list for Claude — puts Bot Shield
at position 8, after fields/presets/identity/REST/DB/email are already
built. Follow the **SSR numbering above** since it's the more recent,
implementation-specific source, but build the REST submit endpoint (M5) and
the token/nonce/honeypot checks it depends on together — don't ship an
insecure REST endpoint in M5 and defer all security to M8. At minimum, single-
use tokens + nonce verification belong in M5; rate-limit/honeypot escalation
can wait for M8.

**Pro (separate track, after M10, per Free vs. Pro split above):** advanced
field types, Conditional Display Engine, Multi-Step Forms
(`e-aae-a-form-step`), Calculator/Quote, abandonment tracking/partial save,
Analytics, AI Copilot, HTML Email Template Builder, advanced Bot Shield
(Turnstile/reCAPTCHA v3, disposable-email/keyword/country blocking).

### Advanced Field Types — Batch 1: Date/Time/Range/Rating (shipped 2026-07-20)

First slice of the Pro "advanced field types" list — split into batches by
risk rather than shipped as one large change (Country followed in Batch 2;
Signature/Repeater/HTML/
Calculation/Address/Country remain unbuilt). Two different implementation
shapes, chosen per the same free/Pro registry constraint documented under
[Multi-Step Forms](#multi-step-forms--shipped-2026-07-19-free-plugin-not-pro-gated-in-code):

- **Date, Time** are new `type` enum values on the EXISTING
  `AAE_A_Form_Input` widget, added entirely from Pro
  (`animation-addons-for-elementor-pro/inc/AtomicV4/FormFields/Schema.php`)
  via a filter hook the free widget already exposed and documented for this
  exact purpose (`aae_form/input_types` — its own docblock said "Pro adds
  date/time here" before any Pro code used it). No new widget class, no new
  props, no JS bundle — one `add_filter` call. Registered in
  `inc/AtomicV4/Bootstrap.php` alongside the other AtomicV4 modules.
- **Rating and Range are each a genuinely new element type**
  (`e-aae-a-form-rating`, `e-aae-a-form-range`), so — same reasoning as
  `e-aae-a-form-step`/`-next`/`-prev` — both live in the FREE plugin
  unconditionally, with `is_pro => true` in `class-atomic.php`'s dashboard
  metadata marking them premium-tier (upsell badge only, not a functional
  gate; new optional widgets default to inactive until toggled on in the
  Atomic Widgets dashboard, same as any other non-seeded widget).
  - **Rating** (`inc/AtomicWidgets/Widgets/Form/class-aae-a-form-rating.php`):
    the real control is a plain `<input type="number">` (min 0, max = star
    count, required/error_message all work exactly like any other number
    field to Validator.php and Schema_Walker), progressively enhanced by
    `lib/rating.js` (mirrors `lib/multi-select.js`'s pattern) into a
    clickable star row that mirrors clicks onto the hidden input's value and
    fires `change` — so the rest of the form runtime never needs
    special-case Rating logic. Clicking the currently-set star again clears
    the rating (toggle off). CSS in `form.scss` (`.aae-a-form-rating-*`); no
    framework, no GSAP.
  - **Range** (`class-aae-a-form-range.php`) — originally shipped as a
    THIRD Pro `type` value on the Input widget (like Date/Time), then split
    into its own widget the same day after the user asked to set the
    slider's own colour, independently, from the Style panel. Root problem:
    `accent-color` — the one CSS property that actually recolors a native
    range's track/thumb consistently across browsers — has **no
    equivalent key in Elementor's atomic Style-panel schema at all**
    (confirmed by listing every file in
    `elementor/modules/atomic-widgets/controls/types/` — there is also no
    generic "Color" Content-tab control to fall back to; atomic widgets
    only expose color pickers through `Style_Definition`/`Color_Prop_Type`,
    i.e. the Style tab). Solution: Range gets its own `background` prop on
    its own base style (Style tab → Background Color, same mechanism
    Select's background uses), and `lib/range.js` bridges that to
    `accent-color` by reading the rendered input's own **computed**
    `background-color` at init and copying it onto `input.style.accentColor`
    — a live runtime bridge, not a build-time one (mirrors
    `lib/multi-select.js`'s `applyStyle()`, which does the identical
    computed-style-copy trick for the multi-select trigger). Defaults to
    `#69727D` (the same neutral gray Checkbox's `:checked` state uses), not
    transparent — transparent would make the accent invisible until a
    builder explicitly paints a colour, which reads as broken rather than
    "using the default". Because Range moved to its own widget, Pro's
    `FormFields` module shrank back down to Date/Time only — `Controls.php`
    and `Renderer.php` (which existed only to splice a `step` attribute onto
    the shared Input widget) were deleted entirely; `step` is now a plain
    prop directly on the Range widget itself, no splicing needed.
- `Validator.php` gained `date`/`time` format/range checks inside
  `check_format()` (dispatched by the Input widget's `type` string — works
  regardless of which plugin registered that type) plus one new filter,
  `aae_form/validator/check_format`, for any FUTURE type this switch
  doesn't handle. `rating` and `range` are each their OWN top-level `case`
  in `Validator::validate()`'s main switch instead (they're separate field
  TYPES from `Schema_Walker::FIELD_TYPES`, not Input `type` values, so they
  never reach `check_format()` at all) — `range` reuses the same
  `check_range()` helper the input family's own min/max check uses, and
  never fires a required error (a native range always posts a value).
  `Schema_Walker::FIELD_TYPES` gained `e-aae-a-form-rating` → `rating` and
  `e-aae-a-form-range` → `range` mappings, each with a matching case in
  `build_field()` — Rating's carries `max` into the schema as the star-count
  ceiling, Range's carries `min`/`max` (its `step` is display-only, not a
  submission-security concern, so it isn't in the schema at all).
- New preset: `presets/advanced-fields.json` — Date, Time, Range, Rating,
  Submit, plus the required Field Error / Success / Error message widgets
  (same "every preset needs these three" pattern as `all-fields.json`).
  Deliberately a NEW preset rather than a retrofit of `all-fields.json` (
  which would have required regenerating the derived
  `all-fields-columns.json` too, per the [Form presets](#form-presets)
  note that the columns version is script-generated, not hand-edited).
- **Verified end-to-end** via a WP-CLI fixture (`make-form-advfields-page.php`,
  mirrors `make-form-allfields-page.php`) + Playwright
  (`verify-form-advfields.mjs`, `E:\Local Testing\`): all four field types
  render with correct attributes (native `date`/`time`/`range` inputs, 5
  painted stars for Rating's default max), required-field errors fire
  correctly (range never fires one — a native range input always has a
  value), star clicks mirror to the real input and clear its error, and a
  full REST submit reaches `form-state-success` — proving the whole
  pipeline (Schema_Walker → Validator → Rest.php) accepts all four new
  types.
- **Gotcha — a WP-CLI fixture builder using `Document::save()` alone left
  the page rendering as plain `post_content` (wpautop-wrapped `<p>`/`<br>`,
  no `<form>`/div-block wrappers at all), even though `_elementor_data` and
  the schema-sync DB row were both correct.** Root cause: a freshly
  `wp_insert_post()`-created plain `page` has no `_elementor_edit_mode` meta
  yet, and `Document::save()` doesn't set it — so WordPress never hands the
  page to Elementor's frontend renderer, no matter how correct the saved
  element tree is. This affected the EXISTING `make-form-allfields-page.php`
  fixture too (not something Batch 1 introduced) — it had simply never been
  re-run since being written, so the bug was latent. Fixed in both scripts
  by explicitly `update_post_meta( $post_id, '_elementor_edit_mode',
  'builder' )` BEFORE calling `$document->save()` (mirrors
  `make-host-page.php`, which writes `_elementor_data` directly and always
  set this meta itself — the one fixture-builder pattern in this codebase
  that never hit the bug). Diagnose this class of "children render but the
  container doesn't, and there's no PHP error anywhere" failure by checking
  for a literal `<form`/`<div class="e-div-block"` tag in the raw response
  HTML, not just `data-element_type` attribute counts — Elementor's own
  `render_content` filter still fires per-widget even when the parent
  document isn't being rendered as a document at all, which is what made
  the child widgets appear to "work" while the root silently didn't.
- Bot Shield's minimum-submit-time default (3s) will reject a same-request
  fixture test that fills every field and submits in well under a second —
  this is Bot Shield doing its job, not a bug; any new form-submit
  Playwright test needs a `waitForTimeout` of at least that long before
  clicking Submit (see `verify-form-advfields.mjs`).
- **Bug found + fixed same day (2026-07-20), reported live via screenshot:
  the Rating field's required-error message rendered INLINE next to the
  stars (squeezing Submit onto the same line) instead of on its own line
  below.** Root cause, two independent gaps: (1) `errorAnchor()` in
  `form.js` didn't know about the Rating wrapper — for every other
  enhanced-control field (multi-select, checkbox row, radio group) it walks
  up to the wrapper via `.closest(...)` before inserting the error, but
  Rating's hidden `<input>` fell through to the default case, so
  `insertAdjacentElement('afterend', el)` inserted the error INSIDE
  `.aae-a-form-rating` (next to the visible star row) instead of as a
  sibling of the wrapper — breaking the `:where(.aae-form-field-error) {
  flex-basis: 100% }` row-break, since that rule only forces a line break
  for direct flex children of the `<form>`, not something nested one level
  deeper. Fixed by adding `control.closest('.aae-a-form-rating')` to the
  same lookup chain. (2) Separately, `AAE_A_Form_Rating::define_base_styles()`
  never set `width: 100%` (unlike Input/Select/Textarea, which all do) — so
  the wrapper shrank to its star row's own width, leaving room for Submit to
  tuck in beside it even before the error-anchor fix. Both were required;
  fixing only one still left a layout bug. Verify this class of bug by
  checking computed `getBoundingClientRect()` Y-positions of the field,
  its error, and the next control — not just that the error text exists
  somewhere in the DOM.

### Advanced Field Types — Batch 2 started: Country (shipped 2026-07-20)

**Country** (`e-aae-a-form-country`,
`inc/AtomicWidgets/Widgets/Form/class-aae-a-form-country.php`) — a
single-value sibling of the Select widget: same "value|Label|selected"
options format, same base styles/twig mechanics, but the `options` prop
**defaults to the full built-in ISO 3166-1 list** (239 entries,
`inc/Forms/Countries.php` — translatable names, `aae_form/countries`
filter), values submit as ISO alpha-2 codes, and the `<select>` carries
`autocomplete="country-name"`. No JS bundle (`has_script: false` — native
select). Free plugin + `is_pro => true` badge, same reasoning as
Rating/Range. Priority countries = builder reorders/prunes the pre-filled
options textarea; `|selected` pre-picks one.

- **Why the list is a prop DEFAULT, not render-time PHP context:** atomic
  twigs also render client-side in the editor preview
  (`Has_Template::get_initial_config` ships `twig_templates`), where only
  settings + prop defaults exist — injecting extra context in a `render()`
  override would leave the editor's select empty. Defaults flow through
  both pipelines.
- **Schema/Validator:** `Schema_Walker::FIELD_TYPES` maps to its own
  `country` type whose `build_field()` case falls back to
  `Countries::options_string()` when the prop is untouched — so the schema
  snapshot always carries the full whitelist. `Validator::validate()` adds
  `case 'country':` as a bare fallthrough into `case 'select':` (identical
  required + options-whitelist mechanics, `multiple` absent → false).
- Verified end-to-end (`E:\Local Testing\verify-form-country.mjs` +
  `make-form-country-page.php` + `enable-form-country-widget.php`):
  inventory (240 options incl. placeholder, autocomplete attr, BD label),
  required error with per-field message, **tampered DOM-injected option →
  server 422 whitelist rejection mapped back onto the field**, then valid
  BD → success.

**Address (shipped same day) is a PRESET, not a widget** —
`presets/address.json`: Street / Apartment(optional) / City / State(optional)
/ Postal + the Country widget + Submit + the three message parts. Deliberate
call: an "Address field" has no server-side semantics of its own (it's five
plain text values), so a composite widget would only have re-implemented
Label+Input layout while LOSING per-part styling, reordering, and
conditional-display support. The one thing genuinely missing was native
autofill, so that was added to the shared Input widget instead:

- **New `autocomplete` prop on `e-aae-a-form-input`** (Content → "Autofill",
  `Select_Control`) with a curated token list —
  `AAE_A_Form_Input::autocomplete_options()`: name/given-name/family-name,
  email, tel, url, organization, street-address, address-line1/2,
  address-level2 (City), address-level1 (State), postal-code, country-name,
  off. `''` = browser default → attribute not rendered at all. Enum values
  come from `array_column(autocomplete_options(), 'value')` so control and
  prop can't drift. **Display-only — deliberately NOT in the schema
  snapshot** (same reasoning as Range's `step`: no submission-security
  meaning). Benefits every form, not just Address.
- Verified: `E:\Local Testing\verify-form-address.mjs` +
  `make-form-address-page.php` — all 6 autocomplete tokens land on the
  rendered fields, required errors fire on exactly the 4 required parts
  (line2/state stay optional), full fill + BD → success. Regression
  (allfields/country/regex) re-run green after the Input change.
- **Test-writing note:** the schema stores an input field's SUBTYPE
  (`text`, `email`, …) as its `type`, NOT the widget-family name `input` —
  an assertion expecting `input*` fails against the correct `text*`.

**Password (shipped 2026-07-20) — a SEPARATE widget, not the Input widget's
`password` type.** `e-aae-a-form-password` + `lib/password.js` (reveal-eye
toggle only; min-length/match validation lives in `form.js`'s
`validateFrontend` next to the other rules). The Input widget keeps its own
`password` type for the legacy/simple case; the new widget is the
privacy-correct one, and the split exists **because storage behavior must be
schema-driven**: deriving "never store this" from an Input's mutable `type`
prop would leave already-stored plaintext behind the moment a builder flips
Text→Password. Props: `min_length`, `match_field` (the partner field's
`_cssid`, set on the *confirm* field), `show_toggle`, `store_mode`,
`mismatch_message`. Twig wraps the input in a `.aae-a-form-password` div
that carries the base style, so the eye button sits INSIDE the field box —
which also means `errorAnchor()` in form.js needed the wrapper added to its
`.closest()` chain (same bug class as Rating's, same day).

- **Redaction is central and unconditional.** `store_mode` has NO `plain`
  value by design: `never` (default) records `********`, `hash` stores a
  `wp_hash_password()` digest. `Validator::redact_passwords()` runs in
  `Rest.php` **before** `submission_validated`/storage/the action queue/CSV,
  so no downstream consumer can see a readable password. Note the Validator
  case deliberately does NOT `sanitize_text_field()` the value (it would
  strip legal password characters) — safe because the value is never
  rendered as HTML, only discarded or hashed. Confirm-match uses
  `hash_equals`.
- **Create User action (PRO, `inc/AtomicV4/FormUser/`)** — turns a signup
  submission into a real WP account. Free ships ONE new seam:
  `aae_form/submission_raw`, fired in `Rest.php` immediately BEFORE
  redaction, synchronously (the raw credential must never reach the async
  job queue, which persists its payload). Pro's `Engine` hooks it;
  `Fields.php` maps payload keys onto user fields by **alias**
  (`fname`/`f_name`/`firstname`, `lname`/`l_name`/`lastname`,
  `user_name`/`username`, `email`/`user_email`/`mail`, …). Config is a
  `create_user` block in `actions_json`, edited in the Actions dialog's new
  **Create User** tab, which shows live ✓/! readiness rows naming the exact
  field keys this form has/needs. Safety: role is re-checked against
  `ALLOWED_ROLES` server-side (subscriber/customer only — an edited
  `actions_json` can never mint an admin); an email that already exists is
  SKIPPED, never updated; every failure is caught and logged so a
  submission is never lost.
- **Alias matching is exact-then-suffix, never substring.** Exact normalised
  match first (`email`), then a suffix match so real-world prefixed keys
  (`aae-signup-email`, `billing_email`) resolve — anchored to the END
  specifically so `email_consent` can never be mistaken for the address.
  The same two-pass logic is mirrored in the editor dialog's
  `matchedAlias()`; change one, change both.
- **Gotcha found while testing: `AAE_A_Form_Input` has NO `name` prop** (only
  Select/Country do), yet its twig and `Schema_Walker` both read one. Setting
  `name` on an Input in a preset/fixture is silently dropped at render while
  the schema still records it → the posted key can never match its own
  schema (422 on every submit). Use `_cssid` to control an Input's field key.
- **`presets/signup.json` ("User Registration") is the registration preset** —
  Name / Username (optional) / Email / Password / Confirm, and it ships with
  `create_user` ALREADY ENABLED in its `actions_json` (plus admin email), so
  applying it gives a working registration form with no dialog trip. Its
  field `_cssid`s are deliberately named for the aliases (`name`,
  `user_name`, `email`, `password`, `password_confirm`) — a preset whose keys
  don't match the aliases would apply cleanly and then silently fail to
  create users. Username blank → login derives from the email's local part;
  filled → used verbatim. (`autocomplete` gained a `username` token for it.)
- Verified: `verify-form-password.mjs` (25 checks) + `make-form-signup-page.php`
  — reveal toggle, both frontend blocks with zero network requests, error
  placement below the wrapper, `********` read straight out of
  `aae_submission_values`, the created account's password actually
  authenticating via `wp_check_password`, a duplicate-email re-submit proving
  the existing user is untouched, and BOTH username paths (blank → email
  local part, filled → verbatim).

**GOTCHA — retry-after-failure got 403 `too_fast`, a general form.js bug
this test exposed (fixed 2026-07-20).** The server's minimum-submit-time
check measures from TOKEN ISSUE (`Rest.php`), and form.js's
interaction-prefetch listeners were `{ once: true }` — after the first
submit consumed the token, nothing re-armed them, so a retry's token was
fetched at click time (age ~0s) and Bot Shield blocked an honest retry.
Re-arming the listeners alone is NOT enough: the 422 path auto-focuses the
invalid field (`focusFirstInvalid`) BEFORE the re-arm runs, so a
re-armed `focusin` never fires (focusing an already-focused element is a
no-op). Fix in form.js: on ANY failed submit (non-ok response or catch),
`prefetch()` a fresh token IMMEDIATELY — its age then measures the real
time the visitor spends fixing input. Success path only re-arms the
interaction listeners (covers the success-reset → fill-again flow without
fetching a token nobody may use). Diagnose this class of block via the
spam log: `wp eval` on `wp_aae_action_logs` shows `bot_shield | blocked |
too_fast` with timestamps (the `wp db query` CLI path is broken on this
Local install — no mysql binary in PATH).

### Hide Form After Success (shipped 2026-07-20)

Two props on `e-aae-a-form`, next to the existing "Reset After Success":
`success_hide_form` (Switch) + `success_hide_delay` (Number, seconds; 0 =
hide immediately). Rendered as `data-aae-form-hide-on-success` /
`data-aae-form-hide-delay`; runtime is `scheduleSuccessHide()` in `form.js`,
called next to `scheduleSuccessReset()` on a successful submit.

- **Hiding the `<form>` itself is wrong** — the authored success/error
  message widgets are its CHILDREN, so hiding the form takes the message
  with it and the visitor sees nothing. Instead every DIRECT child EXCEPT
  the status-message containers gets `.aae-form-body-hidden`; the message
  keeps its authored styling and position.
- **The CSS rule needs `.elementor` on the selector** — atomic base styles
  compile as `.elementor .e-<widget>-base { display: … }` (0,2,0), so a bare
  `.aae-form-body-hidden` (0,1,0) loses to them and nothing hides. Same trap
  as `e-aae-a-form-step` (see [Common breakage points](#common-breakage-points)).
- **Hide and Reset are independent and must cooperate**: the reset path calls
  `showFormBody()` so a form with BOTH settings comes back instead of
  resetting into an empty box, and a real Reset/Clear click cancels a pending
  hide timer. Verified as its own case.
- **GOTCHA — `dataset.elementType` does NOT read `data-element_type`.** The
  attribute has an UNDERSCORE, so `dataset` (which maps `data-element-type`,
  hyphen) always returns undefined; `getAttribute('data-element_type')` is
  the only correct read. This silently made every child look like a
  non-message element, so the success message got hidden along with the
  fields — caught only because the test asserted the message stays VISIBLE
  (computed style), not merely that the fields disappeared.
- Verified: `verify-form-success-hide.mjs` + `set-form-success-hide.php` —
  four cases: delayed hide (message still visible), immediate hide (delay 0),
  hide-then-reset-restores, and switch-off leaves the default behaviour
  unchanged.

### Conditional Display Engine (PRO — shipped 2026-07-19)

Lives in `animation-addons-for-elementor-pro/inc/AtomicV4/FormConditions/`
(FlexboxChildHover module pattern). Free ships ONLY neutral plumbing:

- **JS hook registry** — free `form.js` exposes `window.AAEFormHooks`
  (WP-style addAction/addFilter; `aae_form/init` fires per form, late
  registrations replay). Pro runtime
  (`src/modules/atomic-v4/form-conditions.js`, handle
  `aae-pro-form-conditions`, depends on `aae-a-form-js`) binds there.
  Free validation/collect skip anything under `[data-aae-cond-hidden]`.
- **PHP filters (free)** — `aae_form/schema_walker/field` (per-field extras),
  `aae_form/schema_walker/schema` (whole schema + raw tree — used to attach
  ancestor row conditions to inner fields as `conditions_parents`),
  `aae_form/validator/skip_field` (skipped = not validated, not stored).
- **Config delivery** — field/content WIDGETS get `data-aae-cond` spliced
  into their first tag via `elementor/widget/render_content`;
  flexbox/div-block CONTAINERS can't (elements, not widgets) so their
  configs ride `window.AAE_FORM_COND` keyed by `data-id` (collected at
  `before_render`, printed at `wp_footer` prio 5). Runtime enqueued only
  when something actually carries conditions.
- **Rule semantics live in THREE mirrored places** — PHP `Engine.php`,
  runtime `form-conditions.js`, dialog `FormConditionsControl.jsx`
  (free editor-bridge, dormant without pro — Integrations-tab pattern;
  PHP control stub type `aae-form-conditions`). Operators: equals /
  not_equals / empty / not_empty / contains / greater_than / less_than;
  logic all/any; action show/hide. Change one → change all three.
- Editor canvas never hides conditioned elements (styling access); reveal
  animation class `aae-cond-reveal` lives in free `form.scss` behind
  `prefers-reduced-motion`.

### Calculation field — shipped 2026-07-20 (free plugin, `is_pro` badge only)

`e-aae-a-form-calculation`: a read-only field whose value is computed from
OTHER fields by a builder-authored formula (`round(({package} *
{quantity} + {support}) * 1.15, 2)`). Same free-plugin/pro-badge
arrangement as Rating/Range/Country/Password — the element type must be
registered from the free plugin (Elementor's atomic registry has no
outside-registration path), `is_pro => true` is marketing only.

**The security model is the whole point.** The posted value is
DISCARDED: `Validator::validate()`'s `case 'calculation'` recomputes the
total from the other posted values using the formula in the ACTIVE SCHEMA
SNAPSHOT, so editing the hidden input in DevTools changes nothing about
what gets stored or emailed. Verified end-to-end (a submit with the input
rewritten to `1.00` stored the correct `2298.85`).

- **Two mirrored evaluators, and they MUST agree**: `inc/Forms/Formula.php`
  and `Widgets/Form/assets/js/lib/calculation.js`. Hand-written tokenizer +
  shunting-yard, **never `eval()`** (a formula is untrusted input that runs
  server-side on every submit). Supported: numbers, `{field_key}`, `+ - *
  / %`, unary minus, parens, and `round/floor/ceil/abs/min/max`. Anything
  else fails to parse → null → nothing stored (never an error: a builder's
  typo must not block a submission the visitor can't fix). Division by zero
  returns null on BOTH sides (not Infinity). `round()` is half-away-from-zero
  on both — JS routes through a string exponent shift (`roundHalfUp`) because
  `Math.round(-2.5)` is `-2` while PHP's is `-3`, and `1.005` drifts in
  binary.
  - **Parity is regression-tested**: 56 cases (incl. `round(1.005,2)`,
    `round(-2.5)`, `0.1+0.2`, `-10 % 3`, div-by-zero, every syntax error
    shape) run through both implementations and compared —
    `scratchpad/formula-{cases.json,php.php,js.mjs}` pattern. Re-run it
    after ANY change to either evaluator.
- **Empty-form placeholder is a JS-only concern**: an untouched form would
  total 0 and read as a real quote of "$0.00", so `syncCalculations()` holds
  the `empty_text` placeholder until at least one REFERENCED field is
  non-empty. The server has no equivalent case (it only computes at submit
  time). A preset whose first select option is a real value therefore looks
  "already filled" — give such selects a `placeholder` so a genuine empty
  state exists (quote-calculator.json does).
- Value reading (`valuesOf`): unchecked checkbox contributes nothing, a
  checked one contributes ITS OWN value (so `{support}` with value `199`
  works), multi-select sums its picked values, and anything under
  `[data-aae-cond-hidden]` (pro Conditional Display) is skipped — a hidden
  field must not feed a total the visitor can't see.
- Runtime binds ONE delegated `input`/`change` pair on the `<form>` (not
  per-dependency listeners) so it survives fields being added/removed by
  conditional display or a preset apply; `reset` re-syncs on the next tick.
- **Gotcha — Input/Textarea have NO `name` prop** (their submission key IS
  `_cssid`), while Select/Checkbox/Rating/Calculation DO. A formula
  referencing `{quantity}` therefore needs the Input's **ID** set to
  `quantity`, not a `name` setting — a preset that sets `name` on an Input
  silently gets `_cssid` as the real key instead (cost a full e2e debug
  cycle; the panel description for Calculation's Name field says "the key
  this total is saved under" for the same reason).
- **Chained calculations work**: a formula may reference an EARLIER
  calculation field (`{subtotal}` → `{rush_surcharge}` → `{project_total}`).
  Both sides feed each result back into the working value map as they go —
  `Validator.php` writes into `$posted`, `calculation.js` into its `values`
  object — so resolution is strictly DOCUMENT ORDER: a formula can only read
  calculations declared above it, which is also why a circular reference is
  impossible. (Without the feed-back the chain silently produced 0 on the
  server while working in the browser, since the browser reads the previous
  field's rendered DOM value.)
- **Multi-value fields SUM**: `number_of()`/`numberOf()` add up an array, so
  a multi-select's picks total into one number (`{addons}` = 600+900+450).
  **Name normalisation matters here**: a multi-select's DOM name carries the
  PHP array suffix (`addons[]`) but the SCHEMA keys it without one
  (`addons`) — the browser and server would otherwise disagree on the key.
  Both spellings resolve on both sides (`valuesOf()` registers both;
  `Formula::tokenize()` falls back from `{addons[]}` to `addons`). Prefer
  the plain `{addons}` form in presets/docs.
- A multi-select with NOTHING picked leaves its key ABSENT rather than
  reading 0 — otherwise the placeholder check would see a present-but-zero
  value and treat an untouched form as filled.
- Presets (7): `quote-calculator.json` (select-as-price + quantity + priced
  checkbox), `booking-calculator.json` (RANGE slider as quantity, plus a
  second calculation used purely as a slider read-out), `loan-calculator.json`
  (three chained calculations: interest → repayable → monthly),
  `web-project-estimate.json` (the advanced showcase: multi-select summing,
  three priced checkboxes, and a subtotal → percentage-surcharge → total
  chain), plus three INDUSTRY presets:
  `doctor-appointment.json` (3-step multi-step form: patient details →
  appointment → medical info; radio groups, date+time, a NEGATIVE option
  value for a video-call discount wrapped in `max(…, 0)`, multi-file upload,
  consent checkbox, submit inside the last step's nav row),
  `salon-booking.json` (multi-select services + stylist-surcharge select,
  per-guest multiplication, home-visit checkbox, reference-photo upload),
  `hotel-booking.json` (Country widget, check-in/out dates, nights DERIVED
  from those dates via `days_between`, room-type select, rooms RANGE,
  multi-select extras, and a five-calculation chain: nights → room subtotal
  → extras subtotal → taxed total, plus a slider read-out).
  - **Date arithmetic goes through `days_between(a, b)`, never `-`.** Plain
    subtraction still evaluates to 0: `{field}` tokens carry a NUMERIC
    reading and "2026-09-04" has none. `days_between` is the one function
    whose operands are dates — tokens therefore keep a third slot with the
    RAW posted string, and the RPN evaluator runs a parallel `raw` stack so
    date functions can reach it (computed values have no raw form and store
    ''). Hotel's nights field uses it:
    `days_between({check_in}, {check_out})` → 3 for Sep 1 → Sep 4.
    - Returns null (⇒ whole formula uncomputable, nothing stored) when
      either side isn't a real calendar date — including a nonexistent one
      like `2026-02-30`. A BACKWARDS range returns a NEGATIVE number, not
      null; guard with `max(days_between(…), 1)` when a minimum makes sense.
    - Accepts an ISO date with or without a time part (the time is ignored).
    - Both sides count whole days from Y-M-D via the same civil-from-days
      algorithm — deliberately NOT `strtotime`/`Date`, so DST and timezones
      can't shift a night boundary and neither side needs to know the site's
      timezone.
  Fixtures/tests: `verify-industry-presets.mjs` (all three), plus
  `make-form-calc-page.php` +
  `verify-form-calculation.mjs` (tamper case), `make-form-estimate-page.php` +
  `verify-form-estimate.mjs` (multi-select + chain, compares stored values
  against what the page displayed), `read-last-submission.php`,
  `enable-form-calculation-widget.php`.
- **Writing Playwright tests against these long preset forms — two traps
  that both LOOK like product bugs and are not:** (1) a `page.click()` on a
  submit button sitting past the viewport bottom silently no-ops (Playwright
  still reports the click as successful), so dispatch
  `form.requestSubmit()` instead — same code path, no hit-testing; (2) the
  success state is erased ~5s later by Reset After Success, so
  `waitForSelector('.form-state-success')` can miss it entirely — arm a
  MutationObserver latch BEFORE submitting. Also clear Bot Shield's rate
  limiter between submits (`clear-form-rate-limit.php` — transients keyed
  `aae_frl_*`; 5 submits per 5 minutes per visitor+form), or a multi-form
  suite trips it legitimately and it reads as a broken form. Each of these
  cost a full debug cycle chasing a non-existent bug.

### Validation Pro — regex rules (PRO — shipped 2026-07-20)

First slice of Validation Pro. Same free-hooks/pro-engine split as
Conditional Display: free ships two neutral filter points, the regex
engine lives entirely in
`animation-addons-for-elementor-pro/inc/AtomicV4/FormValidation/`
(Schema/Controls/Renderer/Engine/Assets — module registered in
`AtomicV4/Bootstrap.php`).

- **Free plumbing (the only free changes):** PHP filter
  `aae_form/validator/value_error` (Validator.php — runs for input-family +
  textarea AFTER every built-in check passed; return a message string to
  fail) and JS filter `aae_form/validate/value_error` (form.js
  validateFrontend — same contract, only applied when the control's
  built-in checks passed). Both are generic extension points, not
  regex-specific.
- **Props** `aae_regex_pattern` + `aae_regex_message` on
  `e-aae-a-form-input`/`-textarea` (message dep-gated on pattern non-empty);
  panel controls land in the widget's native **Settings** section at filter
  prio 24 — one before Conditional Display (25), so the section reads
  ID → Regex → Conditional Display.
- **Match semantics mirrored in TWO places** — PHP `Engine.php` and runtime
  `src/modules/atomic-v4/form-validation.js` (handle
  `aae-pro-form-validation`, dep `aae-a-form-js`, register-only +
  on-demand enqueue when the Renderer actually splices). Change one →
  change both. Semantics: **partial match** (users anchor with `^ $`),
  pattern accepted bare (`^\d{5}$`) or as `/pattern/flags` (flags
  whitelisted per engine), an **invalid pattern always passes** (never
  blocks a visitor on a builder's typo). Message precedence:
  `regex_message` > field `error_message` > translated default.
- Rule rides the schema snapshot as `regex`/`regex_message` (walker filter
  `aae_form/schema_walker/field`) and the rendered control as
  `data-aae-regex`/`data-aae-regex-message` (first-tag splice, same as
  Conditional Display's Renderer).
- Verified end-to-end: `E:\Local Testing\make-form-regex-page.php` (server:
  schema carries rule, Validator rejects bad value with custom message,
  good value clean) + `verify-form-regex.mjs` (frontend blocks with custom
  message and ZERO network requests; good value → form-state-success).

### Form "Reset After Success" (free — shipped 2026-07-20)

`success_reset_delay` Number prop on `e-aae-a-form` (default **5** seconds,
`0` = keep success state until reload; control in the Content section under
Submission Behavior) → twig `data-aae-form-reset-delay` →
`scheduleSuccessReset()` in form.js: after a successful submit (skipped on
redirect), waits the delay then restores the resting state — success
message/class cleared, field errors cleared, and a multi-step form returns
to its FIRST step (`form.__aaeStepState.current = 0` + `resyncSteps`).
Fields themselves were already cleared by `form.reset()` at success time.
Verified: `E:\Local Testing\verify-form-success-reset.mjs` (multi-step
contact preset — submits on step 2, auto-returns to step 1 with message
cleared).

### Multi-Step Forms — shipped 2026-07-19 (free plugin, not pro-gated in code)

Hard requirements (all live-verified end-to-end on development.local: applied
via a real preset, published, driven on the actual frontend — Next blocked
with 2 field errors on empty required fields, advanced to step 2 once filled,
Previous appeared):

1. **Next only after the current step validates** — advancing to the next
   step MUST run that step's field validation first and block on errors
   (user requirement, non-negotiable). ✅ Confirmed.
2. **A Multi-Step preset must ship with the feature** (user requirement).
   ✅ Two presets: `multi-step-contact.json` (2 steps) and
   `multi-step-lead.json` (3 steps) — see below.
3. Panel copy/UX — the Step widget's Content section labels/describes
   `step_title` clearly (shown in the step-nav progress indicator, not
   inside the step itself).

**Architecture decision (verified via Explore agent before building — see
also `AAE_A_Btn_Pro` precedent, `Widgets/BtnPro/class-aae-a-btn-pro.php`):
Elementor's atomic widget registry (`inc/AtomicWidgets/class-atomic.php`'s
`get_available_widgets()`) is 100% free-plugin territory — a Pro plugin
cannot register a brand-new element TYPE from outside it.** So
`e-aae-a-form-step` is a normal FREE-plugin element
(`inc/AtomicWidgets/Widgets/Form/class-aae-a-form-step.php`, container:
`Atomic_Element_Base` + `Has_Element_Template`, unrestricted children like
the parent form) registered in `class-atomic.php` in all three places new
Form children need: the "always-active internal widgets" list (~line 327,
alongside the other form parts), the Widgets-dashboard metadata (`is_pro:
true` — marketing/upsell badge ONLY, exactly like `aae-a-btn-pro`; the code
itself is NOT license-gated), and the class/file registry (`has_script:
false` — step-nav JS rides inside the existing `aae-a-form-js` bundle, not
a separate script).

**Runtime**: `inc/AtomicWidgets/Widgets/Form/assets/js/lib/multi-step.js`
(new module, mirrors `lib/multi-select.js`'s pattern) — `initSteps(form,
validateStep, onBlocked)` toggles `aae-form-step-active` on exactly one
`[data-aae-form-step="true"]` at a time (same class-toggle-reveal trick as
`form-state-{value}`). Runs in the editor preview too (builders need to
see/style every step) — only the Next-button VALIDATION gate is
frontend-only (`validateStep`/`onBlocked` null in `isEditMode()`).
`form.js` reuses its own private `validateFrontend`/`showFieldError`/
`focusFirstInvalid` for this (no duplicated validation logic) —
`controlsOf()` was generalized to accept ANY root element (not just
`form.elements`) so validation can scope to one step's controls via
`querySelectorAll`.

**Next/Previous are REAL atomic widgets (added 2026-07-19), not
runtime-injected DOM buttons** — `e-aae-a-form-next` /
`e-aae-a-form-prev` (`class-aae-a-form-next.php` / `-prev.php`, leaf
`Atomic_Widget_Base`, same free-plugin reasoning as the Step element
above), rendering `<button data-aae-form-step-nav="next"|"prev">`. This
gives builders full Style-tab control (colors, borders, spacing, hover
state) — something a JS-injected `<div>` never had. `e-aae-a-form-step`
seeds a Prev+Next pair (`e-flexbox.aae-form-step-nav-row`) as its
`define_default_children()`, so a fresh Step drops in ready to navigate;
builders can delete/move/restyle them freely.

- **Detection, not injection, is the default path.** `multi-step.js`'s
  `initSteps` scans each step for its OWN `[data-aae-form-step-nav]`
  widgets (`ownNavButtons()`) and delegates clicks to them via one listener
  on the `<form>` (event delegation — covers both author-placed widgets
  present at initial render AND any fallback button appended later).
  `render()` toggles the `hidden` attribute on whichever Prev/Next widgets
  exist per step (Prev hidden on step 0, Next hidden on the last step) —
  `.aae-a-form-next[hidden], .aae-a-form-prev[hidden] { display: none
  !important }` in `form.scss` is required because the widgets' own atomic
  base style sets `display: flex`, which otherwise beats the UA `[hidden]`
  rule (same specificity class of bug as the entry in [Common breakage
  points](#common-breakage-points) — a `!important` here is the deliberate,
  narrow exception).
- **Fallback only when a step has neither widget.** Gated by the
  **PER-STEP "Auto Step Navigation" switch** (`step_nav_auto` prop on
  `e-aae-a-form-step` — moved here 2026-07-20 from the Form widget, so a
  builder doing fully custom navigation on ONE particular step, e.g. a
  review/confirm step, can turn it off there without affecting the rest of
  the wizard) — read via `data-aae-form-step-nav-auto` on the step element
  itself (see `aae-a-form-step.html.twig`). ON (default): a step missing
  both widgets gets `multi-step.js`'s own injected Prev/Next/`N / M` bar
  (`buildFallbackNav()`, appended at the end of that step's content, tagged
  with the same `data-aae-form-step-nav` attrs so the SAME click-delegation
  path handles it — no separate injected-button code path). OFF: no
  fallback ever for that step — un-advanceable without its own widget,
  which is the point.

**GOTCHA #2 — toggling ANY setting on the form (not just the Auto Step
Navigation switch) made the ENTIRE multi-step form vanish in the editor.**
Root cause, confirmed via a diagnostic that flipped `step_nav_auto`
programmatically and inspected the preview iframe DOM: Elementor's editor
repaints a form's `innerHTML` from scratch on ANY settings change to the
form or its children (not just "Apply Preset") — the `<form>` node itself
is reused (so its `data-aae-form-ready`/`data-aae-steps-bound` dataset
survives), but every STEP node inside gets freshly re-rendered with NO
`aae-form-step-active` class. The original `initSteps()` closed over the
OLD step elements and the "already bound" guard (`aaeStepsBound === true`)
made it skip re-running entirely — so the new step nodes sat there
permanently `display:none` (their own base style), nothing ever reapplied
the active class to any of them, and the whole form looked empty. Fix
(`lib/multi-step.js`, rewritten 2026-07-20): step-navigation state
(current index) now lives on `form.__aaeStepState`, not a closure over
stale DOM references, and a new exported `resyncSteps(form)` always
re-queries fresh step nodes and reapplies active/hidden state — cheap and
idempotent, so it's safe to call unconditionally. `form.js`'s existing
editor-only 1-second polling loop (added for the preset-apply gotcha
below) now calls `resyncSteps()` on EVERY tick for EVERY already-bound
multi-step form, not just unbound ones — this is what actually heals a
form after Elementor repaints its steps out from under it. Verified with
a regression test that flips an intentionally UNRELATED setting
(`behavior`, not `step_nav_auto`) and confirms the form stays visible —
proving the fix isn't specific to one switch, it's general re-render
resilience.
- **Icon**: `Svg_Src_Prop_Type` + `Svg_Control` (`icon` prop) — empty by
  default, Twig falls back to a built-in inline chevron (`→` for Next,
  rendered AFTER the text; `←` for Previous, rendered BEFORE the text).
  Uploading an SVG via the Icon control replaces the chevron. Same
  empty-prop-falls-back-to-glyph pattern as
  `class-aae-a-offcanvas-trigger.php`. Icon sized `1em` in the twig so it
  scales with the button's own Font Size style, no separate icon-size prop.

**Step-change animation (added 2026-07-20, rebuilt CSS-only later the same
day)** — plays when Next/Previous change the active step. Originally used
GSAP as a deliberate, explicit exception to Motion policy's "no GSAP for
core form submit" rule (at the user's direct request, "gsap use koro"),
then was **fully removed and rebuilt as a pure CSS transition** (also
user-requested: "use css animation, remove gsap , it still has issue") —
there is no GSAP dependency anywhere in the Form Builder now, and Motion
policy's "no GSAP" rule holds without exception.

- **Per-step selectable**, not a global form setting: `step_transition`
  prop on `e-aae-a-form-step` (enum `none` / `fade` / `slide` /
  `fade-slide`, default `fade-slide`) — read via
  `data-aae-form-step-transition` on the ARRIVING step (see
  `aae-a-form-step.html.twig`), so a wizard can mix effects per step (e.g.
  a punchier slide on most steps, `none` on a review step for instant
  feedback). Direction (`+1` Next / `-1` Prev) is inferred at runtime, not
  stored — Next always reads as moving forward, Previous as moving back,
  regardless of which step's transition is playing.
- **Implementation**: `animateStepChange()` in `lib/multi-step.js` sets the
  FROM state on the incoming step's inline styles (`transitionDuration`,
  `opacity`, `transform: translateX(...)`), forces a reflow
  (`toStep.offsetHeight`), then flips to the TO state one `requestAnimationFrame`
  later (double-rAF, so the browser commits the FROM state as a real paint
  before starting the transition — a same-frame write would get coalesced
  into a single paint and never animate). Completion is detected via a
  `transitionend` listener scoped to `toStep`'s `transform`/`opacity`
  properties, with a `setTimeout` safety net (`TRANSITION_MS + 120`) in case
  `transitionend` never fires (hidden tab, ancestor `display:none` mid-
  transition, etc.) — the form must never get stuck mid-transition forever.
  `prefersReducedMotion()` and `step_transition === 'none'` both fall back
  to an instant class-toggle swap, calling `onDone()` synchronously.
- **CSS side** (`form.scss`): `transition-property: transform, opacity` is
  scoped to `form.aae-form-step-anim-run .aae-form-step-active` — a class
  added by the runtime only for the animation's duration (removed on
  finish) — specifically so a step's transform/opacity never transitions
  outside an explicit Next/Previous action. This matters because
  `resyncSteps()`'s instant reconciliation (editor polling, GOTCHA #2)
  toggles the SAME `aae-form-step-active` class on unrelated repaints; if
  the transition-property applied unconditionally, every poll-tick
  class-toggle would visibly animate too.
- **Layout containment during the transition**: both the outgoing and
  incoming step are briefly `aae-form-step-active` at once (so they can
  cross-fade/slide against each other), which would otherwise fight the
  form's own `flex-direction: row; flex-wrap: wrap` base style (two
  flex-item steps visible → layout jump/height jump for the transition's
  duration). Fixed with a transition-scoped class pair in `form.scss`:
  `form.aae-form-step-transitioning` (added/removed only for the
  animation's duration) pins the OUTGOING step `position: absolute`
  (`:not(.aae-form-step-incoming)` — the incoming one stays in normal
  flow, so the form keeps the arriving step's real height throughout,
  never collapsing to 0). Both classes are removed in `finish()` (the
  `transitionend`/safety-timer completion handler), at which point
  `resyncSteps()` also runs to reconcile nav-button hidden-state/labels —
  normal flex flow resumes immediately.

**GOTCHA #3 — the outgoing step visibly overlapped/misaligned with the
incoming one during the cross-fade (reported live on a real page,
2026-07-20).** Root cause: the FIRST version of the containment fix above
set `top: 0; left: 0; width: 100%` in plain CSS on the outgoing step —
those percentages/offsets resolve against the form's own BORDER box once
`position: absolute` applies, but the form has `padding: 20px` in its base
style, so the outgoing step's edges landed 20px further out than its
sibling (which stays in normal, padding-respecting flex flow). Confirmed
via Playwright: outgoing step measured `1110px` wide starting at the
form's left edge while the incoming one was `1070px` inset by 20px — a
visible edge mismatch during the overlap window, exactly matching the
"overlap/conflict" report. **Fix**: `animateStepChange()` captures the
outgoing step's REAL `getBoundingClientRect()` a moment before switching
it to `position: absolute` (while it's still in normal flow) and sets
`top`/`left`/`width` inline from that, converted to form-relative
coordinates — pixel-perfect regardless of the form's padding/border/gap,
and immune to future Style-panel edits. `form.scss`'s rule now only sets
`position: absolute` itself, nothing else. Verified with a Playwright
regression across desktop/tablet/mobile: outgoing/incoming widths match
to 0.00px at every sampled frame of the transition; the LEFT position
legitimately drifts during a `slide`/`fade-slide` transition — that's the
animation's own intended motion (the CSS `translateX` offset), not a bug,
and settles back to the exact pre-transition position once the transition
completes (confirmed: `opacity: 1`, `position: relative` — fully reset;
Chromium reports the resting `transform` as the identity matrix
`matrix(1, 0, 0, 1, 0, 0)` rather than the literal string `none` on an
element with a reachable `transition-property: transform` — visually
identical, not a residual transform).

**GSAP removed entirely (2026-07-20, same day, user-requested: "use css
animation, remove gsap , it still has issue")** — after the GOTCHA #3 fix
above, the user asked for GSAP to be dropped in favor of pure CSS despite
the fix being verified. `getGsap()`/`gsap.timeline()`/`gsap.set()` were all
deleted from `lib/multi-step.js`; `class-atomic.php`'s `aae-a-form` entry
no longer declares `'gsap'` in `script_deps`. The rect-capture fix from
GOTCHA #3 (form-relative `top`/`left`/`width` on the outgoing step) was
KEPT as-is — it's independent of which animation engine drives the
transition. Re-verified with the same Playwright regression (desktop/
tablet/mobile): 0.00px width drift, no horizontal overflow, correct
final-state reset, and a real mid-flight opacity/transform sample
confirming the CSS transition actually runs (not an instant swap).
- `resyncSteps()` (the editor re-render healing path, GOTCHA #2 above)
  deliberately NEVER animates — it's the instant, idempotent
  ground-truth reconciliation the polling loop calls every tick, and must
  stay side-effect-free w.r.t. motion or every poll tick would replay a
  transition. Only the explicit `goNext()`/`goPrev()` user-action paths
  call `animateStepChange()`.
- Both new/changed files: `lib/multi-step.js` (rewritten twice — first to
  add GSAP, then to remove it in favor of `transitionend` + inline
  `transform`/`opacity` styles), `class-aae-a-form-step.php`
  (`step_transition` prop + `Select_Control`), `aae-a-form-step.html.twig`
  (data attr), `form.scss` (containment rules + the scoped
  `transition-property` declaration), `class-atomic.php` (no longer
  declares a `gsap` script dependency for `aae-a-form`).
- Both preset JSONs (`multi-step-contact.json`, `multi-step-lead.json`)
  were updated to place explicit `e-aae-a-form-next` / `-prev` widgets in
  every step (in an `aae-form-step-nav-row` flexbox), demonstrating the
  real-widget path rather than relying on the fallback.

**GOTCHA #1 — applying a preset to an ALREADY-initialized form never bound
steps via the MutationObserver.** `initForm`'s one-time `aaeFormReady` guard
means `bindStepsFor` (the shared helper wiring `initSteps`) never re-runs
once a form has already initialized — and empirically, Elementor's
canvas re-render after "Apply Preset" does NOT surface the new step nodes
as an observable `childList` mutation on `document.body` (confirmed via a
diagnostic MutationObserver: 87 nodes added during a preset apply, ZERO of
them contained `[data-aae-form-step]` — the existing form container is
reused/repainted, not replaced as a discrete addable node). Fixed with an
**editor-only 1-second polling fallback** (`isEditMode()` gate) that
re-scans `form.aae-a-form` and calls `bindStepsFor` on any not yet
`aaeStepsBound` — safe because `initSteps`/`bindStepsFor` are fully
idempotent and cheap (early-return under 2 steps). The existing
"late-added `select[multiple]`" MutationObserver branch right above this
one in `form.js` was NOT changed/removed — it may or may not have the same
underlying issue, untested; the step-specific late-MutationObserver branch
is ALSO still present (harmless, just apparently never the one that fires)
alongside the polling fallback that actually does the job.

**Schema**: `Schema_Walker::collect()` threads a `$current_step` accumulator
down the recursion (same technique as the pro Conditional Display engine's
`Engine.php::walk_containers()` ancestor walk) — entering an
`e-aae-a-form-step` sets `$step_scope` to that element's raw id and appends
`{key, title}` to a new `$steps` array; every field built while inside that
scope gets a `'step' => (string) $step` entry. The top-level schema gains a
`'steps'` key (empty array for ordinary single-page forms — no migration
needed, no separate is-multi-step flag). Validator/Rest.php were NOT
changed — final submit still re-validates every field regardless of step
(existing safety-net behavior), the new `step` field is additive metadata
only, not yet consumed server-side (no per-step server gate exists — the
non-negotiable requirement is specifically about the FRONTEND Next-button
gate, confirmed sufficient by re-reading the requirement wording).

### Form presets

`inc/AtomicWidgets/Widgets/Form/presets/`: `contact`, `newsletter`,
`all-fields` (every field type, single column), `all-fields-columns`
(same fields, paired into two-column `e-flexbox` rows with
`e-div-block` columns — desktop `width: calc(50% - 8px)` via the
custom-unit trick, mobile variant `width: 100%`; ids re-keyed
`aae-cols-*` so both all-fields presets can coexist on a page).
`all-fields-columns.json` is GENERATED from `all-fields.json` — script:
scratchpad `make-columns-preset.js` pattern (pairs list + row/column
wrappers). Regenerate rather than hand-editing both.

### Hard "do not" rules (SSR §34, consolidated with the PRD's own list)

- Build the full product in one pass.
- Use public `admin-ajax.php` for submissions.
- Modify unrelated widgets/modules, or refactor the whole plugin, while
  building this feature.
- Depend on Elementor Pro form controls.
- Load assets globally — load form assets only on pages/previews that
  actually have an AAE Form (reuses
  [On-demand asset loading](#on-demand-asset-loading)).
- Add GSAP to MVP submit.
- Run external actions (email/webhook/Sheets) synchronously inside the
  submit request, and never let one of them failing delete or lose an
  already-saved submission.
- Make validation messages removable widgets.
- Store raw IP by default — only with explicit admin opt-in.
- Hide security failures behind a generic "success" in admin logs — log the
  real reason for admins even when the visitor sees a safe generic message.

---

## AAE Popup — global popup system (PRO, AtomicV4, shipped 2026-07)

Site-wide popup system for atomic elements, lives in the PRO plugin:
`animation-addons-for-elementor-pro/inc/AtomicV4/Popup/` +
`inc/AtomicV4/Support/TriggerMap.php` +
`src/modules/atomic-v4/popup{,-editor,-settings-core,-list}.js`. Full
design/data contract:
`animation-addons-for-elementor-pro/docs/atomic-v4/popup-developer.md`.
**Feature-frozen as of 2026-07-16** — consolidation only (tests,
performance, simplification, docs); no new popup features without an
explicit product decision.

Key facts (read the doc before touching):

- Popups are **AAE Builder templates** (this plugin's `wcf-addons-template`
  CPT), template type `aae-popup` ("Popup — Atomic") registered via the
  existing `wcf_builder_template_types` filter. Distinct from the legacy v3
  `popup` type — do not merge them.
- Trigger = "AAE Popup" panel section (`aae_v4_popup_{enabled,action,target}`
  props) on the canonical atomic types; renders NO custom attrs — configs go
  into `window.AAE_V4_POPUP` keyed by `data-interaction-id` via `TriggerMap`
  (atomic widgets are Twig-rendered, custom wrapper attrs are impossible).
  `TriggerMap` is the shared engine a future Click Reveal reuses.
- **Atomic CSS for footer-rendered documents does NOT happen by itself**
  (Elementor Pro's own popup is broken this way — elementor#35397).
  `Popup/Registry.php` fixes it two ways: Tier 1 re-fires
  `do_action('elementor/post/render', $popup_id)` during the host post's
  render pass (mirrors core's Components module; cached via `Cache_Validity`
  root key `aae-v4-popup-refs`); Tier 2 prints inline CSS at `wp_footer` via
  the public `Styles_Renderer` for theme-builder/archive contexts. Any new
  feature that renders an Elementor document outside the main loop needs the
  same treatment.
- wp_footer order: popups print at prio 3, trigger maps at prio 5, scripts 20.
- **One shared settings modal, three entry points:** the premium tabbed
  modal (`popup-settings-core.js`, shared ES module) is the ONLY chrome
  editing UI — opened from the editor's floating button
  (`popup-editor.js`, editor-only seams via `opts`), the popup-list row
  action, and the classic edit screen's metabox launcher button (both via
  `popup-list.js` + `AAE_V4_POPUP_LIST`). The old metabox form is gone.
- **Every popup asset has a tracked SOURCE** (`assets/css/` and
  `assets/build/` are gitignored): modal CSS source is
  `src/modules/atomic-v4/popup-settings-modal.css` (CSS-only webpack
  entry); frontend chrome CSS source is `src/scss/atomic-v4-popup.scss`
  (gulp). After a pull: `npm run build` + `npx gulp buildCss`. A
  hand-authored file directly under `assets/` will be missing on every
  other machine (caused the "modal styles break on another PC" bug).
- Animation: 4 CSS presets (stagger/blur/spring/pop) + none; per-popup
  `anim_duration` (150–900 ms) / `anim_easing` (smooth/spring/ease) ride
  as inline `--dur`/`--ease` vars; runtime close-fallback reads `--dur`.
- Usage tab (list/metabox modal): on-demand host scan
  (`Registry::hosts_of_popup` — SQL LIKE prefilter matching BOTH target
  shapes, query `"id":{"$$type":"number","value":N}` AND string
  `"aae_v4_popup_target":{"$$type":"string","value":"N"}`, verified by
  `parse_post_refs`, revisions excluded) — NOT the volatile render cache.
- Teardown pattern (leak fixes 2026-07-16): timers/listeners armed by an
  open/close cycle must be cancellable by the next cycle —
  `cancelCloseFinish()` in popup.js, `psetCloseTimer` in
  popup-settings-core.js, `disposeEditorHooks()` in popup-editor.js.
- Verified end-to-end via WP-CLI selftest + Playwright; regression suite:
  `E:\Local Testing\run-popup-sweep.mjs` runs all `verify-popup-*.mjs`
  (~19 tests). Suite conventions (learned from a fixture-drift sweep,
  2026-07-16 — keep them when writing new tests): tests SET their own
  fixture state up front (`set-popup-chrome.php`,
  `set-popup-auto-fixtures.php`, `make-host-page.php`) instead of assuming
  it; list-screen navigation uses `edit.php?...&s=<popupId>` (the popup's
  title must contain its ID — pagination breaks bare list URLs as junk
  templates accumulate); every page sets
  `page.setDefaultTimeout(120_000)` (slow editor boots; also rescues the
  `waitForFunction(fn, {timeout})` misuse — options is the 3rd arg, a
  2nd-arg object is silently treated as `arg` leaving the 30s default).
- Fixtures on the dev site: popup 8244 ("AAE Popup Selftest #8244") +
  page 8245; popup 9074; auto popups 8304/8305/8306 + page 8303; popup
  8662 (atomic ✕). Trigger-host pages are GENERATED by
  `make-host-page.php <popupId> <slug> <heading>` (idempotent by slug):
  9161 `aae-popup-scan-host`→9074, 9265 `aae-close-host`→8662. The helper
  wraps the heading in an `e-div-block` (bare root widgets don't render)
  and uses the `html-v3` title envelope (a raw string title renders
  empty); host page titles must never contain the trigger heading text
  (the theme's H1 steals `text=` clicks in Playwright).

## Elementor core reference (Atomic v4 architecture)

A full clone of the official Elementor development repo lives at:

```
e:\Local Sites\app\public\wp-content\plugins\elementor-src-repo\
```

- **What it is:** `github.com/elementor/elementor`, branch `main` — the
  development version (e.g. 4.3.0 while the installed runtime plugin
  `elementor/` is 4.1.4). `git log` / `git pull` it to track upcoming
  architecture. This is the source of truth for **Atomic v4** APIs.
- **Where to look:**
  - PHP atomic core: `modules/atomic-widgets/` — prop types
    (`prop-types/*.php`), the style schema (`styles/style-schema.php`),
    style transformers (`props-resolver/transformers/styles/`), element
    base classes (`elements/base/`), styles manager + base-styles cache
    (`styles/atomic-styles-manager.php`, `styles/atomic-widget-base-styles.php`).
  - Editor (TS/React): `packages/packages/core/*` — `editor-canvas`,
    `editor-editing-panel`, `editor-styles-repository`, `editor-elements-panel`, …
  - Docs: `docs/`, `AGENTS.md` at repo root.
- **When an atomic API question comes up** (valid style keys, prop-type
  shapes, base-style pipeline, why a definition silently fails), read the
  answer HERE first instead of guessing. Example: "why doesn't my
  `define_base_styles()` work" → `styles/style-schema.php` shows there is
  no `flex-grow`/`flex-shrink`/`flex-basis` key, only the `flex` shorthand
  (`Flex_Prop_Type`), and `height` must be a `Size_Prop_Type` — one invalid
  key fails the whole definition silently.
- **Style-key/format quick answer:** the complete verified tables (every
  valid key, its prop type, every enum value, generate() shapes for
  Background/Dimensions/Border_Radius/Flex/gap, and the list of keys that
  DON'T exist) are pre-digested in
  `animation-addons-for-elementor-pro/docs/atomic-v4/atomic-style-schema-reference.md` —
  check there before grepping the schema.
- **Caveat:** the RUNTIME is the installed `elementor/` plugin, which is
  older than this repo. Before using an API found here, confirm it exists
  in the installed version too.

---

## Quick reference

### To add a control to an existing effect
1. Schema.php — add const + prop registration
2. Controls.php — add control to the relevant section
3. Render.php — add data-attr in the relevant `build_*_attrs()` method
4. editor-bridge/features.js — add to the feature's `attrMap`
5. Effect JS — read the new data-attr in `read*()`

### To add a new widget type to an existing effect
1. Controls.php — add type to the `in_array()` check in `inject_controls()`
2. Render.php — add type to the `in_array()` check in `inject_into_html()`
3. editor-bridge/features.js — add type to the feature's `widgetTypes`

### To turn auto-replay on/off for an effect
- Set `autoReplaySetting` to the Boolean prop name in `features.js` (e.g.
  `'aae_text_enable_editor'`) to enable, or `null` to disable.

### To build the AAE Form Builder
See [AAE Atomic Form Builder (planned — new widget family)](#aae-atomic-form-builder-planned--new-widget-family).
Follow the **Widgets/** container+children pattern, not the effect recipe —
implement one milestone at a time from the SSR-doc order listed there. Full
spec: `C:\Users\UseR\Documents\atomic-form\AAE_Atomic_Form_Builder_Claude_PRD_SSR_Pack\`.

### To add presets to a NATIVE atomic widget (e-heading, e-button, …)
No code needed — drop JSONs in a folder named after the element type:
```
inc/AtomicWidgets/Presets/<element-type>/*.json   ← folder name IS the key
```
- `Atomic\Presets\Controls` (inc/Atomic/Presets/) injects the "Presets"
  section via `elementor/atomic-widgets/controls` for any native type that
  has ≥1 JSON there; `class-atomic.php::get_widget_presets()` scans the
  same folders and keys each preset by the folder name (no
  `detect_primary_widget_type()` detection — native types aren't `e-aae-a-*`).
- JSON format: same two formats as AAE-widget presets (Elementor native
  export with a flex wrapper preferred). Reload the editor — no build.
- **Model shape:** native atomic widgets inside the JSON must keep the
  export shape `{ "elType": "widget", "widgetType": "e-heading", … }`
  (exports produce this automatically). Do NOT hand-convert to
  `"elType": "e-heading"` — atomic containers' `getChildType()` whitelists
  `'widget'`, not the native atomic type names, so the atomic shape makes
  the v1 `addElement` delegate into a widget view and crash
  (`addElement is not a function` → apply silently does nothing).
- AAE's own widgets (`e-aae-a-*`) are deliberately skipped by the injector;
  they place the section themselves in `define_atomic_controls()` and keep
  their JSONs in `Widgets/<Name>/presets/` (see the add-widget-presets skill).
- **Animated presets** (keyframes, ::before/::after layers, descendant
  :hover — things atomic styles can't express): bake the CSS into the
  preset via the AAE **Custom CSS extension** props on the element that
  owns the effect — `aae_custom_css_enable: {$$type:'aae-rj',
  value:{desktop:true}}` + `aae_custom_css_css: {…value:{desktop:'<css>'}}`.
  Use the literal token `selector` in the CSS; the runtime replaces it with
  `[data-interaction-id="<id>"]`, so `selector:hover .child {…}` and
  top-level `@keyframes` both work. To target a child, give it a plain hook
  class in `classes` (e.g. `aae-team-overlay`) — hook classes survive style
  id regeneration. NO .css files, no Preset_Styles entry. References:
  `e-button/shine-pulse.json`, `e-flexbox/team-card.json`.
