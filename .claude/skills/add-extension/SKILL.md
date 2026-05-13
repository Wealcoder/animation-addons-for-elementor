---
name: add-extension
description: Add a new animation extension (e.g. tilt, sticky, image-hover) to the v4 atomic-widgets pipeline. Generates PHP Schema/Controls/Render, the editor section (config.js + predicates.js), and the frontend JS effect bundle — wires them into Bootstrap.php, Assets.php, webpack, and the editor-bridge. Mirrors how RegularAnimation / TextAnimation / Parallax are structured.
---

# Add a new atomic animation extension

This skill encodes the full pipeline for adding an animation extension to
the v4 atomic-widgets system. An extension lives across **8 files in 4
layers** (PHP schema/controls/render, JS frontend effect, JS editor
extension config + predicates, JS editor-bridge feature, webpack/asset
wiring, bootstrap registration). Missing one layer produces silent
failures (no section in panel, no live preview, no animation on publish).

The architecture already shipped:

- **PHP**: per-extension dir under `inc/Atomic/<Name>/` with Schema +
  Controls + Render + Section_Anchor_Prop_Type.
- **Storage**: every responsive prop is `Responsive_Json_Prop_Type`
  (`$$type: 'aae-rj'`) holding a `{ desktop, tablet, mobile, ... }` map
  of bare scalars. Non-responsive props are Elementor's primitives
  (Boolean_Prop_Type, String_Prop_Type, etc.).
- **Editor UI**: ONE placeholder `Text_Control` per section, bound to a
  unique `Section_Anchor_Prop_Type`. React replacement registry swaps it
  for `<ResponsiveSection>` driven by `extensions/<name>/config.js`.
- **Frontend dispatch**: `Render.php` hooks `elementor/frontend/before_render`
  (fires for widgets AND containers), calls
  `InteractionsMap::register('<ns>', $id, $cfg)`. A single inline `<script>`
  in `wp_footer` writes `window.AAE_INTERACTIONS_<NS>`.
- **Frontend runtime**: per-effect bundle (`src/modules/atomic/effects/<name>/index.js`)
  registers a "kind" via `window.AAEADDON.register({ name, mapName,
  boundFlag, playedKey, read, play, bind, unbind, reset })`. `common.js`
  scans `[data-interaction-id]` nodes, calls `kind.read(el)` per element,
  and dispatches to `bind(el, cfg)`.

**Always read `CLAUDE.md` at the project root first** for the canonical
file map, naming conventions, and storage shape diagrams.

## Before you start

