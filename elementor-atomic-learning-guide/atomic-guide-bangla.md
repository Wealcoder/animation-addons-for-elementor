# Elementor v4 Atomic Widget — সম্পূর্ণ বাংলা গাইড 🇧🇩

> এই গাইডটি পড়ে আপনি নিজে একদম শূন্য থেকে Elementor v4 এর নতুন **Atomic Widget** তৈরি করতে পারবেন। কোনো অফিশিয়াল ডকুমেন্টেশন নেই, তাই সব কিছু সোর্স কোড থেকে বের করা হয়েছে।

---

## 📚 সূচিপত্র

1. [Atomic Widget কী? (v3 vs v4 পার্থক্য)](#1-atomic-widget-কী)
2. [ফোল্ডার স্ট্রাকচার ও প্রয়োজনীয় ফাইল](#2-ফোল্ডার-স্ট্রাকচার)
3. [২ ধরনের Atomic Base ক্লাস](#3-২-ধরনের-base-ক্লাস)
4. [Props Schema — ডেটা স্ট্রাকচার](#4-props-schema)
5. [Atomic Controls — এডিটর প্যানেল](#5-atomic-controls)
6. [Base Styles — ডিফল্ট স্টাইল](#6-base-styles)
7. [Twig Template — রেন্ডার পদ্ধতি](#7-twig-template)
8. [Widget রেজিস্ট্রেশন](#8-রেজিস্ট্রেশন)
9. [গুরুত্বপূর্ণ Hooks](#9-guruত্বপূর্ণ-hooks)
10. [উদাহরণ: সম্পূর্ণ Alert Widget](#10-উদাহরণ)
11. [কন্ট্রোল ও Prop Type চিটশিট](#11-chitasit)
12. [ডিবাগিং টিপস](#12-dibaging)

---

## 1. Atomic Widget কী?

### v3 (Widget_Base) vs v4 (Atomic_Widget_Base) — পার্থক্য

| বিষয় | v3 (পুরোনো) | v4 (Atomic) |
|------|-------------|-------------|
| **রেন্ডার** | PHP `render()` method এ HTML echo করা | **Twig template** ফাইল (`.html.twig`) |
| **কন্ট্রোল** | `_register_controls()` এ `$this->add_control()` | **Section + Control object** chain (declarative) |
| **স্টাইল** | `$this->add_responsive_control()` + CSS selector | **Style_Definition / Style_Variant** (declarative, auto-generated CSS) |
| **প্যানেল** | Tab → Section → Control (বড় স্ট্রাকচার) | শুধু **Section → Control** (সরল) |
| **সেটিংস** | plain key-value (যেমন `'text' => 'Hello'`) | **Typed props** (যেমন `{ $$type: 'string', value: 'Hello' }`) |
| **Schema** | নেই | **`define_props_schema()`** — প্রতিটি ফিল্ডের টাইপ ঘোষণা |
| **Validation** | ম্যানুয়াল | **অটোমেটিক** (schema থেকে যাচাই হয়) |
| **Inline edit** | আলাদা | **Inline_Editing_Control::bind_to('field')** |
| **ক্যাটেগরি** | `'general'` | **`'v4-elements'`** |

### মূল সুবিধা
- ✅ **Declarative** — কম কোড, বেশি কাজ
- ✅ **Auto validation** — schema থেকে সব যাচাই হয়
- ✅ **Twig template** — HTML ও PHP আলাদা
- ✅ **Auto CSS generation** — Style_Definition থেকে
- ✅ **Live preview** — editor এ রিয়েল-টাইম

---

## 2. ফোল্ডার স্ট্রাকচার

প্রতিটি Atomic Widget এর জন্য ন্যূনতম **২টি ফাইল** লাগে:

```
my-widget/
├── my-widget.php          ← মূল PHP ক্লাস
└── my-widget.html.twig    ← Twig HTML টেমপ্লেট
```

এটি একটি সম্পূর্ণ PHP ক্লাসের স্ট্রাকচার:

```php
<?php
namespace MyPlugin\Widgets\My_Widget;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
// ... বাকি use স্টেটমেন্ট

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_Widget extends Atomic_Widget_Base {
    use Has_Template;  // ← Twig রেন্ডার করার জন্য আবশ্যক

    // ১. Element type (unique ID)
    public static function get_element_type(): string {
        return 'e-my-widget';
    }

    // ২. টাইটেল, আইকন, কিওয়ার্ড
    public function get_title() { return esc_html__( 'My Widget', 'my-plugin' ); }
    public function get_icon() { return 'eicon-icon-box'; }
    public function get_keywords() { return [ 'ato', 'atom', 'atomic' ]; }

    // ৩. Props Schema (ডেটা স্ট্রাকচার)
    protected static function define_props_schema(): array {
        return [ /* ... */ ];
    }

    // ৪. Atomic Controls (এডিটর প্যানেল)
    protected function define_atomic_controls(): array {
        return [ /* ... */ ];
    }

    // ৫. Base Styles (ডিফল্ট স্টাইল)
    protected function define_base_styles(): array {
        return [ /* ... */ ];
    }

    // ৬. Twig টেমপ্লেট পাথ
    protected function get_templates(): array {
        return [
            'elementor/elements/my-widget' => __DIR__ . '/my-widget.html.twig',
        ];
    }
}
```

---

## 3. ২ ধরনের Base ক্লাস

### A) `Atomic_Widget_Base` — সাধারণ উইজেট
**ব্যবহার:** Button, Heading, Image, Paragraph ইত্যাদি (লিফ উইজেট, যেগুলো বাচ্চা ধারণ করে না)

```php
class Atomic_Button extends Atomic_Widget_Base {
    use Has_Template;
    // ...
}
```

রেজিস্টার করতে হয় `widgets_manager->register()` দিয়ে।

### B) `Atomic_Element_Base` — কন্টেইনার উইজেট
**ব্যবহার:** Div Block, Flexbox, Tabs (যেগুলো বাচ্চা এলিমেন্ট ধারণ করে)

```php
class Div_Block extends Atomic_Element_Base {
    // Has_Template লাগে না — before_render/after_render দিয়ে HTML তৈরি
}
```

রেজিস্টার করতে হয় `elements_manager->register_element_type()` দিয়ে।

> ⚠️ **গুরুত্বপূর্ণ:** `Has_Template` trait শুধু `Atomic_Widget_Base` এর সাথে ব্যবহার করুন। কন্টেইনার এলিমেন্ট (`Atomic_Element_Base`) সাধারণত `before_render()` ও `after_render()` ব্যবহার করে।

---

## 4. Props Schema

**Props Schema হলো** উইজেটের ডেটা স্ট্রাকচারের ঘোষণা — কোন ফিল্ড থাকবে, কী টাইপ, ডিফল্ট ভ্যালু কী।

### কেন Schema দরকার?
1. **Validation** — সেভ করার সময় স্বয়ংক্রিয়ভাবে যাচাই হয়
2. **Default value** — নতুন উইজেট টানলে ডিফল্ট ভ্যালু আসে
3. **Editor binding** — কন্ট্রোল ও schema ফিল্ড এক হতে হবে
4. **Sanitization** — স্বয়ংক্রিয়ভাবে sanitize হয়

### উদাহরণ

```php
protected static function define_props_schema(): array {
    return [
        // ✅ classes সব Atomic Widget এ রাখা উচিত
        'classes' => Classes_Prop_Type::make()
            ->default( [] ),

        // ✅ টেক্সট ফিল্ড (string)
        'title' => String_Prop_Type::make()
            ->default( 'Hello World' ),

        // ✅ টেক্সট যা ভিতরে HTML থাকতে পারে (inline editing)
        'content' => Html_V3_Prop_Type::make()
            ->default( [
                'content'  => String_Prop_Type::generate( 'আমার টেক্সট' ),
                'children' => [],
            ] ),

        // ✅ Select এর জন্য enum
        'tag' => String_Prop_Type::make()
            ->enum( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] )
            ->default( 'h2' ),

        // ✅ রঙ
        'color' => Color_Prop_Type::make()
            ->default( '#333333' ),

        // ✅ Link
        'link' => Link_Prop_Type::make(),

        // ✅ সাইজ (number + unit)
        'font_size' => Size_Prop_Type::make(),

        // ✅ HTML attributes
        'attributes' => Attributes_Prop_Type::make()
            ->meta( Overridable_Prop_Type::ignore() ),

        // ✅ Boolean
        'show_title' => Boolean_Prop_Type::make()
            ->default( true ),
    ];
}
```

### ⚠️ গুরুত্বপূর্ণ নিয়ম
- **প্রতিটি Control এর `bind_to('field')` এর ফিল্ড নাম অবশ্যই schema তে থাকতে হবে** — না হলে error throw হবে।
- `_cssid` ফিল্ড অটোমেটিক schema তে যোগ হয় (CSS ID এর জন্য) — আপনাকে যোগ করতে হবে না।
- `classes` ও `attributes` সব উইজেটে রাখা ভালো।

---

## 5. Atomic Controls

Controls হলো এডিটর প্যানেলের ফর্ম ফিল্ড। এগুলো **Section** এর ভেতরে থাকে।

### স্ট্রাকচার
```
Section (ট্যাবের মতো)
  └── Control (bind_to করে schema ফিল্ডে যুক্ত)
  └── Control
  └── ...
```

### উদাহরণ

```php
protected function define_atomic_controls(): array {
    return [
        // ১ম Section
        Section::make()
            ->set_label( __( 'Content', 'my-plugin' ) )
            ->set_items( [
                // ইনলাইন এডিটিং কন্ট্রোল
                Inline_Editing_Control::bind_to( 'content' )
                    ->set_placeholder( 'আপনার টেক্সট লিখুন' )
                    ->set_label( 'টেক্সট' ),
            ] ),

        // ২য় Section
        Section::make()
            ->set_label( __( 'Settings', 'my-plugin' ) )
            ->set_id( 'settings' )
            ->set_items( [
                // Select
                Select_Control::bind_to( 'tag' )
                    ->set_label( 'HTML Tag' )
                    ->set_options( [
                        [ 'value' => 'h1', 'label' => 'H1' ],
                        [ 'value' => 'h2', 'label' => 'H2' ],
                        [ 'value' => 'h3', 'label' => 'H3' ],
                    ] ),

                // লিংক
                Link_Control::bind_to( 'link' )
                    ->set_label( 'Link' )
                    ->set_placeholder( 'https://...' ),

                // টেক্সট ইনপুট
                Text_Control::bind_to( '_cssid' )
                    ->set_label( 'CSS ID' )
                    ->set_meta( $this->get_css_id_control_meta() ),
            ] ),
    ];
}
```

### প্রতিটি Control এর মূল পদ্ধতি
- **`bind_to( 'field_name' )`** (static) — schema এর কোন ফিল্ডে যুক্ত হবে
- **`set_label()`** — লেবেল টেক্সট
- **`set_placeholder()`** — প্লেসহোল্ডার
- **`set_meta()`** — বাড়তি কনফিগ (যেমন `topDivider`, `layout`)

---

## 6. Base Styles

Base Styles হলো উইজেটের ডিফল্ট স্টাইল যা **স্বয়ংক্রিয়ভাবে CSS class** তৈরি করে। এই class আপনি Twig template এ যুক্ত করেন।

### স্ট্রাকচার
```
Style_Definition (একটি class এর সমার্থক)
  └── Style_Variant (একটি state/breakpoint variant)
        ├── add_prop('css-prop', value)
        └── add_prop('css-prop', value)
```

### উদাহরণ (Button থেকে)

```php
protected function define_base_styles(): array {
    $background_color = Background_Prop_Type::generate( [
        'color' => Color_Prop_Type::generate( '#375EFB' ),
    ] );

    $padding = Dimensions_Prop_Type::generate( [
        'block-start'  => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
        'inline-end'   => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
        'block-end'    => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
        'inline-start' => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
    ] );

    return [
        // key 'base' → Twig এ base_styles.base হিসেবে পাবেন
        'base' => Style_Definition::make()
            ->add_variant(
                Style_Variant::make()
                    ->add_prop( 'background', $background_color )
                    ->add_prop( 'padding', $padding )
                    ->add_prop( 'text-align', 'center' )
                    ->add_prop( 'display', 'inline-block' )
            ),
    ];
}
```

Twig এ ব্যবহার:
```twig
{% set classes = settings.classes | merge( [ base_styles.base ] ) | join(' ') %}
<button class="{{ classes }}">...</button>
```

> 💡 **টিপ:** উইজেটে ক্লাস যোগ করার সময় `base_styles.base` (অথবা আপনার key) অবশ্যই যুক্ত করুন, নাহলে ডিফল্ট স্টাইল কাজ করবে না।

---

## 7. Twig Template

Atomic Widget **Twig টেমপ্লেট দিয়ে রেন্ডার** হয় (PHP এর `render()` নয়)।

### টেমপ্লেটে যা যা পাওয়া যায় (context variables)

| Variable | ব্যাখ্যা |
|----------|---------|
| `settings` | সব atomic settings (resolved) |
| `settings.classes` | ইউজারের দেওয়া কাস্টম CSS classes |
| `settings._cssid` | CSS ID |
| `settings.attributes` | কাস্টম HTML attributes (raw string) |
| `base_styles` | base styles এর class name dictionary |
| `interaction_id` | interaction এর জন্য unique ID |
| `id` | widget ID |
| `type` | widget type |

### Button উদাহরণ

```twig
{% if settings.text is not empty %}
    {# শুধু নির্দিষ্ট HTML ট্যাগ অনুমোদিত #}
    {% set allowed_tags = '<b><strong><sup><sub><s><em><i><u><del><span><br>' %}

    {# class তৈরি: ইউজার classes + base style class #}
    {% set classes = settings.classes | merge( [ base_styles.base ] ) | join(' ') %}

    {# ID attribute #}
    {% set id_attribute = settings._cssid is not empty ? 'id=' ~ settings._cssid | e('html_attr') : '' %}

    {% if settings.link and settings.link.attributes is not empty %}
        {# Link আছে → <a> ট্যাগ #}
        <{{ settings.link.tag | e('html_tag') }}
            {{ settings.link.attributes | raw }}
            class="{{ classes }}"
            data-interaction-id="{{ interaction_id }}"
            {{ id_attribute }} {{ settings.attributes | raw }}
        >
            {{ settings.text | striptags(allowed_tags) | raw }}
        </{{ settings.link.tag | e('html_tag') }}>
    {% else %}
        {# Link নেই → <button> ট্যাগ #}
        <button class="{{ classes }}" data-interaction-id="{{ interaction_id }}" {{ id_attribute }} {{ settings.attributes | raw }}>
            {{ settings.text | striptags(allowed_tags) | raw }}
        </button>
    {% endif %}
{% endif %}
```

### Twig এ নিরাপত্তা (Security)
- **`| raw`** — এস্কেপিং বন্ধ (শুধু বিশ্বস্ত ডেটার জন্য, যেমন `link.attributes`)
- **`| e('html_attr')`** — attribute এস্কেপ
- **`| e('html_tag')`** — HTML tag এস্কেপ
- **`| striptags(allowed_tags)`** — শুধু নির্দিষ্ট ট্যাগ রাখা
- ⚠️ ইউজারের দেওয়া HTML এ সরাসরি `| raw` ব্যবহার করবেন না

---

## 8. রেজিস্ট্রেশন

### A) Widget (`Atomic_Widget_Base`) রেজিস্ট্রেশন

```php
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    $widgets_manager->register( new \MyPlugin\Widgets\Alert_Box\Alert_Box() );
} );
```

### B) Element (`Atomic_Element_Base`) রেজিস্ট্রেশন

```php
add_action( 'elementor/elements/elements_registered', function( $elements_manager ) {
    $elements_manager->register_element_type( new \MyPlugin\Elements\Container_Box\Container_Box() );
} );
```

> ⚠️ দুটি আলাদা hook এবং আলাদা method! ভুল করলে কাজ করবে না।

---

## 9. গুরুত্বপূর্ণ Hooks

### Props Schema modify করার hook
```php
add_filter( 'elementor/atomic-widgets/props-schema', function( $schema ) {
    // সব বা নির্দিষ্ট উইজেটের schema বদলান
    return $schema;
} );
```

### Controls modify করার hook
```php
add_filter( 'elementor/atomic-widgets/controls', function( $controls, $widget ) {
    if ( 'e-my-widget' === $widget->get_name() ) {
        // নির্দিষ্ট উইজেটের controls বদলান
    }
    return $controls;
}, 10, 2 );
```

### Frontend scripts রেজিস্টার করার hook
```php
add_action( 'elementor/atomic-widgets/frontend/loader/scripts/register', function( $loader ) {
    // কাস্টম JS রেজিস্টার করুন
} );
```

### Settings transformers রেজিস্টার করার hook
```php
add_action( 'elementor/atomic-widgets/settings/transformers/register', function( $transformers ) {
    // কাস্টম prop type এর জন্য transformer
} );
```

### Styles transformers রেজিস্টার করার hook
```php
add_action( 'elementor/atomic-widgets/styles/transformers/register', function( $transformers ) {
    // কাস্টম style এর জন্য transformer
} );
```

---

## 10. উদাহরণ: সম্পূর্ণ Alert Box Widget

আমি এই গাইডের সাথে একটি সম্পূর্ণ **Alert Box** উইজেট দিয়েছি (`alert-box.php` + `alert-box.html.twig`)। ফাইলগুলো `example/` ফোল্ডারে আছে।

এটি আপনাকে দেখায়:
- ✅ `define_props_schema` — সম্পূর্ণ schema
- ✅ `define_atomic_controls` — Section, multiple controls
- ✅ `define_base_styles` — Color, padding, border-radius
- ✅ `get_templates` — Twig টেমপ্লেট পাথ
- ✅ Twig টেমপ্লেটে সব কন্টেক্সট ব্যবহার

---

## 11. Control ও Prop Type চিটশিট

### Prop Types (schema এর জন্য)

| Prop Type | ব্যবহার | Example |
|-----------|---------|---------|
| `String_Prop_Type` | টেক্সট | `::make()->default('Hello')` |
| `Number_Prop_Type` | সংখ্যা | `::make()->default(10)` |
| `Boolean_Prop_Type` | বুলিয়ান | `::make()->default(true)` |
| `Color_Prop_Type` | রঙ | `::make()->default('#fff')` |
| `Size_Prop_Type` | সাইজ + unit | `::generate(['size'=>12,'unit'=>'px'])` |
| `Link_Prop_Type` | লিংক | `::make()` |
| `Image_Prop_Type` | ছবি | `::make()->default_url(...)` |
| `Classes_Prop_Type` | CSS classes | `::make()->default([])` |
| `Attributes_Prop_Type` | HTML attributes | `::make()->meta(Overridable_Prop_Type::ignore())` |
| `Html_V3_Prop_Type` | Inline-editable HTML | কমপ্লেক্স স্ট্রাকচার |
| `Background_Prop_Type` | ব্যাকগ্রাউন্ড | `::generate(['color'=>...])` |
| `Dimensions_Prop_Type` | প্যাডিং/মার্জিন | কমপ্লেক্স স্ট্রাকচার |

### Controls (প্যানেলের ফর্ম)

| Control | ব্যবহার |
|---------|---------|
| `Text_Control` | টেক্সট ইনপুট |
| `Textarea_Control` | মাল্টিলাইন টেক্সট |
| `Number_Control` | সংখ্যা ইনপুট (min/max/step) |
| `Select_Control` | ড্রপডাউন |
| `Switch_Control` | অন/অফ টগল |
| `Toggle_Control` | বহু-অপশন টগল |
| `Link_Control` | লিংক পিকার |
| `Image_Control` | ইমেজ আপলোডার |
| `Inline_Editing_Control` | ইনলাইন এডিটিং |
| `Html_Tag_Control` | HTML tag নির্বাচক |
| `Date_Time_Control` | তারিখ/সময় |
| `Svg_Control` | SVG আপলোডার |
| `Video_Control` | ভিডিও |
| `Chips_Control` | চিপস |

---

## 12. ডিবাগিং টিপস

### 1. WP_DEBUG চালু রাখুন
`wp-config.php` এ:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```
ত্রুটি `wp-content/debug.log` এ যাবে।

### 2. Schema validation error
যদি error আসে `Prop 'xxx' is not defined in the schema` — মানে আপনি `bind_to('xxx')` করেছেন কিন্তু schema তে `'xxx'` নেই। Schema তে যোগ করুন।

### 3. Twig template লোড হচ্ছে না
চেক করুন:
- `get_templates()` সঠিক path ফেরত দিচ্ছে কিনা (`__DIR__ . '/file.twig'`)
- Twig ফাইলের নাম ও key সঠিক কিনা

### 4. Elementor debug mode
```php
if ( Utils::is_elementor_debug() ) {
    // Twig exception throw হবে
}
```

### 5. সব use স্টেটমেন্ট ঠিক আছে কিনা যাচাই করুন
সবচেয়ে সাধারণ ভুল হলো namespace import করা ব্যাক পড়ে যাওয়া। উদাহরণ:
```php
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
```

---

## 🎁 সারসংক্ষেপ — নতুন উইজেট তৈরির চেকলিস্ট

1. [ ] ফোল্ডার তৈরি করুন (যেমন `alert-box/`)
2. [ ] `alert-box.php` তে `Atomic_Widget_Base` extend করুন
3. [ ] `Has_Template` trait যুক্ত করুন
4. [ ] `get_element_type()` — unique slug দিন (`e-alert-box`)
5. [ ] `get_title()`, `get_icon()`, `get_keywords()` পূরণ করুন
6. [ ] `define_props_schema()` — সব ফিল্ড ঘোষণা করুন
7. [ ] `define_atomic_controls()` — Section + Control যুক্ত করুন
8. [ ] `define_base_styles()` — ডিফল্ট স্টাইল দিন (ঐচ্ছিক)
9. [ ] `get_templates()` — Twig ফাইলের পাথ দিন
10. [ ] `alert-box.html.twig` — HTML টেমপ্লেট লিখুন
11. [ ] `elementor/widgets/register` hook দিয়ে রেজিস্টার করুন

**ব্যস! আপনার প্রথম Atomic Widget প্রস্তুত! 🎉**

---

> 📘 **অতিরিক্ত রিসোর্স:** এই গাইডের সাথে `example/` ফোল্ডারে সম্পূর্ণ Alert Box উইজেট দেওয়া আছে। সেটি Elementor এর `atomic-widgets` মডিউলে কপি করে রেজিস্টার করলেই দেখতে পাবেন।