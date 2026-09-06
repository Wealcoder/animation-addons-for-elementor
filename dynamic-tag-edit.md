# Dynamic Tags on Atomic (v4) — the Internal URL defect and its fix

Scope: `aae-internal-url` ("Internal URL", group **AAE Site**, category `url`).
Everything here also applies to any future AAE tag that wants a search-and-select
field, so the mechanism was built as a reusable opt-in rather than a one-off patch.

Code lives in the **Pro** plugin; the free plugin only owns the on/off toggle
(`inc/AtomicWidgets/class-atomic.php`, the `dynamic-tags` atomic-extension entry).

---

## 1. Background: dynamic tags have two conversion paths, not one

Elementor's Atomic (v4) editor cannot consume a v3 dynamic tag directly. It runs
**two independent conversions over the same tag**, and they read from *different*
methods:

| What it produces | Built by | Reads from |
|---|---|---|
| The panel UI (`atomic_controls`) | `Dynamic_Tags_Editor_Config::get_tags()` | `get_tags_config()` → each tag's **`get_editor_config()`** |
| The value contract (`props_schema`) | `Dynamic_Tags_Schemas::get()` | each tag's **`get_controls()`** (raw stack) |

Both live in `elementor/modules/atomic-widgets/dynamic-tags/`.

At render time `Dynamic_Transformer::transform()` resolves the saved settings
against `props_schema` and only then calls the tag:

```php
$schema   = $this->dynamic_tags_schemas->get( $value['name'] );
$settings = $this->props_resolver->resolve( $schema, $value['settings'] ?? [] );
return $this->dynamic_tags_manager->get_tag_data_content( null, $value['name'], $settings );
```

`resolve()` iterates the **schema**, so any setting whose control is absent from
`props_schema` is silently dropped before the tag ever sees it.

Two consequences that drive everything below:

1. A control must be present in **both** outputs, with agreeing types, or the
   panel renders a field that is bound to nothing.
2. `get_editor_config()` is shared: the **same** `get_tags_config()` array feeds
   the v3 panel *and* the v4 converter. Whatever you emit there, v3 gets too.

---

## 2. The issue

`Internal_URL_Tag` registered its four ID pickers with the plugin's own AJAX
select2 control (`Select2_Control::CONTROL_ID` = `aae_select2`):

```php
$this->add_control( 'post_id', array(
    'label'     => esc_html__( 'Search & Select', ... ),
    'type'      => Select2_Control::CONTROL_ID,
    'ajax'      => array( 'action' => 'aae_dt_get_posts', ... ),
    'condition' => array( 'type' => 'post' ),
) );
```

Elementor's Atomic pipeline has no idea what `aae_select2` is, and the two paths
disagreed about what to do with it:

* **UI path** — `Atomic_Compatibility_Trait::get_editor_config()` rewrote
  `aae_select2` → `text` (there are no `options`), so Elementor emitted four
  `Text_Control`s labelled "Search & Select".
* **Schema path** — `Dynamic_Tags_Schemas::get()` read the **raw** `aae_select2`
  and fell straight into `Dynamic_Tags_Converter::convert_control_to_prop_type()`'s
  `default: return null`. So `post_id`, `taxonomy_id`, `attachment_id` and
  `author_id` were **never added to `props_schema`**.

### Symptoms

1. **Labels with no input.** Each `Text_Control` is bound to a prop that does not
   exist in the schema, so the React panel renders the label and nothing else.
2. **All four branches visible at once.** A control's `condition` is turned into
   *prop-type dependencies* — `$prop_type->set_dependencies( … )`, the last line
   of `convert_control_to_prop_type()`. No prop type ⇒ no dependencies ⇒ nothing
   hides `taxonomy_id`/`attachment_id`/`author_id` when Type = Content.
3. **The tag can never produce a URL in v4.** The ID can't be set, and even a
   hand-written one is dropped by `props_resolver->resolve()`, so `get_value()`
   always returns `''`. Only the `type` dropdown (a real `select`) worked.
4. **Dead code.** `Select2_Control::content_template()` is a Backbone template
   and `assets/js/dynamic-tags/select2-control.js` an `elementor.modules.controls`
   view — the v4 panel is React and renders neither. The `aae_dt_get_posts` /
   `_get_terms` / `_get_authors` / `_get_attachments` AJAX endpoints are never
   called from v4.

`aae-internal-url` is the **only** tag in the codebase that uses `aae_select2`;
all other AAE tags use plain `select` / `text` / `switcher`, which convert
cleanly. So this defect was isolated to Internal URL.

---

## 3. Constraint: v3 must not change at all

`query` is the one native Elementor type that means "search & select", and the
Atomic converter already knows how to turn it into a `Query_Control` wired to
Elementor's own REST endpoints. But it cannot simply replace `aae_select2`:

