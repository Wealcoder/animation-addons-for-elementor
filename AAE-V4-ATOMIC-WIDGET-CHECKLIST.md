# Elementor V4 Atomic Widget Creation Guide & Checklist

This document provides a concise, step-by-step guide to creating new Elementor V4 Atomic Widgets for the AAE (Animation Addons for Elementor) plugin. It incorporates essential lessons learned from developing the Counter, NestedSlider, and Menu widgets.

## 1. Directory Structure

Create your widget folder inside `inc/AtomicWidgets/Widgets/[WidgetName]`:
```text
[WidgetName]/
├── assets/
│   ├── css/          # (Don't use! SCSS goes in scss/)
│   ├── scss/
│   │   └── widget.scss
│   └── js/
│       └── widget.js
├── class-aae-a-widget.php
└── aae-a-widget.html.twig
```

## 2. PHP Class (`class-aae-a-widget.php`)

Your PHP class must extend `Atomic_Widget_Base` and implement `Has_Template`.

### Key Methods:
- `get_element_type()`: Returns the unique internal name (e.g., `e-aae-a-menu`).
- `define_props_schema()`: Define your widget's data properties (e.g., `String_Prop_Type`, `Classes_Prop_Type`).
- `define_atomic_controls()`: Bind controls to your props.

### ⚠️ CRITICAL RULES FOR CONTROLS:
**Select_Control** requires an array of associative arrays, **NOT** a standard key-value map.
```php
// ❌ WRONG (Classic Elementor Style)
->set_options([ 'horizontal' => 'Horizontal', 'vertical' => 'Vertical' ])

// ✅ CORRECT (Elementor V4 Atomic Style)
->set_options([
    [ 'value' => 'horizontal', 'label' => 'Horizontal' ],
    [ 'value' => 'vertical',   'label' => 'Vertical'   ]
])
```

## 3. Twig Template (`aae-a-widget.html.twig`)

Elementor V4 renders Atomic widgets using Twig.
- **Frontend**: Rendered via PHP.
- **Editor**: Rendered via pure JavaScript (`twing.js`).

### ⚠️ CRITICAL RULES FOR TWIG:
Because the Editor renders Twig in JavaScript, variables generated dynamically in PHP (via `get_atomic_settings()`) will be `undefined` in the Editor.
If you use the `|raw` filter on an undefined variable, the **editor will crash** with `Cannot read properties of undefined (reading 'toString')`.

**Always use `|default('')` before `|raw`:**
```twig
// ❌ WRONG (Will crash editor if undefined)
{{ settings.attributes | raw }}

// ✅ CORRECT
{{ settings.attributes | default('') | raw }}
```

## 4. Javascript Handler (`assets/js/widget.js`)

Your JS must be structured as an ES6 module and register with `@elementor/frontend-handlers`.

```javascript
import { register } from '@elementor/frontend-handlers';

const initWidget = (container) => {
    // 1. GSAP Logic goes here
    if (typeof window.gsap !== 'undefined') {
        window.gsap.to(container, { ... });
    }
};

register({
    elementType: 'e-aae-a-widget',
    id: 'aae-a-widget-handler',
    callback: ({ element }) => {
        // Elementor may pass the wrapper or the exact element. 
        const container = element.classList.contains('aae-a-widget') ? element : element.querySelector('.aae-a-widget');
        if (container) initWidget(container);
    }
});
```

### Server-Side Rendering (SSR) in the Editor
If your widget relies heavily on PHP functions (like `wp_nav_menu()`), the JS engine cannot render it natively. You must fetch the HTML via AJAX:
1. Provide a fallback placeholder in Twig: `<div class="placeholder"></div>`
2. Add a `wp_ajax_*` endpoint in `class-atomic.php`.
3. In your JS `init` function, check if the editor is active and fetch the HTML:
```javascript
if (typeof window.elementorFrontend !== 'undefined' && window.elementorFrontend.isEditMode()) {
    const ajaxUrl = elementorFrontend.config.ajaxurl || '/wp-admin/admin-ajax.php';
    fetch(`${ajaxUrl}?action=your_action`).then(res => res.json()).then(html => {
        container.innerHTML = html.data;
        // Re-initialize GSAP on the new HTML here!
    });
}
```

## 5. Styling (`assets/scss/widget.scss`)

- Do NOT write raw `.css` files. Always write `.scss` inside the `assets/scss/` directory.
- The build pipeline (`gulp compile:atomic-scss`) automatically finds `inc/AtomicWidgets/Widgets/**/*.scss` and outputs minified CSS into `assets/atomic/css/`.

## 6. Registration (`class-atomic.php`)

Add your widget to the `get_available_widgets()` array in `class-atomic.php`:
```php
'aae-a-widget' => [
    'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\WidgetName\AAE_A_Widget',
    'file' => 'Widgets/WidgetName/class-aae-a-widget.php',
    'script_handle' => 'aae-a-widget-js',
    'script_path' => '/assets/atomic/js/widget.js', // Output from Webpack
    'has_script' => true,
    'style_handle' => 'aae-a-widget-css',
    'style_path' => '/assets/atomic/css/widget.css', // Output from Gulp
],
```

## 7. Build Pipeline

Whenever you create or modify JS or SCSS files, you must run the build commands for them to appear in the `assets/atomic/` directory:
- JS Build: `npm run build` (runs wp-scripts / webpack)
- SCSS Build: `npm run buildCss` (or `gulp buildCss`)

## Summary Checklist
- [ ] Created Folder and files (PHP, Twig, JS, SCSS)
- [ ] Registered widget in `class-atomic.php` with paths pointing to `/assets/atomic/...`
- [ ] Used `[['value' => '...', 'label' => '...']]` array structure for `Select_Control`
- [ ] Handled `|default('')` for `|raw` filters in Twig to prevent Editor crashes
- [ ] Initialized GSAP within the `@elementor/frontend-handlers` callback
- [ ] Implemented Editor AJAX fallback for dynamic PHP data
- [ ] Ran Build commands (`npm run build`)
