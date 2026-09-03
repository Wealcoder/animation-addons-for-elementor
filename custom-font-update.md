# Custom Fonts — Elementor V4 (Atomic) fix

**File:** `inc/class-wcf-custom-fonts.php` (`WCF_ADDONS\Extensions\CustomFonts_Lite`)
**Date:** 2026-09-02
**Verified against:** Elementor 4.2.2, AAE Pro 4.2.0, PHP 8.2

Custom fonts stopped loading on Elementor V4 pages: the CSS said
`font-family: lora` but the browser rendered its serif fallback, because no
`@font-face` for that family was ever printed. This documents what the code
used to do, why V4 broke it, and what it does now.

---

## 1. How it worked before

### Detection — a substring scan of the raw page data

`elementor/frontend/before_get_builder_content` (priority 15) read the page's
raw `_elementor_data` string and looked for each known family name inside it:

```php
$_elementor_data = get_post_meta( $document->get_post()->ID, '_elementor_data', true );

foreach ( $this->configs as $font => $val ) {
    if ( is_string( $_elementor_data ) && str_contains( $_elementor_data, $font ) ) {
        $this->elementor_local_font[ $font ] = $font;
    }
}
```

### Storage — a per-page cache written on every front-end request

Whatever matched was written back to the object being viewed:

| Context | Where the list was stored |
|---|---|
| archive / taxonomy | `update_term_meta( id, 'wcf_addon_custom_fonts', … )` |
| search | option `wcf_addon_custom_fonts_search` |
| 404 | option `wcf_addon_custom_fonts_error` |
| everything else | `update_post_meta( id, 'wcf_addon_custom_fonts', … )` |

Note the post-meta key is the **same key** the Custom Font CPT uses for its own
variation data — the same name meaning two different things depending on post
type.

### Emission

`_custom_webfonts()` (priority 4 on `wcf_addin_pro_custom_webfonts`) read that
stored list back and resolved each name against `$this->configs`.
`global_custom_webfonts()` (priority 9) added every font with the
*Enable For Global* toggle on. The combined list was turned into `@font-face`
rules by one of:

- `push_dynamic_style()` — `wp_enqueue_scripts` **20**, via `wp_add_inline_style()`
- `wp_push_style()` — `wp_head` **20**, via `echo` (used when *Load in head* is on)

---

## 2. What broke, and why

### 2.1 The font name is no longer in `_elementor_data` (the root cause)

Elementor V4 splits styling across three stores; the old scan only ever saw the
first:

| Where the style lives | Stored in | Scan could see it |
|---|---|---|
| Element's own local style | `_elementor_data` (`styles` on the element) | yes |
| **Global class** | `e-global-class` CPT → `_elementor_global_class_data` | **no** |
| **Global variable** | kit meta `_elementor_global_variables`; element holds only `{"$$type":"global-font-variable","value":"e-gv-…"}` | **no** |

Class-based styling is V4's default workflow, so most typography now lives in
the second row. Measured on this install:

```
elementor_atomic_styles_fonts-global-889-frontend  =  ["Adamina"]
post 889 _elementor_data LIKE '%Adamina%'          →  0
_elementor_global_class_data LIKE '%Adamina%'      →  1
```

Page 889 renders in Adamina, and its `_elementor_data` never mentions it. No
number of reloads could fix this — the scan simply had nothing to match.

### 2.2 The list was always one request out of date

The list was **written** during `the_content` but **read** during
`wp_enqueue_scripts` / `wp_head` — earlier in the *same* request. So every page
was styled from the previous request's answer. Reproduced by clearing the cache
row and loading twice:

| | `@font-face` for `lora` |
|---|---|
| Load 1 (cache cleared) — also the load that wrote the correct cache | **0** |
| Load 2 | 1 |

That is the reported symptom exactly: assign a font, open the page, get Times
New Roman; refresh, and it appears.

### 2.3 Substring matching produced false positives

`str_contains` is not a word match. A font named `ested` matched three pages
that never used it:

```
2154  elementor_library  training-json-data     ← "nested"
2291  elementor_library  loop-grid-slider-element
889   page               testqwe                ← "requested"
```

A font named `Inter` would have matched this plugin's own `data-interaction-id`
markup on every page.

