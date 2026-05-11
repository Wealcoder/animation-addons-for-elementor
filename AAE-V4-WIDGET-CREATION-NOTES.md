# Elementor v4 — Complete Widget Creation Knowledge Base

> **Purpose:** Future skill (v3→v4 conversion / new v4 widget creation) এর জন্য reference notes।
> **Source:** Elementor 4.0.7 — `wp-content/plugins/elementor/modules/atomic-widgets/elements/` সব file পড়ে compile।
> **Date:** 2026-05-10
> **DO NOT delete** — this is the master reference for any future v4 widget skill।

---

## Table of Contents

1. [Class Hierarchy & Architecture](#1-class-hierarchy)
2. [The Two Element Types — Widget vs Element](#2-widget-vs-element)
3. [Required Abstract Methods](#3-required-methods)
4. [Trait System — কোনটা কখন use](#4-traits)
5. [Complete Anatomy — Atomic Widget skeleton](#5-anatomy)
6. [Prop Types — Full Catalog](#6-prop-types)
7. [Control Types — Full Catalog](#7-control-types)
8. [Twig Template Patterns](#8-twig-patterns)
9. [Base Styles — Style_Definition + Style_Variant](#9-base-styles)
10. [Conditional Props — Dependency_Manager](#10-conditional-props)
11. [Container Widgets — define_default_children](#11-default-children)
12. [Widget Registration](#12-registration)
13. [V3 → V4 Conversion Mapping](#13-v3-to-v4-mapping)
14. [Real Widget Catalog — Which to Copy When](#14-real-widget-catalog)
15. [Hello World Skeleton — Copy-Paste Ready](#15-hello-world)
16. [Section Injection Strategies (for filter-based inject)](#16-section-injection)

---

## 1. Class Hierarchy

```
┌─ Elementor\Widget_Base (v3 widget base, also used by atomic widgets)
│   └─ Atomic_Widget_Base (abstract)        ← regular atomic widgets extend this
│        uses Has_Atomic_Base
│        uses Has_Meta
│
└─ Elementor\Element_Base (v3 element base, for containers)
    └─ Atomic_Element_Base (abstract)        ← container-type atomic elements extend this
         uses Has_Atomic_Base
         uses Has_Meta
```

**Common traits:**
- `Has_Atomic_Base` — provides core atomic methods (controls, props-schema, settings parsing)
- `Has_Base_Styles` — provides base styles management (used inside Has_Atomic_Base)
- `Has_Meta` — metadata management
- `Has_Template` — for simple widgets (no children) using Twig template
- `Has_Element_Template` — for nested/container widgets using Twig template with children support

**Key file locations:**
```
modules/atomic-widgets/elements/base/
├── atomic-widget-base.php         (Atomic_Widget_Base abstract class)
├── atomic-element-base.php        (Atomic_Element_Base abstract class)
├── has-atomic-base.php            (core trait)
├── has-base-styles.php            (base styles trait)
├── has-template.php               (simple widget Twig trait)
├── has-element-template.php       (container Twig trait with children)
├── widget-builder.php             (Widget_Builder helper)
├── element-builder.php            (Element_Builder helper)
└── render-context.php             (render context stack)
```

---

## 2. Widget vs Element

### `Atomic_Widget_Base` — Regular widgets
- For self-contained widgets (heading, button, image, paragraph, etc.)
- Cannot have children
- Registered via: `$widgets_manager->register( new MyWidget() )`
- Hook: `elementor/widgets/register`
- `elType` = `'widget'`, `widgetType` = the element_type

### `Atomic_Element_Base` — Container/nested elements
- For elements that CAN have children (containers, forms, tabs)
- Has `define_default_children()` method
- Registered via: `$elements_manager->register_element_type( new MyElement() )`
- Hook: `elementor/elements/elements_registered`
- `elType` = the element_type (no separate widgetType)
- Constructor sets `$this->meta( 'is_container', true )`

**Quick test:** Will your widget have children/nested elements? → `Atomic_Element_Base`. Otherwise → `Atomic_Widget_Base`.

---

## 3. Required Methods

### For both Widget and Element

| Method | Static? | Returns | Required? |
|---|---|---|---|
| `get_element_type()` | ✅ static | string | ✅ MANDATORY (returns `e-NAME`) |
| `get_title()` | instance | string | ✅ MANDATORY (display name) |
| `get_icon()` | instance | string | ✅ MANDATORY (eicon class) |
| `get_keywords()` | instance | array | optional, for search |
| `define_props_schema()` | ✅ static | array | ✅ MANDATORY (data props) |
| `define_atomic_controls()` | instance | array | ✅ MANDATORY (editor UI) |
| `define_base_styles()` | instance | array | optional (default CSS) |
| `get_templates()` | instance | array | ✅ MANDATORY if using Has_Template |

### Element-only (containers)

| Method | Returns | Purpose |
|---|---|---|
| `get_type()` | string | Same as `get_element_type()` — both required |
| `define_default_children()` | array | Initial child elements when added to page |
| `define_default_html_tag()` | string | `'div'`, `'form'`, etc. |
| `define_panel_categories()` | array | Where in panel sidebar |
| `define_allowed_child_types()` | array | Whitelist children types |
| `define_initial_attributes()` | array | data-* attributes on render |
| `add_render_attributes()` | void | Override to customize wrapper HTML |

### Optional — advanced features

| Method | Purpose |
|---|---|
| `get_script_depends()` | Frontend JS dependencies |
| `register_frontend_handlers()` | Register frontend JS for this widget |
| `define_atomic_pseudo_states()` | hover/focus states |
| `define_render_context()` | Push context to Render_Context stack |
| `build_template_context()` | Override Twig context (for `Has_Element_Template`) |
| `get_css_id_control_meta()` | Override `_cssid` control display |

---

## 4. Traits

### `Has_Template` — Simple widget with Twig template
- No children support
- Single `render()` method that just outputs Twig
- Context: id, interaction_id, type, settings, base_styles

```php
class My_Widget extends Atomic_Widget_Base {
    use Has_Template;

    protected function get_templates(): array {
        return [
            'elementor/elements/my-widget' => __DIR__ . '/my-widget.html.twig',
        ];
    }
}
```

### `Has_Element_Template` — Container widget with children
- Supports nested elements
- Renders children via `<!-- elementor-children-placeholder -->` in Twig
- Adds `support_nesting`, `twig_main_template`, `twig_templates` to config

```php
class My_Container extends Atomic_Element_Base {
    use Has_Element_Template;

    protected function get_templates(): array {
        return [
            'elementor/elements/my-container' => __DIR__ . '/my-container.html.twig',
        ];
    }
}
```

In Twig:
```twig
<div data-interaction-id="{{ interaction_id }}">
    {{ children_placeholder | raw }}
</div>
```

---

## 5. Anatomy

```php
<?php
namespace MyPlugin\Elements\My_Widget;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) exit;

class My_Widget extends Atomic_Widget_Base {
    use Has_Template;

    // 1. Optional widget-level description
    public static $widget_description = 'My custom widget description';

    // 2. MANDATORY — unique element type
    public static function get_element_type(): string {
        return 'e-my-widget';
    }

    // 3. MANDATORY — display name
    public function get_title() {
        return esc_html__( 'My Widget', 'textdomain' );
    }

    // 4. MANDATORY — icon (Elementor's eicon-* class names)
    public function get_icon() {
        return 'eicon-my-icon';
    }

    // 5. Optional — search keywords
    public function get_keywords() {
        return [ 'my', 'custom', 'widget' ];
    }

    // 6. MANDATORY — data props schema
    protected static function define_props_schema(): array {
        return [
            'classes' => Classes_Prop_Type::make()->default( [] ),
            'my_text' => String_Prop_Type::make()->default( 'Hello' ),
            'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
        ];
    }

    // 7. MANDATORY — editor panel controls
    protected function define_atomic_controls(): array {
        return [
            Section::make()
                ->set_label( __( 'Content', 'textdomain' ) )
                ->set_items( [
                    Text_Control::bind_to( 'my_text' )
                        ->set_label( __( 'Text', 'textdomain' ) ),
                ] ),
            Section::make()
                ->set_label( __( 'Settings', 'textdomain' ) )
                ->set_id( 'settings' )
                ->set_items( [
                    Text_Control::bind_to( '_cssid' )
                        ->set_label( __( 'ID', 'textdomain' ) )
                        ->set_meta( $this->get_css_id_control_meta() ),
                ] ),
        ];
    }

    // 8. Optional — default CSS styles (auto-applied)
    protected function define_base_styles(): array {
        return [
            'base' => Style_Definition::make()
                ->add_variant(
                    Style_Variant::make()
                        ->add_prop( 'padding', Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
                ),
        ];
    }

    // 9. MANDATORY (when using Has_Template) — Twig template path
    protected function get_templates(): array {
        return [
            'elementor/elements/my-widget' => __DIR__ . '/my-widget.html.twig',
        ];
    }
}
```

Companion Twig template (`my-widget.html.twig`):

```twig
{% set id_attribute = settings._cssid is not empty ? 'id=' ~ settings._cssid | e('html_attr') : '' %}
<div
    data-interaction-id="{{ interaction_id }}"
    class="{{ settings.classes | merge( [ base_styles.base ] ) | join(' ') }}"
    {{ id_attribute }}
    {{ settings.attributes | raw }}
>
    {{ settings.my_text }}
</div>
```

---

## 6. Prop Types

`Elementor\Modules\AtomicWidgets\PropTypes\` namespace।

### Primitive Prop Types (`Primitives\` subfolder)

| Type | Use for | Fluent methods |
|---|---|---|
| `String_Prop_Type` | text/string | `->default()`, `->enum([...])`, `->description()` |
| `Number_Prop_Type` | int/float | `->default()`, `->description()` |
| `Boolean_Prop_Type` | true/false | `->default()` |
| `String_Array_Prop_Type` | array of strings | `->default([])` |

### Composite Prop Types (root namespace)

| Type | Use for |
|---|---|
| `Color_Prop_Type` | color value (hex/rgb/hsl) |
| `Size_Prop_Type` | `{size, unit}` — supports `->units([...])` |
| `Dimensions_Prop_Type` | top/right/bottom/left dimensions |
| `Background_Prop_Type` | full background spec (color + gradient + image overlay) |
| `Border_Width_Prop_Type` | border width values |
| `Border_Radius_Prop_Type` | border radius values |
| `Box_Shadow_Prop_Type` | box shadow spec |
| `Shadow_Prop_Type` | generic shadow |
| `Stroke_Prop_Type` | SVG stroke |
| `Image_Prop_Type` | image with `->default_url()`, `->default_size()` |
| `Image_Src_Prop_Type` | just image source |
| `Image_Attachment_Id_Prop_Type` | WP attachment ID |
| `Video_Src_Prop_Type` | video source |
| `Svg_Src_Prop_Type` | SVG with `->default_url()` |
| `Link_Prop_Type` | URL + target + tag |
| `Url_Prop_Type` | just URL |
| `Email_Prop_Type` | email value with form settings |
| `Position_Prop_Type` | x/y position |
| `Layout_Direction_Prop_Type` | row/column |
| `Flex_Prop_Type` | flex shorthand |
| `Date_Time_Prop_Type` | date+time |
| `Date_Range_Prop_Type` | date range |
| `Time_Range_Prop_Type` | time range |
| `Classes_Prop_Type` | wrapper CSS classes array |
| `Attributes_Prop_Type` | HTML attributes — usually with `->meta(Overridable_Prop_Type::ignore())` |
| `Union_Prop_Type` | accept multiple types — `->add_prop_type(...)` chained |
| `Options_Prop_Type` | predefined options |
| `Html_V3_Prop_Type` | Rich HTML content (used by heading/paragraph/button text) |

### Special meta modifiers

```php
// Mark prop as ignorable in overridable contexts:
->meta( Overridable_Prop_Type::ignore() )

// Mark prop as ignorable in dynamic tags:
->meta( Dynamic_Prop_Type::ignore() )

// Add custom metadata (e.g., suffix for unit display):
->meta( 'suffix', 'SEC' )

// Mark a prop generates a CSS class with placeholder:
->meta( 'generates_class', 'form-state-{value}' )

// Conditional visibility:
->set_dependencies( $dependency_manager )
```

### Static `::generate()` helpers

Most prop types have a static `::generate()` to create the wrapped value format:

```php
String_Prop_Type::generate( 'hello' )
// → [ '$$type' => 'string', 'value' => 'hello' ]

Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] )
// → [ '$$type' => 'size', 'value' => [ 'size' => 10, 'unit' => 'px' ] ]

Color_Prop_Type::generate( '#FF0000' )
Dimensions_Prop_Type::generate( [ 'block-start' => Size_Prop_Type::generate([...]) ] )
```

---

## 7. Control Types

`Elementor\Modules\AtomicWidgets\Controls\Types\` namespace।

### Available controls

| Control | Best for | Pairs with prop type |
|---|---|---|
| `Text_Control` | Single-line text | `String_Prop_Type` |
| `Textarea_Control` | Multi-line text | `String_Prop_Type` |
| `Number_Control` | Number — `->set_min()`, `->set_max()` | `Number_Prop_Type` |
| `Switch_Control` | On/off toggle | `Boolean_Prop_Type` |
| `Toggle_Control` | Multi-option toggle group — `->add_options()`, `->set_exclusive()` | `String_Prop_Type` (with enum) |
| `Select_Control` | Dropdown — `->set_options([{value, label}])` | `String_Prop_Type` |
| `Link_Control` | URL + target | `Link_Prop_Type` |
| `Image_Control` | Image picker (media library) | `Image_Prop_Type` |
| `Video_Control` | Video picker | `Video_Src_Prop_Type` |
| `Svg_Control` | SVG picker | `Svg_Src_Prop_Type` |
| `Inline_Editing_Control` | Click-to-edit text directly on canvas | `Html_V3_Prop_Type` |
| `Html_Tag_Control` | HTML tag selector — `->set_options()`, `->set_fallback_labels()` | `String_Prop_Type` (with enum) |
| `Size_Control` | Number + unit picker | `Size_Prop_Type` |
| `Chips_Control` | Multi-select chips | `String_Array_Prop_Type` |
| `Query_Chips_Control` | Posts/terms multi-select | `String_Array_Prop_Type` |
| `Query_Control` | Posts/terms picker | `String_Prop_Type` |
| `Date_Time_Control` | Date+time | `Date_Time_Prop_Type` |
| `Date_Range_Control` | Date range | `Date_Range_Prop_Type` |
| `Time_Range_Control` | Time range | `Time_Range_Prop_Type` |
| `Repeatable_Control` | Repeater | array prop |
| `Attachment_Type_Control` | WP attachment type | `String_Prop_Type` |
| `Email_Form_Action_Control` | Form email action settings | `Email_Prop_Type` |

### Universal fluent API (all controls inherit from `Atomic_Control_Base`)

```php
SomeControl::bind_to( 'prop_key' )       // ⭐ static factory — links to prop
    ->set_label( 'Display Label' )
    ->set_placeholder( 'Hint text' )
    ->set_meta( [
        'layout' => 'two-columns',        // visual layout
        'topDivider' => true,              // show divider above
    ] )
```

### Section API

```php
Section::make()
    ->set_id( 'unique_section_id' )       // optional but recommended
    ->set_label( 'Section Title' )
    ->set_description( 'Section helper' )  // optional
    ->set_items( [                          // array of controls or sub-sections
        Control1::bind_to('...')->set_label('...'),
        Control2::bind_to('...')->set_label('...'),
    ] )
```

---

## 8. Twig Patterns

### Standard Twig context (set automatically)

```
{
    id: <element_id>,
    interaction_id: <interaction_id (orig OR widget id)>,
    type: <widget_type or element_type>,
    settings: { ... },                    ← parsed atomic settings
    base_styles: { 'base': '<class>', 'link-base': '<class>', ... },
    children_placeholder: '<!-- elementor-children-placeholder -->',  (only with Has_Element_Template)
}
```

### Common Twig escapers (custom registered)

| Filter | Purpose |
|---|---|
| `\| e('html_attr')` | Escape for HTML attribute value |
| `\| e('html_tag')` | Validate HTML tag name (whitelist) |
| `\| e('full_url')` | Escape URL via `esc_url()` |
| `\| raw` | Output raw HTML (use carefully) |
| `\| striptags('<allowed><tags>')` | Strip but allow specific tags |
| `\| merge([...])` | Merge arrays |
| `\| join(' ')` | Join array to string |

### Standard widget template

```twig
{% set id_attribute = settings._cssid is not empty ? 'id=' ~ settings._cssid | e('html_attr') : '' %}
{% set classes = settings.classes | merge( [ base_styles.base ] ) | join(' ') %}

<{{ settings.tag | e('html_tag') }}
    class="{{ classes }}"
    data-interaction-id="{{ interaction_id }}"
    {{ id_attribute }}
    {{ settings.attributes | raw }}
>
    {{ settings.content }}
</{{ settings.tag | e('html_tag') }}>
```

### Conditional rendering

```twig
{% if settings.title is not empty %}
    ... render ...
{% endif %}

{% if settings.link and settings.link.attributes is not empty %}
    <a {{ settings.link.attributes | raw }}>...</a>
{% else %}
    ...
{% endif %}
```

### Container template (with children)

```twig
<div
    class="{{ base_styles.base }} {{ settings.classes | join(' ') }}"
    data-interaction-id="{{ interaction_id }}"
>
    {{ children_placeholder | raw }}
</div>
```

### Rich HTML output (Html_V3 props)

```twig
{% set allowed_tags = '<b><strong><sup><sub><s><em><i><u><a><del><span><br>' %}
{{ settings.title | striptags(allowed_tags) | raw }}
```

---

## 9. Base Styles

### `Style_Definition` + `Style_Variant` pattern

```php
protected function define_base_styles(): array {
    return [
        'base' => Style_Definition::make()
            ->add_variant(
                Style_Variant::make()
                    ->add_prop( 'background', Background_Prop_Type::generate([
                        'color' => Color_Prop_Type::generate('#375EFB'),
                    ]))
                    ->add_prop( 'display', String_Prop_Type::generate('inline-block') )
                    ->add_prop( 'padding', Dimensions_Prop_Type::generate([
                        'block-start' => Size_Prop_Type::generate(['size' => 12, 'unit' => 'px']),
                        // ... block-end, inline-start, inline-end
                    ]))
                    ->add_prop( 'border-radius', Size_Prop_Type::generate(['size' => 2, 'unit' => 'px']) )
                    ->add_prop( 'text-align', String_Prop_Type::generate('center') )
            ),

        // Additional named style
        'link-base' => Style_Definition::make()
            ->add_variant(
                Style_Variant::make()
                    ->add_prop( 'all', 'unset' )
                    ->add_prop( 'cursor', 'pointer' )
            ),
    ];
}
```

### Responsive base styles via breakpoints

```php
use Elementor\Core\Breakpoints\Manager as Breakpoints_Manager;

Style_Variant::make()
    ->set_breakpoint( Breakpoints_Manager::BREAKPOINT_KEY_DESKTOP )  // desktop/tablet/mobile
    ->add_prop( 'display', String_Prop_Type::generate('flex') )
```

### State styles (hover/focus)

```php
use Elementor\Modules\AtomicWidgets\Styles\Style_States;

Style_Variant::make()
    ->set_state( Style_States::HOVER )   // HOVER/ACTIVE/FOCUS/FOCUS_VISIBLE/CHECKED
    ->add_prop( 'background', '#ff0000' )
```

### Available breakpoint keys
- `BREAKPOINT_KEY_DESKTOP` = `'desktop'`
- `BREAKPOINT_KEY_TABLET` = `'tablet'`
- `BREAKPOINT_KEY_MOBILE` = `'mobile'`
- Plus: `widescreen`, `laptop`, `tablet_extra`, `mobile_extra`

### Generated class names

`base_styles_dictionary` returns: `[ 'base' => 'e-my-widget-base', 'link-base' => 'e-my-widget-link-base' ]`

In Twig: `base_styles.base` or `base_styles['link-base']`

---

## 10. Conditional Props (`Dependency_Manager`)

`Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;`

### Basic syntax

```php
$dependency = Dependency_Manager::make()
    ->where([
        'operator' => 'eq',                  // operator
        'path'     => [ 'autoplay' ],        // prop key (or nested path)
        'value'    => true,                  // value to compare against
        'effect'   => 'hide',                // what happens when condition matches
    ])
    ->get();

// Attach to prop:
'playsinline' => Boolean_Prop_Type::make()
    ->default( false )
    ->set_dependencies( $dependency ),
```

### Operators (verified from source)

| Operator | Meaning |
|---|---|
| `eq` | equals |
| `ne` | not equals |
| `exists` | prop has a value |
| `not_exist` | prop has no value |
| `contains` | array contains value (for array props) |
| `gte` | greater than or equal |
| `lt` | less than |
| `in` | value in array of options |

### Effects

| Effect | What happens |
|---|---|
| `'hide'` | Hide this control/prop when condition matches |
| `'disable'` | Disable but show |

### Multiple conditions (AND/OR)

```php
Dependency_Manager::make( Dependency_Manager::RELATION_AND )    // or RELATION_OR
    ->where([ 'operator' => 'ne', 'path' => ['object-fit'], 'value' => 'fill' ])
    ->where([ 'operator' => 'exists', 'path' => ['object-fit'] ])
    ->get()
```

### Nested paths (object props)

```php
->where([
    'operator'   => 'ne',
    'path'       => [ 'link', 'destination' ],   // dot-walk into link.destination
    'nestedPath' => [ 'group' ],                  // deeper into .destination.group
    'value'      => 'action',
    'newValue'   => [                              // optional — replace value when condition matches
        '$$type' => 'string',
        'value'  => 'button',
    ],
])
```

### Real example — Self-hosted video

```php
// playsinline hidden when autoplay = true
$dep = Dependency_Manager::make()
    ->where([
        'operator' => 'eq',
        'path' => [ 'autoplay' ],
        'value' => true,
        'effect' => 'hide',
    ])->get();

'playsinline' => Boolean_Prop_Type::make()->default(false)->set_dependencies($dep),
```

---

## 11. Default Children

For container widgets, provide initial children when added to canvas:

```php
use Elementor\Modules\AtomicWidgets\Elements\Base\Widget_Builder;
use Elementor\Modules\AtomicWidgets\Elements\Base\Element_Builder;

protected function define_default_children() {
    return [
        // A widget child
        Widget_Builder::make( 'e-heading' )
            ->settings([
                'title' => Html_V3_Prop_Type::generate([
                    'content'  => String_Prop_Type::generate( 'Default heading' ),
                    'children' => [],
                ]),
            ])
            ->is_locked( true )    // user can't delete
            ->editor_settings([ 'title' => 'Locked label' ])
            ->build(),

        // An element child with nested children
        Element_Builder::make( 'e-flexbox' )
            ->settings([ 'classes' => Classes_Prop_Type::generate([ 'my-class' ]) ])
            ->children([
                Widget_Builder::make('e-button')->settings([...])->build(),
            ])
            ->build(),
    ];
}
```

### `Widget_Builder` API
```php
Widget_Builder::make( $widget_type_string )
    ->settings( array )
    ->is_locked( bool )
    ->editor_settings( array )
    ->build()
```

### `Element_Builder` API (containers)
```php
Element_Builder::make( $element_type_string )
    ->settings( array )
    ->is_locked( bool )
    ->editor_settings( array )
    ->children( array )                // nested elements
    ->build()
```

### Element generate() shortcut

Each widget/element class has `::generate()` returning a fresh builder:

```php
Atomic_Tab::generate()
    ->editor_settings([ 'title' => 'Tab 1' ])
    ->is_locked(true)
    ->build()
```

---

## 12. Registration

### Widget registration (`Atomic_Widget_Base`)

```php
add_filter( 'elementor/widgets/register', function( $widgets_manager ) {
    $widgets_manager->register( new My_Widget() );
});
```

### Element registration (`Atomic_Element_Base`)

```php
add_action( 'elementor/elements/elements_registered', function( $elements_manager ) {
    $elements_manager->register_element_type( new My_Element() );
});
```

### Frontend script handler registration

```php
add_action( 'elementor/frontend/before_register_scripts', function() {
    wp_register_script(
        'my-widget-handler',
        $url . '/js/my-widget-handler.min.js',
        [ Frontend_Assets_Loader::FRONTEND_HANDLERS_HANDLE ],
        $version,
        true
    );
});

// Then in widget class:
public function get_script_depends() {
    return array_merge(
        parent::get_script_depends(),
        [ 'my-widget-handler' ]
    );
}
```

### Module bootstrap pattern (Elementor's own)

```php
class My_Atomic_Module {
    public function __construct() {
        $this->register_hooks();
    }

    private function register_hooks() {
        add_filter( 'elementor/widgets/register', fn( $m ) => $this->register_widgets( $m ) );
        add_action( 'elementor/elements/elements_registered', fn( $m ) => $this->register_elements( $m ) );
        add_action( 'elementor/frontend/before_register_scripts', fn() => $this->register_frontend_scripts() );
    }

    private function register_widgets( $widgets_manager ) {
        $widgets_manager->register( new My_Widget() );
    }

    private function register_elements( $elements_manager ) {
        $elements_manager->register_element_type( new My_Container() );
    }
}
```

---

## 13. V3 → V4 Conversion Mapping

### Class hierarchy
| V3 | V4 |
|---|---|
| `extends \Elementor\Widget_Base` | `extends Atomic_Widget_Base` (or `Atomic_Element_Base` for containers) |
| `use Some_Trait` | `use Has_Template` or `use Has_Element_Template` |

### Methods
| V3 method | V4 method |
|---|---|
| `get_name()` | `get_element_type()` (static) |
| `get_title()` | `get_title()` (same) |
| `get_icon()` | `get_icon()` (same) |
| `get_categories()` | `define_panel_categories()` (Element_Base) / categories config |
| `get_keywords()` | `get_keywords()` (same) |
| `_register_controls()` | Split into 2: `define_props_schema()` (static) + `define_atomic_controls()` |
| `render()` (PHP HTML output) | `get_templates()` returning Twig path |
| `content_template()` (Underscore.js) | No equivalent — Twig handles both editor + frontend |
| `get_script_depends()` | `get_script_depends()` (same) |
| `get_style_depends()` | `get_style_depends()` (same — Element_Base only) |

### Control declaration
| V3 | V4 |
|---|---|
| `$this->start_controls_section()` | `Section::make()` declarative |
| `$this->add_control()` | `Some_Control::bind_to()` declarative |
| `$this->add_responsive_control()` | ❌ Per-control responsive not supported — use `Style_Variant::set_breakpoint()` for base styles or manually register 3 props |
| `$this->add_group_control()` | ❌ Use the equivalent atomic prop type (Background_Prop_Type, etc.) |
| `$this->end_controls_section()` | Implicit (end of array) |
| `'condition' => [...]` | `set_dependencies( Dependency_Manager::make()->where(...) )` |

### Settings access
| V3 | V4 |
|---|---|
| `$settings = $this->get_settings_for_display()` | `$settings = $this->get_atomic_settings()` |
| `$settings['my_key']` | `$settings['my_key']` (same — but value might be wrapped) |
| `$this->add_render_attribute()` | Same (when overriding `add_render_attributes()`) |

### Editor JS
| V3 | V4 |
|---|---|
| Underscore `<#%` templates | Twig `.html.twig` files |
| `model.get('settings').get('key')` | `container.settings.get('key')` |
| `elementor.channels.editor.on('change', ...)` | `frame.contentWindow.addEventListener('elementor/element/render', ...)` |

### Frontend
| V3 | V4 |
|---|---|
| `.elementor-element-{id}` CSS selector | `[data-interaction-id="{id}"]` |
| `elementor/widget/render_content` filter | Twig template — no PHP filter for content |
| `elementor/frontend/widget/before_render` action | `elementor/frontend/the_content` filter (universal) |

---

## 14. Real Widget Catalog

কোন existing widget reference হিসেবে copy/study করবে — use case অনুযায়ী।

### Simple text widget with link → `atomic-heading` or `atomic-paragraph`
- `define_props_schema()` with `Classes`, `Html_V3`, `String` (tag), `Link`, `Attributes`
- `Inline_Editing_Control` + `Select_Control` (tag) + `Link_Control`
- 2 base styles: `'base'` + `'link-base'`

### Button (text + link, fancy default styles) → `atomic-button`
- Same as heading but more elaborate `define_base_styles()` (background, padding, border-radius)
- Twig: button OR anchor tag depending on link presence

### Media widget (image picker) → `atomic-image`
- `Image_Prop_Type` with `->default_url()`, `->default_size()`
- `Image_Control::bind_to()` for picker UI
- Twig iterates `settings.image` properties

### SVG widget → `atomic-svg`
- `Svg_Src_Prop_Type` with `->default_url()`
- Static constants for default SVG path
- `Svg_Control::bind_to()`

### Boolean settings galore (many switches) → `atomic-youtube`
- All `Boolean_Prop_Type::make()->default()` props
- Many `Switch_Control::bind_to()` controls
- Optional Text_Control for URL with `Dynamic_Prop_Type::ignore()` for some

### Conditional props (one prop hides based on another) → `atomic-self-hosted-video`
- `Dependency_Manager::make()->where([...])->get()`
- Many examples: `playsinline` hidden when `autoplay=true`, `download` hidden when `controls=false`

### Container with children + dependency on link → `div-block` or `flexbox`
- Extends `Atomic_Element_Base` (not Widget_Base)
- `$this->meta('is_container', true)` in constructor
- `define_default_html_tag()` returns 'div'
- Custom `add_render_attributes()` for HTML wrapper class
- Tag has dependency that changes when link is set
- No Twig template — uses default render flow

### Container with Twig + nested children → `atomic-form`
- Extends `Atomic_Element_Base`
- `use Has_Element_Template`
- `define_default_children()` populated with default form fields via `Widget_Builder` + `Element_Builder`
- `define_default_html_tag()` returns 'form'
- Custom `build_template_context()` override

### Complex multi-element widget → `atomic-tabs/*`
- Main `Atomic_Tabs` element + child elements (`Atomic_Tab`, `Atomic_Tab_Content`, `Atomic_Tabs_Menu`, `Atomic_Tabs_Content_Area`)
- All extend `Atomic_Element_Base` + `use Has_Element_Template`
- `Tabs_Control` for menu items in panel
- `define_render_context()` passes data to children via Render_Context stack
- Frontend handlers: Alpine.js based

---

## 15. Hello World

### Minimum-viable atomic widget (4 files)

#### `inc/elements/my-widget/my-widget.php`

```php
<?php
namespace MyPlugin\Elements\My_Widget;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) exit;

class My_Widget extends Atomic_Widget_Base {
    use Has_Template;

    public static function get_element_type(): string {
        return 'e-my-widget';
    }

    public function get_title() {
        return esc_html__( 'My Widget', 'my-plugin' );
    }

    public function get_icon() {
        return 'eicon-star';
    }

    public function get_keywords() {
        return [ 'my', 'custom' ];
    }

    protected static function define_props_schema(): array {
        return [
            'classes' => Classes_Prop_Type::make()->default( [] ),
            'content' => Html_V3_Prop_Type::make()
                ->default([
                    'content' => String_Prop_Type::generate( __( 'Hello World', 'my-plugin' ) ),
                    'children' => [],
                ]),
            'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
        ];
    }

    protected function define_atomic_controls(): array {
        return [
            Section::make()
                ->set_label( __( 'Content', 'my-plugin' ) )
                ->set_items([
                    Inline_Editing_Control::bind_to( 'content' )
                        ->set_label( __( 'Content', 'my-plugin' ) )
                        ->set_placeholder( __( 'Type here', 'my-plugin' ) ),
                ]),
            Section::make()
                ->set_label( __( 'Settings', 'my-plugin' ) )
                ->set_id( 'settings' )
                ->set_items([
                    Text_Control::bind_to( '_cssid' )
                        ->set_label( __( 'ID', 'my-plugin' ) )
                        ->set_meta( $this->get_css_id_control_meta() ),
                ]),
        ];
    }

    protected function define_base_styles(): array {
        return [
            'base' => Style_Definition::make()
                ->add_variant( Style_Variant::make() ),
        ];
    }

    protected function get_templates(): array {
        return [
            'my-plugin/elements/my-widget' => __DIR__ . '/my-widget.html.twig',
        ];
    }
}
```

#### `inc/elements/my-widget/my-widget.html.twig`

```twig
{% if settings.content is not empty %}
    {% set classes = settings.classes | merge( [ base_styles.base ] ) | join(' ') %}
    {% set id_attribute = settings._cssid is not empty ? 'id=' ~ settings._cssid | e('html_attr') : '' %}
    {% set allowed_tags = '<b><strong><em><i><u><a><span><br>' %}

    <div
        class="{{ classes }}"
        data-interaction-id="{{ interaction_id }}"
        {{ id_attribute }}
        {{ settings.attributes | raw }}
    >
        {{ settings.content | striptags(allowed_tags) | raw }}
    </div>
{% endif %}
```

#### Plugin bootstrap (somewhere in your main plugin file)

```php
add_action( 'elementor/init', function () {
    require_once __DIR__ . '/inc/elements/my-widget/my-widget.php';
});

add_filter( 'elementor/widgets/register', function( $widgets_manager ) {
    $widgets_manager->register( new \MyPlugin\Elements\My_Widget\My_Widget() );
});
```

---

## Final Checklist — New Widget Creation

When making a new v4 widget, verify each step:

- [ ] Class extends `Atomic_Widget_Base` (or `Atomic_Element_Base` if container)
- [ ] `use Has_Template` (or `Has_Element_Template` for containers with children)
- [ ] `get_element_type()` returns unique `e-NAME` string
- [ ] `get_title()`, `get_icon()`, `get_keywords()` implemented
- [ ] `define_props_schema()` returns array with at minimum: `classes`, `attributes`
- [ ] Every prop in schema-এ matching control আছে `define_atomic_controls()`-এ (validated by `validate_schema()`)
- [ ] Every `Control::bind_to('key')` — `'key'` schema-এ register করা
- [ ] `_cssid` control included for ID input (using `String_Prop_Type` — auto-added by framework)
- [ ] `get_templates()` returns `[ 'unique/template/name' => __DIR__ . '/template.html.twig' ]`
- [ ] Twig template uses `data-interaction-id="{{ interaction_id }}"` on wrapper
- [ ] Twig template includes `class="{{ settings.classes | merge([base_styles.base]) | join(' ') }}"`
- [ ] Twig template handles `settings._cssid` for id attribute
- [ ] Twig template outputs `settings.attributes | raw` for custom attributes
- [ ] If using `Html_V3_Prop_Type`, output via `striptags(allowed_tags) | raw`
- [ ] For containers: `define_default_children()` provides reasonable starting children
- [ ] Widget registered via `elementor/widgets/register` filter (or `elementor/elements/elements_registered` for element)
- [ ] Bootstrap deferred via `elementor/init` action

---

## File Structure Recommendation for AAE Plugin

```
animation-addons-for-elementor/
└── inc/
    └── v4-elements/
        ├── loader.php                            (bootstrap, registers all)
        └── widgets/
            ├── my-counter/
            │   ├── my-counter.php
            │   └── my-counter.html.twig
            ├── my-accordion/
            │   ├── my-accordion.php             (Atomic_Element_Base)
            │   └── my-accordion.html.twig
            └── my-icon-box/
                ├── my-icon-box.php
                └── my-icon-box.html.twig
```

**Bootstrap pattern** (`loader.php`):
```php
add_action( 'elementor/init', function () {
    require_once __DIR__ . '/widgets/my-counter/my-counter.php';
    require_once __DIR__ . '/widgets/my-accordion/my-accordion.php';
    require_once __DIR__ . '/widgets/my-icon-box/my-icon-box.php';
});

add_filter( 'elementor/widgets/register', function( $m ) {
    $m->register( new \AAE\V4\My_Counter\My_Counter() );
    $m->register( new \AAE\V4\My_Icon_Box\My_Icon_Box() );
});

add_action( 'elementor/elements/elements_registered', function( $m ) {
    $m->register_element_type( new \AAE\V4\My_Accordion\My_Accordion() );
});
```

---

**Note for future skill:** এই file-টা সব pattern capture করে। Skill বানানোর সময় এই knowledge use করো — কোন pattern কী use করবে use case অনুযায়ী section 14 (Real Widget Catalog) দেখে decide করো।

---

## 16. Section Injection

> ⚠️ **এটা নতুন widget বানানোর জন্য না — existing atomic widget-এ filter দিয়ে section/control inject করার জন্য।**
> Use case: third-party plugin যেটা Elementor-এর existing atomic widget extend করতে চায়।

### Section class API

[modules/atomic-widgets/controls/section.php](wp-content/plugins/elementor/modules/atomic-widgets/controls/section.php):

```php
class Section {
    public static function make(): self
    public function set_id( string $id ): self
    public function get_id()
    public function set_label( string $label ): self
    public function get_label(): ?string
    public function set_description( string $desc ): self
    public function set_items( array $items ): self        // REPLACE all items
    public function add_item( $item ): self                // APPEND single item ⭐
    public function get_items()
}
```

### Three injection strategies — কখন কোনটা use করব

#### 🥇 Strategy B (CANONICAL — Elementor's own pattern) — Existing Section-এ Item Add

**Source verified:** [modules/promotions/module.php:267-294](wp-content/plugins/elementor/modules/promotions/module.php#L267-L294) — Elementor নিজেই এই pattern use করে। তাই **এটাই official "best way"**।

```php
add_filter( 'elementor/atomic-widgets/controls', function ( array $controls, $element ) {
    if ( 'e-heading' !== $element->get_name() ) {
        return $controls;
    }

    foreach ( $controls as $item ) {
        if ( ! ( $item instanceof \Elementor\Modules\AtomicWidgets\Controls\Section ) ) {
            continue;
        }

        // Target existing section by ID (atomic widgets-এ 'settings' standard)
        if ( $item->get_id() !== 'settings' ) {
            continue;
        }

        $item->add_item(
            Select_Control::bind_to( 'my_prop' )
                ->set_label( 'My Control' )
                ->set_meta( [ 'topDivider' => true ] )
        );

        break;
    }

    return $controls;
}, 10, 2 );
```

**Editor UX:** Native "Settings" section-এর শেষে control append হয় — user-এর কাছে seamless লাগে। Plugin-অর integration looks natural।

**Use when:** 1-3 extra controls যেগুলো logically existing settings-এর সাথে fit করে।

---

#### Strategy A — New Top-level Section

```php
add_filter( 'elementor/atomic-widgets/controls', function ( array $controls, $element ) {
    if ( 'e-heading' !== $element->get_name() ) return $controls;

    $controls[] = Section::make()
        ->set_id( 'my_section' )
        ->set_label( __( 'My Section', 'textdomain' ) )
        ->set_items([
            Select_Control::bind_to( 'my_color' )->set_label( 'Color' ),
            Select_Control::bind_to( 'my_size' )->set_label( 'Size' ),
        ]);

    return $controls;
}, 10, 2 );
```

**Use when:** Brand-new feature group যেটা separate identity দরকার (যেমন "AAE Animation", "Custom Effects")।

---

#### Strategy C — Nested Sub-section

```php
// Top-level section-এর items-এ আরেকটা Section
$controls[] = Section::make()
    ->set_id( 'my_advanced' )
    ->set_label( 'My Advanced' )
    ->set_items([
        Section::make()
            ->set_id( 'my_colors_subsection' )
            ->set_label( 'Colors' )
            ->set_items([ /* controls */ ]),

        Section::make()
            ->set_id( 'my_borders_subsection' )
            ->set_label( 'Borders' )
            ->set_items([ /* controls */ ]),
    ]);
```

`Has_Atomic_Base::get_valid_controls()` recursively walks sections — nested sections support করে।

**Use when:** Many related controls যেগুলো sub-groups-এ organize করতে চাই।

---

### Atomic widgets-এর section IDs যা target করা যাবে (Strategy B-র জন্য)

প্রতি atomic widget-এর `define_atomic_controls()` source code পড়ে verified:

| Widget | Section IDs (where `set_id()` called) |
|---|---|
| `e-heading`, `e-button`, `e-paragraph`, `e-image`, `e-svg`, `e-divider` | `'settings'` |
| `e-self-hosted-video`, `e-youtube` | `'settings'` |
| `e-tabs` | `'content'`, `'settings'` |
| `e-form` | `'settings'` |
| `e-flexbox`, `e-div-block` | `'settings'` |

**সব widget-এ `'settings'` section আছে** — তাই এটাই safe universal target।

⚠️ **"Content" section-এ ID set করা নেই** অনেক widget-এ (e.g., atomic-button, atomic-heading content section)। Target করতে হলে label match করতে হবে যা fragile (i18n-এ break হবে)।

---

### Decision Matrix

| Use Case | Strategy | Reason |
|---|---|---|
| Plugin promotion/feature label inside native settings | **B** | Canonical Elementor pattern |
| Brand-new feature group (own label) | **A** | Clear identity |
| Animation controls / styling extensions | **A** | Better organization |
| 1-2 small toggles related to existing settings | **B** | Native UX |
| Many controls in sub-groups | **C** | Hierarchy |

---

### Pattern Comparison — same goal, 3 ways

**Goal:** "Background Color" dropdown inject করো atomic-heading-এ।

```php
// ─── Strategy A: New section (visible separation) ───────────
$controls[] = Section::make()
    ->set_id( 'aae_bg' )
    ->set_label( 'AAE Background' )
    ->set_items([ Select_Control::bind_to('aae_color')->set_label('Color') ]);

// ─── Strategy B: Add to existing 'settings' (native UX) ─────
foreach ( $controls as $item ) {
    if ( $item instanceof Section && $item->get_id() === 'settings' ) {
        $item->add_item(
            Select_Control::bind_to('aae_color')
                ->set_label('Background')
                ->set_meta(['topDivider' => true])
        );
        break;
    }
}

// ─── Strategy C: Nested inside new top-level ────────────────
$controls[] = Section::make()
    ->set_label('AAE Advanced')
    ->set_items([
        Section::make()
            ->set_label('Color Options')
            ->set_items([ Select_Control::bind_to('aae_color')->set_label('Color') ]),
    ]);
```

---

### Key takeaways for skill builder

1. **`Section::add_item()` vs `set_items()`** — `set_items()` overwrites all, `add_item()` appends one (chainable but mutates in place)
2. **Strategy B = canonical** — Elementor's promotions module uses it। When in doubt, default to Strategy B।
3. **`'settings'` is universal target** — every atomic widget has this section ID
4. **Section IDs from atomic widgets** discoverable by reading their `define_atomic_controls()` and grepping `->set_id(`
5. **Order matters** — append (Strategy B) puts control at end of section; controls panel reads array order top-to-bottom
