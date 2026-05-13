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
| Data-attrs not on rendered HTML | `Render::inject_into_html()` not handling the widget type, or `$attrs` empty |
| Animation works in preview but not on frontend | `Render.php` not calling `wp_enqueue_script()` |
| Animation works on frontend but not in editor preview | `editor-bridge/features.js` missing widget type in `widgetTypes` |
| Per-breakpoint values don't preview | `applySettingsToDom()` writing only desktop attrs — check `AAE_RESPONSIVE_BASES` includes the base |
| Number input rounds decimals | Add label to `FLOAT_LABEL_BASES` in `float-step-fix.js` |
| Two tabs visually selected | Don't fight React on `aria-selected` / `Mui-selected` — use `!important` on the indicator, not class strips |

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
