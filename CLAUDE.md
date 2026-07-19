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
used by `Offcanvas`, `NestedSlider`, `FlipBox` (container widget + real child
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

**Pro:** advanced field types — **Date, Time, File Upload, Range, Rating,
Signature, Repeater, HTML, Password, Calculation, Step, Address, Country**;
Premium presets; Private Storage + Google Drive/AWS S3 adapters; Google
Sheets (OAuth); Advanced Webhook (custom headers/auth/mapping/conditions/
retry, n8n/Zapier/Make presets); Action Logs + Retry Queue UI; **Validation
Pro** (regex, real phone validation API, country restriction, disposable
email, domain rules); Telegram/WhatsApp/Slack; **Conditional Display Engine**;
**Multi-Step Forms**; Calculator/Quote forms; Analytics/Lead Management; AI
Copilot; HTML Email Template Builder.

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

### Multi-Step Forms — hard requirements noted 2026-07-19 (build on the
Conditional Display engine + AAEFormHooks; not started)

1. **Next only after the current step validates** — advancing to the next
   step MUST run that step's field validation first and block on errors
   (user requirement, non-negotiable).
2. **A Multi-Step preset must ship with the feature** (user requirement).
3. Panel copy/UX matters: clear per-control descriptions like the
   Conditional Display section (user asked for good in-panel guidance).

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
