# Elementor v4 Atomic Widget Extension — বাংলা Learning Guide

> **Audience:** যারা v3-তে inject control / custom widget বানিয়েছে এবং এখন v4-এ migrate করতে চাইছে।
> **Status:** Elementor 4.0.7 source code reverse-engineer করে লেখা। Official docs এখনো release হয়নি।
> **Last updated:** 2026-05-10

---

## সূচিপত্র

1. [Big Picture — কেন v4 fundamentally আলাদা](#1-big-picture)
2. [Core Concepts — যা না বুঝলে কিছুই কাজ করবে না](#2-core-concepts)
3. [Atomic Widget গুলোর নাম](#3-atomic-widget-নাম)
4. [PHP Side — Inject + Render](#4-php-side)
5. [JS Side — Live Preview](#5-js-side)
6. [Real Example — আমাদের Animation/Color extender](#6-real-example)
7. [Responsive Controls](#7-responsive-controls)
8. [Common Mistakes — যা যা ভুল হয়](#8-common-mistakes)
9. [Quick Reference Cheat Sheet](#9-cheat-sheet)
10. [Reference Files in Elementor source](#10-references)

---

## 1. Big Picture

V4 আসলে Elementor-এর architecture rewrite। শুধু new widgets না — পুরো system পাল্টেছে।

### V3 vs V4 — মূল পার্থক্য

#### 🎨 Render system
- **V3:** PHP `render()` method HTML লেখে
- **V4:** Twig template + React in editor

#### 🎛 Controls definition (imperative vs declarative)
- **V3:** `_register_controls()` — instance method, ভিতরে `$this->add_control(...)` call করে state mutate করে
- **V4:** `define_atomic_controls()` — instance method (NOT static), কিন্তু **declarative** — Section/Control object-এর array return করে; কোনো state mutation নেই

```php
// V3 — imperative (mutate $this state)
protected function _register_controls() {
    $this->start_controls_section( 'section_id', [...] );
    $this->add_control( 'my_control', [...] );
    $this->end_controls_section();
}

// V4 — declarative (return data structure)
protected function define_atomic_controls(): array {
    return [
        Section::make()
            ->set_label( 'My Section' )
            ->set_items([
                Select_Control::bind_to( 'my_color' )
                    ->set_label( 'Color' )
                    ->set_options([
                        [ 'value' => 'red',  'label' => 'Red' ],
                        [ 'value' => 'blue', 'label' => 'Blue' ],
                    ]),
                Text_Control::bind_to( 'my_label' )
                    ->set_label( 'Custom Label' )
                    ->set_placeholder( 'Type here...' ),
            ]),
            
    ];
}
```

> **Note:** শুধু `define_props_schema()` static (`protected static function`)। বাকি দুটো — `define_atomic_controls()` আর `define_base_styles()` — instance method।

#### ⚙️ Settings access (PHP side)
- **V3:** `$settings['key']` (array)
- **V4:** `$element_data['settings']['key']` (sometimes wrapped as `{$$type, value}`)

#### 💾 Settings access (JS side)
- **V3:** `model.get('settings').get('key')`
- **V4:** `container.settings.get('key')`

#### 🪝 Per-widget hook
- **V3:** `elementor/element/{name}/{section}/before_section_end` — per widget targeted
- **V4:** ❌ **নেই** — সব global filter, widget name `$element->get_name()` দিয়ে check করতে হয়

#### 🎯 Wrapper element selector
- **V3:** `.elementor-element-{ID}` (CSS class)
- **V4:** `[data-interaction-id="{ID}"]` (data attribute)

#### 🖥 Editor JS framework
- **V3:** jQuery + Marionette + Backbone
- **V4:** React + new `window.elementorV2` namespace (legacy `window.elementor` কিছু compatibility-এর জন্য আছে)

#### 📡 Live preview event
- **V3:** `elementor.channels.editor.on('change', fn)`
- **V4:** `elementor/element/render` CustomEvent — fire হয় preview iframe-এর `contentWindow`-এ

### সবচেয়ে বড় mental shift

**V3-এ:** আমরা widget-এর জন্য server-side থেকে সব render করতাম। User dropdown change করলে editor automatic update হত।

**V4-এ:** Editor React-based, server না। তাই control inject করা সহজ, কিন্তু **live preview manually JS দিয়ে handle করতে হয়**। Elementor framework custom prop-এর meaning জানে না — তাই auto-render হবে না।

---

## 2. Core Concepts

### 2.1 Container কী?

V4-এর সব element (widget, section, column) একটা `Container` instance দিয়ে represent হয়। JS-এ এটা একটা object — global না, কিন্তু global path দিয়ে access করা যায়।

**Container tree visualization:**

```
window.elementor.documents.getCurrent().container    ← root container (পুরো page)
    ├── children[0] = Container (Section)
    │   └── children[0] = Container (Column)
    │       ├── children[0] = Container (Heading)   ← আপনাদের element
    │       └── children[1] = Container (Button)
    └── children[1] = Container (Section)
```

**প্রতি Container-এ যা থাকে:**

```js
{
    id:        'abc123',                  // unique element id
    type:      'widget',                  // widget / section / column
    model:     <Backbone.Model>,          // element-এর full data
    settings:  <Backbone.Model>,          // user-saved settings
    view:      <Marionette.View>,         // visual rendering
    children:  [<Container>, ...],        // sub-elements
    parent:    <Container>,
    document:  <Document>,
}
```

### 2.2 Props vs Styles — দুইটা আলাদা concept

V4-এ এই separation **অত্যন্ত important** বুঝতে।

| Concept | কী | কোথায় | Live preview |
|---|---|---|---|
| **Props** | Content data (text, link, custom value) | Settings tab | Manual handle করতে হয় |
| **Styles** | CSS visual properties (color, padding, font-size) | Style tab | Framework auto handle করে |

**Rule of thumb:** যদি জিনিসটা CSS property হয়, Style schema-তে register করো। যদি data হয় (যেমন A/B variant id, custom analytics tag), Props schema-তে।

### 2.3 Atomic widget render path (PHP)

```
PHP request
    ↓
Atomic widget defines:
  - define_props_schema()        → user data props
  - define_atomic_controls()     → editor panel controls
  - define_base_styles()         → static CSS
    ↓
Twig template render: atomic-button.html.twig
    ↓
Output HTML: <button data-interaction-id="X" class="...">Click here</button>
```

**মনে রাখো:** HTML wrapper-এ class থাকে না `.elementor-element-X` — থাকে `data-interaction-id="X"` attribute। তাই CSS selector `[data-interaction-id="..."]` ব্যবহার করতে হবে।

### 2.4 Editor React render flow

```
User dropdown change
    ↓
React updates settings in store
    ↓
React re-renders the widget component
    ↓
Elementor dispatches:
  frame.contentWindow.dispatchEvent(
      new CustomEvent('elementor/element/render', {
          detail: { id, type, element }
      })
  )
    ↓
আমরা subscribe করি → custom CSS apply
```

---

## 3. Atomic Widget নাম

`controls` filter-এ `$element->get_name()` দিয়ে target করতে এই নাম গুলো লাগবে:

| Widget | Name |
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
| Tabs | `e-tabs`, `e-tab`, `e-tabs-menu`, `e-tabs-content-area` |
| Containers | `e-flexbox`, `e-grid`, `e-div-block` |

---

## 4. PHP Side

### 4.1 কোন hook কোথায় ব্যবহার করব

| Hook | কাজ | Signature |
|---|---|---|
| `elementor/atomic-widgets/props-schema` | Custom prop register | `($schema): array` |
| `elementor/atomic-widgets/controls` | Section/Control inject | `($controls, $element): array` |
| `elementor/atomic-widgets/styles/schema` | Style tab-এ prop add (auto live preview) | `($schema): array` |
| `elementor/frontend/the_content` | Frontend HTML modify (universal) | `($content): string` |

### 4.2 Inject pattern — full example

```php
<?php
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

// Step 1: elementor/init action-এ defer করো — atomic-widgets module load guarantee
add_action( 'elementor/init', function () {
    if ( ! class_exists( String_Prop_Type::class ) ) {
        return;
    }

    // Step 2: prop register
    add_filter( 'elementor/atomic-widgets/props-schema', function ( $schema ) {
        $schema['my_prop'] = String_Prop_Type::make()->default( '' );
        return $schema;
    });

    // Step 3: control inject (specific widget target)
    add_filter( 'elementor/atomic-widgets/controls', function ( $controls, $element ) {
        if ( $element->get_name() !== 'e-heading' ) {
            return $controls;
        }

        $controls[] = Section::make()
            ->set_id( 'my_section' )
            ->set_label( __( 'My Section', 'textdomain' ) )
            ->set_items([
                Select_Control::bind_to( 'my_prop' )
                    ->set_label( __( 'Choose', 'textdomain' ) )
                    ->set_options([
                        [ 'value' => 'a', 'label' => 'Option A' ],
                        [ 'value' => 'b', 'label' => 'Option B' ],
                    ]),
            ]);

        return $controls;
    }, 10, 2 );
});
```

### 4.3 কেন `elementor/init`-এ wrap করতে হবে?

প্লাগিন load order-এ atomic-widgets module আগে load হবে, না পরে — guaranteed না। `String_Prop_Type` class তখনো না থাকতে পারে। `elementor/init` action ফায়ার হয় Elementor-এর সব module ready হওয়ার পর। Safe।

এই pattern Elementor-এর own promotions module use করে: `wp-content/plugins/elementor/modules/promotions/module.php:230`

### 4.4 Frontend rendering pattern

```php
add_filter( 'elementor/frontend/the_content', function ( $content ) {
    if ( false === strpos( $content, 'data-interaction-id' ) ) {
        return $content;
    }

    $document = \Elementor\Plugin::$instance->documents->get_current();
    if ( ! $document ) return $content;

    $rules = [];
    walk_elements( $document->get_elements_data(), $rules );

    return empty( $rules ) ? $content
        : '<style id="my-styles">' . implode( '', $rules ) . '</style>' . $content;
});

function walk_elements( $elements, &$rules ) {
    foreach ( $elements as $element ) {
        $type = $element['widgetType'] ?? $element['elType'] ?? '';
        if ( 'e-heading' === $type ) {
            $raw = $element['settings']['my_prop'] ?? '';
            // Atomic prop wrap unwrap: { $$type, value }
            if ( is_array( $raw ) && isset( $raw['value'] ) ) $raw = $raw['value'];
            if ( ! is_string( $raw ) || '' === trim( $raw ) ) continue;

            $rules[] = sprintf(
                '[data-interaction-id="%s"]{background-color:%s;}',
                esc_attr( $element['id'] ),
                esc_attr( $raw )
            );
        }
        if ( ! empty( $element['elements'] ) ) {
            walk_elements( $element['elements'], $rules );
        }
    }
}
```

### 4.5 Asset enqueue — কোন hook কোথায় load হয়

| Hook | Load location | কখন use করব |
|---|---|---|
| `wp_enqueue_scripts` | Frontend page only | Frontend CSS/JS |
| `elementor/preview/enqueue_styles` | Preview iframe `<head>` | Editor preview-এ visible style |
| `elementor/preview/enqueue_scripts` | Preview iframe scripts | Iframe-এর ভিতরে JS (rare) |
| `elementor/editor/after_enqueue_scripts` | Editor parent window | Editor JS — যেখানে `window.elementor` access করব |
| `elementor/editor/after_enqueue_styles` | Editor parent window | Editor panel-এর CSS |

**Common mistake:** Frontend-এ enqueue করে editor preview-এ enqueue ভুলে যাওয়া। দুই জায়গায় enqueue করতে হয় same CSS।

---

## 5. JS Side — Live Preview

### 5.1 Preview iframe কী?

Editor-এর canvas আসলে একটা **iframe**। আপনি যা edit করছেন সেটা ওই iframe-এর ভিতরে render হয়। Editor parent window আর preview iframe **আলাদা contexts** — different `window`, `document`।

```
Editor parent window
    │
    ├── Right panel (controls, dropdowns)
    │
    └── <iframe src="...preview...">
            │
            └── Preview iframe (canvas)
                  └── <body>
                       └── <div data-interaction-id="X">Heading</div>
```

**Access করতে:**

```js
window.elementor.$preview[0]                    // ← iframe DOM element
window.elementor.$preview[0].contentDocument   // ← iframe-এর ভিতরের document
window.elementor.$preview[0].contentWindow     // ← iframe-এর ভিতরের window
```

### 5.2 Live preview-এর জন্য official hook

**বিশাল important:** v4-এর live preview-এর জন্য Elementor একটা CustomEvent dispatch করে — `elementor/element/render`। প্রতি widget React re-render-এ এই event fire হয়।

**Source:** `wp-content/plugins/elementor/assets/js/atomic-widgets-editor.js:1473`

```js
// Elementor internally যা dispatch করে:
elementor.$preview[0].contentWindow.dispatchEvent(
    new CustomEvent( 'elementor/element/render', {
        detail: { id, type, element }
    })
);
```

**Subscribe করতে:**

```js
( function attach() {
    var frame = window.elementor && window.elementor.$preview && window.elementor.$preview[ 0 ];
    if ( ! frame || ! frame.contentWindow ) {
        return setTimeout( attach, 200 );  // poll until iframe ready
    }

    // Dedupe across preview reloads
    frame.contentWindow.removeEventListener( 'elementor/element/render', onRender );
    frame.contentWindow.addEventListener( 'elementor/element/render', onRender );
} )();

function onRender( e ) {
    var id   = e.detail.id;     // element id যেটা re-render হয়েছে
    var type = e.detail.type;   // widget / section etc.
    var el   = e.detail.element; // actual DOM node
    // ...do something
}
```

### 5.3 ⚠️ Critical — event কোথায় fire হয়

Event fire হয় **`frame.contentWindow`-এ**, parent `window`-এ না, `elementor` channel-এও না। `addEventListener` exactly preview iframe-এর `contentWindow`-এ লাগাতে হবে।

### 5.4 Settings value পড়া

```js
function readSetting( container, propKey ) {
    if ( ! container || ! container.settings ) return '';

    // V4 atomic widget primary path
    var v = container.settings.get
        ? container.settings.get( propKey )
        : container.settings.attributes && container.settings.attributes[ propKey ];

    // Atomic prop wrapped format unwrap
    if ( v && typeof v === 'object' && 'value' in v ) v = v.value;

    return ( typeof v === 'string' ) ? v.trim() : '';
}
```

**Why multi-path?** V4-এ container.settings shape কখনো Backbone Model (with `.get()`), কখনো plain object (with `.attributes`)। তিনটাই try করতে হয়।

### 5.5 Container by id খোঁজা

```js
function findContainer( root, id ) {
    if ( ! root ) return null;
    if ( root.id === id ) return root;
    if ( ! root.children || ! root.children.forEach ) return null;
    for ( var i = 0; i < root.children.length; i++ ) {
        var f = findContainer( root.children[ i ], id );
        if ( f ) return f;
    }
    return null;
}

// Usage:
var rootContainer = window.elementor.documents.getCurrent().container;
var found = findContainer( rootContainer, 'abc123' );
```

### 5.6 Preview iframe-এ scoped CSS inject

React widget DOM re-render-এ inline style wipe হয়ে যায়। তাই iframe-এর `<head>`-এ একটা persistent `<style>` tag inject করতে হবে — সেখানে CSS lifetime-এ থাকবে।

```js
function applyTo( id ) {
    var doc = frame.contentDocument;
    if ( ! doc || ! doc.head ) return;

    var styleEl = doc.getElementById( 'my-style-tag' );
    if ( ! styleEl ) {
        styleEl = doc.createElement( 'style' );
        styleEl.id = 'my-style-tag';
        doc.head.appendChild( styleEl );
    }

    // Diff-merge: parse existing rules, replace only THIS id's rule
    var rules = {};
    var re = /\[data-interaction-id="([^"]+)"\][^}]+\}/g;
    var m;
    while ( ( m = re.exec( styleEl.textContent || '' ) ) ) {
        rules[ m[ 1 ] ] = m[ 0 ];
    }

    // Build new rule
    var color = readSetting( /* container */, 'my_prop' );
    if ( color ) {
        rules[ id ] = '[data-interaction-id="' + id + '"]{background-color:' + color + ';}';
    } else {
        delete rules[ id ];
    }

    // Write back
    styleEl.textContent = Object.keys( rules ).map( function ( k ) { return rules[ k ]; } ).join( '' );
}
```

**Diff-merge কেন important?** একাধিক heading widget থাকতে পারে। শুধু ONE widget-এর change হলেও সবগুলোর rule overwrite করা waste। Existing rules parse → শুধু target id update → reserialize।

---

## 6. Real Example — আমাদের Color/Style extender

### Architecture

```
┌─ inc/aae-atomic-extender.php ───────────────┐
│                                              │
│  • props-schema filter → register প্রতি prop │
│  • controls filter → AAE Style section inject│
│  • the_content filter → frontend CSS render  │
│  • editor/after_enqueue_scripts → JS load   │
└──────────────────────────────────────────────┘
                       ↓
┌─ assets/js/aae-atomic-extender.js ──────────┐
│                                              │
│  • Subscribe elementor/element/render        │
│  • Read settings via container.settings.get  │
│  • Build CSS rule per heading                │
│  • Inject scoped <style> in preview head    │
│  • (Optional) Inject Re-render button       │
└──────────────────────────────────────────────┘
```

### Config-driven approach

PHP-এ central config:
```php
function aae_style_props(): array {
    return [
        'aae_color' => [
            'css'     => 'background-color',
            'options' => [ /* dropdown options */ ],
        ],
        'aae_text_color' => [
            'css'     => 'color',
            'options' => [ /* options */ ],
        ],
        // ... more
    ];
}
```

JS-এ same shape mirror:
```js
var STYLE_MAP = [
    { prop: 'aae_color',      css: 'background-color', allowed: [/* values */] },
    { prop: 'aae_text_color', css: 'color',            allowed: [/* values */] },
    // ... more
];
```

**Future-এ control add করতে:**
1. PHP config-এ entry add
2. JS STYLE_MAP-এ same entry add
3. Done — auto inject + auto frontend + auto live preview

---

## 7. Responsive Controls

### 7.1 V4-এ responsive কীভাবে কাজ করে

**V3-এ:** Each control-এ `'responsive' => true` → automatic 3 sub-control (desktop/tablet/mobile)

**V4-এ:** Per-control responsive flag **নেই**। Responsive STYLE level-এ `Style_Variant::set_breakpoint()` দিয়ে।

| Concern | V3 | V4 |
|---|---|---|
| Control-এ responsive flag | আছে | ❌ নেই |
| Auto desktop/tablet/mobile | Yes | শুধু Style schema props-এ |
| Custom data prop responsive | Auto | **Manual** — 3 separate props register করতে হয় |

### 7.2 Custom prop responsive কীভাবে

Manually 3 prop register করো — desktop/tablet/mobile-এর জন্য:

```php
const BREAKPOINTS = [ 'desktop', 'tablet', 'mobile' ];

add_filter( 'elementor/atomic-widgets/props-schema', function ( $schema ) {
    foreach ( BREAKPOINTS as $bp ) {
        $key = 'my_color_' . $bp;
        $schema[ $key ] = String_Prop_Type::make()->default( '' );
    }
    return $schema;
});

add_filter( 'elementor/atomic-widgets/controls', function ( $controls, $element ) {
    if ( $element->get_name() !== 'e-heading' ) return $controls;

    $items = [];
    foreach ( BREAKPOINTS as $bp ) {
        $items[] = Select_Control::bind_to( 'my_color_' . $bp )
            ->set_label( ucfirst( $bp ) . ' Color' )
            ->set_options( /* options */ );
    }

    $controls[] = Section::make()
        ->set_id( 'responsive_color' )
        ->set_label( __( 'Responsive Color' ) )
        ->set_items( $items );

    return $controls;
}, 10, 2 );

// Frontend — @media wrap
function emit_css( $element, &$rules ) {
    $bp_media = [
        'desktop' => '',                              // base — no media
        'tablet'  => '@media (max-width: 1024px)',
        'mobile'  => '@media (max-width: 767px)',
    ];

    foreach ( BREAKPOINTS as $bp ) {
        $raw = $element['settings'][ 'my_color_' . $bp ] ?? '';
        if ( is_array( $raw ) && isset( $raw['value'] ) ) $raw = $raw['value'];
        if ( ! is_string( $raw ) || '' === trim( $raw ) ) continue;

        $body = sprintf(
            '[data-interaction-id="%s"]{background-color:%s;}',
            esc_attr( $element['id'] ),
            esc_attr( $raw )
        );
        $rules[] = $bp_media[ $bp ] ? $bp_media[ $bp ] . '{' . $body . '}' : $body;
    }
}
```

### 7.3 Available breakpoints

`wp-content/plugins/elementor/core/breakpoints/manager.php` থেকে:
- `desktop`, `tablet`, `mobile` (default)
- `mobile_extra`, `tablet_extra`, `widescreen`, `laptop` (extended)

---

## 8. Common Mistakes

### ❌ যা করবে না

| ভুল | কেন | বদলে কী করবে |
|---|---|---|
| `elementor/element/{name}/before_section_end` | V3 only — v4-এ exist করে না | `elementor/atomic-widgets/controls` filter |
| `.elementor-element-{ID}` selector | V4 wrapper-এ এই class নেই | `[data-interaction-id="{ID}"]` |
| `elementor/frontend/widget/before_render` action | Atomic widget-এ reliably fire হয় না | `elementor/frontend/the_content` filter |
| `elementor:loaded` jQuery event | V4 React bootstrap-এ fire হয় না | Polling or attach immediately |
| Inline `style.X = '...'` on widget wrapper | React re-render wipe করে | `<style>` tag in iframe `<head>` |
| `$e.commands.on('run:after')` for atomic settings | V4 atomic-এ fire হয় না | `elementor/element/render` CustomEvent |
| Filter without `elementor/init` wrap | Class load order issue | `add_action('elementor/init', ...)` দিয়ে wrap |

### ✅ যা করবে

- **CSS selector** → সবসময় `[data-interaction-id="{ID}"]`
- **Frontend render** → `the_content` filter
- **Live preview** → `elementor/element/render` CustomEvent on **iframe contentWindow**
- **Re-attach listener** → `preview:loaded` event-এ (full preview reload-এ iframe replace হয়)
- **Filter registration** → `elementor/init`-এ defer
- **Asset enqueue** → frontend (`wp_enqueue_scripts`) **AND** preview (`elementor/preview/enqueue_styles`) — দুই জায়গায়
- **Atomic prop value** → flat string বা `{$$type, value}` দুই format-ই handle

### ⚠️ সাবধান

- Section's `set_id()` ব্যবহার করো — কিন্তু v4 widget-এ predefined section নেই, সব custom
- `Inline_Editing_Control` শুধু HTML/text props-এ Twig binding-এর সাথে কাজ করে
- Frontend filter-এ `wp_list_pluck` দিয়ে value whitelist বানিয়ে XSS prevent করো

---

## 9. Cheat Sheet

### Minimal subscribe pattern (5 lines)

```js
( function attach() {
    var frame = window.elementor && window.elementor.$preview && window.elementor.$preview[ 0 ];
    if ( ! frame || ! frame.contentWindow ) return setTimeout( attach, 200 );

    frame.contentWindow.addEventListener( 'elementor/element/render', function ( e ) {
        console.log( '[render]', e.detail.id, e.detail.type );
    });
})();
```

### PHP minimal inject (one filter set)

```php
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
```

### Asset enqueue — frontend + editor + preview iframe

```php
// Frontend
add_action( 'wp_enqueue_scripts', fn() =>
    wp_enqueue_style( 'h', URL.'/css/h.css' )
);

// Preview iframe
add_action( 'elementor/preview/enqueue_styles', fn() =>
    wp_enqueue_style( 'h', URL.'/css/h.css' )
);

// Editor parent window
add_action( 'elementor/editor/after_enqueue_scripts', fn() =>
    wp_enqueue_script( 'h', URL.'/js/h.js', [ 'elementor-editor' ], '1.0', true )
);
```

---

## 10. References

আসল Elementor source files যেগুলো থেকে এই guide লেখা:

### Core hook source
- `modules/atomic-widgets/elements/base/has-atomic-base.php` — `controls` + `props-schema` filter
- `modules/atomic-widgets/styles/style-schema.php` — `styles/schema` filter
- `modules/atomic-widgets/styles/atomic-styles-manager.php` — `styles/register` action
- `modules/atomic-widgets/styles/atomic-widget-styles.php` — `editor_data/element_styles` filter

### Real Elementor extension examples (canonical reference)
- `modules/promotions/module.php:230` — props-schema + controls inject pattern
- `modules/variables/hooks.php:75` — styles/schema augmentation
- `modules/variables/hooks.php:66` — transformers/register

### Atomic widget definitions
- `modules/atomic-widgets/elements/atomic-button/atomic-button.php` — sample widget
- `modules/atomic-widgets/elements/atomic-heading/atomic-heading.php` — heading widget
- `modules/atomic-widgets/elements/atomic-button/atomic-button.html.twig` — Twig template example

### Editor JS (live preview event source)
- `assets/js/atomic-widgets-editor.js:1473` — `dispatchPreviewEvent()` — যেখানে render event fire হয়
- `assets/js/packages/editor-canvas/editor-canvas.js` — `settingsTransformersRegistry`

### Style system internals (advanced)
- `modules/atomic-widgets/styles/style-definition.php` — Style_Definition class
- `modules/atomic-widgets/styles/style-variant.php` — Style_Variant (responsive support)
- `modules/atomic-widgets/elements/atomic-button/atomic-button.php:98` — `define_base_styles()` example

### Breakpoints
- `core/breakpoints/manager.php` — breakpoint keys (desktop/tablet/mobile + extras)

---

## শেষ কথা

V4 শিখতে কিছু সময় লাগবে কারণ architecture পুরোপুরি বদলেছে। কিন্তু একবার `Container` + `props-schema` + `controls filter` + `elementor/element/render` event বুঝলে — যেকোনো extension বানানো straightforward।

**Practice plan:**
1. PHP-এ একটা simple Select inject করো (no live preview)
2. Frontend-এ `the_content` filter দিয়ে CSS apply করো
3. JS-এ render event subscribe করে editor live preview add করো
4. Multi-prop config-driven structure-এ scale করো
5. Responsive 3-prop pattern-এ extend করো

প্রতি step-এ DevTools console খোলো — `window.elementor.documents.getCurrent().container` exact কী return করছে সেটা দেখলে অনেক confusion clear হবে।

**সেরা debug command:**
```js
window.elementor.documents.getCurrent().container.children[0].children[0].children[0].settings.attributes
```

এটা page-এর প্রথম heading widget-এর সব saved settings show করবে — props-এর actual stored format বুঝতে পারবে।

---

**Version history:**
- v1 (2026-05-10) — Initial Bangla learning guide based on hands-on Elementor 4.0.7 exploration. AAE Color/Style extender example reference।