* **Elementor free registers no `query` control.** `includes/controls/` has only
  `select2.php`. `Controls_Manager::add_control_to_stack()` bails with
  `_doing_it_wrong()` and `return false` on an unknown type, so registering the
  controls as `query` would make them vanish entirely on a site without
  Elementor Pro.
* **`get_editor_config()` is shared with the v3 panel** (§1.2). Emitting `query`
  there hands the v3 panel a control view it has no renderer for.
* **`Controls_Stack::get_init_settings()`** looks each control's type up in the
  controls manager and normalises the value through that control object. Change
  the type and a `query`-less site stops normalising the value.

So the mapping has to be **additive and v4-scoped**, and it has to fail *closed*:
if anything about it stops working, v4 falls back to today's behaviour and v3 is
never at risk.

---

## 4. The solution

Three small pieces. The `aae_select2` control, its Backbone template, its JS and
its four AJAX endpoints are all left in place and still serve v3.

### 4.1 An opt-in map on the tag

`inc/core/dynamic-tags/site/internal-url-tag.php`

```php
protected function get_atomic_query_controls() {
    return array(
        'post_id'       => array( 'object' => 'post',       'display' => 'detailed',
                                  'query'  => array( 'post_type' => 'any' ) ),
        'taxonomy_id'   => array( 'object' => 'tax',        'display' => 'detailed' ),
        'attachment_id' => array( 'object' => 'attachment', 'display' => 'detailed' ),
        'author_id'     => array( 'object' => 'author',     'display' => 'detailed' ),
    );
}
```

The `object` slugs are the same ones **Elementor Pro's own** `Internal_URL` tag
uses, because they are exactly what
`Dynamic_Tags_Editor_Config::convert_autocomplete_control_to_atomic()` matches on
when it picks a REST endpoint (`is_querying_wp_terms`, `is_querying_wp_media`,
`is_querying_wp_users` — the last one tests the value against real WP role names,
and `author` is a role).

`register_controls()` is **untouched** — the controls still register as
`aae_select2`.

### 4.2 Trait: map for the Atomic pipeline, unmap for v3

`inc/core/dynamic-tags/base/atomic-compatibility-trait.php`

* **`get_controls()`** — post-processes the *returned array* (never the registered
  stack): the mapped controls come back as `type => 'query'` plus the
  `autocomplete` block. This is what `Dynamic_Tags_Schemas` reads, so the props
  finally land in `props_schema` — with their `condition` carried through as
  dependencies. A single-control lookup (`get_controls( 'post_id' )`) is returned
  verbatim.
* **`get_init_settings()`** — raises a lock so the v3 settings pass sees the real
  stack (`aae_select2`) and keeps normalising values through the registered
  control object, exactly as before.
* **`get_editor_config()`** — when the Atomic config pass is *not* open, it puts
  the mapped controls back to `text` and strips `autocomplete`, reproducing the
  pre-fix v3 shape byte for byte. When the pass *is* open, `query` passes through
  and Elementor builds a real `Query_Control`.

`get_atomic_query_controls()` returns `array()` on the base trait, so every other
AAE tag is completely unaffected.

### 4.3 Knowing which panel is asking

`inc/core/dynamic-tags/dynamic-tags-module.php` marks out the window in which
Elementor builds its Atomic config:

```php
add_filter( 'elementor/editor/localize_settings', array( $this, 'open_atomic_config_pass' ), 1 );
add_filter( 'elementor/editor/localize_settings', array( $this, 'close_atomic_config_pass' ), PHP_INT_MAX );
```

Why this works, and why the window is deliberately wide:

* The v3 config is built **before** the filter chain. In Elementor's
  `core/editor/loader/common/editor-common-scripts-settings.php`,
  `'dynamicTags' => Plugin::$instance->dynamic_tags->get_config()` sits inside the
  `$client_env` array literal (~line 103) while
  `apply_filters( 'elementor/editor/localize_settings', $client_env )` is ~70 lines
  further down. So v3's array is already frozen with `text` in it.
* Elementor's atomic module then calls `get_tags_config()` *again* from inside the
  chain (`Dynamic_Tags_Module::add_atomic_dynamic_tags_to_editor_settings`).
  `get_tags_config()` is **not** memoised — it re-runs `get_editor_config()` on
  every tag — which is what makes the two arrays divergeable at all.
* Spanning the whole chain (1 → `PHP_INT_MAX`) rather than bracketing priority 10
  keeps this independent of Elementor's own filter priority.

The failure mode is safe: if the window never opens, v4 gets today's broken
`text` controls back and v3 is untouched. It can never break v3.

Note `props_schema` is *not* gated by the window — it is needed on the frontend
and on REST saves too, and `Dynamic_Prop_Type::validate_value()` /
`sanitize_value()` only ever read `props_schema`, never `atomic_controls`.