### 2.4 `wp_push_style()` was a PHP 8 fatal

```php
echo wp_kses( '<style>' . $custom_css . '</style>' );   // ArgumentCountError
```

`wp_kses()` requires `$allowed_html`. Turning the **Load in head** setting on
would have white-screened the site.

### 2.5 Smaller defects

- `numberposts => 15` in both getters — font #16 onward silently did not exist.
- Drafts were included but titles were not checked, so an untitled draft
  registered itself as `font-family: ""`.
- `style` was collected in the editor UI but never reached the CSS, so italic
  variants collapsed onto the upright face.
- Up to six `get_posts()` calls per request across the two getters.

---

## 3. How it works now

### 3.1 Ask Elementor instead of guessing

Elementor already resolves every family a request needs — across local styles,
global classes **and** global variables — and the result is observable in the
`elementor_atomic_styles_fonts-*` options. The pipeline is:

```
Atomic_Styles_Manager::enqueue_styles()
  → Styles_Renderer
  → Font_Family_Prop_Type::get_enqueue_font_family()
  → Style_Fonts::add()                     (option elementor_atomic_styles_fonts-<key>)
  → Frontend::enqueue_font()
  → Frontend::print_fonts_links()          wp_head:7   (and again on wp_footer)
      ├─ do_action( 'elementor/fonts/register_styles', $fonts_to_enqueue )
      └─ do_action( "elementor/fonts/print_font_links/{$font_type}", $font )
```

Both hooks fire at **`wp_head` priority 7** — after Elementor has resolved
everything, and before `wp_print_styles()` at priority 8. So the list is
complete *and* still in time to be attached to a stylesheet, in the same
request. This is the same integration Elementor Pro's own Custom Fonts uses
(`elementor-pro/modules/assets-manager/asset-types/fonts-manager.php:710-711`).

We register both:

```php
// inc/class-wcf-custom-fonts.php:122-123
add_action( 'elementor/fonts/register_styles', [ $this, 'register_font_styles' ] );
add_action( 'elementor/fonts/print_font_links/' . $this->font_group_key, [ $this, 'print_font_link' ] );
```

`register_styles` (Elementor 3.29+) hands over the whole list at once;
`print_font_links/{group}` (Elementor 2.0+) arrives one family at a time and
covers older installs. Running both is safe — `printed_fonts` keeps the overlap
from emitting anything twice.

### 3.2 New / changed members

| Member | Line | Role |
|---|---|---|
| `$printed_fonts` | 38 | families already emitted this request (dedup) |
| `$fonts_loaded`, `$global_fonts_cache` | 43-44 | one `get_posts()` each, per request |
| `global_custom_webfonts()` | 168 | **unchanged** — the *Enable For Global* set |
| `_custom_webfonts( $return_fonts, $requested = null )` | 192 | now maps Elementor's list; `null` = a call site that runs before Elementor has resolved anything, so it contributes nothing |
| `match_family()` | 219 | case-insensitive, quote-tolerant name → `$configs` key |
| `is_load_in_head()` | 248 | the *Load in head* setting |
| `register_font_styles()` | 258 | Elementor 3.29+ entry point |
| `print_font_link()` | 266 | Elementor 2.0+ entry point |
| `wp_push_style()` | 274 | `wp_head:20`, globals only, echo |
| `push_dynamic_style()` | 286 | `wp_enqueue_scripts:20`, globals only, inline |
| `emit_font_faces()` | 299 | dedup + build + route the output |
| `build_font_faces()` | 359 | one family → `@font-face` rules |
| `ensure_fonts_loaded()` | 465 | memoised loader |
| `collect_fonts( $global_only )` | 492 | shared parser for both getters |

### 3.3 Output routing

`emit_font_faces()` attaches to `\WCF_ADDONS\Plugin::INLINE_STYLE_HANDLE`
(`aae-inline-styles`, always enqueued) while styles have not been printed yet,
and echoes a `<style id="wcf-custom-fonts">` once they have — which is what
covers Elementor's second `print_fonts_links()` pass on `wp_footer` for
templates rendered below the head.

### 3.4 Removed

- `before_get_builder_content()` and its hook — the scan, and the front-end
  `update_post_meta()` / `update_term_meta()` / `update_option()` write it did
  on every page view.