Confirm with the user (don't guess defaults — they bake in silently):

1. **Extension name** — kebab-case (e.g. `tilt`, `image-hover`,
   `sticky-element`). Used for:
   - PHP namespace: `WCF_ADDONS\Atomic\Tilt`
   - PHP dir: `inc/Atomic/Tilt/`
   - prop prefix: `aae_tilt_` (short — storage cost matters)
   - JS effect bundle: `src/modules/atomic/effects/tilt/`
   - JS editor extension: `src/modules/atomic/extensions/tilt/`
   - InteractionsMap namespace: `'tilt'` (one short word)
   - JS handle: `aae-effect-tilt`
   - Map global: `window.AAE_INTERACTIONS_TILT`
   - Section anchor `$$type`: `aae-section-aae-tilt`

2. **Target widget types** — subset of
   `Bootstrap::target_element_types()`: `e-heading`, `e-paragraph`,
   `e-button`, `e-image`, `e-svg`, `e-flexbox`, `e-div-block`, `e-grid`.

3. **Field list** — for each field:
   - `bind` (short suffix, joins prefix)
   - `label`
   - `control` type (`switch`, `number`, `text`, `select`, `multi-select`, `repeater`)
   - `defaultValue`
   - `responsive` (defaults true — most fields are; mark `false` only
     for global on/off / markers / enable-editor toggles)
   - visibility predicate (`when: someFn`) if conditional
   - `options` if select / multi-select

4. **Trigger model** (frontend) — what makes the animation play?
   - `on_page_load` — fires once at DOMContentLoaded
   - `on_scroll` (scroll-tied) — fires once when entering view
   - `play_with_scroll` (scrub) — tween progress follows scroll position
   - `mouseover` / `click` — DOM events on `el` or `triggerSelector`
   - `in-view` — fallback (intersection observer)
   - **always-on** — like tilt; no trigger, bind on first scan

5. **Auto-replay in editor?** — when settings change, should the editor
   preview replay the animation live? If yes:
   - Add an `aae_<name>_enable_editor` Boolean prop.
   - Set `autoReplaySetting` on the feature.
   - Add `{ control: 'play-button', when: someFn }` row to config.js.

6. **V3 source file** to port from (if applicable). Usually under
   `animation-addons-for-elementor-pro/inc/extensions/` and
   `animation-addons-for-elementor-pro/assets/js/`.

If any answer is unclear, ask. Defaults baked into a port that should
match V3 will silently diverge.

## The 8 steps (in order)

Do them in this order. Each step builds on the previous one, and
verifying mid-way catches integration errors early.

---

### Step 1 — Schema (PHP)

**File:** `inc/Atomic/<Name>/Schema.php`

Defines prop constants and registers them with the
`elementor/atomic-widgets/props-schema` filter.

Pattern (every extension is variations of this):

```php
<?php
namespace WCF_ADDONS\Atomic\Tilt;

use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Schema {

    /* ---- section anchor (unique $$type for the React replacement) ---- */
    const TILT_SECTION_ANCHOR = 'aae_tilt_section_anchor';

    /* ---- props (responsive: aae-rj; non-responsive: native types) ---- */
    const TILT_ENABLE        = 'aae_tilt_enable';        // responsive
    const TILT_MAX           = 'aae_tilt_max';           // responsive
    const TILT_PERSPECTIVE   = 'aae_tilt_perspective';   // responsive
    const TILT_SCALE         = 'aae_tilt_scale';         // responsive
    const TILT_SPEED         = 'aae_tilt_speed';         // responsive
    const TILT_ENABLE_EDITOR = 'aae_tilt_enable_editor'; // NON-responsive bool

    public function register(): void {
        add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_tilt_props' ] );
    }

    public function add_tilt_props( array $schema ): array {
        // Anchor — placeholder for the React replacement (Controls.php binds
        // a Text_Control to this).
        $schema[ self::TILT_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

        // Responsive props — value is a per-bp map of bare primitives.
        // Set defaults only for `desktop`; other bps inherit via cascade.
        $schema[ self::TILT_ENABLE      ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => false ] );
        $schema[ self::TILT_MAX         ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 15 ] );
        $schema[ self::TILT_PERSPECTIVE ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 1000 ] );
        $schema[ self::TILT_SCALE       ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 1.05 ] );
        $schema[ self::TILT_SPEED       ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => 400 ] );

        // Non-responsive bool — uses Elementor's native Boolean_Prop_Type.
        $schema[ self::TILT_ENABLE_EDITOR ] = Boolean_Prop_Type::make()->default( false );

        return $schema;
    }
}
```

**Also create:** `inc/Atomic/<Name>/Section_Anchor_Prop_Type.php`

```php
<?php
namespace WCF_ADDONS\Atomic\Tilt;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Section_Anchor_Prop_Type extends Base_Section_Anchor {
    public static function get_key(): string {
        return 'aae-section-aae-tilt';
    }
}
```

The `get_key()` value MUST match the editor extension config's `anchorKey`
(see step 5).

**Verify:** `php -l inc/Atomic/Tilt/Schema.php` (or load the Elementor
editor — atomic widgets crash visibly if schema is malformed).

---

### Step 2 — Controls (PHP)

**File:** `inc/Atomic/<Name>/Controls.php`

Registers ONE section per extension with ONE placeholder `Text_Control`
bound to the section anchor prop. The React replacement registry swaps
it for `<ResponsiveSection>` at render time.

```php
<?php
namespace WCF_ADDONS\Atomic\Tilt;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Controls {
    const TD = 'animation-addons-for-elementor';

    public function register(): void {
        add_filter( 'elementor/atomic-widgets/controls', [ $this, 'inject_controls' ], 10, 2 );
    }

    public function inject_controls( array $controls, $element ) {
        if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
            return $controls;
        }
        if ( ! class_exists( Section::class ) ) {
            return $controls;
        }

        $type = $element->get_element_type();
        if ( in_array( $type, Bootstrap::target_element_types(), true ) ) {
            $controls[] = $this->build_tilt_section();
        }

        return $controls;
    }

    private function build_tilt_section(): Section {
        return Section::make()
            ->set_label( __( 'Tilt', self::TD ) )
            ->set_items( [
                Text_Control::bind_to( Schema::TILT_SECTION_ANCHOR ),
            ] );
    }
}
```

**Notes:**
- Use `Bootstrap::target_element_types()` if the extension targets all
  atomic widgets. If it targets a subset (e.g. text only),
  define a static method like `Schema::widget_types()` and use that.
- The placeholder Text_Control is invisible at runtime — React replaces
  it before render.

**Verify:** reload the editor, select a target widget — the new section
should appear in the panel.

---

### Step 3 — Render (PHP)

**File:** `inc/Atomic/<Name>/Render.php`

Hook `elementor/frontend/before_render` (NOT `elementor/widget/render_content`
— that filter doesn't fire for atomic containers). Build a cfg object
mirroring what the JS reader expects, register it with `InteractionsMap`,
and enqueue the effect bundle.

```php
<?php
namespace WCF_ADDONS\Atomic\Tilt;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Render {

    public function register(): void {
        add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
    }

    public function maybe_register( $element ): void {
        if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
            return;
        }
        $type = $element->get_element_type();
        if ( ! in_array( $type, Bootstrap::target_element_types(), true ) ) {
            return;
        }

        // get_settings() (raw saved props) — NOT get_atomic_settings() which
        // strips unrecognized $$types. Reading raw lets us walk the aae-rj
        // envelope ourselves.
        $settings = method_exists( $element, 'get_settings' )
            ? $element->get_settings()
            : [];

        $config = $this->build_config( $settings );
        if ( empty( $config ) ) return;

        $id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
        if ( '' === $id ) return;

        InteractionsMap::register( 'tilt', $id, $config );

        if ( ! is_admin() ) {
            wp_enqueue_script( 'aae-effect-tilt' );
        }
    }

    private function build_config( array $settings ): array {
        // Read the enable envelope. Bail if no breakpoint has it on.
        $enable_map = $this->envelope_to_map( $settings[ Schema::TILT_ENABLE ] ?? null );
        if ( ! $this->any_breakpoint_enabled( $enable_map ) ) {
            return [];
        }

        $extra_bps = $this->get_extra_breakpoints();
        $config    = [];

        $this->emit_responsive(
            $config, $settings, Schema::TILT_ENABLE, 'enabled', false, $extra_bps,
            static fn( $v ) => (bool) $v
        );
        $this->emit_responsive(
            $config, $settings, Schema::TILT_MAX, 'max', 15, $extra_bps,
            static fn( $v ) => is_numeric( $v ) ? (float) $v : null
        );
        // … repeat for perspective / scale / speed

        // Non-responsive editor flag.
        $editor = $settings[ Schema::TILT_ENABLE_EDITOR ] ?? null;
        if ( is_array( $editor ) && ! empty( $editor['value'] ) ) {
            $config['enableEditor'] = true;
        }

        return $config;
    }

    // Copy emit_responsive / envelope_to_map / cascade_parent /
    // get_extra_breakpoints / any_breakpoint_enabled from
    // inc/Atomic/Parallax/Render.php — they're feature-agnostic.
}
```

**Notes:**
- The cfg the runtime reads is **flat**: `{enabled: true, max: 15,
  max_mobile: 8, ...}`. Per-bp keys carry the `_<bp>` suffix and are
  emitted only when they differ from the cascaded parent (dedup).
- `emit_responsive` is the canonical helper — copy it verbatim from
  `Parallax/Render.php`. It handles cascade walking + dedup + cast.

**Verify:** save a widget with the extension enabled, view the published
frontend page, view source — `window.AAE_INTERACTIONS_TILT = {...}`
should appear inline, keyed by the widget id.

---

### Step 4 — Editor extension (JS) — config.js

**File:** `src/modules/atomic/extensions/<name>/config.js`

Declarative table that drives `<ResponsiveSection>`. One row per field;
each row knows its label, input, default, visibility predicate.

```js
/* eslint-env browser */

import { isTiltEnabled, isTiltEditorOn } from './predicates';

const config = {
    // MUST match Section_Anchor_Prop_Type::get_key() from step 1.
    anchorKey:  'aae-section-aae-tilt',
    // Prefix joined with each field's `bind` to form the prop key.
    // e.g. bind:'max' → prop 'aae_tilt_max'.
    bindPrefix: 'aae_tilt_',
    fields: [
        { bind: 'enable',      label: 'Enable Tilt',  control: 'switch', defaultValue: false },

        { bind: 'max',         label: 'Max Tilt',     control: 'number', defaultValue: 15,   when: isTiltEnabled },
        { bind: 'perspective', label: 'Perspective',  control: 'number', defaultValue: 1000, when: isTiltEnabled },
        { bind: 'scale',       label: 'Scale',        control: 'number', defaultValue: 1.05, when: isTiltEnabled },
        { bind: 'speed',       label: 'Speed (ms)',   control: 'number', defaultValue: 400,  when: isTiltEnabled },

        // Non-responsive editor toggle + Play Now button.
        { bind: 'enable_editor', label: 'Enable On Editor', control: 'switch',
          responsive: false, defaultValue: false, when: isTiltEnabled },
        { control: 'play-button', when: isTiltEditorOn },
    ],
};

export default config;
```

**Supported `control` types:** `switch`, `number`, `text`, `select`,
`multi-select`, `repeater`, `play-button`. See
`responsive-section/inputs/` for the implementations.

---

### Step 5 — Editor extension (JS) — predicates.js

**File:** `src/modules/atomic/extensions/<name>/predicates.js`

Pure functions `(settings, activeBreakpoint) => boolean` that gate row
visibility. Read responsive values via `valueAt` / `valueEq` / `valueIn`
from `responsive-section/helpers` (they walk the cascade).

```js
/* eslint-env browser */

import { valueAt } from '../../responsive-section/helpers';

/** True when tilt is enabled at the active breakpoint (with cascade). */
export function isTiltEnabled(s, bp) {
    return valueAt(s, 'aae_tilt_enable', bp) === true;
}

/** True when tilt + editor-replay are both on (drives Play Now visibility). */
export function isTiltEditorOn(s, bp) {
    if (!isTiltEnabled(s, bp)) return false;
    const v = s?.aae_tilt_enable_editor;
    // Non-responsive bool: { $$type: 'boolean', value: true }
    if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
    return !!v;
}
```

**Notes:**
- `valueAt(settings, propKey, bp)` reads the `aae-rj` envelope's
  `value[bp]` walking the bp cascade.
- Non-responsive booleans need manual envelope unwrap (see helper above).
- Keep predicates pure — no side effects, no DOM access.

---

### Step 6 — Frontend effect bundle (JS)

**File:** `src/modules/atomic/effects/<name>/index.js`

Implements `read` / `play` / `bind` / `reset` and registers a kind with
`window.AAEADDON`. NEVER `import` from `common.js` — read helpers off
`window.AAEADDON` at module init (helpers are exposed there so each
effect bundle stays tiny).

```js
/* eslint-env browser */

const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const TILT_MAP    = 'AAE_INTERACTIONS_TILT';
export const TILT_PLAYED = '__aaeTiltPlayed';

function r(cfg, key, fallback) {
    const v = pickConfigResponsive(cfg, key);
    return (v === undefined || v === '') ? fallback : v;
}

export function readTilt(el) {
    const cfg = configFor(el, TILT_MAP);
    if (!cfg) return null;
    const enabled = pickConfigResponsive(cfg, 'enabled');
    if (!enabled) return null;
    return {
        max:         Number(r(cfg, 'max', 15)),
        perspective: Number(r(cfg, 'perspective', 1000)),
        scale:       Number(r(cfg, 'scale', 1.05)),
        speed:       Number(r(cfg, 'speed', 400)),
    };
}

export function playTilt(el, config) {
    // For trigger-based effects (fade/move/spin/etc.), use GSAP fromTo.
    // For always-on effects (tilt, parallax), wire pointer listeners here.
    const gsap = getGsap();
    if (!gsap) return;
    // ... implementation
}

export function bindTilt(el, config) {
    // For always-on effects, just call play directly (no trigger wiring).
    // For trigger-based, import wireTrigger from './triggers' (see
    // effects/animation/triggers.js) and dispatch.
    playTilt(el, config);
}

export function resetTilt(el) {
    if (el[TILT_PLAYED]) {
        el[TILT_PLAYED].kill?.();
        delete el[TILT_PLAYED];
    }
    // Remove any listeners installed by bindTilt.
}

function cleanupTilt(el) {
    // Called by common.js rebind() before re-binding. Tear down anything
    // bindTilt installed (event listeners, observers, ScrollTriggers).
    resetTilt(el);
}

// Self-register at module load. window.AAEADDON is guaranteed present
// because common.js is a script dep of this bundle (Assets.php).
window.AAEADDON.register({
    name:       'tilt',
    mapName:    TILT_MAP,
    boundFlag:  'aae-tilt-bound',
    playedKey:  TILT_PLAYED,
    read:       readTilt,
    play:       playTilt,
    bind:       bindTilt,
    unbind:     cleanupTilt,
    reset:      resetTilt,
});
```

**For trigger-based effects** (when the animation only plays on scroll /
hover / click), import the shared `wireTrigger` and `modeFor` /
`resolveTriggerEl` from `effects/animation/triggers.js`. See
`effects/animation/regular.js` for the canonical pattern. (Note: if
adding the effect outside `effects/animation/`, you'll need to extract
`triggers.js` to a shared location — currently it's co-located.)

---

### Step 7 — Wire it all up

**a. webpack.config.js** — add the entry:

```js
entry: {
    ...
    "modules/atomic/effects/tilt": "./src/modules/atomic/effects/tilt/index.js",
},
```

**b. inc/Atomic/Assets.php** — register the bundle handle:

```php
const EFFECT_BUNDLES = [
    'aae-effect-animation' => 'effects/animation.js',
    'aae-effect-tilt'      => 'effects/tilt.js',  // ← add
];
```

The handle MUST match what `Render.php::maybe_register` enqueues
(`wp_enqueue_script('aae-effect-tilt')`).

**c. inc/Atomic/Bootstrap.php** — register the three PHP classes:

```php
( new \WCF_ADDONS\Atomic\Tilt\Schema()   )->register();
( new \WCF_ADDONS\Atomic\Tilt\Controls() )->register();
( new \WCF_ADDONS\Atomic\Tilt\Render()   )->register();
```

**d. src/modules/atomic/editor-bridge.js** — import + register the editor section:

```js
import tiltSection from './extensions/tilt/config';
// ... existing imports

registerResponsiveSection( tiltSection );
```

**e. src/modules/atomic/editor-bridge/features.js** — add a feature entry:

```js
export const FEATURES = [
    // ... existing entries
    {
        name: 'tilt',
        widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image',
                      'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
        enableSetting:     'aae_tilt_enable',
        autoReplaySetting: 'aae_tilt_enable_editor', // or null
        mapName:           'AAE_INTERACTIONS_TILT',
        buildConfig:       buildTiltConfig,
        findTarget:        findByInteractionId,
    },
];
```

**f. features.js** — implement `buildTiltConfig`:

Mirrors `Render.php::build_config` but reads from the live settings
model. Use `envelopeToMap` / `readAt` / `emitResponsive` helpers
already in `features.js`. Pattern:

```js
function buildTiltConfig(settings) {
    const enabledAtDesktop = readAt(settings, 'aae_tilt_enable', 'desktop', false);
    const enabledAnyBp     = BPS.some((bp) => readAt(settings, 'aae_tilt_enable', bp, false));
    if (!enabledAtDesktop && !enabledAnyBp) return null;

    const cfg = {};
    const TILT_RESPONSIVE = {
        aae_tilt_enable:      { configKey: 'enabled',     default: false },
        aae_tilt_max:         { configKey: 'max',         default: 15 },
        aae_tilt_perspective: { configKey: 'perspective', default: 1000 },
        aae_tilt_scale:       { configKey: 'scale',       default: 1.05 },
        aae_tilt_speed:       { configKey: 'speed',       default: 400 },
    };
    emitResponsive(cfg, settings, TILT_RESPONSIVE);

    if (plain(settings, 'aae_tilt_enable_editor')) cfg.enableEditor = true;
    return cfg;
}
```

The shape MUST match what `Render.php` emits — the editor and frontend
runtime share the same reader (`readTilt`).

---

### Step 8 — Build & verify

```bash
npm run build
```

The build should complete clean (only the pre-existing asset-size
warnings for the dashboard bundle). Check that
`assets/build/modules/atomic/effects/tilt.js` and
`tilt.asset.php` were generated.

## Final verification checklist

After all 8 steps, walk through this:

| Check | Where |
|---|---|
| Schema loads without error | Editor opens, no React crash |
| Section appears in panel | Select a target widget, panel shows new section |
| Fields render with correct controls | Switch / Number / Select inputs match config.js |
| Visibility predicates work | Toggle Enable → dependent fields appear/hide |
| Settings persist | Save + reload → values stick |
| Live editor preview | Edit a field → preview iframe updates without save (if autoReplaySetting) |
| Play Now button | Toggle Enable On Editor → Play Now button appears, plays animation |
| Per-breakpoint inherit | Switch to Tablet/Mobile → empty inputs show desktop value as italic placeholder |
| Frontend cfg on publish | View source → `window.AAE_INTERACTIONS_TILT = {...}` keyed by widget id |
| Conditional bundle load | Page without the effect: no `effects/tilt.js`. Page WITH effect: bundle present. |
| Animation runs on frontend | Visit published page → animation plays |
| Bound class on frontend | Inspect element → `aae-tilt-bound` class present after scan |

## Debugging probes

**Editor (outer frame):**
```js
// Saved settings for the selected widget:
elementor.documents.getCurrent().container.children
    .findRecursive((c) => c.id === 'YOUR_ID')?.settings.toJSON()
```

**Editor (preview iframe):**
```js
window.AAE_INTERACTIONS_TILT          // map keyed by widget id
window.AAEADDON.configFor(el, 'AAE_INTERACTIONS_TILT')
```

**Frontend (published page):**
```js
window.AAE_INTERACTIONS_TILT          // same shape as editor
document.querySelectorAll('.aae-tilt-bound').length  // bound count
```

If the map is populated but no element is bound, common.js's scan didn't
run yet — call `window.AAEADDON.scan(document)` to force a rescan.

## Don't

- **Don't use `elementor/widget/render_content`** — atomic containers
  (e-flexbox/div-block/grid) extend `Element_Base`, not `Widget_Base`,
  so that filter never fires for them. Always use
  `elementor/frontend/before_render`.
- **Don't import from `common.js`** in an effect bundle — would inline
  ~1.5 KB of helpers into every effect. Read off `window.AAEADDON`.
- **Don't store responsive values without `Responsive_Json_Prop_Type`** —
  the old per-bp-suffix shape (`aae_tilt_max_mobile` as separate props)
  was removed. One `aae-rj` envelope per prop holds all breakpoints.
- **Don't set `responsive: false`** on a field unless you mean it (markers,
  enable-editor, play-button rows). Defaults to true.
- **Don't add `assets => scripts`** to controls — atomic widgets ignore
  that array. Use `wp_enqueue_script` from `Render.php::maybe_register`.
- **Don't add legacy/migration code** — there is no legacy data; this is
  pre-release. Refuse to add back-compat shims.
- **Don't put `console.log` / `console.debug`** in the editor-bridge
  or effect bundles. Only `console.warn` for genuine error states.
