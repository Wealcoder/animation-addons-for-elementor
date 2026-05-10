# AAE — Elementor v4 Atomic Widget Extension Guide

> **Status:** Elementor v4 official docs এখনো release হয়নি। এই guide-টা actual v4 source code (`wp-content/plugins/elementor/modules/atomic-widgets/`) থেকে reverse-engineer করা।
>
> Last verified against: Elementor 4.0.7

---

## Table of Contents

1. [V3 → V4 — কী বদলেছে](#1-v3--v4--কী-বদলেছে)
2. [Atomic Widget Names (target করার জন্য)](#2-atomic-widget-names)
3. [PHP Hooks — Complete List](#3-php-hooks--complete-list)
4. [Available Controls](#4-available-controls)
5. [Available Prop Types](#5-available-prop-types)
6. [JS APIs (editor live preview-র জন্য)](#6-js-apis)
7. [Asset Enqueue Hooks](#7-asset-enqueue-hooks)
8. [Standard Patterns (use case → recipe)](#8-standard-patterns)
9. [Full Walkthrough — Animation Extender](#9-full-walkthrough)
10. [Pitfalls & Gotchas](#10-pitfalls--gotchas)
11. [Reference Files (verified examples in core)](#11-reference-files)

---

## 1. V3 → V4 — কী বদলেছে

| Aspect | V3 (`Widget_Base`) | V4 (`Atomic_Widget_Base`) |
|---|---|---|
| Controls definition | `_register_controls()` runtime method | `define_atomic_controls()` static-ish method, returns Section objects |
| Settings access | `$settings['key']` array | `$container->settings->get('key')` (JS) / `$element_data['settings']['key']` (PHP) |
| Render | PHP `render()` method writes HTML | Twig template (`*.html.twig`) — server-rendered, React-managed in editor |
| Per-widget hooks | `elementor/element/{name}/{section}/before_section_end` | ❌ **নেই** — সব global filter, widget name check করতে হয় |
| Wrapper class | `.elementor-element-{ID}` | `[data-interaction-id="{ID}"]` attribute |
| Editor JS | jQuery + Marionette (Backbone) | React + new `window.elementorV2` namespace |
| Live preview event | `elementor:loaded` jQuery event | ❌ Doesn't fire reliably — poll OR MutationObserver |
| Settings change event | `elementor.channels.editor.on('change')` | `$e.commands.on('run:after')` for `document/elements/settings` |

**Big idea:** V4 React-based, তাই অনেক jQuery-era hooks gone। Settings save হয় PHP-তে (filter দিয়ে), কিন্তু render হয় client-side React + Twig template দিয়ে।

---

## 2. Atomic Widget Names

`define_atomic_controls()` filter-এ `$element->get_name()` দিয়ে target করতে এই names ব্যবহার করো:

| Widget | `get_name()` |
|---|---|
| Heading | `e-heading` |
| Button | `e-button` |
| Image | `e-image` |
| Paragraph | `e-paragraph` |
| Divider | `e-divider` |
| SVG | `e-svg` |
| YouTube | `e-youtube` |
| Self-hosted Video | `e-self-hosted-video` |
| Form | `e-form` |
| Tabs | `e-tabs`, `e-tabs-menu`, `e-tab`, `e-tabs-content-area` |
| Containers | `e-flexbox`, `e-grid`, `e-div-block` |

পরিবর্তন check করতে: `wp-content/plugins/elementor/modules/atomic-widgets/elements/{NAME}/{NAME}.php`-এ `get_element_type()` method দেখো।

---

## 3. PHP Hooks — Complete List

### 3.1 Inject controls / props (most common need)

| Hook | Type | Purpose | Signature |
|---|---|---|---|
| `elementor/atomic-widgets/props-schema` | filter | Custom prop register (data layer) | `($schema: array): array` |
| `elementor/atomic-widgets/controls` | filter | Inject Section/Control in editor panel | `($controls: array, $element): array` |

**Verified:** `has-atomic-base.php:152` (controls), `has-atomic-base.php:307` (props-schema)

```php
add_action( 'elementor/init', function () {
    add_filter( 'elementor/atomic-widgets/props-schema', function ( array $schema ): array {
        if ( ! isset( $schema['my_prop'] ) ) {
            $schema['my_prop'] = String_Prop_Type::make()->default( '' );
        }
        return $schema;
    });

    add_filter( 'elementor/atomic-widgets/controls', function ( array $controls, $element ): array {
        if ( $element->get_name() !== 'e-heading' ) return $controls;

        $controls[] = Section::make()
            ->set_id( 'my_section' )
            ->set_label( __( 'My Section', 'textdomain' ) )
            ->set_items([
                Select_Control::bind_to( 'my_prop' )
                    ->set_label( __( 'Choose', 'textdomain' ) )
                    ->set_options([
                        ['value' => 'a', 'label' => 'Option A'],
                    ]),
            ]);
        return $controls;
    }, 10, 2 );
});
```

**কেন `elementor/init`-এ wrap?** `String_Prop_Type` etc. classes load হবে guarantee — এটাই Elementor-এর own promotions module pattern (`modules/promotions/module.php:230`)।

### 3.2 Style schema (for visual props that auto-render)

| Hook | Type | Purpose | Signature |
|---|---|---|---|
| `elementor/atomic-widgets/styles/schema` | filter | Add prop to Style tab (auto live preview!) | `($schema: array): array` |

**Verified:** `style-schema.php:34`

```php
add_filter( 'elementor/atomic-widgets/styles/schema', function ( array $schema ): array {
    $schema['my-css-prop'] = Color_Prop_Type::make();
    return $schema;
});
```

⚠️ **এই hook-এ register করলে user-এর Style tab-এ control আসবে, custom Settings panel-এ না।** Live preview free — framework auto CSS render করে transformer দিয়ে।

### 3.3 Style register / cache invalidation (advanced)

| Hook | Type | Purpose |
|---|---|---|
| `elementor/atomic-widgets/styles/register` | action | Per-element CSS register via Atomic_Styles_Manager |
| `elementor/atomic-widgets/styles/clear` | action | Manually invalidate styles cache |
| `elementor/atomic-widgets/styles/transformers/register` | action | Custom prop → CSS transformer register |
| `elementor/atomic_widgets/editor_data/element_styles` | filter | Modify per-element styles array sent to editor |
| `elementor/atomic-widgets/settings/transformers/classes` | filter | Modify how `classes` prop value transforms |
| `elementor/atomic-widgets/styles/transitions/allowed-properties` | filter | Whitelist transition properties |
| `elementor/atomic-widgets/frontend/loader/scripts/register` | action | Register frontend JS for atomic widgets |
| `elementor/atomic/dynamic_tags/select_control_options` | filter | Modify dynamic tags Select options |

### 3.4 Frontend rendering

| Hook | Type | Purpose |
|---|---|---|
| `elementor/frontend/the_content` | filter | Append/prepend HTML or `<style>` to rendered Elementor output (works in editor preview iframe AND frontend) |

⚠️ **Atomic widgets-এ `elementor/frontend/widget/before_render` action fire হয় না reliably।** তাই custom prop-এর CSS inject করতে হলে `the_content` filter-ই go-to।

```php
add_filter( 'elementor/frontend/the_content', function ( string $content ): string {
    if ( false === strpos( $content, 'data-interaction-id' ) ) return $content;

    $document = \Elementor\Plugin::$instance->documents->get_current();
    if ( ! $document ) return $content;

    $rules = [];
    walk_elements( $document->get_elements_data(), $rules );

    return empty( $rules ) ? $content
        : '<style id="my-styles">' . implode( '', $rules ) . '</style>' . $content;
});
```

---

## 4. Available Controls

`Elementor\Modules\AtomicWidgets\Controls\Types\` namespace থেকে:

| Control | Use for |
|---|---|
| `Select_Control` | Dropdown |
| `Text_Control` | Single-line text input |
| `Textarea_Control` | Multi-line text |
| `Number_Control` | Number input |
| `Switch_Control` | On/off toggle |
| `Toggle_Control` | Multi-option toggle group |
| `Link_Control` | URL + target |
| `Image_Control` | Image picker (media library) |
| `Svg_Control` | SVG picker |
| `Video_Control` | Video picker |
| `Size_Control` | Number + unit (px, em, %, etc.) |
| `Inline_Editing_Control` | Click-to-edit text on canvas (used by heading/button title) |
| `Html_Tag_Control` | h1/h2/.../div tag selector |
| `Date_Time_Control`, `Date_Range_Control`, `Time_Range_Control` | Date/time inputs |
| `Chips_Control`, `Query_Chips_Control` | Tag-style multi-select |
| `Query_Control` | Posts/terms picker |
| `Repeatable_Control` | Repeater for nested controls |
| `Email_Form_Action_Control` | Email action picker (form-only) |

All controls support fluent API:
```php
ControlClass::bind_to( 'prop_key' )
    ->set_label( 'Label' )
    ->set_placeholder( 'Hint' )
    ->set_meta( ['topDivider' => true] )  // optional
```

---

## 5. Available Prop Types

`Elementor\Modules\AtomicWidgets\PropTypes\` namespace।

### Primitives
- `Primitives\String_Prop_Type` — string with optional `enum()`
- `Primitives\Number_Prop_Type` — int/float
- `Primitives\Boolean_Prop_Type` — true/false
- `Primitives\String_Array_Prop_Type` — array of strings

### Composite
- `Color_Prop_Type` — color value
- `Size_Prop_Type` — `{size, unit}`
- `Dimensions_Prop_Type` — top/right/bottom/left
- `Background_Prop_Type` — full background spec
- `Border_Width_Prop_Type`, `Border_Radius_Prop_Type`
- `Box_Shadow_Prop_Type`, `Shadow_Prop_Type`, `Stroke_Prop_Type`
- `Image_Prop_Type`, `Image_Src_Prop_Type`, `Image_Attachment_Id_Prop_Type`
- `Video_Src_Prop_Type`, `Video_Attachment_Id_Prop_Type`, `Svg_Src_Prop_Type`
- `Link_Prop_Type`, `Url_Prop_Type`, `Email_Prop_Type`
- `Position_Prop_Type`, `Layout_Direction_Prop_Type`, `Flex_Prop_Type`
- `Date_Range_Prop_Type`, `Date_Time_Prop_Type`, `Time_Range_Prop_Type`
- `Classes_Prop_Type`, `Attributes_Prop_Type`
- `Union_Prop_Type` — multiple types acceptable
- `Options_Prop_Type` — predefined choices
- `Transform\Transform_Prop_Type`, `Transition_Prop_Type`
- `Filters\Filter_Prop_Type`, `Filters\Backdrop_Filter_Prop_Type`

### Common usage:
```php
String_Prop_Type::make()
    ->default( 'value' )
    ->enum( ['a', 'b', 'c'] )
    ->description( 'Help text' );

Color_Prop_Type::make();

Size_Prop_Type::make()->units( ['px', 'em', '%'] );
```

---

## 6. JS APIs

### 6.1 Editor (parent window)

| API | Available? | Use for |
|---|---|---|
| `window.elementor` | ✅ in v4 (legacy + reused) | Main editor object |
| `window.elementor.$preview[0].contentDocument` | ✅ | Preview iframe DOM access |
| `window.elementor.documents.getCurrent()` | ✅ | Current document container tree |
| `window.elementor.on('preview:loaded', fn)` | ✅ | Preview iframe ready event |
| `window.elementor.channels.editor.on('change', fn)` | ⚠️ Some changes fire, not all v4 atomic ones |
| `window.$e.commands.on('run:after', fn)` | ⚠️ Fires for many cases but **NOT** reliably for atomic widget settings changes |
| `window.elementorV2` | ✅ New v4 React namespace |
| `window.elementorV2.editorCanvas.settingsTransformersRegistry` | ✅ | Register prop value transformers (for predefined props like `classes`) |
| `window.elementorV2.utils.isProActive()` | ✅ | Pro detection |
| `window.elementorV2.editorNotifications.notify(...)` | ✅ | Show editor notification |

### 6.2 Settings access on a container

V4-এ container.settings shape vary করে। Robust read:

```js
function readSetting( container, key ) {
    var v;
    if ( container.settings && typeof container.settings.get === 'function' ) {
        try { v = container.settings.get( key ); } catch ( e ) {}
    }
    if ( typeof v === 'undefined' && container.settings && container.settings.attributes ) {
        v = container.settings.attributes[ key ];
    }
    if ( typeof v === 'undefined' && container.model && typeof container.model.get === 'function' ) {
        var s = container.model.get( 'settings' );
        if ( s && typeof s.get === 'function' ) { v = s.get( key ); }
        else if ( s && typeof s === 'object' ) { v = s[ key ]; }
    }
    // Atomic prop wrapper: { $$type: 'string', value: '...' }
    if ( v && typeof v === 'object' && 'value' in v ) v = v.value;
    return ( typeof v === 'string' ) ? v.trim() : v;
}
```

### 6.3 Live preview detection — recommended approach

Since `$e.commands.on` fails for atomic widget setting changes, **MutationObserver-ই reliable**:

```js
function installObserver( rebuildFn ) {
    var doc = window.elementor.$preview[0].contentDocument;
    var debounce;
    var mo = new MutationObserver( function () {
        clearTimeout( debounce );
        debounce = setTimeout( rebuildFn, 50 );  // debounce React rapid renders
    });
    mo.observe( doc.body, {
        childList: true, subtree: true, attributes: true,
        attributeFilter: ['data-interaction-id', 'class']
    });
}
```

### 6.4 Bootstrap pattern

V4-এ `elementor:loaded` jQuery event fire হয় না reliably। Polling করো:

```js
function bind() {
    if ( ! window.elementor ) return setTimeout( bind, 250 );
    // ... subscribe events here
}
jQuery( bind );
```

---

## 7. Asset Enqueue Hooks

| Hook | Where script/style runs | Use for |
|---|---|---|
| `wp_enqueue_scripts` | Frontend page only | Frontend CSS/JS |
| `elementor/preview/enqueue_styles` | Preview iframe `<head>` | CSS for editor live preview rendering |
| `elementor/preview/enqueue_scripts` | Preview iframe scripts | JS that runs INSIDE preview iframe (rare) |
| `elementor/editor/after_enqueue_scripts` | Editor parent window | Editor JS — has access to `window.elementor`, `$e`, `elementorV2` |
| `elementor/editor/after_enqueue_styles` | Editor parent window | Editor panel CSS |

**Common mistake:** Frontend CSS-এ enqueue করে `elementor/preview/enqueue_styles` skip করলে editor preview-তে CSS পাবে না। দুই জায়গায় enqueue করতে হবে।

---

## 8. Standard Patterns

### Pattern A — Custom Section + Control (data prop, no live render needed)

**Use when:** Save করার জন্য data dropdown (analytics tag, A/B variant id, custom attribute)

**Need:** ✅ props-schema + ✅ controls + ❌ JS + ❌ CSS

```php
add_action( 'elementor/init', function () {
    add_filter( 'elementor/atomic-widgets/props-schema', /* register prop */ );
    add_filter( 'elementor/atomic-widgets/controls', /* inject section + control */ );
});
```

Frontend-এ value চাইলে `the_content` filter দিয়ে read করো।

### Pattern B — Visual styling with Live Preview

**Use when:** Custom CSS class / animation / background that needs live editor update

**Need:** ✅ props-schema + ✅ controls + ✅ CSS file + ✅ JS bridge + ✅ frontend filter

| Layer | File | Job |
|---|---|---|
| PHP | `inc/extender.php` | Register prop + control + enqueue + the_content filter for frontend |
| CSS | `assets/css/extender.css` | Predefined classes / `@keyframes` |
| JS | `assets/js/extender.js` | Bridge — observe preview iframe, rebuild scoped `<style>` block |

This is **Pattern B = our animation extender's exact pattern**. Walkthrough in section 9 below.

### Pattern C — True Style prop (use Style tab, not custom section)

**Use when:** Standard CSS property (margin, color, font-size, etc.) — Elementor probably already provides it!

**Need:** ✅ styles/schema filter only

```php
add_filter( 'elementor/atomic-widgets/styles/schema', function ( array $schema ): array {
    $schema['my-css-prop'] = Color_Prop_Type::make();
    return $schema;
});
```

Live preview free — framework auto-render via transformers। **শুধু new CSS property যেটা style schema-এ নেই, তখন এটা use করো।**

### Pattern D — Simple inject without live preview (acceptable trade-off)

**Use when:** Internal admin tool, demo, low-frequency change

**Need:** Pattern A + frontend `the_content` filter + tell user "click Update to see preview refresh"

No JS = simplest possible code.

---

## 9. Full Walkthrough — Animation Extender

আমাদের actual implementation, Pattern B:

### File structure

```
animation-addons-for-elementor/
├── inc/
│   └── aae-atomic-extender.php       # PHP: prop + control + frontend + enqueue
├── assets/
│   ├── src/
│   │   ├── css/aae-atomic-extender.css      # Source CSS (gulp build skips)
│   │   └── js/aae-atomic-extender.js        # Source JS
│   ├── css/aae-atomic-extender.css   # Runtime CSS (enqueued)
│   └── js/aae-atomic-extender.js     # Runtime JS (enqueued)
└── class-plugin.php                  # require_once 'inc/aae-atomic-extender.php'
```

### PHP layer — `inc/aae-atomic-extender.php`

Responsibilities:
1. Register `aae_animation` prop in props-schema
2. Inject "AAE Animation" Section + Select control on `e-heading` widget
3. Enqueue CSS (frontend + preview iframe) and JS (editor only)
4. `the_content` filter — generate inline `<style>` for frontend rendering

### CSS layer — `assets/css/aae-atomic-extender.css`

Just `@keyframes` definitions. Loaded in BOTH frontend and preview iframe (via `wp_enqueue_scripts` + `elementor/preview/enqueue_styles`).

### JS layer — `assets/js/aae-atomic-extender.js`

Editor-only bridge:
1. Poll `window.elementor` until ready
2. Subscribe to `preview:loaded` event for initial sync
3. Install `MutationObserver` on preview iframe body (debounced 50ms)
4. On any trigger: walk container tree → find `e-heading` → read `aae_animation` value → write scoped `<style>` block in preview iframe `<head>`

### Why MutationObserver instead of `$e.commands`?

`$e.commands.on('run:after')` doesn't fire for v4 atomic widget setting changes (verified — the `event $e cmd` log never appeared in our debug output). MutationObserver catches the React re-render of the widget DOM and triggers our rebuild reliably.

### Why scoped `<style>` block instead of inline element style?

V4 React re-renders the widget DOM on every settings change, **wiping any inline styles on the wrapper**. A `<style>` block in `<head>` survives because React doesn't touch the head.

---

## 10. Pitfalls & Gotchas

### ❌ Don't do

- **`elementor/element/{name}/before_section_end`** — V3 only, doesn't exist for atomic widgets
- **`elementor/frontend/widget/before_render`** action — not reliably fired for atomic widgets (PHP)
- **`elementor:loaded`** jQuery event — doesn't fire reliably in v4 React bootstrap
- **Inline `style.backgroundColor = ...`** on widget wrapper — React re-renders wipe it
- **`.elementor-element-{ID}`** selector — V4 wrapper doesn't have this class anymore
- **Filter without `elementor/init` wrapper** — atomic-widget classes might not be loaded yet
- **`add_filter` with bound method when class hierarchy may not exist** — guard with `class_exists()`

### ✅ Do

- **Target via `[data-interaction-id="{ID}"]`** in CSS selectors
- **Use `the_content` filter** for frontend rendering — universal, fires for editor preview iframe too
- **Use MutationObserver** for live editor preview — most reliable v4 detection
- **Debounce MutationObserver callbacks** — React fires many micro-mutations, debounce 50ms
- **Wrap registration in `elementor/init`** action — ensures atomic-widgets module loaded
- **Check widget name in controls filter** — global filter, fires for ALL atomic widgets
- **Enqueue styles in BOTH `wp_enqueue_scripts` AND `elementor/preview/enqueue_styles`** — frontend + preview iframe need same CSS

### ⚠️ Watch out

- Atomic prop values in PHP `$element_data['settings']` can be either flat string OR `['$$type' => 'string', 'value' => '...']` — handle both
- Section's `set_id()` must match a known section ID if you want to add to an EXISTING section (rare in v4 since most widgets only have `'settings'` section ID)
- `Inline_Editing_Control` only works for HTML/text props with proper render binding in Twig

---

## 11. Reference Files

These are real Elementor files I learned from. Inspect them when designing new extensions:

### Core hooks definition
- `wp-content/plugins/elementor/modules/atomic-widgets/elements/base/has-atomic-base.php` — `controls` + `props-schema` filter sources
- `wp-content/plugins/elementor/modules/atomic-widgets/styles/style-schema.php` — `styles/schema` filter source
- `wp-content/plugins/elementor/modules/atomic-widgets/styles/atomic-styles-manager.php` — `styles/register` action source
- `wp-content/plugins/elementor/modules/atomic-widgets/styles/atomic-widget-styles.php` — `styles/clear`, `editor_data/element_styles`

### Real extension examples by Elementor itself
- `wp-content/plugins/elementor/modules/promotions/module.php:230` — **canonical inject pattern** (props-schema + controls)
- `wp-content/plugins/elementor/modules/variables/hooks.php:75` — styles/schema augmentation
- `wp-content/plugins/elementor/modules/variables/hooks.php:66` — transformers/register
- `wp-content/plugins/elementor/modules/atomic-widgets/dynamic-tags/dynamic-tags-module.php:65` — transformers/register

### Available widget definitions (for `get_element_type()` reference)
- `wp-content/plugins/elementor/modules/atomic-widgets/elements/atomic-button/atomic-button.php`
- `wp-content/plugins/elementor/modules/atomic-widgets/elements/atomic-heading/atomic-heading.php`
- (etc. — see folder list in section 2)

### Style system internals (advanced)
- `wp-content/plugins/elementor/modules/atomic-widgets/styles/style-definition.php`
- `wp-content/plugins/elementor/modules/atomic-widgets/styles/style-variant.php`
- `wp-content/plugins/elementor/modules/atomic-widgets/elements/atomic-button/atomic-button.php:98` — `define_base_styles()` example

### Editor JS bundle (transformer registry)
- `wp-content/plugins/elementor/assets/js/atomic-widgets-editor.js:1821` — `settingsTransformersRegistry.get('classes')` usage
- `wp-content/plugins/elementor/assets/js/packages/editor-canvas/editor-canvas.js` — `settingsTransformersRegistry.register("classes", ...)` registration

---

## Quick Start Cheat Sheet

```php
// === PHP: register prop + control ===
add_action( 'elementor/init', function () {
    add_filter( 'elementor/atomic-widgets/props-schema', function ( $schema ) {
        $schema['my_prop'] = String_Prop_Type::make()->default( '' );
        return $schema;
    });

    add_filter( 'elementor/atomic-widgets/controls', function ( $controls, $element ) {
        if ( $element->get_name() !== 'e-heading' ) return $controls;
        $controls[] = Section::make()
            ->set_label( 'My Section' )
            ->set_items([
                Select_Control::bind_to( 'my_prop' )
                    ->set_label( 'Choose' )
                    ->set_options([['value' => 'a', 'label' => 'A']]),
            ]);
        return $controls;
    }, 10, 2 );
});

// === PHP: frontend rendering (no live preview) ===
add_filter( 'elementor/frontend/the_content', function ( $content ) {
    if ( false === strpos( $content, 'data-interaction-id' ) ) return $content;
    // ... walk document, generate <style>, prepend
    return $content;
});

// === PHP: enqueue ===
add_action( 'wp_enqueue_scripts',                  fn() => wp_enqueue_style( 'h', URL.'/css/h.css' ) );
add_action( 'elementor/preview/enqueue_styles',    fn() => wp_enqueue_style( 'h', URL.'/css/h.css' ) );
add_action( 'elementor/editor/after_enqueue_scripts', fn() => wp_enqueue_script( 'h', URL.'/js/h.js', ['jquery','elementor-editor'], '1.0', true ) );
```

```js
// === JS: editor live preview bridge ===
( function ( $ ) {
    function bind() {
        if ( ! window.elementor ) return setTimeout( bind, 250 );

        var doc, debounce;
        function rebuild() {
            doc = doc || window.elementor.$preview[0].contentDocument;
            // ... walk elementor.documents.getCurrent().container, write <style> in doc.head
        }

        window.elementor.on( 'preview:loaded', rebuild );
        new MutationObserver( function () {
            clearTimeout( debounce );
            debounce = setTimeout( rebuild, 50 );
        }).observe( window.elementor.$preview[0].contentDocument.body, {
            childList: true, subtree: true, attributes: true
        });
    }
    $( bind );
})( jQuery );
```

---

**Version history:**
- v1 (2026-05-10) — Initial guide based on Elementor 4.0.7 source. Animation extender example working.

**Maintainer note:** Re-verify hook names + signatures every Elementor major version until Elementor publishes official v4 docs.
