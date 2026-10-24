---
name: aae-prefix-rules
description: Naming, prefix and compat-helper rules for ANY new PHP/JS code in Animation Addons (free) and Animation Addons Pro — prefixes (aaeaddon_ / Aaeaddon_ / AAEADDON_ / aaeaddon-), namespaces, options, transients, nonces, admin-ajax actions, REST namespaces, hooks, admin page slugs, JS globals, escaping/printing helpers, lazy-load rules. Load before writing or renaming any global symbol in either plugin.
---

# AAE prefix & naming rules (both plugins)

The WordPress.org Plugins Team reviewed free 4.2 for "generic prefixes". Every
rule below was paid for once; a new symbol that breaks one fails a suite
(`verify-ajax-prefix.php`, `verify-class-rename.php`, `verify-function-rename.php`,
`verify-pro-prefix.php`, `verify-namespace-alias.php`). Follow it in BOTH plugins.

## 1. The prefix, per kind of symbol

| Symbol | FREE | PRO | Never |
|---|---|---|---|
| namespace root | `Wealcoder\AnimationAddons\` | `Wealcoder\AnimationAddonsPro\` | `WCF_ADDONS\`, `WCFAddonsPro\` |
| class / trait / interface | `Aaeaddon_*` (or a PSR-ish name INSIDE a namespace: `Fields\Registry`) | same | `AAE_*`, `WCF_*` |
| global function | `aaeaddon_*` | `aaeaddon_pro_*` | `aae_*`, `wcf_*` |
| constant (`define`) | `AAEADDON_*` via `aaeaddon_define( new, default, legacy )` | `AAEADDON_PRO_*` via `aaeaddon_pro_define()` | a bare `define()` — a wp-config override of the OLD name must still win |
| option key | `aaeaddon_*` (read through `Key_Bridge`-aware `get_option`) | `aaeaddon_pro_*` through `Compat\Options::get/update/delete` | `aae_*`, `wcf_*` |
| transient | `aaeaddon_*` | `aaeaddon_pro_*` | |
| post meta / CPT / taxonomy names | `aaeaddon_*` for NEW ones (`_aaeaddon_*` when hidden from Custom Fields UI) | same | renaming a SHIPPED key — that is customer data |
| admin-ajax action | `aaeaddon_*`, registered through `Ajax_Alias::register( old, new, cb, nopriv )` — for a NEW action pass the new name as both, or plain `add_action('wp_ajax_aaeaddon_…')` | same, Pro's `Compat\Ajax_Alias` | `wp_ajax_aae_*` |
| nonce action | a constant on `inc/Nonce.php` (free) / `inc/Compat/Nonce.php` (Pro), verified with CORE (`check_ajax_referer( Nonce::action( Nonce::X, 'nonce' ), 'nonce' )`, `wp_verify_nonce`, `check_admin_referer`) | | a literal string; `Nonce::check_ajax()` (Plugin Check cannot see it) |
| REST namespace | `aae/v1` (existing, free) | `aae-pro/v1` for new Pro routes | |
| script/style handle | `aaeaddon-*` (existing handles keep their names) | `aaeaddon-pro-*` | |
| admin page slug | `aaeaddon_*` / `aaeaddon-*`; parent = `Compat\Free_Pages::slug('wcf_addons_page')` in Pro | | |
| JS global (`wp_localize_script` / inline) | `AAEADDON_*` | `AAEADDON_PRO_*` | renaming a FRONT-END global (cached HTML keeps the old `var`) |
| hook names (`do_action`/`apply_filters`) | `aaeaddon/<area>/<name>` | same; Pro LISTENS on free hooks through `Compat\Legacy_Hooks::add_filter()` when a pre-4.2 spelling exists | firing BOTH spellings of a hook |
| Abilities API names | `aae/<verb>-<noun>` (namespace `aae` — public API surface, short by design) | same | |
| CSS class / data-attr | `aae-<feature>-*` / `data-aae-<feature>-<setting>` (established convention, keep) | | |

## 2. Rules that are not just spelling

- **A renamed constant needs `aaeaddon_define()`**; a renamed function needs a
  guarded thin wrapper under the old name ONLY when a shipped Pro/theme calls it
  unguarded — measure the call sites first. A renamed hook fires ONE spelling.
- **Never `phpcs:ignore` EscapeOutput.** Echo through core escapers, or
  `aaeaddon_print_builder_html()` (Elementor markup) / `aaeaddon_print_css()`
  (CSS text) — plain functions, not `Class::method()` (WPCS ignores those).
- **Every `$wpdb` call is prepared or literal**, `%i` for table names; a WHERE
  is ONE literal template with `( %s = '' OR col = %s )` pairs, never fragments.
- **A request value that becomes a path or key is `sanitize_key()`**, never
  `sanitize_text_field()`.
- **Pro has NO autoloader.** Every class is `require_once`d by path; admin-only
  code sits behind `is_admin()` or a static accessor that `require_once`s on
  first call, with hooks registered as closures. Free uses PSR-4 class-string
  callables (`[ Foo::class, 'method' ]`). Keep the front-end parse small —
  `E:\Local Testing\verify-lazy-load.php front|admin|ajax` carries a budget.
- **Free constants from Pro:** `aaeaddon_pro_free_constant( 'PATH' )`; Pro
  constants from free: `aaeaddon_pro_constant( 'VERSION' )`.
- **Free CPT builder storage (`aaeptypebilder`, `aae_ptypebilder_meta`, caches
  `aaeaddon_cpts_cache`) is customer data — read/write the rows, never rename.**
- **ABSPATH guard within the first 50 lines** of every PHP file, before a long
  docblock.
- New JS in `src/modules/**` builds with `npm run build` (wp-scripts) and the
  built `assets/build/**` are committed in Pro.

## 3. Quick checklist before committing a new module

1. `grep -rn "aae_\|wcf_" <new files>` returns only shipped data keys / hook names you consciously kept.
2. Every `add_action( 'wp_ajax_` line uses `aaeaddon_`.
3. Every nonce goes through `Nonce::…` constants and a core verifier.
4. `php vendor/bin/phpcs --standard=WordPress --sniffs=WordPress.NamingConventions.PrefixAllGlobals --runtime-set prefixes "Wealcoder,aaeaddon,Aaeaddon,AAEADDON" <files>` reports only hook names.
5. Run `verify-ajax-prefix.php`, `verify-class-rename.php`, `verify-function-rename.php`, `verify-pro-prefix.php` (all in `E:\Local Testing`).