- The `wp_kses()` fatal.

### 3.5 Other fixes carried in the same pass

- `numberposts => -1`.
- Untitled drafts skipped.
- `font-style` now written into `@font-face`.
- URLs escaped with `esc_url_raw()`; weight/style pattern-filtered; the echo
  path passes through `wp_strip_all_tags()`.
- At most two `get_posts()` per request.

---

## 4. Compatibility

### 4.1 Elementor version

| | |
|---|---|
| This plugin's own minimum | **3.32.0** (`MINIMUM_ELEMENTOR_VERSION`) |
| `elementor/fonts/register_styles` added in | **3.29.0** |

The primary hook predates the minimum Elementor this plugin will run on, so it
is always present. `print_font_links/{group}` (2.0+) is belt-and-braces.

### 4.2 Other plugins

- **AAE Pro ≤ 2.4.11** — the guard at the top of the file is unchanged; that
  version's own `CustomFonts` class still wins.
- **AAE Pro (current)** — `inc/hook.php` reads `wcf_addon_custom_fonts` only
  from the `wcf-custom-fonts` CPT, to download remote font files during template
  import. Unrelated to the per-page cache that was removed.
- **Elementor Pro Custom Fonts** — different font group (`custom` vs
  `wcf-anim-addon-font`); each side only handles its own families.
- **`wcf_addin_pro_custom_webfonts`** — still fired, with a second argument
  added. Existing one-parameter callbacks are unaffected.

### 4.3 Sites using *Enable For Global*

`global_custom_webfonts()` and the `custom_font_global == 'true'` check are
untouched. Verified with the toggle on across seven page types:

| Page | `@font-face` for the global font | total |
|---|---|---|
| Plain WP post (no Elementor) | 1 | 1 |
| Plain WP page (no Elementor) | 1 | 1 |
| Home / blog index | 1 | 1 |
| Elementor page not using it | 1 | 1 |
| Search results | 1 | 1 |
| 404 | 1 | 1 |
| Page that also uses it locally | 1 | 2 |

The last row is the dedup check: the family is both global and page-local, and
is still emitted exactly once.

---

## 5. Test results

| Case | Before | After |
|---|---|---|
| First load after assigning a font (cache cleared) | no `@font-face` | `@font-face` present |
| Font applied via a **global class** (page 889) | never worked | `@font-face` present |
| Font applied via a local element style (page 2478) | worked on 2nd load | works on 1st load |
| Duplicate emission (global + local, both hooks) | n/a | exactly 1 per family |
| *Load in head* setting on | PHP 8 fatal | HTTP 200, `<style id="wcf-custom-fonts">` |
| Browser render, page 2478 | Times New Roman | real webfont — `document.fonts.check('500 30px lora')` → `true`; measured 490.8px vs 484.4px serif |

The global-class case was proven by temporarily creating a custom font named
`Adamina` (the family page 889 applies through a global class and never mentions
in `_elementor_data`), confirming the rule was emitted, then deleting it.

---

## 6. Notes for existing installs

### 6.1 Leftover data

After upgrading, sites will still carry rows the new code neither reads nor
writes:

- `wcf_addon_custom_fonts` post meta on **pages/posts** (not on
  `wcf-custom-fonts` posts — those are the real font data and must be kept)
- `wcf_addon_custom_fonts` term meta
- options `wcf_addon_custom_fonts_search`, `wcf_addon_custom_fonts_error`

Harmless, but dead. A one-time cleanup routine behind a version marker would
remove them.

### 6.2 One behaviour change worth documenting for users

A family referenced **only from hand-written CSS** — Custom CSS, theme CSS,
Additional CSS — rather than picked in the Typography control is not something
Elementor resolves as a font, so it is no longer auto-loaded. The old substring
scan happened to catch this case (one request late).

**Workaround:** switch **Enable For Global** on for that font, which loads it on
every page.

### 6.3 Where the rules print

For a V3 theme-builder header/footer, Elementor resolves those fonts while
rendering the body, so the rules print from the `wp_footer` pass rather than in
`<head>`. This is still the same request — previously they only appeared on the
*next* page load. V4 atomic headers/footers resolve in the head as usual.