### 4.4 No change needed in `get_value()`

`Query_Prop_Type`'s shape is `{ id: number (required), label: string }`, but
Elementor registers a transformer for it
(`props-resolver/transformers/settings/query-transformer.php`):

```php
public function transform( $value, Props_Resolver_Context $context ) {
    return $value['id'] ?? null;
}
```

So a resolved v4 setting arrives as a **plain int** — the existing
`(int) $settings['post_id']` is already correct for both v3 and v4.

---

## 5. Verified

Booted against the live Local site (`Dynamic_Tags_Schemas` /
`Dynamic_Tags_Editor_Config` / `Render_Props_Resolver` exercised directly).

**v4 — fixed**

```
props_schema
  type           String_Prop_Type   deps=NONE
  post_id        Query_Prop_Type    deps={"relation":"and","terms":[{"operator":"eq","path":["type"],"value":"post","effect":"disable"}]}
  taxonomy_id    Query_Prop_Type    deps=… "value":"taxonomy"  …
  attachment_id  Query_Prop_Type    deps=… "value":"attachment" …
  author_id      Query_Prop_Type    deps=… "value":"author"    …

atomic_controls
  type           select  "Type"              options: Content / Taxonomy / Media / Author
  post_id        query   "Search & Select"   url: elementor/v1/post
  taxonomy_id    query   "Search & Select"   url: elementor/v1/term
  attachment_id  query   "Search & Select"   url: elementor/v1/post  (included_types ["attachment"], is_public false)
  author_id      query   "Search & Select"   url: elementor/v1/user

render round-trip
  {"$$type":"query","value":{"id":889,"label":"testqwe"}}
    → resolved {"type":"post","post_id":889,…} → http://localhost:10004/testqwe/
```

**v3 — unchanged**

```
get_editor_config()  (Atomic pass closed — this is what the v3 panel receives)
  type           select        (unchanged)
  post_id        text          no `autocomplete` key
  taxonomy_id    text          no `autocomplete` key
  attachment_id  text          no `autocomplete` key
  author_id      text          no `autocomplete` key

get_controls( 'post_id' )   → aae_select2   (single lookup untouched)

v3 settings pass   keys: type, post_id, taxonomy_id, attachment_id, author_id
                   post_id = 889, taxonomy_id = ''   (still normalised)

v3 render          {type: post, post_id: 889} → http://localhost:10004/testqwe/
```

Gotcha when re-running this from CLI: Elementor strips `label`, `placeholder` and
`options` off the control stack on frontend requests
(`Controls_Stack::add_control()` under `Performance::should_optimize_controls()`,
which is `! is_admin() && ! preview && ! REST_REQUEST`). Labels then read
"Post Id" instead of "Search & Select". Define `REST_REQUEST` before booting to
see what the editor actually gets.

---

## 6. Known, deliberately left alone

* **v3's Internal URL is a plain text box, not a select2.** The
  `aae_select2` → `text` downgrade in `get_editor_config()` predates this work; it
  was added for Atomic compatibility and collaterally degraded the v3 panel to
  manual ID entry. Restoring it is now a one-line change — drop `'aae_select2'`
  from the downgrade list in `get_editor_config()` — but it *is* a v3 behaviour
  change, so it is out of scope here.
* **v4 taxonomy search spans all taxonomies.** v3's select2 was pinned to
  `category` via its `ajax.params`; `Term_Query` with no `included_types` searches
  every taxonomy. This matches Elementor Pro's own Internal URL tag.
* **The v4 query control requires 2 typed characters** (`Query_Control`'s
  `minimum_input_length`), where the v3 select2 opened with results at 0.
* **`Base_Tag::get_editor_config()` now sets `atomic_group` itself** (Elementor
  4.2.2). The trait still sets it to the same value — harmless duplication, not
  worth a change.

---

## 7. Files touched

| File | Change |
|---|---|
| `animation-addons-for-elementor-pro/inc/core/dynamic-tags/base/atomic-compatibility-trait.php` | `get_atomic_query_controls()` hook, `get_controls()` / `get_init_settings()` overrides, v3 un-mapping in `get_editor_config()` |
| `animation-addons-for-elementor-pro/inc/core/dynamic-tags/dynamic-tags-module.php` | `$atomic_config_pass` flag + the two `localize_settings` window filters |
| `animation-addons-for-elementor-pro/inc/core/dynamic-tags/site/internal-url-tag.php` | `get_atomic_query_controls()` map for the four ID pickers |

No PHP was removed, and no v3 asset (`select2-control.php`, `select2-control.js`,
`ajax-handler.php`, `query-manager.php`) was modified — so nothing needs a rebuild.
