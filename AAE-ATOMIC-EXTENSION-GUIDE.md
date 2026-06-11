# AAE Atomic Extension — Complete Architecture Guide

> **Plugin:** Animation Addons for Elementor  
> **Path:** `wp-content/plugins/animation-addons-for-elementor/`  
> **Elementor Version:** v4+ (Atomic Widgets)  
> **Last Updated:** June 2026

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Directory Structure](#2-directory-structure)
3. [PHP Side — File-by-File](#3-php-side--file-by-file)
4. [JS Side — File-by-File](#4-js-side--file-by-file)
5. [Supporting Infrastructure](#5-supporting-infrastructure)
6. [Data Flow (End-to-End)](#6-data-flow-end-to-end)
7. [How to Add a New Extension](#7-how-to-add-a-new-extension)
8. [How to Add a Control to an Existing Extension](#8-how-to-add-a-control-to-an-existing-extension)
9. [Control Types Reference](#9-control-types-reference)
10. [Responsive System](#10-responsive-system)
11. [Key Conventions & Gotchas](#11-key-conventions--gotchas)
12. [Target Element Types](#12-target-element-types)

---

## 1. Architecture Overview

Each AAE atomic extension injects custom animation/effect controls into **every Elementor v4 atomic widget** (`e-heading`, `e-button`, `e-image`, etc.) without modifying Elementor core. The system uses:

- **PHP** for registering props, injecting control sections, and outputting per-element config data to the frontend.
- **JS (Editor)** for rendering responsive control UI inside the Elementor panel.
- **JS (Frontend)** for reading config data and applying the actual effect (GSAP animations, event listeners, etc.).

Each extension consists of **6 files** across 2 directories:

| Layer | Files | Purpose |
|-------|-------|---------|
| PHP (`inc/Atomic/{Name}/`) | `Schema.php`, `Controls.php`, `Render.php`, `Section_Anchor_Prop_Type.php` | Register props, inject editor section, output frontend config |
| JS Editor (`src/modules/atomic/extensions/{name}/`) | `config.js`, `predicates.js` | Define editor UI fields and visibility conditions |
| JS Effect (`src/modules/atomic/effects/{name}/index.js`) | `index.js` | Frontend runtime effect logic |
| Registration | `Bootstrap.php`, `Assets.php`, `editor-bridge.js`, `common.js` | Wire everything together |

---

## 2. Directory Structure

```
animation-addons-for-elementor/
├── inc/Atomic/
│   ├── Bootstrap.php                          # Registers all extensions + assets
│   ├── InteractionsMap.php                    # Outputs window.AAE_INTERACTIONS_{NS}[id] in footer
│   ├── Assets.php                             # Registers/enqueues JS bundles
│   ├── PropTypes/
│   │   ├── Responsive_Json_Prop_Type.php      # Custom prop type for responsive fields
│   │   └── Section_Anchor_Prop_Type.php       # Base sentinel prop type (abstract)
│   ├── Traits/
│   │   └── Responsive_Config.php              # Trait for building responsive config in Render.php
│   └── {ExtensionName}/                       # One folder per extension
│       ├── Schema.php                         # Registers prop definitions
│       ├── Controls.php                       # Injects editor panel section
│       ├── Render.php                         # Outputs frontend config data
│       └── Section_Anchor_Prop_Type.php       # Unique sentinel prop type
│
├── src/modules/atomic/
│   ├── common.js                              # Core runtime (registry, scan, helpers)
│   ├── editor-bridge.js                       # Editor↔preview bridge entry point
│   ├── editor-bridge/                         # Bridge internals
│   │   ├── disposables.js
│   │   ├── features.js
│   │   ├── helpers.js
│   │   └── settings-bridge.js
│   ├── responsive-section/                    # Responsive UI component system
│   │   ├── index.js                           # registerResponsiveSection()
│   │   ├── registry.js
│   │   ├── helpers.js                         # valueAt() helper
│   │   ├── ResponsiveSection.jsx
│   │   ├── ResponsiveRow.jsx
│   │   └── inputs/                            # Control input components
│   ├── extensions/{name}/                     # Editor config per extension
│   │   ├── config.js                          # Field definitions (UI controls)
│   │   └── predicates.js                      # Conditional visibility functions
│   └── effects/{name}/                        # Frontend effect per extension
│       └── index.js                           # read/bind/unbind/play/reset
│
└── assets/build/modules/atomic/               # Webpack output (built JS)
    ├── common.js
    ├── editor-bridge.js
    └── effects/
        ├── tilt.js
        ├── animation.js
        └── ...
```

---

## 3. PHP Side — File-by-File

### 3.1 `Schema.php` — Register Props

**Purpose:** Defines data fields (props) that Elementor stores for each widget instance.

**Hook:** `elementor/atomic-widgets/props-schema` filter

**Key Pattern:**
```php
namespace WCF_ADDONS\Atomic\{ExtensionName};

use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

final class Schema {
    // Constants for each field key
    const SECTION_ANCHOR = 'aae_tilt_section_anchor';
    const ENABLE         = 'aae_tilt_enable';
    const MAX            = 'aae_tilt_max';

    public function register(): void {
        add_filter('elementor/atomic-widgets/props-schema', [$this, 'add_props']);
    }

    public function add_props(array $schema): array {
        // Section anchor — sentinel for JS editor replacement
        $schema[self::SECTION_ANCHOR] = Section_Anchor_Prop_Type::make()->default('');

        // Responsive field — stores {desktop: val, tablet: val, mobile: val}
        $schema[self::ENABLE] = Responsive_JSON_Prop_Type::make()->default([
            'desktop' => false,
        ]);

        // Responsive number field
        $schema[self::MAX] = Responsive_JSON_Prop_Type::make()->default([
            'desktop' => '',
        ]);

        // Non-responsive boolean (e.g., "enable in editor")
        $schema['aae_tilt_enable_editor'] = Boolean_Prop_Type::make()->default(false);

        return $schema;
    }
}
```

**Field naming convention:** `aae_{extension}_{field}` (e.g., `aae_tilt_max`, `aae_tilt_glare`)

**Prop Types:**
| Prop Type | Use Case | Stored Shape |
|-----------|----------|-------------|
| `Responsive_Json_Prop_Type` | Responsive fields (per-breakpoint) | `{$$type: 'aae-rj', value: {desktop: ..., tablet: ..., mobile: ...}}` |
| `Section_Anchor_Prop_Type` | Sentinel for JS UI replacement | `{$$type: 'aae-section-aae-tilt', value: ''}` |
| `Boolean_Prop_Type` | Non-responsive booleans | `true` or `false` |

---

### 3.2 `Controls.php` — Inject Editor Panel Section

**Purpose:** Adds a named section to the Elementor editor panel.

**Hook:** `elementor/atomic-widgets/controls` filter (priority 10, 2 args)

**Key Pattern:**
```php
namespace WCF_ADDONS\Atomic\{ExtensionName};

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

final class Controls {
    public function register(): void {
        add_filter('elementor/atomic-widgets/controls', [$this, 'inject_controls'], 10, 2);
    }

    public function inject_controls(array $controls, $element) {
        // Only inject into supported atomic widget types
        if (!in_array($element->get_element_type(), Bootstrap::target_element_types(), true)) {
            return $controls;
        }

        $controls[] = $this->build_section();
        return $controls;
    }

    private function build_section(): Section {
        return Section::make()
            ->set_label('Tilt')  // Section title in editor panel
            ->set_items([
                Text_Control::bind_to(Schema::SECTION_ANCHOR),  // Sentinel control
            ]);
    }
}
```

**How it works:**
- Creates ONE section with ONE `Text_Control` bound to the `SECTION_ANCHOR` prop
- The JS `registerResponsiveSection()` intercepts this control via its `$$type` and replaces it with the full responsive UI (`<ResponsiveSection>` component)
- The section label appears as the tab name in the Elementor panel

---

### 3.3 `Render.php` — Output Frontend Config

**Purpose:** Reads widget settings and outputs them as `window.AAE_INTERACTIONS_{NAMESPACE}[element_id]` in the footer.

**Hook:** `elementor/frontend/before_render` action

**Key Pattern:**
```php
namespace WCF_ADDONS\Atomic\{ExtensionName};

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;
use WCF_ADDONS\Atomic\Traits\Responsive_Config;

final class Render {
    use Responsive_Config;  // Provides emit_responsive(), envelope_to_map(), etc.

    public function register(): void {
        add_action('elementor/frontend/before_render', [$this, 'maybe_register']);
    }

    public function maybe_register($element): void {
        // Guard: check element type
        if (!in_array($element->get_element_type(), Bootstrap::target_element_types(), true)) {
            return;
        }

        $settings = $element->get_settings();

        // Extract responsive enable map
        $enabled_map = $this->envelope_to_map($settings[Schema::ENABLE] ?? null);
        
        // Skip if not enabled on any breakpoint
        if (!$this->any_breakpoint_enabled($enabled_map, $extra_bps)) {
            return;
        }

        // Build config array
        $config = $this->build_config($settings, $extra_bps, $enabled_map);

        // Register with InteractionsMap
        InteractionsMap::register('tilt', $element->get_id(), $config);

        // Enqueue the effect JS (frontend only, not admin)
        if (!is_admin()) {
            wp_enqueue_script('aae-effect-tilt');
        }
    }

    private function build_config(array $settings, array $extra_bps, array $enabled_map): array {
        $config = [];
        $cast_bool = static fn($v) => is_bool($v) ? $v : ($v === 'yes' || $v === 'true' || $v === 1 || $v === '1');
        $cast_num  = static fn($v) => is_numeric($v) ? (float) $v : $v;

        // Compute disabled breakpoints (for omitting non-enable fields)
        $disabled_bps = [...];

        // Emit each responsive field
        $this->emit_responsive($config, $settings, Schema::MAX, 'max', 15, $extra_bps, $cast_num, $disabled_bps);
        $this->emit_responsive($config, $settings, Schema::GLARE, 'glare', false, $extra_bps, $cast_bool, $disabled_bps);
        // ... more fields

        return $config;
    }
}
```

**`emit_responsive()` parameters:**
| Param | Description |
|-------|-------------|
| `&$config` | Config array being built (passed by reference) |
| `$settings` | Widget's raw settings |
| `$base_key` | Schema constant (e.g., `Schema::MAX`) |
| `$cfg_key` | Output key name (e.g., `'max'`) — used in JS as `cfg.max` |
| `$default` | Default value (desktop value omitted if it matches default) |
| `$extra_bps` | Active extra breakpoints from Elementor |
| `$cast` | Cast function (`$cast_bool` or `$cast_num`) |
| `$disabled_bps` | Breakpoints where extension is disabled |

**Output format** (in footer `<script>`):
```js
window.AAE_INTERACTIONS_TILT = Object.assign(window.AAE_INTERACTIONS_TILT || {}, {
    "abc123": {
        "enabled": true,
        "max": 15,
        "max_tablet": 10,
        "speed": 300,
        "glare": false
    }
});
```

---

### 3.4 `Section_Anchor_Prop_Type.php` — Sentinel Prop Type

**Purpose:** Provides a unique `$$type` key that the JS editor uses to identify which section to render.

```php
namespace WCF_ADDONS\Atomic\{ExtensionName};

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

class Section_Anchor_Prop_Type extends Base_Section_Anchor {
    public static function get_key(): string {
        return 'aae-section-aae-tilt';  // Must match config.js anchorKey
    }
}
```

**Convention:** `aae-section-aae-{name}` (kebab-case)

---

## 4. JS Side — File-by-File

### 4.1 `extensions/{name}/config.js` — Editor UI Definition

**Purpose:** Defines all the control fields that appear in the editor panel.

```js
import { isEnabled, isGlareEnabled } from './predicates';

const config = {
    anchorKey:  'aae-section-aae-tilt',    // Must match PHP Section_Anchor_Prop_Type::get_key()
    bindPrefix: 'aae_tilt_',                // Prepended to each field's `bind`

    fields: [
        {
            bind: 'enable',                 // Full key: aae_tilt_enable
            label: 'Enable Tilt',
            control: 'switch',              // Control type
            responsive: true,               // Has per-breakpoint values
            defaultValue: false,
            tab: 'Content',                 // Panel tab
            play_group: 'aae_tilt_',        // For play-button grouping
        },
        {
            bind: 'max',
            label: 'Max Tilt',
            control: 'slider',
            responsive: true,
            defaultValue: 15,
            when: isEnabled,                // Show only when tilt is enabled
            tab: 'Content',
        },
        {
            bind: 'enable_editor',
            label: 'Enable in Editor',
            control: 'switch',
            responsive: false,              // Non-responsive
            defaultValue: false,
            when: isEnabled,
            tab: 'Content',
        },
        {
            control: 'play-button',         // Special play button control
            when: showPlayButton,
            play_group: 'aae_tilt_',
        },
    ],
};

export default config;
```

**Field properties:**
| Property | Required | Description |
|----------|----------|-------------|
| `bind` | Yes (except play-button) | Field name appended to `bindPrefix` |
| `label` | Yes | Display label |
| `control` | Yes | Control type (see §9) |
| `responsive` | Yes | Whether field has per-breakpoint values |
| `defaultValue` | Yes | Default value |
| `when` | No | Predicate function `(settings, breakpoint) => boolean` |
| `tab` | No | Panel tab: `Content`, `Style`, or `Advanced` |
| `play_group` | No | Groups fields for play-button behavior |

---

### 4.2 `extensions/{name}/predicates.js` — Conditional Visibility

**Purpose:** Functions that determine whether a field should be visible based on current settings.

```js
import { valueAt } from '../../responsive-section/helpers';

export function isEnabled(s, bp) {
    return valueAt(s, 'aae_tilt_enable', bp) === true;
}

export function isGlareEnabled(s, bp) {
    return valueAt(s, 'aae_tilt_glare', bp) === true;
}

export function showPlayButton(s, bp) {
    return isEnabled(s, bp) && plainBool(s, 'aae_tilt_enable_editor');
}
```

- `valueAt(settings, key, breakpoint)` — reads a responsive value at a specific breakpoint
- `s` = settings object, `bp` = current breakpoint

---

### 4.3 `effects/{name}/index.js` — Frontend Effect Runtime

**Purpose:** Reads config from the `window.AAE_INTERACTIONS_{NS}` map and applies the effect to DOM elements.

```js
const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_TILT';
const PLAYED_KEY = '__aaeTiltPlayed';
const DISPOSE_KEY = '__aaeTiltDispose';

// Read config for an element → return parsed config or null (effect off)
function read(el) {
    const cfg = configFor(el, MAP);
    if (!cfg) return null;

    const enabled = pickConfigResponsive(cfg, 'enabled');
    if (!enabled) return null;

    return {
        enabled: true,
        max: Number(pickConfigResponsive(cfg, 'max') || 15),
        // ... more fields
    };
}

// Wire up event listeners / triggers
function bind(el, config) {
    // Set up GSAP, add event listeners, etc.
}

// Clean up listeners / triggers
function unbind(el) {
    // Remove event listeners, kill tweens
}

// Run the animation once (for play-button / preview)
function play(el, config) {
    unbind(el);
    bind(el, config);
    // Run GSAP timeline for preview
}

// Restore element to pre-animation state
function reset(el) {
    unbind(el);
    // Clean up DOM changes
}

// Register with the core runtime
window.AAEADDON.register({
    name: 'tilt',
    mapName: MAP,                    // Window variable name
    boundFlag: 'aae-tilt-bound',     // CSS class to prevent double-bind
    playedKey: PLAYED_KEY,           // Property name for cached tween
    read,
    bind,
    unbind,
    play,
    reset,
});
```

**Kind interface (what `register()` expects):**
| Property | Type | Description |
|----------|------|-------------|
| `name` | string | Unique identifier for logging/debugging |
| `mapName` | string | Window map key (e.g., `'AAE_INTERACTIONS_TILT'`) |
| `boundFlag` | string | CSS class added to bound elements (prevents double-bind) |
| `playedKey` | string | DOM element property for caching the active tween |
| `read(el)` | function | Returns config object or `null` (effect off) |
| `bind(el, config)` | function | Wire up triggers (event listeners, ScrollTrigger, etc.) |
| `unbind(el)` | function | Tear down triggers |
| `play(el, config)` | function | Run animation once (preview/play-button) |
| `reset(el)` | function | Restore to pre-animation state |

**Available `window.AAEADDON` helpers:**
| Helper | Description |
|--------|-------------|
| `getGsap()` | Returns `window.gsap` |
| `getScrollTrigger()` | Returns `window.ScrollTrigger` |
| `getSplitText()` | Returns `window.SplitText` |
| `currentBreakpoint()` | Returns active breakpoint key |
| `configFor(el, mapName)` | Reads config from interactions map using `data-interaction-id` |
| `pickConfigResponsive(cfg, key)` | Picks value for active breakpoint with cascade fallback |
| `BP_CASCADE` | Breakpoint cascade order |

---

## 5. Supporting Infrastructure

### 5.1 `Bootstrap.php` — Master Registration

Registers all extensions in order: **Schema → Controls → Render** for each, then Assets.

```php
// Each extension follows this pattern:
(new \WCF_ADDONS\Atomic\Tilt\Schema())->register();
(new \WCF_ADDONS\Atomic\Tilt\Controls())->register();
(new \WCF_ADDONS\Atomic\Tilt\Render())->register();

// Assets loaded last
(new Assets())->register();
```

**Target element types** (atomic widgets that receive extensions):
```php
public static function target_element_types(): array {
    return ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'];
}
```

---

### 5.2 `InteractionsMap.php` — Frontend Config Output

Singleton that collects per-element configs and outputs them as inline `<script>` in `wp_footer`.

- **Namespace-based:** Each extension has its own namespace (e.g., `'tilt'`, `'text'`)
- **Window key rule:** Namespace `'tilt'` → `window.AAE_INTERACTIONS_TILT`
- **Merge-safe:** Uses `Object.assign()` so multiple elements don't clobber each other
- **Auto-hooks:** First `register()` call hooks `wp_footer` and `elementor/preview/footer`

---

### 5.3 `Assets.php` — JS Bundle Registration

**Key constants:**
| Constant | Description |
|----------|-------------|
| `HANDLE` | `'aae-atomic-common'` — core runtime handle |
| `BUILD_DIR` | `'assets/build/modules/atomic/'` |
| `EFFECT_BUNDLES` | Map of `handle → file path` for each effect |

**Effect bundle registration pattern:**
```php
const EFFECT_BUNDLES = [
    'aae-effect-tilt' => 'effects/tilt.js',
    'aae-effect-animation' => ['file' => 'effects/animation.js', 'deps' => ['SplitText']],
    // ...
];
```

**Loading behavior:**
- **Frontend:** `register_common()` registers all bundles; `Render.php` calls `wp_enqueue_script()` only for effects actually used
- **Editor:** `enqueue_all_in_editor()` blanket-loads everything (user may toggle any effect)
- **GSAP deps:** Auto-registers from Pro plugin if not already registered

---

### 5.4 `editor-bridge.js` — Editor Entry Point

Imports all `config.js` files and registers them:

```js
import tilt from './extensions/tilt/config';
registerResponsiveSection(tilt);
```

Also handles:
- Settings mirroring from editor panel to preview iframe
- Initial scan on `document:loaded`
- Cleanup on document switch / unload

---

### 5.5 `common.js` — Core Runtime

The always-loaded core that provides:

1. **Helper functions** on `window.AAEADDON`
2. **Kind registry** — `KINDS[]` array populated by effect bundles
3. **Scanner** — `scan(root)` finds `[data-interaction-id]` elements and binds them
4. **Rebinder** — `rebind(el)` destroys and re-binds all kinds on an element
5. **Replayer** — `replay(el)` force-replays animations (for play-button)
6. **Resetter** — `resetEl(el)` restores element to pre-animation state
7. **Responsive resize** — Listens to `window.resize`, detects breakpoint change, rebinds all elements

**Play group mapping** (in `isKindInPlayGroup`):
```js
if (group === 'aae_tilt_' && kindName === 'tilt') return true;
// Each extension needs an entry here
```

---

## 6. Data Flow (End-to-End)

```
┌─────────────────────────────────────────────────────────────┐
│                     EDITOR PANEL                             │
│                                                              │
│  config.js fields ──→ registerResponsiveSection()           │
│       │                replaces Section_Anchor               │
│       │                with <ResponsiveSection> UI           │
│       ▼                                                      │
│  User toggles/sliders ──→ Elementor saves props             │
│                           (Schema.php prop types)            │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                     FRONTEND RENDER                          │
│                                                              │
│  Render.php::maybe_register($element)                       │
│       │                                                      │
│       ├─ Reads $element->get_settings()                     │
│       ├─ Builds responsive config via emit_responsive()      │
│       ├─ InteractionsMap::register('tilt', $id, $config)    │
│       └─ wp_enqueue_script('aae-effect-tilt')               │
│                                                              │
│  Footer output:                                              │
│  <script>window.AAE_INTERACTIONS_TILT = {id: {...}}</script>│
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                     FRONTEND JS                              │
│                                                              │
│  common.js loads → sets up registry, helpers                 │
│  effects/tilt/index.js loads → registers kind               │
│       │                                                      │
│       ├─ read(el) → configFor(el, 'AAE_INTERACTIONS_TILT')  │
│       ├─ bind(el, config) → wire event listeners            │
│       └─ play(el, config) → run GSAP animation              │
│                                                              │
│  scan(document) finds [data-interaction-id] elements         │
│  and binds each matching kind                                │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. How to Add a New Extension

### Step-by-step checklist:

1. **Create PHP files** in `inc/Atomic/{Name}/`:
   - [ ] `Section_Anchor_Prop_Type.php` — subclass with unique `get_key()`
   - [ ] `Schema.php` — define all field constants and register props
   - [ ] `Controls.php` — inject section with anchor control
   - [ ] `Render.php` — build config and register with InteractionsMap

2. **Create JS editor files** in `src/modules/atomic/extensions/{name}/`:
   - [ ] `predicates.js` — visibility condition functions
   - [ ] `config.js` — field definitions with `anchorKey`, `bindPrefix`, `fields[]`

3. **Create JS effect file** in `src/modules/atomic/effects/{name}/`:
   - [ ] `index.js` — implement `read/bind/unbind/play/reset`, call `AAEADDON.register()`

4. **Register in infrastructure files:**
   - [ ] `Bootstrap.php` — add Schema/Controls/Render registration
   - [ ] `Assets.php` — add entry to `EFFECT_BUNDLES`
   - [ ] `editor-bridge.js` — import config + `registerResponsiveSection()`
   - [ ] `common.js` — add entry to `isKindInPlayGroup()`

### Template for `Section_Anchor_Prop_Type.php`:
```php
<?php
namespace WCF_ADDONS\Atomic\{Name};
use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base;
if (!defined('ABSPATH')) exit;

class Section_Anchor_Prop_Type extends Base {
    public static function get_key(): string {
        return 'aae-section-aae-{name}';
    }
}
```

### Template for `Bootstrap.php` entry:
```php
// {Name}
(new \WCF_ADDONS\Atomic\{Name}\Schema())->register();
(new \WCF_ADDONS\Atomic\{Name}\Controls())->register();
(new \WCF_ADDONS\Atomic\{Name}\Render())->register();
```

### Template for `Assets.php` entry:
```php
'aae-effect-{name}' => 'effects/{name}.js',
```

### Template for `editor-bridge.js` entry:
```js
import {name}Section from './extensions/{name}/config';
registerResponsiveSection({name}Section);
```

### Template for `common.js` play group entry:
```js
if (group === 'aae_{name}_' && kindName === '{name}') return true;
```

---

## 8. How to Add a Control to an Existing Extension

### Example: Adding a "border_radius" slider to Tilt

**1. Schema.php** — Add constant and prop registration:
```php
const BORDER_RADIUS = 'aae_tilt_border_radius';

// In add_props():
$schema[self::BORDER_RADIUS] = Responsive_Json_Prop_Type::make()->default([
    'desktop' => '',
]);
```

**2. config.js** — Add field definition:
```js
{
    bind: 'border_radius',
    label: 'Border Radius',
    control: 'slider',
    responsive: true,
    defaultValue: 0,
    when: isEnabled,
    tab: 'Style',
},
```

**3. Render.php** — Add emit_responsive call in build_config():
```php
$this->emit_responsive(
    $config, $settings, Schema::BORDER_RADIUS, 'borderRadius', 0, $extra_bps,
    $cast_num,
    $disabled_bps
);
```

**4. effects/{name}/index.js** — Read and use the new config value:
```js
// In read():
borderRadius: Number(val('borderRadius', 0)),

// In bind():
el.style.borderRadius = config.borderRadius + 'px';
```

**No changes needed** in: Bootstrap.php, Controls.php, Assets.php, editor-bridge.js, common.js, Section_Anchor_Prop_Type.php, predicates.js (unless you need conditional visibility).

---

## 9. Control Types Reference

Available `control` values in `config.js` fields:

| Control Type | Description | JS Component |
|-------------|-------------|--------------|
| `switch` | Toggle on/off | Renders switch input |
| `slider` | Numeric range slider | Renders slider input |
| `select` | Dropdown select | Renders select input |
| `text` | Text input | Renders text input |
| `color` | Color picker | Renders color input |
| `choose` | Icon/button chooser | Renders choose buttons |
| `dimensions` | Box dimensions (top/right/bottom/left) | Renders dimension inputs |
| `textarea` | Multi-line text | Renders textarea |
| `code` | Code editor | Renders code editor |
| `media` | Media upload | Renders media picker |
| `link` | Link picker | Renders link input |
| `play-button` | Special animation preview button | Renders play button |

---

## 10. Responsive System

### How responsive values work:

1. **Storage:** Each responsive field stores `{desktop: val, tablet: val, mobile: val, ...}` via `Responsive_Json_Prop_Type`

2. **Config output (PHP):** `emit_responsive()` in `Responsive_Config` trait:
   - Desktop value emitted as `cfg.key`
   - Per-breakpoint overrides emitted as `cfg.key_tablet`, `cfg.key_mobile`, etc.
   - Values matching defaults are omitted (JS supplies defaults)
   - Values matching cascaded parent are omitted

3. **Config reading (JS):** `pickConfigResponsive(cfg, key)` in `common.js`:
   - Gets current breakpoint via `currentBreakpoint()`
   - Walks `BP_CASCADE` from smallest to largest
   - Returns first non-empty per-breakpoint override, falling back to desktop value

### Breakpoint cascade order (smallest → largest):
```
mobile → mobile_extra → tablet → tablet_extra → laptop → desktop
widescreen (standalone)
```

### Editor responsive UI:
- `ResponsiveSection.jsx` renders each field with responsive dots
- Each dot represents a breakpoint (desktop/tablet/mobile)
- Active breakpoint determines which value is edited

---

## 11. Key Conventions & Gotchas

### Naming Conventions:
- **PHP constants:** `aae_{extension}_{field}` (snake_case) — e.g., `aae_tilt_max`
- **Config keys (output):** camelCase — e.g., `maxGlare`, `borderRadius`
- **Section anchor key:** `aae-section-aae-{name}` (kebab-case)
- **Bound flag class:** `aae-{name}-bound` (kebab-case)
- **Window map:** `AAE_INTERACTIONS_{NAME}` (UPPER_SNAKE_CASE)
- **Played key:** `__aae{Name}Played` (camelCase)
- **Dispose key:** `__aae{Name}Dispose` (camelCase)
- **Play group:** `aae_{name}_` (snake_case with trailing underscore)
- **Effect handle:** `aae-effect-{name}` (kebab-case)

### Important Notes:
- **`Responsive_Json_Prop_Type`** defaults must include `'desktop'` key
- **`Boolean_Prop_Type`** is for NON-responsive booleans only
- **`Section_Anchor_Prop_Type`** value is never actually used — it's a sentinel
- **`emit_responsive()`** passes `&$config` by reference
- **Render.php** must enqueue effect script with `wp_enqueue_script()` NOT `wp_register_script()`
- **Effects must NOT `import` from `common.js`** — read helpers from `window.AAEADDON` at runtime
- **`unbind()` must be called inside `bind()`** to prevent double-binding
- **`InteractionsMap`** namespace must be lowercase and match the pattern for window key generation

### Common Pitfalls:
- Forgetting to add `play_group` mapping in `common.js` `isKindInPlayGroup()`
- Not adding the effect to `EFFECT_BUNDLES` in `Assets.php`
- Mismatch between `Section_Anchor_Prop_Type::get_key()` and `config.js` `anchorKey`
- Using wrong cast function in `emit_responsive()` (bool vs num)
- Forgetting `responsive: false` for non-responsive fields in `config.js`

---

## 12. Target Element Types

These are the Elementor v4 atomic widgets that receive AAE extensions:

| Element Type | Description |
|-------------|-------------|
| `e-heading` | Heading widget |
| `e-paragraph` | Paragraph/text widget |
| `e-button` | Button widget |
| `e-image` | Image widget |
| `e-svg` | SVG widget |
| `e-flexbox` | Flexbox container |
| `e-div-block` | Div block container |
| `e-grid` | Grid container |

Some extensions may target only specific types (e.g., TextAnimation targets heading-class widgets) — this filtering is done in `Render.php` and `Controls.php`.

---

## File Responsibility Quick Reference

| Task | File(s) to Modify |
|------|-------------------|
| Add a new extension | `Schema.php`, `Controls.php`, `Render.php`, `Section_Anchor_Prop_Type.php`, `config.js`, `predicates.js`, `effects/{name}/index.js`, `Bootstrap.php`, `Assets.php`, `editor-bridge.js`, `common.js` |
| Add a field/control | `Schema.php`, `config.js`, `Render.php`, `effects/{name}/index.js` |
| Change a label | `config.js` |
| Change default value | `Schema.php` (PHP default), `config.js` (JS default), `Render.php` (emit default) |
| Add conditional visibility | `predicates.js`, `config.js` (add `when` to field) |
| Change frontend behavior | `effects/{name}/index.js` |
| Change which widgets get extension | `Bootstrap.php` `target_element_types()` (global) or add type check in `Controls.php`/`Render.php` |
| Add a new prop type | `inc/Atomic/PropTypes/` |
| Fix responsive issues | `Responsive_Config.php` (PHP), `common.js` `pickConfigResponsive()` (JS) |