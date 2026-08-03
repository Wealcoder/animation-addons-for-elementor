# Animation Addons for Elementor — Project Memory

This file is the project's working memory for AI assistants and new
contributors. It describes the architecture, the file map, and the exact
steps for the common task: **adding a new animation effect to atomic
widgets**.

If you only read one section, read [Adding a new effect](#adding-a-new-effect).

---

## What this plugin does

Animation Addons (AAE) extends Elementor with scroll/hover/click animations.
The codebase has two parallel implementations:

| Era | Where | Status |
|---|---|---|
| **V3 (legacy)** | `inc/extensions/`, `src/js/` (root) | Maintenance — not extended |
| **V4 (atomic)** | `inc/Atomic/`, `src/modules/atomic/` | Active development |

The V4 path targets Elementor's **Atomic Widgets** (Elementor 4.x):
`e-heading`, `e-paragraph`, `e-button`, `e-image`, `e-svg`, `e-flexbox`,
`e-div-block`, `e-grid`. Atomic widgets have a different controls API and
render pipeline than the V3 `Element_Base` widgets, so the two systems
do not share code.

This document covers the **V4 atomic side only**.

---

## High-level architecture (V4 atomic)

Four cooperating layers:

```
┌────────────────────────────────────────────────────────────────┐
│ 1. SCHEMA           inc/Atomic/.../Schema.php                  │
│    Registers top-level scalar props on atomic widgets via     │
│    elementor/atomic-widgets/props-schema filter.              │
├────────────────────────────────────────────────────────────────┤
│ 2. CONTROLS         inc/Atomic/.../Controls.php                │
│    Builds the panel sections (Section::make()->set_items()).  │
│    Hooks elementor/atomic-widgets/controls.                   │
├────────────────────────────────────────────────────────────────┤
│ 3. RENDER           inc/Atomic/.../Render.php                  │
│    Splices data-aae-* attrs into the rendered widget HTML     │
│    via elementor/widget/render_content. Decides which JS      │
│    bundle to enqueue based on actually-applied settings.      │
├────────────────────────────────────────────────────────────────┤
│ 4. RUNTIME          src/modules/atomic/common.js + effects/   │
│    Browser-side. common.js is the always-loaded core          │
│    (window.AAERegistry). Each effect file in effects/         │
│    self-registers a Kind that the dispatcher binds to         │
│    matching DOM elements.                                     │
└────────────────────────────────────────────────────────────────┘

editor-bridge (separate)    src/modules/atomic/editor-bridge.js
  Mirrors panel settings → preview iframe DOM live, injects
  the "Play Now" button, handles responsive panel UX.
```

### On-demand asset loading

Elementor's atomic widgets do **not** support setting-level conditional
script loading (V3's `assets => scripts` array is broken for them).
We replicate the V3 pattern ourselves:

1. `Assets::register()` registers all JS handles on `wp_enqueue_scripts`
   but does **not** enqueue them.
2. When `Render.php::inject_into_html()` actually adds animation attrs,
   it calls `wp_enqueue_script( 'aae-effect-foo' )`.
3. Effect bundles declare `aae-atomic-common` as a dependency, so the
   core runtime gets pulled in automatically.
4. Editor preview iframe (`elementor/preview/enqueue_scripts`) blanket-
   enqueues everything because the user can toggle effects live.

Net effect: a page with no AAE animations loads zero AAE JavaScript.

---

## File map

```
inc/Atomic/                              PHP (server-side)
├── Bootstrap.php                        Boot — registers all the *.php below
├── Assets.php                           Script registration + on-demand enqueue
└── TextAnimation/
    ├── Schema.php                       Props for both Text and Regular animation
    ├── Controls.php                     Panel sections for both
    └── Render.php                       Data-attr splicing for both

src/modules/atomic/                      JS (browser-side)
├── common.js                            Core runtime — exposes window.AAERegistry
├── presets.js                           Regular-animation GSAP fromTo presets
├── effects/
│   └── animation.js                     Text + Regular animation kind, self-registers
├── editor-bridge.js                     Editor entry — bootstraps the modules below
└── editor-bridge/                       Editor implementation (one module each)
    ├── disposables.js                   Listener teardown registry
    ├── helpers.js                       getPreviewWindow, getSelectedContainer, unwrap
    ├── features.js                      FEATURES table — single source of truth
    ├── responsive-config.js             Breakpoint + responsive metadata
    ├── panel-rows.js                    DOM row utilities (rowFromLabel, isSelectRow…)
    ├── settings-bridge.js               applySettingsToDom, replayInPreview
    ├── live-bridge.js                   Selection + change-event subscription
    ├── preview-pipe.js                  Forward elementor/element/render to runtime
    ├── play-button.js                   "Play Animation" row → "Play Now" button
    ├── responsive-visibility.js         Show/hide rows per device, strip label suffixes
    ├── responsive-placeholders.js       Inherit hints in inputs + selects
    ├── float-step-fix.js                MUI Number_Control step="any" override
    └── responsive-bridge.js             Orchestrates the three responsive modules
```

---

## The Kind interface

Every animation effect plugs into the runtime via this contract:

```js
window.AAERegistry.register({
  name:      'text',                       // unique id, logs only
  selector:  '[data-aae-text-anim]',       // querySelector for matching nodes
  boundFlag: 'aae-text-anim-bound',        // class added to prevent double-bind
  playedKey: '__aaeTextPlayed',            // property on el that caches the tween
  read:  (el) => config | null,            // null = effect off on this element
  play:  (el, config) => void,             // run the GSAP tween
  bind:  (el, config) => void,             // wire trigger → play
});
```

The dispatcher (`common.js`) walks `KINDS` on every scan/rebind/replay,
so kinds added later still work on already-rendered elements (registry
triggers a microtask rescan).

The matching editor-side contract is in `editor-bridge/features.js`:

```js
{
  name:              'text-animation',
  widgetTypes:       ['e-heading', 'e-paragraph'],
  enableSetting:     'aae_text_effect',
  autoReplaySetting: 'aae_text_enable_editor',  // optional Boolean
  attrMap: {
    aae_text_effect:  'data-aae-text-anim',
    aae_text_trigger: 'data-aae-text-trigger',
    // … one per setting that should mirror to a data-attr
  },
  findTarget: (doc, id) => doc.querySelector(`[data-interaction-id="${id}"]`),
}
```

---

## Adding a new effect

Concrete walkthrough: porting a **Tilt** effect.

### 1. Schema (PHP)

Edit `inc/Atomic/TextAnimation/Schema.php` (or create a sibling like
`inc/Atomic/Tilt/Schema.php` if the effect is large enough to warrant its
own namespace):

```php
const TILT_ENABLE     = 'aae_tilt_enable';
const TILT_MAX_TILT   = 'aae_tilt_max';
const TILT_PERSPECTIVE = 'aae_tilt_perspective';
const TILT_SCALE      = 'aae_tilt_scale';
const TILT_SPEED      = 'aae_tilt_speed';
```

In `add_animation_props()`:

```php
$schema[ self::TILT_ENABLE ] = Boolean_Prop_Type::make()->default( false );
$tilt_active = $this->dep_eq( self::TILT_ENABLE, true );

$schema[ self::TILT_MAX_TILT ] = Number_Prop_Type::make()
    ->default( 20 )
    ->set_dependencies( $tilt_active );
// … rest
```

### 2. Controls (PHP)

Edit `inc/Atomic/TextAnimation/Controls.php`. Either add a new section
method (`build_tilt_section()`) and call it from `inject_controls()`, or
extend an existing section if the effect belongs there:

```php
private function build_tilt_section(): Section {
    return Section::make()
        ->set_label( __( 'Tilt', self::TD ) )
        ->set_items( [
            Switch_Control::bind_to( Schema::TILT_ENABLE )
                ->set_label( __( 'Enable', self::TD ) ),
            Number_Control::bind_to( Schema::TILT_MAX_TILT )
                ->set_label( __( 'Max Tilt (deg)', self::TD ) ),
            // …
        ] );
}
```

Then in `inject_controls()`:

```php
if ( in_array( $type, [ 'e-flexbox', 'e-div-block', 'e-grid' ], true ) ) {
    $controls[] = $this->build_tilt_section();
}
```

### 3. Render (PHP)

Edit `inc/Atomic/TextAnimation/Render.php`. Add a `build_tilt_attrs()`
method and call it from `inject_into_html()`:

```php
$is_tilt = in_array( $type, [ 'e-flexbox', 'e-div-block', 'e-grid' ], true );
if ( $is_tilt ) {
    $attrs = array_merge( $attrs, $this->build_tilt_attrs( $settings ) );
}
```

In `inject_into_html()`, when `$attrs` is non-empty, also enqueue the
effect's bundle:

```php
if ( ! is_admin() && ! empty( $tilt_attrs ) ) {
    wp_enqueue_script( 'aae-effect-tilt' );
}
```

### 4. Effect bundle (JS)

Create `src/modules/atomic/effects/tilt.js`:

```js
import { getGsap } from '../common';

function readTilt(el) {
  if (el.dataset.aaeTiltEnable !== 'true') return null;
  return {
    max:         parseFloat(el.dataset.aaeTiltMax)         || 20,
    perspective: parseFloat(el.dataset.aaeTiltPerspective) || 1000,
    scale:       parseFloat(el.dataset.aaeTiltScale)       || 1,
    speed:       parseFloat(el.dataset.aaeTiltSpeed)       || 300,
  };
}

function playTilt(/* el, config */) {
  // No-op: tilt is hover-driven, not a tween. bindTilt() does the work.
}

function bindTilt(el, config) {
  // mousemove handler that applies transform to el. See V3 tilt.js for
  // port reference: animation-addons-for-elementor-pro/src/js/tilt.js
}

function registerWhenReady() {
  if (!window.AAERegistry) {
    Promise.resolve().then(registerWhenReady);
    return;
  }
  window.AAERegistry.register({
    name:      'tilt',
    selector:  '[data-aae-tilt-enable="true"]',
    boundFlag: 'aae-tilt-bound',
    playedKey: '__aaeTiltBound',
    read:      readTilt,
    play:      playTilt,
    bind:      bindTilt,
  });
}
registerWhenReady();
```

### 5. Webpack entry

Edit `webpack.config.js`:

```js
"modules/atomic/effects/tilt": "./src/modules/atomic/effects/tilt.js",
```

### 6. PHP bundle registration

Edit `inc/Atomic/Assets.php`, append to `EFFECT_BUNDLES`:

```php
const EFFECT_BUNDLES = [
    'aae-effect-animation' => 'effects/animation.js',
    'aae-effect-tilt'      => 'effects/tilt.js',
];
```

### 7. Editor-bridge feature entry

Edit `src/modules/atomic/editor-bridge/features.js`, push:

```js
{
  name: 'tilt',
  widgetTypes: ['e-flexbox', 'e-div-block', 'e-grid'],
  enableSetting: 'aae_tilt_enable',
  autoReplaySetting: null,
  attrMap: {
    aae_tilt_enable:     'data-aae-tilt-enable',
    aae_tilt_max:        'data-aae-tilt-max',
    aae_tilt_perspective:'data-aae-tilt-perspective',
    aae_tilt_scale:      'data-aae-tilt-scale',
    aae_tilt_speed:      'data-aae-tilt-speed',
  },
  findTarget: (doc, id) => doc.querySelector(`[data-id="${id}"]`),
},
```

### 8. Build & test

```bash
npm run build
```

Open Elementor editor, select a flexbox container, toggle the Tilt enable
switch. The `data-aae-tilt-*` attrs should appear on the preview node and
the effect should run.

---

## Registering a new widget or extension — what must stay in sync

Read this **before** adding either. Registration is split across several
hand-maintained lists, and a widget/extension that is missing from one of them
does not error — it just becomes silently unreachable. Two real cases:
`aae-a-menu` shipped with a complete class, twig, CSS and JS but no dashboard
metadata, so it could never be enabled by anyone; the Template Library's only
`require` was never called, so its whole feature was dead code regardless of
any setting.

> Note: `get_available_widgets()`'s docblock points at a "HOW TO ADD A NEW
> ATOMIC WIDGET" block "above `register_widget_definitions()`". **That block
> does not exist** — this section is the actual checklist.

### A new atomic widget

All PHP below is `inc/AtomicWidgets/class-atomic.php` unless stated.

| # | Where | Purpose | Skip it and… |
|---|---|---|---|
| 1 | `inc/AtomicWidgets/Widgets/<Name>/` | class + twig + `assets/` | nothing to register |
| 2 | `get_available_widgets()` | `class` / `file` / `*_handle` / `*_path` / `has_script` | **never registers with Elementor** |
| 3 | `register_widget_definitions()` (`$widgets_registry`) | dashboard card metadata | **`is_widget_active()` can never return true → unreachable** |
| 4 | `WIDGET_PARENT_MAP` + `is_internal => true` | structural children of a composite widget | child gets its own card, or can never activate |
| 5 | `webpack.config.js` | JS/SCSS entry | asset 404s |

Lists 2 and 3 must agree. `assert_registry_integrity()` diffs them and
`error_log()`s drift — but **only under `WP_DEBUG`**, so it is silent in
production.

Three constants change the default behaviour; use the narrowest one:

- **`ALWAYS_ACTIVE_WIDGETS`** — force-registers regardless of the saved option.
  Only for slugs with **no** dashboard card (e.g. `aae-a-counter-number`).
  Putting a *carded* widget here makes its switch inert.
- **`PARKED_WIDGETS`** — class exists, card deliberately withheld. Keeps
  `assert_registry_integrity()` quiet. Remove the entry when you ship the card.
- **`FORMERLY_FORCED_WIDGETS`** — one-time upgrade path only; see
  `backfill_formerly_forced_widgets()`.

Client side, `src/modules/dashboard/lib/atomicWidgetService.js` filters the
payload *again* before rendering. A widget PHP happily sends can still be
invisible via `INTERNAL_WIDGET_SLUGS`, `DEMO_ONLY_SLUGS`, or the `-main`
regex — check there before concluding the PHP side is broken.

### A new atomic extension

1. Module under `inc/Atomic/<Feature>/` (free) or
   `animation-addons-for-elementor-pro/inc/AtomicV4/<Feature>/` (Pro).
2. Gate it: `Atomic::instance()->is_extension_active( '<slug>' )` in the owning
   `Bootstrap::init()` / `register_modules()`.
3. Add the `$this->extensions_registry` entry in **the free plugin** —
   the registry lives there even for Pro-only modules, and Pro reads it back
   through `is_extension_active()`.

Nothing verifies steps 2 and 3 against each other. Audit both directions with:

```bash
# slugs the code actually gates on
grep -rho "is_extension_active( *'[a-z0-9-]*'" \
  animation-addons-for-elementor{,-pro}/inc animation-addons-for-elementor{,-pro}/class-plugin.php \
  --include=*.php | sed "s/.*'\(.*\)'/\1/" | sort -u
# vs. the registry keys in class-atomic.php
```

### Adding a new dashboard category

Two files, and the JS needs a rebuild:

- widgets → `CATEGORY_LABELS` **and** `CATEGORY_ORDER` in `atomicWidgetService.js`
- extensions → the same two in `atomicExtensionService.js`

Missing from `CATEGORY_LABELS`, the heading renders as the raw slug; missing
from `CATEGORY_ORDER`, the group falls to the end regardless of intent.

### Gotchas that have actually bitten

- **`'default' => true` does nothing for a widget.** `aae_atomic_widgets` is
  only ever written by the dashboard save handler — there is no seeder or
  first-run migration reading the key, so a new widget arrives **off** on
  existing sites. For **extensions** it does work:
  `migrate_newly_offered_extensions()` switches on newly-offered slugs
  (`LEGACY_OFFERED_EXTENSIONS` is the pre-marker baseline).
- **The dashboard card can lie.** `get_dashboard_config()` computes
  `is_active` from the raw saved option, *not* `is_widget_active()`. A
  force-active widget whose slug was never saved renders a card showing
  "off" while the widget is in fact registering.
- **Icon names are case-sensitive and must exist in the font.** The
  Capitalised `wcf-icon-Dynamic-Tags` / `wcf-icon-Template-library` style is
  what resolves; lower-case guesses like `wcf-icon-parallax` match nothing and
  render as empty circles. Verify with
  `grep -o '\.wcf-icon-<Name>:before{content:"[^"]*"}' assets/css/*.css`.
- **Changing these JS lists requires `npm run build`.** Pure PHP registry
  changes are data-driven and need no rebuild; anything touching
  `atomic*Service.js` does.
- **An effect that injects a floating element must mount it in a body-level
  `position: fixed` layer, never inside the widget's parent.** Image Hover
  shipped mounting its overlay in `el.parentElement`, absolutely positioned in
  that parent's frame, and it produced the classic "works in the editor,
  invisible on the front end" report (fixed 2026-08-02). Three separate
  failures, all from the same choice: an ancestor `overflow: hidden` clips an
  element that is *designed* to spill outside its widget (measured on fixture
  9852: overlay bottom 3541px vs container top 3549px — 100% hidden); a
  positioned ancestor is a stacking context, so the user's z-index can never
  lift the image above content outside that container; and offsets resolved
  against the parent's box land somewhere else in the editor, where Elementor
  adds its own wrappers. The pattern that works is
  `effects/image-hover/index.js` → `mountLayer()`: one `body > .aae-ih-layer`,
  sized 0×0, carrying **no** z-index/transform/filter/opacity (any of them
  would re-create the stacking context AND become a containing block for
  `position: fixed`), with every injected node positioned from
  `e.clientX/clientY` and tagged `data-aae-ih-owner="<interactionId>"` so an
  editor re-render's orphans can be swept. Covered by
  `E:\Local Testing\verify-image-hover{,-editor}.mjs`.

---

## The setup wizard — what a new install starts with (2026-08-02)

A fresh install activates **nothing**. `config.php` carries no
`is_active => true`, `aae_atomic_widgets` / `aae_atomic_extensions` are absent,
and the wizard is the only thing that writes them. The v3 step is gone from the
wizard entirely: new users are offered the atomic registry only
(`WizWidget.jsx` / `WizExtension.jsx` branch on `hasAtomic`, keeping the v3
list as a fallback for a site whose Elementor is too old for atomic — without
it that user reaches an empty step and finishes with nothing offered at all).

### The two presets

Step 1's radio (`WizardStart.jsx`, values `basic` / `advance`) seeds the widget
and extension steps through `lib/setupPresets.js` — **one module for both**, so
the rule cannot drift between them.

| | pre-selects | today |
|---|---|---|
| **Basic** (recommended) | free, non-`animation` | 25 / 33 widgets, 2 / 20 extensions |
| **Custom** (`advance`) | everything the licence can register | 33 / 33, 20 / 20 |

Three decisions behind that, each of which could reasonably have gone the other
way:

- **A `badge_only` item stays OFF in Basic.** Those are Pro-BADGED but ship
  free code, so pre-enabling them would work — and would make the badge read as
  a lie while quietly giving away the upsell. The badge is the promise; honour
  it. (`isCustomItem` still enables them, because Custom means "everything that
  can run".)
- **Custom never enables what the licence cannot register.** Its three branches
  mirror `activeAtomicFullWidgetFn` / `activeAtomicFullExtensionFn` exactly.
  `get_dashboard_config()` computes a card's `is_active` from the raw saved
  option rather than from `is_widget_active()`, so enabling a widget whose class
  does not exist paints an "on" card for something that can never render.
- **The `animation` category is excluded from Basic** even though it changes
  nothing today — every animation widget and extension in the registry is
  already Pro. It is there so a FREE animation item added later does not
  silently join the recommended set.

Seeding runs in the STEP components (`ShowWizAtomicWidgets` /
`ShowWizAtomicExtensions`), on a `useEffect` keyed to `setupType` — not inside
`setSetupType`, which carries an empty dependency array and so reads a
`mainState` frozen at first render. That is harmless for the v3 branches it
drives (they re-read the untouched `WCF_ADDONS_ADMIN` config) and would seed
the atomic steps from a stale snapshot.

### DANGER — a migration used to overrule the wizard seconds later

`migrate_newly_offered_extensions()` bails only while `aae_atomic_extensions`
is **absent** — "fresh install, the wizard decides". The wizard's own save is
what ends that. On the very next `admin_init` the option existed, the OFFERED
list did not, so it fell back to `LEGACY_OFFERED_EXTENSIONS` (a 13-slug
baseline) and treated every extension added since as newly-offered: **six Pro
extensions switched themselves back on immediately after the user picked
Basic** — 2 became 8, with no error and nothing in the UI to explain it.

`ajax_save_extension_settings()` now writes `aae_atomic_extensions_offered`
alongside the settings, which makes the key mean what its name says: the set
the user has actually been shown. The migration then correctly does nothing
until a future plugin update adds an extension neither the wizard nor a
dashboard save ever displayed.

**This class of bug is invisible from the wizard.** Assert the option AFTER a
second `admin_init`, never straight after the save.

### Testing it

`E:\Local Testing\verify-wizard-setup-presets.mjs` (16 checks) drives both
modes and audits every card against the registry's own
`is_pro`/`badge_only`/`category` read out of `WCF_ADDONS_ADMIN.addons_config` —
**never a hand-written slug list**, which would go stale the moment a widget is
added and then pass by not knowing about it.

Two things that will bite:

- **The site must be in a fresh-install state** or the step opens with the
  saved option instead of the preset. `scratchpad/fresh-install-state.php`
  does `snapshot` / `clean` / `restore`, and REFUSES to clean without a
  readable snapshot on disk — see the `set-v3-state.php` incident in the cache
  section for why that guard exists.
- **Walking the wizard to the end completes it** (`wcf_addons_setup_wizard`
  becomes `complete`) and the page then redirects, so the next run finds no
  wizard. Re-clean between runs. The footer button is **"Continue"**, not
  "Next", and `hasText: /^Continue$/` does not match it — the button's text
  node carries whitespace. Use `getByRole('button', { name: /^continue$/i })`.
- Per-widget switches carry **no id**; `WidgetCard` puts the slug on its ROOT
  `div` (`id={slug}`) with the switch inside. The category "Enable All"
  switches are the ones with an id, and they cannot collide.

### What a fresh install does NOT control

`maybe_enable_used_v3_widgets()` still runs, and on a site that already has v3
content it enables exactly the slugs that content references — the dev site
lands on 36. That is the documented data-loss guard, not the wizard leaking:
a genuinely new install has no v3 content, so it enables nothing.

---

## Animation Settings (v4 dashboard) vs. the v3 legacy surface

AAE's five site-wide chrome features — **Preloader, Cursor, Scroll to Top,
Scroll Indicator, Popup** — exist twice, and the rules for keeping the two
copies from fighting are the whole point of this section. Get one of them wrong
and you either paint two preloaders on a live site or silently strip a paying
customer's popup off their pages.

| | v3 (legacy) | v4 (current) |
|---|---|---|
| UI | Elementor **Site Settings** tabs | Dashboard → **Animation Settings** |
| Code | `pro/inc/settings/wcf-*.php` (5 `Tab_Base` classes, `elementor/kit/register_tabs`) | `inc/AnimationSettings/class-animation-settings.php` + `src/modules/dashboard/pages/AnimationSettings.jsx` |
| Storage | the Elementor **Kit** (`_elementor_page_settings`), keys `wcf_enable_preloader`, `wcf_preloader_layout`, … | the **`aae_animation_settings` option** |
| Renderer | `pro/inc/global-elements.php` (`wp_body_open` -1, `wp_footer` 11) | Pro, reading the option |
| Plugin | Pro only | UI in **free** (PRO badge, locked without Pro); renderer in Pro |

**The v4 side never reads or writes the Kit.** They are separate systems on
purpose — a shared store would make "which one wins" unanswerable.

### Rule 1 — there is ONE renderer, fed through one filter

Do **not** write a v4 renderer. `global-elements.php::get_site_settings()` ends
in `apply_filters('aae/global_elements/site_settings', $settings)`, and Pro's
`AtomicV4\AnimationSettings\Bridge` answers that filter by overwriting the Kit
keys for each feature the v4 side has claimed (`wcf_enable_preloader`,
`wcf_preloader_layout`, the three colours). The existing v3 markup then paints
the v4 settings.

This is worth insisting on:

- **One markup source.** All 18 preloader layouts worked the day the bridge
  landed; a layout fixed for v3 is fixed for v4.
- **Double-paint is structurally impossible.** There is one settings array, so
  there can only ever be one preloader. Not "we remember to turn the other one
  off" — there is no other one.
- **Hand-over is per feature.** A feature v4 hasn't claimed passes through
  untouched, which is exactly what keeps an existing v3 popup or cursor working
  when the v4 preloader is switched on. Never gate the whole v3 renderer on a
  single "v4 mode" flag.
- **The Kit is only ever read.** Turning the legacy switch back on restores the
  v3 path byte for byte.

`Bridge::resolve_color()` flattens a "Global Color" pick to the Kit colour's
current hex, so the renderer never learns that globals exist.

### Rule 2 — hiding a settings screen must not disable anything

`legacy_v3` controls **UI visibility only**. Off = the five Site Settings tabs
aren't registered, so the editor stops offering them. It does **not** stop
already-saved v3 settings from rendering: a live site's popup keeps working
after the screen that configured it disappears. Anything that conflates "hide
the settings" with "turn the feature off" is a bug.

### Rule 3 — the legacy default is detected once, then stored

`detect_legacy_usage()`, run once by `maybe_bootstrap()` on `admin_init` and
persisted. Never re-detected — otherwise a site crossing a content threshold
would silently flip behaviour, and a user's manual choice would be overwritten.

Two signals, in order:

1. **Kit holds v3 chrome** (`kit_has_legacy_chrome()`, RAW `_elementor_page_settings`
   — `get_settings_for_display()` merges control defaults and reports every
   fresh install as legacy) → **keep**.
2. Otherwise **is this a fresh / under-construction site?** (`is_fresh_site()`:
   ≤1 page, <3 posts, <4 attachments, excluding trash/auto-draft) → fresh means
   a genuinely new user → **hide**. An established site → **keep**.

Signal 2 exists because signal 1 has a blind spot: **v3 popups are
template-driven** and the Popup tab only styles them, so a site can be deep into
using v3 popups with an empty Kit. Established-but-empty therefore defaults to
keeping the tabs; the dashboard switch is how you turn them off deliberately.

### Rule 4 — detection is not enough; IMPORT the old config

The gap detection leaves: hide an existing user's Site Settings tab and their
preloader still *renders* (rule 2), but they can no longer *edit* it, and the
new panel shows them defaults that look nothing like their site. That reads as
"my settings are gone" even though nothing was deleted.

`import_from_kit()` closes it — once, at bootstrap, non-destructively. A v3
preloader configuration is copied into the v4 option, so the new screen opens
**already filled in with their exact settings** and no action is required of them.
Because v4 then reports the feature as enabled, rule 1's bridge hands the
preloader to v4: same look, one owner.

Any future feature moved from v3 to v4 needs its own importer. Detection
without import is how you make a long-time user feel robbed.

### Rule 5 — the flag is a ONE-WAY RATCHET back on

A one-time decision can't see what the user does next. A correctly-detected
fresh install gets the v4-only experience, and then they import a starter
template built on v3 widgets, or decide after a tutorial that they want the old
features. Both leave them staring at a panel that no longer offers what they
came for.

So: **evidence of v3 can always switch the flag back on; nothing ever switches
it off automatically.** Off is only ever a default for a site with no v3
evidence at all. `maybe_reactivate_legacy()` (admin_init, prio 11) checks
`has_v3_usage()` and flips it, importing the Kit config at the same time.

`has_v3_usage()` is the honest signal, not the post-count heuristic: v3 widgets
save as `"widgetType":"wcf--<slug>"` inside `_elementor_data`, so one `LIKE`
finds them anywhere. An imported demo looks fresh by post count and lights this
up instantly. Cached in the `aae_v3_usage` transient for an hour.

The one exception is a deliberate human choice. Once the user has moved the
switch themselves, `legacy_v3_user_set` sticks and the ratchet stops — an import
must never overrule someone who explicitly turned the old surface off. That flag
is set only when the posted value actually DIFFERS from the stored one; every
panel save posts the whole object, so equality-based detection would mark it
claimed the first time anyone saved a preloader colour.

### Rule 6 — always ship the manual escape hatch

There are many existing v3 users and no heuristic gets them all right. The
dashboard carries a always-visible **"Legacy settings in Elementor Site
Settings"** switch (`legacy_v3`) that restores them. It saves immediately on
flip rather than waiting for the panel's Save button — a switch this
consequential shouldn't sit in an uncommitted form.

### DANGER — do not gate `register_widgets()` on a heuristic

`class-plugin.php::register_widgets()` / `register_extensions()` are the only
registration points for the ~69 v3 widgets. **An unregistered widget renders
NOTHING on pages that already use it** — this is not "hidden from the panel",
it is data loss on the front end. Post-count freshness is a proxy, not proof
(an imported demo or a migrated database looks fresh).

Before ever hiding v3 widgets, prove they are unused with `has_v3_usage()`
(`_elementor_data LIKE '%"widgetType":"wcf--%'`, already implemented) and force
legacy ON if any hit comes back. Then hide them from the PANEL only, keeping
them registered — that is unobservable to existing pages.

**SHIPPED — `Animation_Settings::hide_legacy_widgets_in_panel()`. The route is
CLIENT-SIDE. Researched 2026-07-31 against installed 4.2.1 — do not redo it, and
do not reach for PHP.**

Panel visibility is decided entirely in the editor JS
(`assets/dev/js/editor/regions/panel/pages/elements/elements.js`):

```js
shouldAddWidget( widget ) { return widget.show_in_panel && …; }   // :222
```

…and Elementor's OWN code hides deprecated widgets exactly this way:

```js
elementor.widgetsCache[ widgetName ].show_in_panel = false;       // :82
```

So the supported move is to flip `show_in_panel` on `elementor.widgetsCache`
for every `wcf--*` entry. Registration is untouched, so existing pages render
identically; only the panel listing changes. Both symbols are present in the
shipped `elementor/assets/js/editor.js`, so this works on the runtime, not just
the dev clone.

**Timing matters.** `initialize()` calls `initElementsCollection()` FIRST, then
`initCategoriesCollection()`, then `initRegionViews()` — so the
`panel/elements/regionViews` filter is already too late to affect the list.
Mutate before the panel page initializes (AAE already hooks
`elementor/editor/after_enqueue_scripts`, class-plugin.php:1750) and re-apply on
the `elementor/widgets/refreshed` action, since `refreshWidgets()` rebuilds the
cache from scratch.

**Why PHP is a dead end** (all four checked, so nobody re-checks):
`show_in_panel()` is read once in `Widget_Base::get_initial_config()`
(widget-base.php:381) with no filter; widget configs travel by AJAX
(`get_widgets_config` / `refresh_widgets_config`, widgets.php:391/415) built
straight from `$widget->get_config()`, and the Ajax module contains no
`apply_filters` at all; `elementor/widgets/black_list` (widgets.php:188) only
iterates `$wp_widget_factory->widgets`, i.e. WordPress widgets;
`elementor/widgets/is_widget_enabled` (widgets.php:274) gates REGISTRATION —
the data-loss path this box warns about. All 70 v3 widgets extend `Widget_Base`
directly, and PHP cannot subclass a runtime class-name string, so a wrapper at
registration is impossible too.

> **General lesson, worth applying beyond this feature:** "no PHP hook exists"
> is not the same as "it can't be done". Elementor's editor owns a great deal of
> behaviour client-side. When the PHP side comes up empty, grep the editor JS —
> `plugins/elementor/assets/js/` for what actually ships and
> `plugins/elementor-src-repo/` for readable sources — before proposing an
> expensive workaround. Here that difference was ~20 lines versus a 70-file
> codemod. Always confirm the finding exists in the INSTALLED bundle too; the
> clone runs ahead of the runtime.

### Where things are

- Option + schema + sanitizer + AJAX: `inc/AnimationSettings/class-animation-settings.php`
- Public API for Pro: `is_feature_enabled($f)`, `legacy_v3_enabled()`,
  `feature($f)`, `resolve_color($c)`, `global_colors()`, `has_pro()`.
  Pro must guard every call with `class_exists()` — an older free plugin won't
  have the class.
- Boot: `class-plugin.php`, loaded unconditionally (front end and Pro both read it)
- Dashboard payload: `WCF_ADDONS_ADMIN.animation_settings` in `inc/admin/dashboard.php`
  (shipped with the page, not fetched, so the panel paints filled in)
- AJAX: `aae_get_animation_settings` / `aae_save_animation_settings`, nonce
  `wcf_admin_nonce`, `manage_options` — same contract as the widgets/extensions screens
- Colours are `{ mode: 'custom'|'global', custom: '#hex', global: '<kit color id>' }`.
  Both halves are stored side by side so switching modes doesn't lose the other.
- The 18 preloader layouts are **duplicated** in the free class rather than
  imported from Pro — the free dashboard has to render the list with no Pro installed.

### Status (2026-07-31)

**Working end to end for Preloader.** Verified on the front end: with the v4
preloader on, `.wcf-preloader` paints at first paint carrying the chosen layout
(`orbit-loading`), `background-color: #0b1220`, `--color: #4a5fd9`,
`--color2: #e94ec3`; the JS removes it and clears `wcf-preloader-active` after
load; switching v4 off leaves the page with no preloader at all. Zero JS errors.
The Kit on that site holds **no** v3 chrome keys, so the output could only have
come from the v4 option through the bridge.

Also built and probe-verified: option + sanitizer (rejects unknown layouts and
non-hex colours), legacy detection, Kit import, AJAX, cache invalidation on any
writer (`update_option_*`, not just the AJAX handler), the dashboard screen, and
the Site Settings tab gate.

**v3 widget panel hiding works**, verified across the full matrix by
`E:\Local Testing\verify-legacy-matrix.mjs` — v3 widgets all/none × extensions
all/none × `legacy_v3` on/off, six corners, all green:

| widgets | legacy | registered | listed in panel |
|---|---|---|---|
| all | on | 60 | 59 (+1 self-hidden) |
| all | off | **60** | **0** |
| none | either | 0 | 0 |

Registration tracks the widgets dashboard option and **never** the legacy flag —
that is the property that keeps existing pages rendering. Extensions follow
their own option and are untouched by the flag (7/7 or 0/7 as set). Atomic
widgets show 33 in every corner. Zero JS errors throughout.

**The import scenario is verified end to end**
(`verify-v3-import-ratchet.mjs` + `make-v3-import-page.php`): from the
auto-hidden "new user" state, a page carrying `wcf--image-box` /
`wcf--counter` flips `legacy_v3` back ON at the next admin page load, the panel
lists v3 again, and the imported page renders both widgets on the front end.

Two traps when writing tests here:

- **This dev site ships with no v3 widgets enabled** (`wcf_save_widgets` empty →
  `get_widgets()` returns 0 → nothing `wcf--*` registers), so assertions have
  nothing to bite on until you enable some. The option keys are bare slugs
  (`image-box`); Elementor registers them as `wcf--image-box`. Config shape:
  `wcf_get_config()['widgets']['elements'][<category>]['elements'][<slug>]`.
- **`wcf--theme-post-content` hides itself** from the panel (theme-builder only),
  so "all listed" is 59/60, not 60/60. Assert "nothing hidden except the known
  self-hiders", never a bare count match.

**Not built:** the other four features (Cursor, Scroll to Top, Scroll Indicator,
Popup) — listed in the UI as "Soon" — and the v3 *extension* gate (extensions
add control sections to widgets rather than panel entries, so hiding them is a
different problem and skipping their registration would stop saved effects from
running).

---

## Display conditions — every global setting (2026-08-01)

Every feature in Animation Settings AND both Performance groups carry the same
include/exclude rules, in the shape Elementor's theme builder uses. Stored under
the field key **`conditions`** — NOT `display`, because the scroll indicator
already has a v3 field by that name (its own "Entire Website / Specific Pages /
Specific Post Types" enum). The injection in `schema()` skips a feature that
already owns the key, so the collision silently left the scroll indicator as the
one feature with no conditions at all.

**Injected, never repeated.** `Animation_Settings::schema()` appends
`display_field()` to every feature; Pro's `Performance::schema()` does the same
to its groups. Writing it per feature is N chances for one to drift.

### The distinction that makes it work

| | Scope | Asks |
|---|---|---|
| `is_feature_enabled()` | site | "has v4 taken ownership?" |
| `is_feature_active()` | request | "should it run *here*?" |

**`is_feature_enabled()` must stay TRUE on an excluded page.** It is what tells
Pro's v3 renderer to stand down. Return false there and the excluded page falls
straight back to the Kit's v3 values — you exclude the preloader from the 404
page and a v3 preloader appears on it. `Bridge` therefore does not *skip* an
excluded feature; it **blanks the enable key**, switching both systems off for
that request. `Performance::is_enabled()` / `is_active()` are the same pair.

Anything that renders or enqueues asks `is_feature_active()`. Conditions cost
nothing to evaluate — every location resolves to a WordPress conditional tag,
no queries — except `specific`, which compares `get_queried_object_id()` against
a stored id list.

### Rules

- Exclude beats Include, always.
- Exclude-only ⇒ "everywhere except these". Treating a missing Include as
  "nowhere" would make a single Exclude row disable the feature site-wide.
- Empty rule set ⇒ everywhere (fail open).
- An unknown location matches **nothing**, so a rule pointing at a deleted CPT
  cannot start including the whole site.
- A `specific` row with no ids is **dropped by the sanitiser**, not stored —
  a half-built exclusion must not take the site down while someone is picking.

`Specific pages` is backed by `wp_ajax_aae_search_content`, which does double
duty: search by title, and resolve stored ids back to titles so a saved rule
reloads as page names instead of bare numbers.

## Performance — lazy animation & reduced motion (shipped 2026-07-31)

Dashboard → **Performance**. Governs how the animation runtime is DELIVERED,
not what it does. Two switches, **both off by default**.

| | |
|---|---|
| UI | `src/modules/dashboard/pages/Performance.jsx` — **FREE** |
| Store + pipeline | `pro/inc/Performance/class-performance.php` + `class-lazy-scripts.php` — **PRO** |
| Option | `aae_performance_settings` |
| Payload seam | `apply_filters('aae/performance/dashboard_payload', array())` in free's `inc/admin/dashboard.php`; Pro answers it |

The split is deliberate and is the pattern to copy: free ships the SCREEN, Pro
ships everything behind it. With Pro absent the payload is `[]`, which is how
`Performance.jsx` knows to render `LockedNotice` (two PRO-badged cards
describing the features) instead of an empty frame. Panels are drawn by the
same generic `FeaturePanel` Animation Settings uses — a group with
`'pro' => false` in its schema gets no PRO badge and is never locked.

### What the numbers are

`aae-atomic-common` declares gsap + ScrollTrigger as hard deps
(`inc/Atomic/Assets.php`), so one AAE effect on a page costs **71 KB of GSAP +
43 KB of ScrollTrigger** before the 5.8 KB runtime and its 1–19 KB effect
bundle. The libraries are essentially the whole payload — that is the thing
worth deferring.

### The mechanism — one pipeline, two features

Both switches answer the same question ("should this script run now?"), so
there is one mechanism. `script_loader_tag` (priority `PHP_INT_MAX - 10`, i.e.
last, so a defer/async plugin's rewrite is replaced rather than wrapped)
turns our tags into inert placeholders:

```html
<script type="text/aae-lazy" data-aae-handle="gsap" data-aae-src="…"></script>
```

A ~2 KB inline loader at `wp_footer:21` then decides: reduced-motion visitor →
never load; lazy on → load on approach; otherwise → load immediately. **Both
off = nothing is rewritten at all** and the page is byte-identical to before.

### Three properties that make it safe — do not break any of them

1. **Nothing is pre-hidden in CSS.** Every hidden start state (`opacity: 0`,
   offsets) is set by GSAP at play time, so an element whose script never
   arrives renders normally. Worst case is an animation that doesn't play,
   never content stuck invisible. **An effect that pre-hides in CSS must not be
   added to the deferred set** — it would go blank instead.
2. **GSAP is deferred conditionally.** The GSAP family is shared with the ~70 v3
   widgets, which are NOT deferred. `gsap_deferrable()` walks the enqueued
   dependency graph and only defers the libraries when nothing outside the
   deferred set names them. A page mixing v3 and v4 loads GSAP eagerly
   (correct) and still defers the v4 bundles.

   **`LIBRARY_HANDLES` must list every GSAP PLUGIN, not just the core library.**
   A plugin (`flip`, `Physics2DPlugin`, `Draggable`, `ScrollSmoother`…) declares
   `gsap` as its dependency, so one left off the list does not merely stay eager
   — it registers as an eager consumer of `gsap` and blocks the *entire family*
   from deferring. That is how it fails: silently, as "the feature does nothing
   on this page". Anything added to `Plugin::get_library_scripts()` belongs
   here too.
3. **Atomic WIDGET scripts are excluded** (sliders, forms, menu…). They are
   interactive controls, not decoration — a carousel that initialises only once
   you scroll to it is broken, not fast.

The editor preview is excluded permanently: it blanket-enqueues every bundle
precisely so a builder can toggle effects without a round-trip.

### Two lanes — the viewport is not the only trigger (2026-07-31)

Site chrome — the cursor, ScrollSmoother, the read-later buttons, all inside
`wcf--addons-ex` — belongs to the PAGE, not to any element, so the viewport
observer has nothing to hang it on. It gets its own lane:

| Lane | Carries | Trigger |
|---|---|---|
| Effects | `aae-atomic-common` + the effect bundles | IntersectionObserver on animated elements |
| Chrome | `wcf--addons-ex` + the whole GSAP family | `load` → `requestIdleCallback` (2 s timeout) |

`load` *then* idle, deliberately: `load` alone still competes with images and
fonts finishing. Measured on a chrome page: **234 KB of JS off the critical
path**, and the cursor is live well before a visitor notices it is not.

**Keep the lanes separate.** GSAP is ~90 % of the payload, so it is tempting to
give up and pull the whole chain once the chrome lane fires anyway. Don't — that
makes viewport laziness decorative on every site that has a cursor, which is
most of them. `run(only)` filters by handle and claims each node, so the lanes
cannot double-fetch or interleave.

Three constraints this lane created, each of which broke something first:

1. **Document order is NOT dependency order.** `wcf--addons-ex` uses
   ScrollTrigger without declaring it, so WP is free to print ScrollTrigger
   *after* it — and does. Harmless while the bundle waited for
   `DOMContentLoaded` (everything had run by then), fatal once it executes on
   injection. Symptom: `Cannot read properties of undefined (reading
   'getScrollFunc')`. The loader now hoists all library handles to the front of
   the chain — safe as a blanket rule because a GSAP plugin's only dependency
   is GSAP.
2. **`wcf-addons-ex.js` had a bare `DOMContentLoaded` listener**, which is
   silently fatal when the file is injected after that event: the listener is
   registered on something that already happened, so the cursor, the smoother
   and the read-later buttons never initialise, with no error to show for it.
   It now checks `document.readyState` first. **Any bundle added to a deferred
   set needs this check.**
3. **The preloader must never be deferred.** `body.wcf-preloader-active` sets
   `overflow: hidden !important`, so deferring its removal behind a scroll
   trigger deadlocks: the page waits for a scroll it is itself preventing. It
   is safe today only by accident of structure — its removal script is INLINE
   (`pro/inc/global-elements.php`), and inline scripts have no `src`, so
   `rewrite_tag()` never sees them. If that ever moves into a file, it must be
   excluded explicitly.

Reduced motion exempts the chrome lane: that bundle is not the animation
runtime and also carries plain interactive controls, so dropping it would turn
an accessibility preference into a broken page. Open question, not yet acted
on: ScrollSmoother arguably *should* honour `prefers-reduced-motion`, but that
guard belongs inside the bundle, not in the loader.

### GOTCHA — the animated element is `data-interaction-id`, NOT `data-aae-*`

`InteractionsMap`'s own docblock says elements are tagged `data-aae-text-id` /
`data-aae-anim-id`. **They are not.** The shipped markup carries Elementor's
`data-interaction-id`, and `window.AAE_INTERACTIONS_TEXT` (printed at
`wp_footer:5`) is keyed by that. The first version of the loader scanned for
`data-aae*` dataset keys, found nothing, and silently fell back to loading
everything immediately — laziness that looked wired and did nothing.

The loader now reads the `AAE_INTERACTIONS_*` globals themselves and builds
`[data-interaction-id="…"]` selectors from their keys — the same source of
truth the runtime reads, so it cannot drift. The `data-aae*` dataset scan is
kept as a second source for effects that tag elements directly. **If no target
is found at all it loads rather than withholds** — never withhold assets you
cannot reason about.

### Tests

- `E:\Local Testing\probe-performance.php` — 25 checks: schema/defaults/
  sanitise (clamping both ends), the payload filter, `gsap_deferrable()` in
  three shapes (ours only / a direct v3 consumer / a transitive one), and tag
  rewriting incl. leaving foreign handles alone.
- `verify-performance-lazy.mjs` — 24 checks on a real page. Asserts on
  **network requests**, not DOM state: a page can look identical while still
  having downloaded 114 KB. Covers off / lazy / reduced-motion, and that a
  reduced-motion setting does not affect an ordinary visitor.
- `verify-chrome-defer.mjs` — 21 checks on the chrome lane. Splits requests by
  BEFORE vs AFTER the load event, because "deferred" is a claim about timing
  that a did-it-load list can neither prove nor disprove. Every case ends by
  driving the feature (moving the mouse and measuring the cursor), since a
  timing-only test passes just as happily with a broken cursor.
- `verify-performance-ui.mjs` — the dashboard, both with and without Pro.
- `measure-critical-path.mjs` — bytes before `load`, lazy off vs on.
- Fixtures: `make-perf-page.php` (250vh spacer, THEN a char-animated heading —
  the heading must start below the fold or deferred and non-deferred look
  identical), `set-performance.php`.

### Bugs a full-site test found (2026-07-31) — all fixed, all regression-tested

Five real defects, none of which raised an error. Worth reading as a set: every
one of them was invisible without an assertion aimed straight at it.

1. **v4 chrome rendered unstyled on a v4-only site.** `wcf--addons-ex` (which
   holds `.wcf-cursor { position: fixed }`, the indicator's bar height, …) was
   enqueued only when a v3 WIDGET was active. But the v4 side has no renderer —
   it reuses the v3 markup — so a site with v4 chrome and no v3 widgets got the
   markup with none of its CSS: cursor and indicator sat `position: static` at
   the bottom of the document. Fix: `has_active_legacy_assets()` now also
   returns true when any v4 chrome feature is on.
2. **`gsap is not defined` on the v4 cursor.** That cursor is entirely
   GSAP-driven, but `wcf--addons-ex` never declared `gsap` as a dependency — on
   v3 sites some extension always happened to pull it in. A v4-only site has
   `wcf_save_extensions` empty, which makes `get_library_scripts()` return `[]`,
   so GSAP was never even REGISTERED and every enqueue was a no-op. Fix:
   `ensure_v4_chrome_runtime()` in Pro's `class-plugin.php`, gated on the cursor
   specifically.
3. **`z-index: Array` on the scroll indicator.** `wcf_scroll_indicator_z_index`
   is a v3 NUMBER control while its scroll-to-top twin is a SLIDER; the schema
   typed both as `size` and sent `{size, unit}`. Fix: a real `number` field type
   through defaults/sanitise/bridge/panel. **Check the v3 control's actual type
   before adding a field** — the two are not interchangeable.
4. **The v4 scroll-to-top button had no icon.** `global-elements.php` reads the
   Kit via `$kit->get_settings()` — RAW, so Elementor's control defaults are
   never merged. A v4 user never opens the v3 tab, so `scroll_to_icon` is
   guaranteed absent. Fix: `kit_defaults` in the schema, written by the Bridge
   only when the Kit has nothing. Same root cause as the popup's `close_icon`,
   fixed defensively in place.
5. **An imported starter template rendered COMPLETELY BLANK.** The big one —
   see below.

### DANGER — `legacy_v3` does not register anything

`maybe_reactivate_legacy()` correctly detects imported v3 content and flips
`legacy_v3` back on. That flag controls **UI visibility only**. Registration
follows `wcf_save_widgets`, and a starter-template import never writes it — so
a real import left 34 pages built from `wcf--title` / `wcf--image` /
`wcf--counter` rendering **nothing at all**: no error, no notice, no log line,
because an unregistered widget emits not even a wrapper. The home page went
from 478 KB to 199 KB of markup with zero `<h1>`–`<h6>`.

`maybe_enable_used_v3_widgets()` (admin_init, prio 12) closes it — enabling
exactly the slugs the content references, and only when `wcf_save_widgets` has
**never** been written (an empty array is a deliberate choice and is left
alone). Enabling is the safe direction; the DANGER box above is about the
opposite move.

**The slug is NOT the widget name minus `wcf--`.** `wcf--title` lives in
`animated-title.php` under the dashboard key `animated-title`; deriving the
slug by trimming the prefix silently missed 9 of 24 widgets, `title` and `text`
among them, so the page still rendered half-empty and looked like a different
bug. There is no declared map — `Plugin::$widget_element_keys` is built while
widgets register, which is useless when you are deciding what to register — so
`widget_name_to_slug_map()` reads `get_name()` out of the same
`widgets/<slug>.php` files the registrar loads.

Tests: `verify-v3-widget-autoenable.mjs` (auto-enable, used-only, front-end
renders, and that an explicit all-off choice survives),
`verify-starter-import.mjs` (a real import end to end),
`verify-post-import.mjs` (the imported demo with v4 chrome + lazy animation on).

Two traps when driving the Starter Template screen: the Import button lives in a
`hidden group-hover:flex` overlay so all 40 exist and none is visible — hover
the card root `div.group`, not the title; and the catalogue ships a PRO **and** a
FREE variant under the SAME title, so matching on title alone lands on the Pro
one and stops at the license dialog.

### A hyphen in a control name silently breaks its asset condition (fixed)

`Undefined array key "wcf"` — ~228 lines per Elementor CSS regeneration on a
v3-heavy site. Traced to
`Elements_Iteration_Actions\Assets::get_assets()`, which evaluates a control's
`assets → scripts → conditions` with `Conditions::check()`. That parses a term
name with `/(\w+)(?:\[(\w+)])?/`, and the control was named
**`wcf-image-animation`** — hyphens are not `\w`, so the term collapsed to
`wcf`, a setting that has never existed.

**The log noise was the small half.** The comparison became `null !== 'none'`,
which is ALWAYS TRUE, so `aae--animations--modules` was enqueued on every
element carrying the control. The on-demand loading that block existed for had
never once happened. Measured after the fix: the runtime now loads on 1 of 3
pages instead of 3 of 3.

The control name can't be renamed — saved pages reference it — so the enqueue
moved to `WCF_Image_Animation_Effects::maybe_enqueue_runtime()` on
`elementor/frontend/before_render`, which reads the setting directly. As a
bonus it prefix-matches the responsive variants (`…_tablet`, `…_mobile`) that
the single-term condition never looked at.

**Rule: an Elementor control whose name contains a hyphen can never be the
target of an asset condition.** Use underscores in new control names, and for
existing hyphenated ones do the enqueue in PHP.

**Diagnosing this class of bug:** the warning only fires while Elementor
REGENERATES assets, so a normal page load shows nothing and it looks
intermittent. Reproduce with `files_manager->clear_cache()` immediately before
the request. A declarative grep for the bad name finds nothing — the culprit is
reachable only through `get_controls()` on a live stack with the v3 layer
active. What worked: an `mu-plugins/` error handler that captures a
`debug_backtrace()` for the specific message, which named the exact evaluator in
one request.

### DANGER — never `wp plugin deactivate` the Pro plugin to test the free path

It deletes `wcf_addon_sl_license_status`, and `wcf__addons__pro__status()`
gates Pro's ENTIRE `include_files()` on that option being exactly `valid` — so
reactivating leaves every Pro module silently dead (no Bridge, no Performance,
no AtomicV4 anything) while `wp plugin list` cheerfully reports it active. It
also resets `wcf_addons_setup_wizard`, and an incomplete wizard redirects every
AAE settings screen to the setup page before React boots. Recovery:

```bash
wp option update wcf_addon_sl_license_status valid
wp option update wcf_addons_setup_wizard complete
```

Test a Pro-absent UI branch by emptying the payload in `page.addInitScript()`
instead — see `verify-performance-ui.mjs`.

---

## Cache / optimization plugin compatibility

**Slice 1 SHIPPED 2026-08-01** (the image gate + box reservation).
**Slice 2 SHIPPED 2026-08-02** (the LiteSpeed/Rocket filter fencing).
**Slice 3 SHIPPED 2026-08-02** (the skip-lazy opt-out marker + the per-page
admin-bar modal). All three slices are done; everything below the "SHIPPED"
blocks is the original design, kept for the research and the source citations
— check it against the SHIPPED blocks before trusting a detail, since two of
its instructions turned out to be wrong (the `data-no-optimize`-only fence and
the static `API::purge_post()` call).

### SHIPPED — the image gate (Slice 1)

Measured on the real plugin (LiteSpeed 7.8.1, image lazyload on, logged out):
**5/5 atomic reveal animations ran on a placeholder → 0/5**, on both the cold
and the warm view, with no added latency (median enter→first-style-write 2-3ms).

| File | What |
|---|---|
| `pro/src/modules/atomic-v4/effects/image-gate.js` | the whole gate — detection, promotion, per-image shared promise, debounced `ScrollTrigger.refresh()` backstop, and the `window.AAEImageGate` seam |
| `pro/…/effects/animation/triggers.js` | `wireTrigger` wraps its `play` via `gatePlay()`; prebuilt-`animation` and `scrub` modes promote at bind instead (they cannot be deferred) |
| `pro/…/effects/{parallax,tilt,sticky,image-hover,cursor-hover-effect,mouse-move-effect,horizontal-scroll-anim}/index.js` | `promoteLazyImagesIn(el)` at bind — these measure or move a box and never call `wireTrigger` |
| `free/src/modules/atomic/common.js` | `Registry.register()` wraps `kind.play` through the seam — belt for the EDITOR replay path only |
| `free/…/Widgets/PostImage/{class,twig}` | `image_width`/`image_height` props → `<img width height>` + an aspect-ratio fallback when the ratio prop is `auto` |
| `free/…/Widgets/Posts/{class,twig}` | same for `aae-a-post-thumb`, which was the only lazy + unsized + **0px-tall** image in the atomic line |

Verified on the frontend: the thumb now ships `width="768" height="512"` and
LiteSpeed generates a correctly-sized placeholder from it instead of collapsing
to zero.

**Tests / fixtures** (`E:\Local Testing\`): `verify-cache-compat.mjs` (5
checks), `make-cache-compat-page.php` (fixture 9852 — five cases incl. nested
animated ancestors and the same attachment animated twice), `set-litespeed.php`
+ `_run-ls.mjs` (toggle LiteSpeed settings; **`wp litespeed-option` fatals on
this machine**, so write the `litespeed.conf.*` options directly),
`clear-elementor-cache.php`.

**GOTCHA — `currentSrc` alone cannot prove promotion happened.** After the swap
the browser picks a **srcset candidate**, so `currentSrc` is a different sized
file than `data-src` forever. Compare the `src` ATTRIBUTE too, or a correct
promotion reads as "still lazy" — this produced a full false-alarm cycle in the
probe. The gate does compare both.

**GOTCHA — a finished Regular Animation is invisible to sampling.** fadeUp ends
at opacity 1 / transform none and `clearProps` wipes the inline style, so the
computed values equal an element that never ran. `verify-extension-effects.mjs`
sampled for distinct states and started FAILING when LiteSpeed made the page
render faster (tween ran at 404ms, cleared at 1462ms — before the first
sample). It now latches style mutations from `document_start` and passes on
either signal. **Any assertion of the form "did this animate" needs the latch,
not a sample.**

### SHIPPED — the JS/CSS fence (Slice 2)

Plan steps 3, 4 and 6 below. Measured on the real plugin (LiteSpeed 7.8.1, JS
Combine + Minify + **Defer mode 2 "Delayed"**, logged out, fixture
`aae-cache-compat`), driving the page with **scroll only** — never a pointer or
key, because LiteSpeed promotes every delayed script on the first
`mouseover/click/keydown/wheel/touchmove/touchstart` (`assets/js/js_delay.js:1`)
and a test that touches the page measures nothing. `scroll` is deliberately not
in that list, which is what makes the test possible.

| | without the fence | with it |
|---|---|---|
| AAE runtime files present as tags | **0 of 11** (all swallowed into the parked combined file) | 11 |
| `window.gsap` / `ScrollTrigger` / `AAEADDON` | **all undefined** | all defined |
| image animation played | **no**, 0 style writes | yes |
| inline `<style>` blocks left in place | **0 of 11** | 11 |

| File | What |
|---|---|
| `pro/inc/Performance/class-cache-compat.php` | the whole fence — path fragments + inline markers on 6 LiteSpeed/Rocket filters, and the per-page `comb_ext_inl` opt-out |
| `pro/class-plugin.php` | `Cache_Compat::instance()` in `include_files()` — must stay on `plugins_loaded` |
| `pro/…/class-lazy-scripts.php` | `data-no-optimize`/`data-no-defer` on the performance loader |
| `free/inc/Atomic/InteractionsMap.php` | same attributes on the interaction maps — **in FREE**, since free ships the runtime that reads them |
| `pro/…/{TriggerMap,LazyAssets,FormConditions\Renderer,Popup\Registry}.php` | same, for the v4 trigger map, the popup lazy loader, the form-condition map and the popup critical CSS |

**GOTCHA — inline scripts are matched by CONTENT, not by URL.** `_js_inline_defer()`
(`optimize.cls.php:983`) runs `str_hit_array($con, $cfg_js_defer_exc)` against
the script BODY, so a path fragment can never protect an inline block. This
produced a real, silent break: `wcf-addons-ex.js` is on the protected path list
and stayed live, but it reads `WCF_ADDONS_JS`, which is localized onto the FREE
v3 core handle (`free/class-plugin.php:187`) and prints as that handle's
`-js-extra` block. v3 is out of scope, so the block was delayed and the live
file threw `WCF_ADDONS_JS is not defined` — taking ScrollSmoother down and
leaving every scroll-driven animation unplayed, with the runtime itself loading
perfectly. Hence `Cache_Compat::INLINE_MARKERS`, and the rule behind it: **the
fence follows the dependency graph, not the scope boundary.**

**GOTCHA — LiteSpeed intermittently ends up DEACTIVATED mid-session.** Happened
twice on 2026-08-02. `\LiteSpeed\Base` stops existing on the front end, lazyload
silently stops, and the fixture page comes back clean — which reads exactly like
"the fence worked". `active_plugins` in the DB genuinely loses the row; there is
no `deactivate_plugins` call anywhere in LiteSpeed's source and nothing in
`debug.log`. **Cause NOT identified** — it was first blamed on `guest-on`, but it
recurred without Guest Mode, and a deliberate replay (activate → write
`litespeed.conf.*` → frontend request → `clearElementorCache` → `set-v3-state`)
would not reproduce it. Recovery: `wp plugin activate litespeed-cache`. This is
why `verify-cache-jsopt.mjs` asserts `parked > 0` FIRST — never trust a green
cache run that did not prove the optimizer ran.

Guest Mode is still not worth using here: `js-delay` gives the same defer mode 2
behaviour Guest Optimization forces, without the guest branch.

**GOTCHA — the optimizer never runs here by default, and it looks like a pass.**
`Optimize::_finalize()` bails on `!Control::is_cacheable()`, which needs
`LITESPEED_ON`, which `conf.cls.php:459` only defines when `LITESPEED_ALLOWED`
is set — i.e. on a real LiteSpeed server. Before the plugin was re-activated the
fixture page came back with zero combined files and zero parked scripts, which
reads exactly like "the fence worked". Always assert that the optimizer DID
something (`verify-cache-jsopt.mjs` checks `parked > 0` first) before believing
a green run.

**GOTCHA — never assert "did it animate" against one effect's signature.** The
first version of the probe checked for a `clip-path` write, which is only true
while fixture case B is configured as `reveal`. The fixture was re-saved with
`elasticPop` (transform/opacity) mid-session and a perfectly animating page
reported as broken. Assert the effect's own played marker (`__aaeImgPlayed`)
and print which effect ran. Same family of trap as the finished-Regular-Animation
row above.

**Deliberately NOT registered: `litespeed_optimize_css_excludes` /
`rocket_exclude_css`.** Combining deletes the member `<link>`s and re-emits one
file at head-top, so an EXCLUDED sheet keeps its old position while everything
else collapses — excluding is what MOVES our CSS against Elementor's (0,2,0)
atomic base styles. The order-neutral `litespeed_optm_css_comb_ext_inl` => false
is used instead. See the cascade DANGER below before adding one.

**Verified in the v4-only state too** — every v3 widget and extension off, so
`has_active_legacy_assets()` is false and `get_library_scripts()` returns []:
11/11, image animation played. In that state neither `wcf--addons-ex` nor
`WCF_ADDONS_JS` is emitted at all, so the `WCF_ADDONS_JS` inline marker goes
inert (a substring matching nothing) and the remaining v4 runtime — `gsap`,
`ScrollTrigger`, `SplitText`, `aae-atomic-common`, the effect bundles — is
covered entirely by the path fragments. **The fence does not depend on v3.**

**DANGER — `set-v3-state.php` destroyed a real site's v3 enablement.** On
2026-08-02 its snapshot read `wcf_save_extensions` / `wcf_save_widgets` as `''`
in one WP-CLI process moments after a `status` run in another reported 31 and 93
active, so `restore` wrote empties back over the originals. Root cause never
found. The script now ABORTS rather than proceeding when the snapshot is not a
populated array. Note `Animation_Settings::maybe_enable_used_v3_widgets()` will
NOT heal this: it bails unless the option is `false` (absent), and an empty
string is not absent — `delete_option()` first, and even then it only restores
the slugs the content references (36 here, not 93).

**Gated on a cache plugin actually being present** (changed 2026-08-02, revising
the "hook unconditionally" decision). `Cache_Compat::litespeed_active()` reads
`LSCWP_V` / `\LiteSpeed\Core`, `rocket_active()` reads `WP_ROCKET_VERSION`, and
only that plugin's filters are hooked. Timing is safe because WordPress includes
every active plugin's main file BEFORE `plugins_loaded`, where we construct.
The original argument still holds for the PATHS — they are self-scoping, since
they only ever match our own asset URLs, so on a page with no atomic content
they match nothing. It does NOT hold for `INLINE_MARKERS`: `WCF_ADDONS_JS` is a
v3 global, so an ungated entry reaches pages this feature has no business
touching. Never use `is_plugin_active()` here — it only exists in admin.

**Hardening pass (2026-08-02), all three found by reading, all now covered by
`pro/tests/atomic-v4/image-gate.test.js` (12 tests, `npm run test:atomic-v4`):**

- **Leak — the per-image promise could never settle.** Neither `load` NOR
  `error` is guaranteed: a hung request, or a lazyloader re-assigning `src`
  before ours lands, leaves both silent. The listeners then stay attached for
  the life of the document and every later play awaits a promise that will
  never resolve — burning the gate timeout on every play, forever, which is the
  exact failure the detection rules exist to prevent, re-entered from behind.
  Fixed with `PROMOTE_TIMEOUT` (10s, deliberately far longer than the gate's own
  1s so a slow connection does not trip it). **Verified by disabling the fix and
  watching the test fail** — and note the test races the promise against a
  resolved sentinel rather than awaiting it, because a bare `await` turns this
  defect into a hung suite with no output instead of a named failure.
- **`push(...nodeList)` in `unsettledImagesIn`.** A loop-grid/archive subtree
  with thousands of images passes every node as a separate argument and throws
  RangeError past the engine's limit — works on every page you test, dies on the
  one page with a big archive. Now `Array.from`.
- **`page_has_animations()` memoised a NEGATIVE.** Enqueues land during render,
  so an early caller legitimately sees false; caching that answered for the
  whole request from the one moment it could not know, and failed in the
  safe-looking direction (page silently combined, no error). Only a positive is
  memoised now; `wp_script_is()` is an array lookup, so re-asking is free.
- `add_js_exclusions()` also now drops non-strings before `array_unique`, which
  compares by string cast — one nested array in a third party's list would
  otherwise raise "Array to string conversion" on every request, in a filter we
  do not own, on a page that was working.

Checked and found sound: the promotion→`ScrollTrigger.refresh()`→`onEnter`→play
path cannot recurse (each image promotes once, the refresh is debounced, and the
shared promise short-circuits re-entry); `InteractionsMap::print_maps()` clears
`$entries` after printing; `protected_paths()` memoisation runs the filter once.

**Tests** (`E:\Local Testing\`): `verify-cache-jsopt.mjs` (11 checks),
`check-cache-compat-filters.php` (are the filters registered, do they emit the
right paths, does a string setting survive), `set-litespeed.php` +
`_run-ls.mjs` gained `js-defer` / `js-delay` / `inline-comb` / `js-off` /
`guest-on` / `guest-off` modes.

**Known gap, accepted:** the fence is Pro-only, so a free-only site with JS
delay on still loses its animations. Free carries the inline-tag attributes
(which is why those live in `InteractionsMap`), but nothing registers the file
paths. Revisit with slice 3's Pro-only trap discussion.

### SHIPPED — the skip-lazy opt-out marker (Slice 3, step 5)

Performance > **Cache Compatibility**, default OFF. When on, every `<img>`
inside an animated element gets `class="skip-lazy" data-no-lazy="1"` and the
cache plugin leaves it eager. Measured on the fixture with LiteSpeed lazyload
on: 6 images inside animated elements marked and served with real URLs, the 1
image outside any animated element still lazy-loaded — so it is scoped, not a
site-wide "turn lazy loading off". Setting off → 0 marked, lazy loading fully
restored.

| File | What |
|---|---|
| `pro/inc/Performance/class-skip-lazy.php` | the whole feature |
| `pro/inc/Performance/class-performance.php` | `cache_compat` schema group |
| `free/inc/Atomic/InteractionsMap.php` · `pro/…/Support/TriggerMap.php` | `has( $id )` — "is this element animated" |

**The field is named `enable` for a reason.** `Performance::is_active()` reads
that exact key and then applies the shared display-conditions control, so the
naming is what gives the marker per-page targeting without building anything —
which is most of what step 7's modal was for.

**Why output buffering, when that is not the obvious choice.** The marker must
land on the `<img>` ITSELF (Rocket has no parent-class mechanism, so a class on
the wrapper passes a LiteSpeed test and does nothing on the commoner plugin).
But we do not render those images: `data-interaction-id` comes from Elementor
and the tag is built in Elementor's own Twig
(`modules/atomic-widgets/elements/atomic-image/atomic-image.html.twig`), so
there is no attribute filter to hook, and `wp_get_attachment_image_attributes`
never runs. The images are also usually DESCENDANTS of the animated element, so
a per-widget filter would miss them anyway. Buffering between
`elementor/frontend/before_render` (`element-base.php:500`) and `after_render`
(`:583`) is what is left.

Three rules make that safe, and each is load-bearing:

1. **Nothing hooks unless it will do work** — no cache plugin, setting off,
   admin, or editor preview → `ob_start()` is never called. An always-on buffer
   around every element would be a real cost paid by every site for a feature
   almost none enable.
2. **Only the OUTERMOST animated element is buffered** (`$open_id` doubles as
   the "already inside" flag). Animated elements nest — fixture case C is fadeUp
   inside fadeUp — and nested buffers would rewrite the same `<img>` repeatedly.
3. **The close is keyed by element id, never a counter.** If another plugin's
   `after_render` bails early or an element renders nothing, a counter pairs the
   close with the wrong element and swallows its output.

**Hook priority is not arbitrary:** `open` runs at `before_render` **100**
because every extension registers its interactions on that same hook at
10-25, so only by 100 can the maps answer for this element. `close` runs at
`after_render` **0** so we rewrite exactly what the element rendered.

`mark_images()` uses regex, not DOMDocument — a fragment parse/serialise
normalises markup, mangles HTML5 void elements and drops what it thinks is
invalid, which is a destructive amount of change for adding one class. It also
returns the ORIGINAL html when `preg_replace_callback` returns null (PCRE limits
on a huge element): an unmarked image is a missed optimisation, an empty one is
a broken page. 17 cases in `E:\Local Testing\verify-skip-lazy-marker.php` —
quoting styles, self-closing, uppercase, idempotence, foreign `data-no-lazy`,
empty input, and a 4000-node subtree.

Toggle it with `wp eval-file set-cache-compat.php on|off|status`.

### SHIPPED — the per-page override modal (Slice 3, step 7)

Front-end admin-bar node **AAE Cache** → modal for the page you are looking at.
Three states (`inherit` / force on / force off) stored as `_aae_cache_compat`
post meta, saved over AJAX with the established contract (nonce
`wcf_admin_nonce`, `manage_options`).

| File | What |
|---|---|
| `pro/inc/Performance/class-cache-compat-page.php` | meta, precedence, admin-bar node, AJAX, purge, the live report |
| `pro/src/modules/atomic-v4/cache-compat-page.js` | the modal |
| `pro/assets/css/cache-compat-page.css` | its styling |
| `pro/inc/Performance/class-skip-lazy.php` | `Cache_Compat_Page::resolve()` wins over the global answer |

**Standalone modal, NOT `popup-settings-core.js`** (decided 2026-08-02).
`buildSettingsModal()` hardcodes its own title and iterates module-level
`PSET_TABS`/`PSET_FIELDS`, so reuse meant parameterising a shipped, working
module for a second caller with a different field set — regression surface for
no gain. The `aae-v4-pset-*` CLASS NAMES are reused so it reads as the same UI;
the container ids are its own, because the popup CSS is id-scoped and only
enqueued where that UI loads.

**`inherit` deletes the row, it does not store the string.** Keeps "never
touched" and "explicitly back to default" identical, and stops the meta table
filling with rows that mean "do nothing".

**The report reads LIVE values, not just our settings** — including whether
Guest Optimization is forcing lazyload on. A panel that mirrors only our own
option confidently shows the wrong answer on the commonest visitor path.

`current_post_id()` uses `get_queried_object_id()`, not `get_the_ID()`: inside a
loop the latter answers for whichever post is rendering, which on an archive is
not the page you are on. Singular only — an archive has no post to hang meta
from, and writing onto its first post would be worse than offering nothing.

**Tests:** `verify-cache-compat-page.php` (15 — precedence, garbage meta,
report shape, purge does not fatal) and `verify-cache-compat-page.mjs` (16 —
admin-bar node, modal, three radios, Escape writes nothing, save round-trip,
force-on marks images with the global setting OFF, inherit deletes the row,
logged-out visitors get no UI and no config).

**GOTCHA — `wp eval-file` runs the file inside a function.** The script body is
NOT global scope, so a top-level `$pass = 0` and a `global $pass` in a helper
are different variables and the summary prints "0 passed, 0 failed" whatever
happened — a test that cannot fail. Use `$GLOBALS[...]` in these scripts.

### Design for the remaining slices (PLANNED — researched 2026-08-01)

Users install WP Rocket / LiteSpeed / Perfmatters to make pages fast, and those
plugins are currently free to reach in and break every animation on the page.
There is **no compat code at all** today: grepping both plugins' `inc/` for
`rocket|litespeed|skip-lazy|no-lazy|lazyload` returns nothing but a v3
changelog line.

**Decisions taken — do not re-litigate:**

- **LiteSpeed Cache first, one plugin at a time.** Free, installs from wp.org
  (7.8.1 is already installed on the dev site, deactivated), and it does all
  three risky things — image lazyload, JS defer/delay, CSS combine/UCSS. The
  compat layer is plugin-neutral by design, so the second plugin is mostly
  extra TESTING, not extra code.
- **The opt-out marker is a Performance-panel setting, default OFF.** Marking
  our images `skip-lazy` silently cancels the lazy loading the user installed a
  plugin to get. Fix 1 keeps both, so most sites should never need the marker.

### The evidence — measured, not assumed (`home-2`, top to bottom)

A 3rd-party lazyloader was simulated exactly as they all work (`src` →
`data-src`, then an IntersectionObserver with `rootMargin: 200px` swaps it
back):

| | CLS | shifts | ST refresh | height grew | **animations that fired on a not-yet-loaded image** |
|---|---|---|---|---|---|
| native `loading="lazy"` | 0.087 | 19 | 20 | +1293px | **0 / 24** |
| 3rd-party JS lazyload | **0.657** | 31 | 5 | +1813px | **23 / 23** |

**23/23 is the headline, not the CLS.** The plugin assigns `src` ~200px before
the viewport; ScrollTrigger fires at `top 85%`, before the bytes land. Every
reveal animation plays on an empty box and the image then pops in unanimated —
the animation is not "slightly off", it is destroyed. CLS 0.657 is a distant
second (Google's "poor" starts at 0.25). Note the refresh count went DOWN (5 vs
20) while the height grew MORE: stale trigger positions also stay stale longer.

For comparison, native lazy on a page whose images reserve their box (`home`)
moved 3 of 83 triggers by ≤19px and grew 0px. **The root cause is unreserved
space, not laziness** — laziness only decides whether the reflow lands at load
or mid-scroll.

### Four independent failure axes

| # | Plugin feature | What it breaks | Owner |
|---|---|---|---|
| 1 | Image/iframe/video lazyload | the animation itself (23/23 above) + CLS | ours to fix |
| 2 | JS defer / delay / combine | GSAP + ScrollTrigger never initialise in time | ours to exclude |
| 3 | CSS combine / minify / UCSS | **Elementor V4 atomic styles** — a known, widespread 2026 issue | Elementor's, but we inherit it |
| 4 | **HTML Minify** | whitespace inside headings → **SplitText** | ours, and it has no per-tag opt-out |

Axis 3 is not ours but lands on us anyway: V4 splits styles across many more
files, and combine can drop or reorder them; UCSS ("Remove Unused CSS") is the
single most-reported Atomic-CSS culprit since atomic became the default for new
sites in April 2026. Our `define_base_styles()` output rides that same pipeline.
**Unverified and must be tested, not assumed:** whether UCSS also strips the
Custom CSS extension's generated `[data-interaction-id="…"]` rules and its
top-level `@keyframes` — those selectors do appear in the DOM, so they may well
survive; `@keyframes` is the more likely casualty.

### The exclusion mechanisms — read out of LiteSpeed 7.8.1's own source

Do not take these from the docs. The docs describe the SETTINGS screens and are
silent or misleading on the developer-facing half; every name below was read
from `wp-content/plugins/litespeed-cache/src/`, which ships as plain PHP (no
build step — the installed copy IS the source, unlike Elementor).

| Need | LiteSpeed 7.8.1 | WP Rocket |
|---|---|---|
| skip image lazyload — attribute | `data-no-lazy`, `data-skip-lazy`, `data-lazyloaded`, `data-src`, `data-srcset` (`media.cls.php:1053`) | `data-no-lazy`, `data-skip-lazy`, `skip-lazy`, `loading="eager"`, … |
| skip image lazyload — by class | `litespeed_media_lazy_img_cls_excludes` | class on the `<img>` |
| skip image lazyload — by parent class | `litespeed_media_lazy_img_parent_cls_excludes` | **none** |
| skip image lazyload — by src | `litespeed_media_lazy_img_excludes` | `rocket_lazyload_excluded_src` |
| skip JS defer | `data-no-defer` attr (`optimize.cls.php:977`); `litespeed_optm_js_defer_exc` | `rocket_exclude_defer_js` |
| skip JS delay | `litespeed_optm_js_delay_inc` — an **include** list, see below; `litespeed_optm_gm_js_exc` (Guest Mode) | `rocket_delay_js_exclusions` |
| skip JS/CSS optimize (incl. inline) | `data-no-optimize` attr (`optimize.cls.php:889/1135/1169`) | — |
| skip JS combine | `litespeed_optimize_js_excludes` | `rocket_exclude_js` |
| skip CSS combine | `litespeed_optimize_css_excludes` | `rocket_exclude_css` |
| skip UCSS | `litespeed_optimize_ucss_file_exc_inline`, `litespeed_optm_css_to_be_removed` | — |

Three things the documentation got wrong or left out, each of which would have
produced a broken implementation:

1. **The `skip-lazy` class works on BOTH plugins** — LiteSpeed hardcodes it
   into the class excludes at `media.cls.php:1002` (WP core ticket 44427)
   before the settings list is even consulted. *(An earlier revision of this
   section claimed the class does nothing on LiteSpeed — refuted by review;
   `:1064` is where the merged list is checked, `:1002` is where `skip-lazy`
   enters it.)* So the portable marker is `class="skip-lazy"` +
   `data-no-lazy="1"` belt-and-braces; no custom-class filter registration is
   needed for parity.
2. **JS Delay: the include list only matters in defer=1 mode.** Under defer=2
   ("Delayed") — which **Guest Optimization forces** (`optimize.cls.php:85-87`)
   — EVERY parsed script is delayed regardless of `litespeed_optm_js_delay_inc`
   (`:1263`, `:1002` for inline). What protects a file there is the **exclude**
   lists, checked first (`:1258`, fed by `litespeed_optm_js_defer_exc` /
   `litespeed_optm_gm_js_exc`) or `data-no-defer`. Absence from the include
   list is NOT safety.
3. **LiteSpeed's own lazyload threshold is 300px** (`litespeed_lazyload_threshold`,
   `media.cls.php:892`), not the 200 the simulation used — slightly more
   generous, and the 23/23 race still happens well inside it.

**DANGER — the marker must go on the `<img>` itself, never only the wrapper.**
WP Rocket has no parent-class mechanism at all; a class on a parent `<div>`
does nothing there. LiteSpeed does have one, so a wrapper-only implementation
would pass a LiteSpeed test and fail silently on the more widely-installed
plugin. Our animated element IS the wrapper, so the marker has to be written
onto its descendant images.

**`data-no-optimize="1"` is LiteSpeed's own convention for exactly our case** —
it stamps it on every inline script it injects itself (`gui.cls.php:1167,1184`,
`media.cls.php:892,897`). Step 4 below is not a workaround, it is the intended
API.

### The plan, in order

**1. Wait for the image before playing (Pro, runtime, no setting).**

**DANGER — the dispatcher is NOT the choke point.** An earlier revision said
"`common.js` funnels every kind through one dispatcher, so wrap `kind.play` in
`Registry.register()`". Review refuted it: what `scan()` funnels is **`bind`**
(`common.js:325`); the frontend play is a **private closure built inside each
effect's bind** and handed to `wireTrigger` (`regular.js:361-381` →
`triggers.js:102`). `kind.play` on the registry object is called only by the
editor replay paths (`common.js:610-612`, `:552-554`). A register-time wrap
ships a gate that no visitor ever runs — it passes when the admin tests and
does nothing in production.

The real wiring is three call-site classes, all in Pro
(`src/modules/atomic-v4/effects/`, shared module `image-gate.js`):

1. **`wireTrigger`'s play callback** (`triggers.js:102`) — covers regular, text
   (`text.js:729`) and image-animation (`index.js:348`): wrap the callback in
   `whenImagesSettled(el, play)` with a **~1s timeout** so a broken image can
   never block an animation forever. The deferral must stay invisible to
   `chainCompletionDrain`/`stillRunning` (`common.js:268-283`, `:580`) or
   children drain before their gated parent; skip completion hooks on
   `repeat:-1` tweens entirely (onComplete never fires).
2. **Scrub mode** (`triggers.js:241-262`) has no play callback — ScrollTrigger
   drives a prebuilt tween. Promote the images at bind time instead; a scrub
   cannot be deferred.
3. **Geometry effects** (parallax/tilt/sticky/image-hover/cursor-hover/
   mouse-move/horizontal) never call `wireTrigger` — promote at bind time so
   they measure real geometry, not a placeholder's.

Free's `Registry.register()` still gets the `kind.play` wrap as a cheap belt
for the editor replay path — harmless (LiteSpeed never rewrites the editor) but
correct if a cache-rewritten page is ever replayed. Defer only the PLAY, never
the ScrollTrigger creation — the one-shot `once: true` retirement in
`triggers.js` is decided from the trigger's own config, and moving the trigger
would change which instances qualify. After a promoted image loads with no
reserved box, schedule one debounced `ScrollTrigger.refresh()` — otherwise this
fix recreates the "settles after load, nothing refreshes" bug it exists to
solve.

**DANGER — `complete` and `naturalWidth` both LIE on a real lazyloader.** The
markup LiteSpeed actually emits is (`placeholder.cls.php:275-289`):

```html
<img data-lazyloaded="1" src="<PLACEHOLDER>" data-src="<REAL>" data-srcset="…" width=… height=…>
```

`src` holds a *real* placeholder — an SVG data-URI of exactly the declared
width×height, or an LQIP base64 — so `img.complete` is **true** and
`naturalWidth` is **non-zero**, and with the responsive placeholder it is even
the correct size. Both obvious "has it loaded" tests return a false positive and
the animation plays on the placeholder. (The measurement above did not hit this
only because the simulation stripped `src` entirely; real-world detection is
harder than the simulation, even though its CLS is better.)

**Detect by marker + URL comparison, never by pixels — and never by marker
presence alone.** LiteSpeed's client lib keeps `data-lazyloaded`/`data-src` on
the element **forever after loading** and signals completion via class
`litespeed-loaded` / `data-ll-status="loaded"` (`lazyload.lib.js:51,121,493`).
"Marker present ⇒ not final" therefore burns the full gate timeout on every
play of every already-loaded image, forever — a gate that silently adds 1s to
everything. The correct test: not-final only when the **resolved** known source
attribute (`data-src`, `data-lazy-src`, `data-litespeed-src`, `data-lazyload`,
`data-orig-src`; compare via `new URL(v, baseURI).href`) differs from
`currentSrc`, or the image is genuinely incomplete; done =
`complete || litespeed-loaded || ll-status="loaded"`. Treat
`img.closest('picture')` as final — `<source>` elements stay live, so
`currentSrc` never equals `data-src` there and the comparison would false-flag
every picture-wrapped image.

Then **promote it ourselves**: copy `data-src`→`src`, `data-srcset`→`srcset`
**and `data-sizes`→`sizes`** (LiteSpeed renames all three,
`placeholder.cls.php:276-281`; missing `sizes` makes srcset pick the 100vw
candidate) and await `load`/`error`, sharing **one in-flight promise per
`<img>`** as an expando — the same image under two animated ancestors must not
re-derive state from markers mid-flight, or the second ancestor plays on bytes
still in transit. This is exactly what the plugin was about to do a moment
later; we only move it earlier, and only for elements we are about to animate.
Awaiting `load` without promoting is not enough — a placeholder never fires
another `load`, so the timeout would just replay today's bug. **Iframes are out
of scope**: LiteSpeed's delay lib re-assigns their `src` on first interaction
(`media.cls.php:876-898`), so promoting one early means it reloads mid-view.

**2. Reserve the box (free plugin, markup).** Half the CLS in both modes, and it
is what makes a stripped `src` harmless. `aae-a-post-image` already gets this
right — its wrapper carries `aspect-ratio` — and it is the only atomic image
shape that survives. Measured on `aae-v4-widgets`:

| image | `loading` | space reserved |
|---|---|---|
| `aae-a-post-image` | eager | **yes** (`aspect-ratio: 16/9` wrapper) |
| `e-image-base`, `aae-site-logo-img`, image-compare before/after | eager | no |
| **`aae-a-post-thumb`** | **lazy** | **no — and renders 0px tall** |

Emit `width`/`height` where the attachment size is known, and give
`aae-a-post-thumb` the same wrapper treatment. Note `aae-a-post-image`'s twig
only emits `aspect-ratio` when the prop is set and not `'auto'` — `auto` needs a
fallback or it joins the unsized list.

**3. Register JS/CSS exclusions automatically (Pro, `inc/Performance/`).** No
user configuration — one small `Cache_Compat` class that answers the filters in
the table above. Match on **file path, not handle**: the GSAP handles are
literally `gsap` / `ScrollTrigger` / `SplitText` and would collide with any
other plugin shipping GSAP. The paths are
`assets/lib/{gsap,ScrollTrigger,ScrollSmoother,SplitText,DrawSVGPlugin,MotionPathPlugin,TextPlugin}.min.js`
(all `WCF_ADDONS_PRO_URL`) plus `assets/build/modules/atomic*` and
`assets/js/wcf-addons-ex.js`.

Register against every filter, not the one that seems sufficient — combine,
defer and Guest Mode are separate code paths and a file excluded from one is
still processed by the others:
`litespeed_optimize_js_excludes`, `litespeed_optm_js_defer_exc`,
`litespeed_optm_gm_js_exc`, `litespeed_optimize_css_excludes`,
`litespeed_optimize_ucss_file_exc_inline`; and Rocket's `rocket_exclude_js`,
`rocket_exclude_defer_js`, `rocket_delay_js_exclusions`, `rocket_exclude_css`.
Hook them unconditionally — an unused filter on a site without the plugin costs
nothing, and a `class_exists`/`defined` guard would only add a way to get the
detection wrong.

**4. `data-no-optimize="1"` on the inline performance loader.** `print_loader()`
emits `<script id="aae-performance-loader">` with no guard
([class-lazy-scripts.php:650](animation-addons-for-elementor-pro/inc/Performance/class-lazy-scripts.php)).
JS *Delay* covers inline scripts, so under LiteSpeed/Rocket delay-JS the loader
waits for the first user interaction and **nothing on the page animates until
the visitor touches it**. One attribute fixes it.

**5. The opt-out marker setting (Performance panel, default OFF).** Last,
because steps 1–2 should make it unnecessary; it exists for the site that still
has a problem. What it writes onto every `<img>` descendant of an animated
element:

```html
<img data-no-lazy="1" class="… skip-lazy" …>
```

Both halves work on both plugins (see correction #1 above: LiteSpeed hardcodes
the `skip-lazy` class at `media.cls.php:1002` and bypasses on `data-no-lazy`
at `:1054`; Rocket honours both). No filter registration needed.

**6. Per-page inline-combine opt-out (Pro, same class as step 3).** Answer
`litespeed_optm_css_comb_ext_inl` / `…_js_comb_ext_inl` with `false` on pages
that actually carry AAE animations, reusing the signal the Performance module
already computes for on-demand asset loading. This is what protects Elementor's
inline atomic styles and our Custom CSS extension without touching any other
page — see the per-page section below for why this filter and NOT
`litespeed_optm_uri_exc`.

**7. Front-end admin-bar modal — per-page overrides, saved over AJAX (DECIDED).**
Step 6 picks a sensible default automatically; this is how a site owner
overrides it for one page. It belongs where the problem is visible: an admin-bar
node on the FRONT END opening a modal for the page currently being viewed.

- **Reuse the existing modal, do not write a second one.** The Popup module
  already ships one shared tabbed settings modal driven from three entry points
  (`src/modules/atomic-v4/popup-settings-core.js` — see the Popup section). Same
  shape here. It currently lives in Pro; if this UI ships free, the core module
  has to move or be duplicated deliberately — decide that before building, not
  during.
- **AJAX contract is already established** — mirror
  `aae_get_animation_settings` / `aae_save_animation_settings`: nonce
  `wcf_admin_nonce`, `manage_options`, whole-object save. Do not invent a new
  one.
- **Storage is post meta, not an option** (DECIDED). Key `_aae_cache_compat`:
  `aae_` matches the modern option naming, the leading underscore keeps it out
  of the Custom Fields UI. It is read at render time by the step-3/6 filters. An
  option keyed by post id would work and is worse — it does not travel with an
  export or a duplicate.
- **Three states, never a boolean:** `inherit` (default) / `force on` /
  `force off`. `get_post_meta()` returns `''` for a page nobody has touched, and
  a boolean cannot tell that apart from a deliberate "off" — so a boolean makes
  the global default unreachable the moment anyone opens the modal.
- **The Pro-only trap that DOES apply.** If the whole layer is Pro, a lapsed
  licence means Pro's modules never load, the filters never register, and the
  page silently goes back to being combined and lazy-loaded — animations break
  while the saved meta sits there doing nothing. That is why **steps 1–2 belong
  in FREE** (the runtime image gate and the reserved box, which are what
  actually save the animation) and only the integration, filters, settings and
  UI are Pro. Keep that line.
- **The trap that does NOT apply, though it looks like it should.** The
  migration plan's hard rule — *"`Schema.php` must stay free or saved data is
  destroyed"* — is about atomic widget PROPS: `Props_Parser::validate()`
  iterates the schema, so a prop the schema does not declare is erased from
  `_elementor_data` on the next save. Post meta never goes through that parser.
  A Pro-owned `_aae_cache_compat` meta survives a licence lapse untouched. Do
  not "solve" a problem this storage choice does not have.
- **DANGER — the save MUST purge that URL's cache.** The setting changes the
  rendered HTML, so with page cache on, saving does nothing visible until the
  page is purged. That arrives as "the setting doesn't work" (see the page-cache
  section). **CORRECTED 2026-08-02 — the call this section used to prescribe is
  a fatal.** `LiteSpeed\API::purge_post()` (`api.cls.php:240`) is `public
  function`, NOT static, and `method_exists()` returns true for non-static
  methods, so the obvious guard does not protect the obvious call: PHP 8 throws
  "Non-static method cannot be called statically" and takes the save with it.
  Use the **action** — `do_action( 'litespeed_purge_post', $post_id )` —
  which LiteSpeed binds its own instance to at `api.cls.php:111` and which is a
  silent no-op when the plugin is absent. `LiteSpeed\Purge::purge_ucss()`
  (`purge.cls.php:326`) genuinely IS static; verify with `is_callable`, not
  `method_exists`. `rocket_clean_post()` behind `function_exists`. And purge
  `_elementor_element_cache` too, or the render cache replays the old HTML with
  no page cache involved at all.
- **The modal must show what is actually in effect, not just our own settings.**
  Guest Optimization overrides the plugin's own config (see the DANGER box
  below), so a panel that only reflects our meta will confidently show the wrong
  answer. Read LiteSpeed's live conf where we can and label the row accordingly.

#### What the modal can control — combine, order, priority, defer

Asked for: which assets combine, what loads after what, priority, defer. Most of
it is real; one part is not, and building a control for it would ship a switch
that does nothing.

| Ask | Possible? | Mechanism |
|---|---|---|
| which of OUR files are combined | yes, per file | `litespeed_optimize_{css,js}_excludes`. All-or-nothing per file — LiteSpeed emits **one** combined file per type, there is no API for combine *groups* |
| order among our own assets | yes | our own `$deps` + enqueue order. Combine preserves document order (`$src_list[]` is filled in match order), so our order survives into the combined file |
| order **inside** LiteSpeed's combined file | **no** | no API, and nothing to hook. If a specific order matters, that file must be excluded, not reordered |
| order relative to other plugins' assets | only by excluding ours | and see the cascade DANGER below — excluding is what MOVES it |
| defer / async per handle | yes, ours already | `script_loader_tag` at `PHP_INT_MAX - 10` (`class-lazy-scripts.php:125`) already rewrites our tags last. Add `data-no-defer` so LiteSpeed does not re-defer what we just decided |
| enqueue priority | yes | ordinary WP hook priority |
| network priority | yes | `<link rel=preload fetchpriority="high">` for the GSAP core, which is 71 KB and gates everything |

**Design rule: the modal drives OUR pipeline and tells LiteSpeed "hands off".**
Do not build a UI that negotiates with the cache plugin — its behaviour differs
between versions, and Guest Optimization overrides even its own saved config, so
a control that reads "combine: off" would be lying on the most common visitor
path. We already own `script_loader_tag` last and already reorder the chain (the
lazy loader hoists every library handle to the front — see the two-lane section
in Performance). Extend that mechanism; use LiteSpeed's filters purely as a
fence.

**DANGER — never expose free-form "load after X".** The dependency graph is not
a preference, and this exact failure already happened here: `wcf--addons-ex` used
ScrollTrigger without declaring it, WordPress printed ScrollTrigger *after* it,
and the result was `Cannot read properties of undefined (reading
'getScrollFunc')` — see the numbered constraint in the Performance two-lane
section. A UI that lets someone put `ScrollTrigger` before `gsap` reintroduces
that class of bug with no error at save time. Order rows must be constrained by
the declared `$deps`: reorder only among handles that are independent of each
other, and refuse anything that violates the graph.

### How CSS/JS minify + combine is actually kept working

There are two layers, and the attribute layer is the one to build on — it is
per-tag, needs no settings, and LiteSpeed checks it before anything else.

**What LiteSpeed's parser skips, read from `optimize.cls.php`:**

| Tag | Skipped when |
|---|---|
| `<script>` | **`type` is anything but `text/javascript`** (`:895`) · `data-no-optimize` · `data-optimized` · `data-cfasync="false"` |
| `<link rel=stylesheet>` | `data-no-optimize` · `data-optimized` (`:1132`) · external host, unless "combine external and inline" is on |
| inline `<style>` | `data-no-optimize` (`:1169`) · always, unless "combine external and inline" is on |
| inline JS defer | `data-no-defer` (`:977`) |

Four consequences worth internalising:

1. **Inline `<style>` and inline `<script>` ARE combined**, not just files —
   whenever "combine external and inline" is on. That is the setting that
   reaches Elementor's atomic styles and our Custom CSS extension's generated
   `[data-interaction-id="…"]` blocks. Any inline CSS/JS we print and care about
   needs `data-no-optimize="1"` on the tag.
2. **Combine RELOCATES the combined block.** *(An earlier revision said "order
   is preserved… combine alone should not reorder atomic styles" — refuted by
   review.)* Order is preserved only WITHIN the combined block; the block
   itself is deleted from its positions and re-emitted — CSS **prepended to
   head-top** ("Move all css to top", `optimize.cls.php:348-352`), JS appended
   to the footer (`:394-397`). Everything NOT combined (excluded files,
   external-host sheets, `data-no-optimize` tags) keeps its position, so
   combine changes the relative order between combined and uncombined assets
   even when nothing is dropped. That alone can flip the (0,2,0) specificity
   ties against Elementor atomic base styles — see the cascade DANGER below.
3. **`litespeed_optimize_ucss_file_exc_inline` does not "exclude" — it INLINES.**
   `:1103-1107` replaces the matching `<link>` with a `<style>` holding the file
   verbatim, which is *how* it escapes UCSS. The name reads like an exclude
   list; the behaviour is a substitution. Do not expect the `<link>` to survive.
4. **UCSS runs on QUIC.cloud** (`ucss.cls.php:372`, `Cloud::post(SVC_UCSS…)`).
   With no QUIC.cloud connection it never executes at all. **A local "UCSS test"
   on a disconnected dev site proves nothing** — it will pass because the
   feature never ran. Either connect QUIC.cloud for that one axis or test it on
   a staging site, and never report axis 3 as green from a local run alone.

**DANGER — excluding our CSS from combine can itself cause the bug.** Combining
removes the member `<link>`s and emits one file in their place, so an excluded
stylesheet keeps its own position while everything else collapses to a single
point. That MOVES our CSS relative to Elementor's atomic styles. This codebase
already documents the failure that follows: atomic base styles compile as
`.elementor .e-<widget>-base` — specificity (0,2,0) — and our overrides tie at
(0,2,0), so **the winner is decided purely by document order** (see the
"reveal class doesn't override a base style" row in
[Common breakage points](#common-breakage-points)). Flip the order and rules
silently swap winners, with no error and no missing file.

So prefer the **order-neutral** lever: `litespeed_optm_css_comb_ext_inl` =>
`false` leaves inline blocks exactly where they are and moves nothing. Reach for
`litespeed_optimize_css_excludes` only for a file that genuinely must not be
merged, and when you do, verify with the styleSheet-walk diagnostic from that
same Common-breakage row rather than by eye — a cascade flip looks like a
styling opinion, not a bug.

### HTML Minify — the one axis with no per-tag escape hatch

Read from `lib/html-min.cls.php` (LiteSpeed's vendored Minify_HTML) and
`optimizer.cls.php::html_min()`. Runs on the final output buffer, so it sees
everything we rendered.

**Safe, confirmed:** `<script>`, `<style>`, `<pre>` and `<textarea>` bodies are
swapped for placeholder hashes *before* any whitespace pass and restored after
(`:107-139`), so inline JS/CSS is untouched. Tag attributes are never rewritten,
so every `data-aae-*` / `data-interaction-id` survives.

**The risk is text whitespace, and it lands on Text Animation.** After the
placeholders go in, it trims every line (`:143`) and strips whitespace around
~50 **block** elements (`:146-154`). Inline elements — `span`, `a`, `strong`,
`em` — are deliberately NOT in that list, which is what protects most inline
markup. But SplitText splits a heading's text into chars/words/lines, so any
change to the whitespace inside it changes the split: words can join, or an
empty char can appear. **Measure it, do not reason about the regex** — compare
the rendered char/word counts with HTML Minify on and off. Text Animation is
our most-used effect, so this is the axis-4 test that matters.

**HTML comments are removed** (`:121`) except IE conditionals and any listed in
the "HTML Skip Comments" setting. Nothing of ours depends on a comment marker
today; keep it that way.

**DANGER — there is no `data-no-optimize` for HTML minify.** The only lever is
`apply_filters('litespeed_html_min', true)` (`optimizer.cls.php:35`), a global
boolean for the whole page. If minify does break SplitText, we cannot exempt
one heading — the fix has to be on our side (normalise the text before
splitting), because switching the filter off would silently disable a feature
the site owner turned on. Do not reach for that filter as the fix.

Note LiteSpeed always appends `<!-- Page optimized by LiteSpeed Cache @… -->`,
so the HTML differs on every run even when nothing else changed — never diff
raw HTML to decide whether minify altered something.

### Scope — ATOMIC ONLY (decided)

Everything here targets the V4 atomic line: the atomic effect bundles, the
atomic widgets' markup, `aae-atomic-common` and the GSAP family they pull in.
The ~70 v3 widgets are **out of scope** — they are in maintenance, they have
their own asset pipeline, and widening this would double the test matrix for
code we are not extending. One v3 note is kept because it will surface during
testing and must not be mistaken for a new bug:
`src/js/aae-scroll-to-ele.js:1` is a bare `DOMContentLoaded` listener and dies
under any delay-JS plugin. Known, out of scope, do not "fix" it as part of this.

### Two levers found in the source that beat everything above

**1. `do_action( 'litespeed_conf_force', $key, $value )`** (`conf.cls.php:63`
→ `force_option()` at `:401`) — overrides **any** LiteSpeed setting for the
current request, for any key `has_conf()` recognises. One action instead of a
per-feature filter hunt: force `optm-css_comb_ext_inl`, `media-lazy`,
`optm-html_min` off on the pages that need it. This is the "intelligent"
mechanism — we read what is on, decide, and force only what actually conflicts.

Two conditions on it: fire it **before** the feature reads its conf (they read
during `init`/buffer setup, so hook early), and know its blind spot — under
Guest Optimization the `cfg_*` flags are set from
`defined('LITESPEED_GUEST_OPTM') || $this->conf(…)`, which short-circuits
**before** `conf()` is consulted. `conf_force` cannot reach those.

**The levers that DO close the guest blind spot are two per-request
constants** *(an earlier revision said no such lever exists — refuted by
review)*, both checked before any guest branch:

- `LITESPEED_NO_PAGEOPTM` (`optimize.cls.php:235-238`) — skips the ENTIRE
  optimization pass, Guest Optimization included.
- `LITESPEED_NO_LAZY` (`media.cls.php:778-781`) — kills image+iframe lazyload
  unconditionally, and unlike the `litespeed_no_image_lazy` meta below it also
  works on archive/taxonomy URLs (the metabox resolves a post ID only on
  singular / page_for_posts, `metabox.cls.php:133-150`).

Blunt — whole request — but for a per-page "force off" they are the correct,
guest-proof enforcement. Define them early (`plugins_loaded`) from our own
per-page decision.

**2. `litespeed_no_image_lazy` — LiteSpeed's OWN per-page post meta**
(`metabox.cls.php:40`). Three native per-page switches exist: `litespeed_no_cache`,
`litespeed_no_image_lazy`, `litespeed_no_vpi`.

The lazy one is the important one, and it is better than anything we could
build, because of exactly where the check sits (`media.cls.php:832`):

```php
$cfg_lazy = ( defined('LITESPEED_GUEST_OPTM') || $this->conf(O_MEDIA_LAZY) )
            && ! $this->cls('Metabox')->setting('litespeed_no_image_lazy');
```

The metabox test is **outside** the guest short-circuit, so **it is honoured in
every mode, Guest Optimization included** — unlike `litespeed_optm_uri_exc`,
which is not. So where a page genuinely must not lazy-load, write LiteSpeed's
own meta rather than fencing with filters: it is their supported per-page API,
it survives their config changes, and it cannot be overridden by Guest mode.

**Prefer their native lever over ours wherever one exists.** Our `_aae_cache_compat`
meta stays the source of truth for the user's intent; on save it *projects* that
intent onto `litespeed_no_image_lazy` (and forces conf where no native switch
exists). One UI, native enforcement.

### Per-page control from our own settings — yes, and which hook to use

Every filter below is evaluated DURING the request, so the callback can read
`is_page()`, the queried object, or our own per-page setting and answer
differently per URL. Page cache is keyed per URL too, so a per-PAGE decision
caches correctly; only per-VISITOR variation is unsafe (see the page-cache note
above).

| Lever | Scope | Survives Guest Optimization? |
|---|---|---|
| `litespeed_optm_css_comb_ext_inl` / `…_js_comb_ext_inl` | boolean — stop **inline** CSS/JS being combined, keep file combining | **yes** (`:1050`, `:872`) |
| `litespeed_optimize_css_excludes` / `…_js_excludes` | our own files | **yes** — unconditional in `_parse_css/_parse_js` (`:1043`, `:869`) |
| `litespeed_html_min` | boolean, whole page | **yes** (`optimizer.cls.php:35`) |
| `litespeed_media_lazy_img_*` | images | yes |
| `litespeed_optm_js_defer_exc` | defer list | **NO** — replaced by `litespeed_optm_gm_js_exc` |
| `litespeed_optm_uri_exc` | nuclear: skips the WHOLE optimization pass for that URI | **NO** |

**DANGER — Guest Optimization ignores the settings AND the URI excludes.** When
`LITESPEED_GUEST_OPTM` is defined:

- the URI-exclude check at `optimize.cls.php:245` is inside `if (! defined(…))`
  and is skipped entirely. A per-page opt-out built on `litespeed_optm_uri_exc`
  therefore does nothing for guests — i.e. for nearly every real visitor —
  while an admin testing it logged in watches it work perfectly. This is the
  worst possible failure shape; do not build on that filter.
- `:287-291` forces every `cfg_*` TRUE regardless of the saved setting —
  minify, combine, `css_async`, all of it. "I turned CSS Combine off" is not
  true under Guest Optimization.
- defer exclusions come from `litespeed_optm_gm_js_exc`, not
  `litespeed_optm_js_defer_exc` (`:114-128`). Always register both.

**The shape to build:** we already know which pages carry AAE animations — the
Performance module computes exactly that for on-demand asset loading. Feed that
same signal into `litespeed_optm_{css,js}_comb_ext_inl` and return `false` on
those pages only. Inline atomic styles and inline scripts stay untouched where
it matters, every other page keeps full optimization, it works in all modes, and
the user configures nothing.

### Feature review — where each LiteSpeed feature lands for us

Read out of the setting registry (`base.cls.php`, ~200 keys / 15 groups), not
the settings screens, which hide anything the current server or licence does not
support.

**Helps us — worth recommending ON:**

| Setting | Why |
|---|---|
| `media-add_missing_sizes` | adds `width`/`height` to images that lack them — that IS step 2 of our plan, done for us |
| `media-placeholder_resp` | reserves a correctly-shaped placeholder box, **but only when width/height exist** — pairs with the above, useless alone |
| `media-vpi` | QUIC.cloud marks above-fold images and excludes them from lazyload |

**Breaks animations — and it is all ONE bug: the layout settles after
ScrollTrigger measured.**

| Setting | Effect |
|---|---|
| `media-lazy`, `media-iframe_lazy` | measured: 23/23 animations fire on an empty box |
| `optm-css_async` (+ CCSS) | page paints on critical CSS, the real stylesheet lands later, everything below reflows |
| `optm-ggfonts_async`, `optm-ggfonts_rm`, `optm-css_font_display` | font swap changes text metrics — SplitText's line splitting and every trigger below the heading move |
| **`optm-html_lazy`** | injects `content-visibility:auto; contain-intrinsic-size:1px 1000px` for any selector listed (`css.cls.php:62`). Point it at an animated container and the browser skips rendering it — ScrollTrigger then measures 1000px of nothing. Nobody will connect this setting to broken animations. |

**The distinction that decides how bad each one is:** fonts and async CSS
normally settle around the `load` event, and ScrollTrigger auto-refreshes on
`load`, so those mostly self-correct. **Lazy images settle AFTER `load`** —
nothing refreshes for them. That is why laziness alone is unrecoverable without
our own fix, and why the others are second-order.

**Breaks our JS:** `optm-js_defer`, JS delay (`optm-js_delay_inc`),
`optm-js_comb` + `optm-js_comb_ext_inl`. Plus `optm-qs_rm` (Remove Query
Strings) — it strips `?ver=`, so visitors keep stale JS after a plugin update,
and it removes the `filemtime` stamp CLAUDE.md relies on to date a bad build.

**Breaks Elementor atomic CSS:** `optm-css_comb` + `optm-css_comb_ext_inl`,
`optm-ucss` (+ `ucss_inline`).

**Editor and diagnostic traps — not animation bugs, but they generate the
tickets:**

| Setting | Trap |
|---|---|
| `misc-heartbeat_editor` | Elementor's autosave and post lock ride the heartbeat; throttling it degrades the editor |
| `guest`, `guest_optm`, `optm-guest_only`, `optm-exc_roles` | a logged-in admin is served a DIFFERENT page from a visitor. The single biggest source of "works for me" |
| `crawler` | pre-warms the cache from a BOT request, so any PHP-side per-request decision is frozen from the bot's view, not a browser's |
| `debug-disable_all` | the support kill switch — name it first in any troubleshooting doc we write |

**Irrelevant to us:** everything under `cache-*`, `purge-*`, `esi-*`, `cdn-*`,
`object-*`, `db_optm-*`, `discuss-*`, `img_optm-*`. One exception worth
remembering: `img_optm-webp_attr` lists which attributes get their URL swapped
for the WebP/AVIF one — if we ever put an image URL in a custom `data-` attribute,
it must be added there or that copy stays the original format.

### Already checked — do not re-investigate

- **`<script type="text/aae-lazy">` is structurally invisible to LiteSpeed's JS
  optimizer** — `optimize.cls.php:895` skips any script whose `type` is not
  `text/javascript`, before it looks at anything else. Not luck: this is the
  same trick LiteSpeed's own JS-delay placeholders use. Keep the custom `type`,
  and never give the placeholder a real `src`.
- **The atomic core survives late injection.** `common.js` checks
  `document.body` before falling back to a `DOMContentLoaded` listener, so a
  delay-JS plugin injecting it post-DCL still boots it.
- **v3's `aae-scroll-to-ele.js` does NOT** — line 1 is a bare
  `document.addEventListener("DOMContentLoaded", …)`, the same silently-fatal
  shape `wcf-addons-ex.js` was fixed for. Under delay-JS it never runs.

### Page cache is a separate thing, and it freezes our PHP decisions

LSCache's **page cache** — the only part that reduces server time — runs only on
LiteSpeed Web Server / OpenLiteSpeed, or through QUIC.cloud. On Apache or nginx
it is inert: the settings render, nothing caches. The dev site is
`nginx/1.26.1`, so **page cache never runs here**. That does not weaken the test
plan: everything in the three axes above is an *optimization* feature
(lazyload, CSS/JS combine/defer/delay, UCSS), all of which run on any server.

It does add one constraint for real sites, and it is a support-ticket shape:

- **Everything decided in PHP gets baked into the cached HTML.** The step-3
  filters and the step-5 markers run once on a cache MISS; that HTML is then
  served to everyone. A settings change therefore does nothing until the cache
  is purged — which arrives as "the setting doesn't work". Purge on save, or
  say so in the panel description.
- **Anything that varies per VISITOR must not be decided in PHP.** Reduced
  motion is already safe — the inline loader decides it client-side. **Unverified:**
  whether `gsap_deferrable()`'s per-request walk of the enqueued dependency
  graph is deterministic per URL. If it can differ between two requests for the
  same URL, page cache will serve one visitor's answer to everyone. Check this
  before shipping, and prefer a client-side decision wherever a per-visitor
  signal is involved.

### Verification

Install LiteSpeed, then sweep its three feature groups independently — one at a
time, because a page broken by CSS combine and a page broken by JS delay look
identical:

| state | assert |
|---|---|
| lazyload ON | animations fire on LOADED images (the 23/23 metric goes to 0/N), CLS back near the native-lazy figure |
| JS defer ON | GSAP + ScrollTrigger present, trigger count unchanged |
| JS delay ON | animations run **without any user interaction** |
| CSS combine + UCSS ON | atomic base styles intact; Custom CSS `@keyframes` presets still animate. UCSS needs QUIC.cloud — see above, a local pass is meaningless |
| HTML Minify ON | **SplitText char/word counts unchanged** vs minify off, on a heading with multi-line source markup |
| all OFF (control) | byte-identical to today |

Reuse the probe shape from the measurement above — instrument
`ScrollTrigger.create`'s `onEnter` and count how many fired while a descendant
`<img>` was still `!complete`. A "does it animate" assertion cannot see this
bug: the animation *does* run, just on nothing.

---

## Auto-preset — giving a widget a default preset on drop

Some widgets are unusable bare. A Loop Grid Slider drops as a plain Post Image
+ Post Title card. An Image Compare drops as **six children stacked
vertically**, because its `define_base_styles()` styles only the root and every
bit of the actual layout — the before image's `position:absolute` +
`clip-path`, divider/thumb placement, caption positioning, the z-index stack —
lives in its preset JSONs.

`src/modules/atomic/editor-bridge/auto-preset.js` (booted from
`editor-bridge.js`) applies a chosen preset the first time such a widget is
created. It is **not a second apply implementation**: it calls the same
`applyPresetModel()` the "Apply Preset" dropdown runs, so the outcome is
identical to picking that preset by hand.

### Adding a rule for another widget

One entry in `AUTO_PRESETS`, keyed by element type, then `npm run build`.

```js
// Self-target: the preset root IS this widget type, so the dropped widget is
// replaced by the styled one.
'e-aae-a-image-compare': {
  presetId: 'image-compare-horizontal',
  defaultMarker: 'aae-ic-default',
},

// Descendant target: the preset lands on a child, the dropped widget stays.
'e-aae-a-loop-grid-slider': {
  targetType: 'e-aae-a-loop-slide-item',
  presetId: 'bold-overlay-zoom',
  defaultChildren: ['e-aae-a-post-image', 'e-aae-a-post-title'],
  settings: { aae_ns_slides_per_view: 1, aae_ns_peek: 0, aae_ns_gap: 16 },
},
```

| Field | Required | Meaning |
|---|---|---|
| `presetId` | yes | The preset's sanitized json basename. **Falls back to `presets[0]` when it doesn't match** — a typo degrades silently to "some preset", it never errors. |
| `targetType` | no | The DESCENDANT type that receives the preset. Omit → the dropped widget receives it on **itself** and is replaced in place. |
| `defaultChildren` | no | Freshness test by shape: untouched only while the target holds exactly these widget types, nothing else. |
| `defaultMarker` | no | Freshness test by class: untouched only while **every** child carries this class. |
| `settings` | no | Props seeded *before* the apply, in the `aae-rj` envelope. Ignored for a self-target rule — see gotchas. |

### Which freshness test — this is the part that bites

**Does your preset seed a DIFFERENT set of child widget types than
`define_default_children()`?**

- **Yes** → `defaultChildren`. Shape alone tells "fresh" from "already styled".
- **No** → `defaultMarker`, **and** add that marker class to every child in the
  widget's `define_default_children()`. Image Compare's preset seeds the exact
  same six types (image/image/divider/div-block/paragraph/paragraph) as its
  defaults, so only `aae-ic-default` separates them.

Get this wrong and **the watcher re-applies to its own output forever**: the
`handled` Set is keyed by element id, every apply mints a NEW id, so `handled`
can never catch the replacement. The marker works precisely because preset
children don't carry it.

### Why it polls instead of hooking the drop

Atomic-widget creation in this Elementor version emits **no catchable command**
— verified: `run:after` never fires for an atomic drop, and the parent's model
collection emits no `add`. So it's baseline + heartbeat:

1. `establishBaseline()` waits until the document model is non-empty, then marks
   every already-present eligible widget `handled`. **Baselining against an
   empty tree would make existing widgets look new and clobber the user's own
   work** — that's why it waits rather than running at bootstrap.
2. `setInterval(tick, 1000)` scans the model tree; eligible + not in `handled`
   = a fresh drop.

Four guards stack, because one bad apply destroys user work: the `handled` Set
(once per element), the baseline (never touch what was already there),
`isUntouched()` (belt-and-braces if the baseline lost a model-ready race), and a
re-check after the async preset fetch resolves.

Because the interval is registered through `track()`, a document switch tears it
down and `startAutoPreset()` re-baselines against the new document.

### Gotchas

- **`settings` is skipped for a self-target rule.** `applyPresetModel` replaces
  that very element, so anything written there dies with it. Put root settings
  in the preset JSON instead — Image Compare's presets carry their own
  `direction` / `default_position` / `enable_click_move`, which is what makes
  the vertical preset actually render vertically.
- **`settings` values need the `aae-rj` envelope** —
  `{ $$type: 'aae-rj', value: { desktop: N } }`. A bare `{ desktop: N }` is
  rejected as `invalid_value` and corrupts the settings so publish throws. See
  [Writing atomic settings from the editor](#writing-atomic-settings-from-the-editor-the-aae-rj-prop-shape).
- **A marker class that nothing reads is dead weight.** Image Compare shipped
  `aae-ic-default` on every child, with a docblock pointing at this watcher,
  for its whole life before the rule existed — the class was rendered on every
  page and read by nobody. It also cited an `AAE_A_Progressbar` `aae-pb-default`
  precedent that has never existed. Grep the JS before trusting a comment that
  says a marker is wired.
- Presets are fetched on demand over REST (`ensurePresetsLoaded`); the fetch
  starts in parallel with the descendant poll, so it's normally settled by the
  time the target appears.
- Regression tests (`E:\Local Testing\`):
  `verify-image-compare-autopreset.mjs` (self-target, re-apply-loop guard,
  baseline protection of an existing widget) and
  `verify-loopgrid-autopreset.mjs` (descendant target + `aae-rj` settings
  seeding). Run both after touching this file — the two paths fail
  independently.

---

## Conventions

### Data-attribute naming

Standardise `data-aae-<feature>-<setting>`. Per-breakpoint variants get
`-<breakpoint>` appended:

```
data-aae-text-delay              ← desktop
data-aae-text-delay-mobile       ← mobile variant
data-aae-text-delay-mobile-extra ← (snake_case kept literal)
```

### Naming

| Layer | Style |
|---|---|
| PHP class names | `PascalCase` |
| PHP constants  | `UPPER_SNAKE_CASE` |
| Atomic prop names | `aae_<feature>_<setting>` (snake_case) |
| JS exports     | `camelCase` |
| Data-attrs     | `data-aae-<feature>-<setting>` (kebab-case) |
| CSS classes for runtime | `aae-<feature>-bound`, `aae-<feature>-played` |

### Float vs integer Number controls

The Number_Control's underlying `<input type="number">` ships with
`step="1"`, which rounds decimals. For decimal-accepting fields:

1. Add the label string to `FLOAT_LABEL_BASES` in
   `editor-bridge/float-step-fix.js`.
2. The MutationObserver rewrites `step="any"` automatically.

### Responsive props

Use `Schema::register_responsive_string()` / `register_responsive_number()`
helpers. They register `<base>` + `<base>_<breakpoint>` for every active
breakpoint and gate variants behind the same dependencies as the base.

`editor-bridge/responsive-config.js` lists which bases are responsive
(`AAE_RESPONSIVE_BASES`) and maps each base label → schema key
(`LABEL_TO_BASE`). Add new responsive settings to both.

### Writing atomic settings from the editor (the `aae-rj` prop shape)

When you set an AAE atomic prop **programmatically** in the editor — e.g. via
`$e.run('document/elements/settings', { container, settings, options })` from an
editor-bridge module — the value must be wrapped in the prop-type's `$$type`
envelope, **not** passed raw. Passing a raw value throws
`Settings validation failed. <prop>: invalid_value` and, worse, leaves the
element's settings corrupted so the whole document fails to **publish**.

The slider's `Responsive_JSON_Prop_Type` (used for every `aae_ns_*` slider
setting — slidesPerView, peek, gap, …) serialises as `$$type: 'aae-rj'` with a
per-breakpoint `value`:

```js
// WRONG — rejected as invalid_value, breaks publish:
settings[ 'aae_ns_slides_per_view' ] = { desktop: 3 };

// RIGHT — the shape a live slider actually stores:
settings[ 'aae_ns_slides_per_view' ] = {
  $$type: 'aae-rj',
  value: { desktop: 3 },
};
```

**How to find the right `$$type` for any prop:** select a real element that has
the prop set and read it back — `container.settings.toJSON()[ propKey ]` prints
the exact envelope (`{ $$type: '…', value: … }`) Elementor expects. Mirror that
shape when writing. (Other prop types have their own `$$type`, e.g. `image-src`,
`dimensions`, `size`, `classes` — never assume `aae-rj`; read it back.)

Real usage: `editor-bridge/auto-preset.js` → `applySliderSettings()` seeds
slider settings on drop using this exact envelope.

### Cleanup

Anything that adds a listener / observer / timer must call `track(fn)` from
`editor-bridge/disposables.js`. `disposeAll()` runs on document switch and
on `beforeunload`.

---

## Build & development

```bash
npm install
npm run start    # webpack --watch
npm run build    # production build
```

Output goes to `assets/build/`. Each entry produces:
- `<name>.js`
- `<name>.asset.php` (deps + version, generated by @wordpress/scripts)
- `<name>.js.map` (in dev)

### `assets/atomic/js/` belongs to WEBPACK — never raw-copy sources into it

Atomic widget frontend JS (`inc/AtomicWidgets/Widgets/*/assets/js/*.js`) is
built ONLY by webpack (`npm run build` / `npm run start`): the
`getAtomicWidgetEntries()` loop in `webpack.config.js` emits each file to
`assets/atomic/js/<basename>.js`, resolving `@elementor/frontend-handlers`
imports via `externals` → `window.elementorV2.frontendHandlers`, inlining
SCSS imports and the Form widget's `lib/` modules, and skipping `-prev`/
`_prev` widgets. `npm run build` then has gulp `minify:atomic-js` minify
that webpack output to `.min.js` (the file served when
`is_dev_environment()` is false).

**Incident 2026-07-20:** a legacy gulp task (`compile:atomic-js`, since
deleted from `gulpfile.js`) flatten-copied the RAW sources over webpack's
bundles in the same directory. Every widget script on the site broke with
two console-error flavors, both from unbundled source served as classic
scripts: `Cannot use import statement outside a module` (files with ES
imports) and `Identifier 'register' has already been declared` (files
using top-level `const { register } = window.elementorV2?… ` — classic
scripts share one global lexical scope, so only the FIRST such script
survives; every later one throws and its widget handler is dead). The raw
copy also collided `Posts/posts.js` with `Posts_prev/posts.js` (gulp
didn't exclude `-prev`) and dumped Form's `lib/` internals as stray
standalone files. **Fix/recovery: `npm run build` regenerates everything;
never reintroduce a gulp task with `gulp.dest('assets/atomic/js')` for
JS.** Diagnose this class of failure fast: `head` the served file in
`assets/atomic/js/` — a healthy bundle starts with webpack boilerplate
(`!function(){"use strict"…`), never a bare `import` or
`const { register }`; the `?ver=` timestamp in the console is the file's
`filemtime`, which tells you exactly when the clobbering write happened.

### Testing on a real page

1. `npm run build`
2. Open Elementor editor on a test page
3. Add a heading, set Text Animation = Character
4. Save, view the published page
5. Inspect — should see `data-aae-text-anim="char"` on the heading
6. View source — `aae-effect-animation` script should be enqueued only on
   pages with at least one AAE effect

### Common breakage points

| Symptom | Likely cause |
|---|---|
| Widget/extension missing from the dashboard entirely | A registration list is out of sync — see [Registering a new widget or extension](#registering-a-new-widget-or-extension--what-must-stay-in-sync). Most likely no `$widgets_registry` entry (widget unreachable), or the slug is in the JS `INTERNAL_WIDGET_SLUGS` / `DEMO_ONLY_SLUGS` / `-main` hide list. |
| Dashboard toggle does nothing | Either the slug is in `ALWAYS_ACTIVE_WIDGETS` (force-active, switch inert), or nothing in the codebase actually calls `is_extension_active()` for it — audit both directions with the grep in that section. |
| Card shows "off" but the widget still works | `get_dashboard_config()` reports `is_active` from the raw saved option, not `is_widget_active()`. A force-active slug that was never saved reads as off. |
| New widget arrives disabled on an existing site | Expected: `'default' => true` is documentation-only for widgets — there is no seeder. Only extensions get auto-enabled, via `migrate_newly_offered_extensions()`. |
| Dashboard category heading shows a raw slug, or sorts last | Missing from `CATEGORY_LABELS` / `CATEGORY_ORDER` in `atomicWidgetService.js` or `atomicExtensionService.js` — and remember to `npm run build`. |
| Extension/widget icon is an empty circle | Icon class not in the font. Names are case-sensitive: `wcf-icon-Dynamic-Tags` resolves, `wcf-icon-parallax` matches nothing. |
| The "Presets" control vanishes from a widget's panel, and only a full editor reload brings it back | A preset read FAILED and was mistaken for "this type has no presets". `aae/v1/presets` always answers **200**, so an outage and a genuinely-empty type look identical from the list alone — `remote_failed` in the response is the only thing that separates them (`Rest.php` ← `Cache::get_presets_for_type()` ← `Remote_Client::fetch_presets_for_type()` returning **null** on a failed request, `[]` on an empty answer). Client-side, `loadPresetsForType()` in `preset-apply.js` must never memoise a failed read into `_fetchedPresetsByType` — that cache is checked *before* any fetch, so one blip both hid the control (an empty list is how it decides it has nothing to show) and guaranteed nothing ever retried. Note `is_dev_environment()` (any `.local` host) bypasses the transient entirely, so a dev site hits the network on *every* panel open and sees this far more often than production. |
| A widget drops into the canvas looking broken/unstyled, but picking a preset by hand fixes it | It has no layout of its own — all of it lives in the presets — and no [auto-preset](#auto-preset--giving-a-widget-a-default-preset-on-drop) rule. Add an `AUTO_PRESETS` entry. Don't be misled by a `*-default` marker class on the children or a docblock claiming the watcher is wired: grep the JS, the marker may be read by nobody (Image Compare shipped exactly that way). |
| Panel shows "Some classes are missing" on a widget's own parts | A functional hook class is sitting in the `classes` prop. Render it from the element's twig instead — and never tell the user to dismiss the alert, its ✕ unapplies the class. See [Never put a functional hook class in the `classes` prop](#never-put-a-functional-hook-class-in-the-classes-prop). |
| A freshly-dropped widget keeps getting re-presetted, spawning copies forever | The auto-preset rule's freshness test can't tell "untouched" from "just applied" — the preset seeds the same child widget types as `define_default_children()`, so `defaultChildren` (shape) always reads as fresh. Use `defaultMarker` instead and put that class on every default child. `handled` can't save you: each apply mints a new element id. |
| Effect controls not appearing | `Controls::inject_controls()` not adding the section for that widget type |
| Settings don't save | Prop not in Schema, or wrong $$type wrapper |
| `Settings validation failed … invalid_value` on publish (after a programmatic settings write) | Wrote a prop value RAW instead of in its `$$type` envelope. Responsive_JSON props need `{ $$type: 'aae-rj', value: { desktop: N } }`, not `{ desktop: N }`. See [Writing atomic settings from the editor](#writing-atomic-settings-from-the-editor-the-aae-rj-prop-shape). |
| A whole `define_base_styles()` silently does nothing | One invalid key/value fails the entire definition. `width`/`height` are `Size_Prop_Type` — a `String_Prop_Type('fit-content')` is invalid. For CSS keywords/functions use the `custom` unit: `Size_Prop_Type::generate([ 'size' => 'fit-content', 'unit' => 'custom' ])` (transformer emits the `size` verbatim). `auto` → `[ 'size' => 'auto', 'unit' => 'auto' ]`. Another killer: `background` must be `Background_Prop_Type::generate([ 'color' => Color_Prop_Type::generate(…) ])` — a bare `Color_Prop_Type` voids the definition (shipped as a colorless Form submit button). **Full verified key/format tables + shape cookbook: `animation-addons-for-elementor-pro/docs/atomic-v4/atomic-style-schema-reference.md` — check it BEFORE writing any base style.** |
| Base-style change shows in the editor but the frontend is unstyled / wrong | Atomic base styles are generated per-request, but the frontend renders them from a **cached** state; deleting `wp-content/uploads/elementor/` alone leaves it empty until a rebuild. After editing any `define_base_styles()`, **clear Elementor's cache** (wp-admin → Elementor → Tools → "Clear Files & Data", i.e. `#elementor-clear-cache-button`) then reload the frontend. The editor always renders live from PHP, so an editor-only check hides this. (Seen as a nav that's a perfect 44px circle in the editor but a full-width `position:static` block on the frontend.) |
| Slider nav arrow one size in editor, another on frontend (and/or a huge ellipse at slidesPerView=1) | The nav badge USED to be icon-driven (`width/height: fit-content`), so it sized to whatever the SVG happened to be — 20px on a fresh drop (the `aae-a-svg` utility) but 65px on an element saved before that class existed → different footprint per element age, and `auto`/content-driven width could stretch into an ellipse in a single full-width slide. Fix: pin the nav to a **fixed** `width/height: 44px` in `class-aae-a-slider-nav-{prev,next}.php` (constant everywhere, can't stretch) and cap the SVG to fit inside via each slider stylesheet — `.aae-a-navigator-{prev,next} .e-svg-base, svg { max-width/height: 24px }` in BOTH `nestedslider.scss` and `loop-grid-slider.scss` (the two sliders load different CSS; the Loop Grid Slider deliberately does not load the Nested Slider's stylesheet, so the rule is duplicated). |
| A "reveal" class you toggle at runtime (`aae-form-step-active`, etc.) doesn't override a base style's `display:none`, even though the class IS present in the DOM (`classList` has it, but `getComputedStyle().display` still says `none`) | **CSS specificity tie, not a JS/runtime bug.** Atomic base styles compile as `.elementor .e-<widget>-base { display: none; … }` — specificity (0,2,0). A plain `.your-class.active-class { display: flex }` override is ALSO (0,2,0) — a tie, and the base-styles sheet happens to cascade after your own, so the tie silently goes to `display:none`. Fix: add `.elementor` to your override selector so it matches the base rule's specificity (`.elementor .your-class.active-class { display: flex }`), not `!important`. Diagnose by walking `document.styleSheets` for every rule matching the element and setting `display` (see the `e-aae-a-form-step` Multi-Step Forms fix, 2026-07-19, for the exact Playwright snippet) — don't assume the DOM/model layer is broken just because content "isn't showing"; check computed style + winning rule first. |
| Data-attrs not on rendered HTML | `Render::inject_into_html()` not handling the widget type, or `$attrs` empty |
| Animation works in preview but not on frontend | `Render.php` not calling `wp_enqueue_script()` |
| Animation works on frontend but not in editor preview | `editor-bridge/features.js` missing widget type in `widgetTypes` |
| Per-breakpoint values don't preview | `applySettingsToDom()` writing only desktop attrs — check `AAE_RESPONSIVE_BASES` includes the base |
| Number input rounds decimals | Add label to `FLOAT_LABEL_BASES` in `float-step-fix.js` |
| Two tabs visually selected | Don't fight React on `aria-selected` / `Mui-selected` — use `!important` on the indicator, not class strips |
| Console floods with `Cannot use import statement outside a module` + `Identifier 'register' has already been declared` across many widget scripts at once | RAW widget sources got copied over webpack's bundles in `assets/atomic/js/` — see [`assets/atomic/js/` belongs to WEBPACK](#assetsatomicjs-belongs-to-webpack--never-raw-copy-sources-into-it). Recover with `npm run build`. |

---

## V3 reference for porting

When porting a V3 extension to atomic, the source files are at:

```
animation-addons-for-elementor-pro/
├── inc/extensions/wcf-*.php           (PHP control declarations)
└── src/js/                            (frontend runtime)
```

The V3 register pattern uses `elementor/element/<type>/section_*/after_section_end`
with `add_responsive_control`; this maps directly to our `Schema::register_responsive_*`
helpers. Frontend handlers use `elementorFrontend.elementsHandler.addHandler()`
which we replace with our `KINDS` scan/bind dispatcher.

---

## AAE Atomic Form Builder (planned — new widget family)

Spec sources, in order of authority (newer/more implementation-specific wins
on conflict):
1. `C:\Users\UseR\Documents\atomic-form\AAE_Atomic_Form_Builder_Claude_PRD_SSR_Pack\` —
   **primary reference.** `AAE_Atomic_Form_Builder_Complete_PRD_for_Claude.md`
   (product, v2.0) + `AAE_Atomic_Form_Builder_SSR_SRS_for_Claude.md`
   (technical/SRS, v1.0) — both written specifically for coding agents, dated
   July 12 2026, and far more concrete (literal REST routes, DB columns,
   prop names, response-code table) than the doc below.
2. `C:\Users\UseR\Documents\atomic-form\` root — PRD v1.6 + Test Cases v1.1.
   Broader product rationale (pain points, accessibility/i18n detail, exact
   UX copy) not repeated in the SSR pack; still valid where it doesn't
   conflict with #1.

This section is a working distillation for coding purposes — re-read the
source docs for anything not captured here or for product (not architecture)
questions.

This is a **new atomic widget family**, not an animation effect. Do not use
the [Adding a new effect](#adding-a-new-effect) recipe (Schema/Controls/Render
layered onto an *existing* widget) — instead follow the **Widgets/** pattern
used by `Offcanvas`, `NestedSlider`, `FlipBox` (container widget + real child
element widgets registered independently in `class-atomic.php`'s
`widgets_registry`, PHP twig render via `Has_Element_Template`, own JS bundle).
See [File map](#file-map) and `inc/AtomicWidgets/Widgets/` for the shape to
copy.

### Why not use Elementor's native `e-form` instead

Elementor is actively building its own atomic form (`e-form` container +
`e-form-input`/`-label`/`-textarea`/`-checkbox`/`-radio-button`/`-select`/
`-date-picker`/`-time-picker`/`-file-upload`/`-submit-button`, each an
independently-styleable atomic widget — confirmed live in installed
Elementor Pro 4.1.2 behind the hidden, active-by-default `e_pro_atomic_form`
experiment; free core only shows a "Pro feature" paywall placeholder for it).
Investigated 2026-07-12 — see git history in `elementor-src-repo` under
`modules/atomic-widgets/elements/atomic-form/` for how actively it's still
changing (commits within the prior ~30 days touching required-children
behavior, default children, and the free/Pro boundary itself).

**Decided: build AAE's own form independently, not on top of `e-form`.**
Reason: the target client will not run Elementor Pro, so they would only
ever see Pro's paywall placeholder for `e-form` — there is no native form
for AAE to extend or coexist with on this project. This also isn't a loss
relative to what Pro's version offers: confirmed by direct code inspection,
Elementor's native form has no honeypot/CAPTCHA/bot-shield of its own (only
optional Akismet, itself gated behind a further paid tier + a separate
plugin dependency), runs all actions synchronously in one request (no async
queue), has no true save-before-actions guarantee (submission storage is
just another action in the list, skippable), and has no multi-step or
conditional-field logic at all — so every differentiator already planned
below (Bot Shield, async action queue, save-before-actions, schema
versioning, presets, multi-step, conditional logic) remains genuinely
additive, not duplicative. If a future project needs the free/Pro-agnostic
version reconsidered, re-run this investigation rather than trusting this
note blindly — the native feature is pre-GA and changing fast.

### Packaging decision

The SSR doc's stated *preference* is a separate `aae-form-builder` plugin
(namespace `AAEFB\`), but it explicitly allows building inside Animation
Addons with this repo's own conventions instead. **Decided: build inside
`animation-addons-for-elementor`.** Concretely, that means translating the
SSR doc's separate-plugin scaffolding onto this repo's real conventions:

| SSR doc says | Use instead, in this repo |
|---|---|
| Separate plugin `aae-form-builder` | `animation-addons-for-elementor` (this plugin) |
| Namespace `AAEFB\` | `WCF_ADDONS\Forms\` (sibling to `WCF_ADDONS\AtomicWidgets\`, `WCF_ADDONS\Atomic\`) |
| `aae-form-builder.php`, `includes/`, `assets/`, `templates/elementor/elements/` | `inc/AtomicWidgets/Widgets/Form*/` per element (matching `Offcanvas/`, `NestedSlider/`), plus `inc/Forms/` for the non-widget backend (Schema Sync, Submission Core, Queue, Admin) — mirror `inc/Atomic/` vs `inc/AtomicWidgets/` split already used for effects vs. widgets |
| DB tables `aaefb_forms`, `aaefb_form_schemas`, `aaefb_submissions`, `aaefb_submission_values`, `aaefb_action_jobs`, `aaefb_action_logs` | Same columns, prefixed `aae_` to match this plugin's existing option-name convention (`aae_atomic_widgets`, `aae_atomic_extensions`) → `aae_forms`, `aae_form_schemas`, `aae_submissions`, `aae_submission_values`, `aae_action_jobs`, `aae_action_logs` |
| REST namespace `aaefb/v1` | `aae/v1` (or whatever namespace the rest of this plugin already registers under — check before Milestone 5 REST work; grep for `register_rest_route` first) |
| `data-aaefb-ready="true"` ready marker | `data-aae-form-ready="true"`, consistent with this repo's `data-aae-<feature>-<setting>` convention (see [Conventions](#conventions)) |

Everything else below (props, table columns, endpoints, response codes,
milestones) is otherwise a direct port of the SSR doc's content with names
adjusted per this table.

### Core product principles (non-negotiable, PRD §5 + v1.4 security addendum)

1. **Security first, then UI/UX polish.** Public submission must be safe by
   default; the submit pipeline must not depend on Elementor editor classes —
   it must keep working even if the editor layer breaks.
2. **Never lose a lead** — default submission mode is Store + Admin Email.
3. **Save-before-actions, and its inverse, fail-before-save**: a valid
   submission is saved before any email/webhook/Sheets/notification/redirect
   action runs; a request blocked by Bot Shield/validation creates **zero**
   clean submission and **zero** action jobs — it only ever produces a
   spam/security log entry.
4. Backend validation always repeats frontend validation before saving —
   never trust the frontend alone.
5. Actions must be observable: failures need logs and retry where supported.
6. Accessibility, i18n/RTL, privacy, and performance are MVP requirements,
   not later polish.
7. No raw personal data shared with third parties unless the admin enables
   the feature and sees a disclosure.
8. No global frontend bloat — load assets conditionally (reuses
   [On-demand asset loading](#on-demand-asset-loading)).
9. Elementor editor previews must not create real submissions unless the
   admin explicitly uses **Test Submit** / "Test All Actions" mode.
10. Future integrations plug into adapters, not hardcoded one-off code.

### Element family

- `e-aae-a-form` — container element (`Atomic_Element_Base` + `Has_Element_Template`,
  `meta('is_container', true)`, like `AAE_A_Btn_Pro`/`Offcanvas`)
- `e-aae-a-form-field` — child element. Owns its own error-message slot
  render-side (see "Validation message ownership" below) — never a separate
  draggable widget.
- `e-aae-a-form-submit` — child element, normally the last element on the
  final step. **Locked**: deleting it must be blocked or require a restore
  warning — a form must never become silently unsubmittable.
- `e-aae-a-form-step` — **Pro/later** (Multi-Step). Step container holding
  fields, headings, paragraphs, div blocks, and optional review/summary
  content. Don't build this until the Multi-Step milestone.

The form is a container with real child elements, visible in the
Navigator/structure panel — do not build a monolithic repeater-only widget
(i.e. do not model fields as a repeater control on a single widget the way
V3/Elementor Pro forms do). Allowed non-field children (heading/paragraph/
div wrappers) must still be walked in DOM order by the schema builder
regardless of wrapper nesting. **Nested `e-aae-a-form` inside another
`e-aae-a-form` must be rejected or warned at save time.**

### Validation message ownership

Field/form/step error and success messages are **built-in slots owned by the
element itself**, not separate widgets a user can drag away. This is a
deliberate accessibility guard — the panel/Render layer for
`e-aae-a-form-field` must always render its own error container
non-optionally.

### Free vs. Pro split (PRD §24, verbatim boundary)

Follow the split already established by `Btn` (free) / `Btn_Pro` (free
plugin, `inc/AtomicWidgets/Widgets/BtnPro/class-aae-a-btn-pro.php`, type
`e-aae-a-btn-pro`) and by the pro plugin's own extension modules
(`animation-addons-for-elementor-pro/inc/AtomicV4/<Feature>/{Schema,Controls,Renderer,Assets}.php`,
e.g. `FlexboxChildHover`). Gate with `class_exists` guards, not an inline
capability flag inside the free widget. Prefer extending the same
`e-aae-a-form`/`e-aae-a-form-field` family from the pro plugin (matching how
`FlexboxChildHover` extends `e-flexbox`) over forking a second
`e-aae-a-form-pro` container.

**Free:** Basic Form widget; Visual Preset Popup; Contact/Newsletter/Lead/
Support presets; base fields — **Text, Email, Textarea, Phone (basic format
only), URL, Number, Select, Radio, Checkbox, Acceptance, Hidden, Submit**;
AJAX/REST submit; Store Submission; Admin Email (To/CC/BCC/Reply-To); Auto
Reply; basic Submission Dashboard + CSV export; basic Webhook (URL, POST
JSON, all fields, test button); Honeypot + basic reCAPTCHA option;
accessibility/i18n/RTL/privacy/performance foundations.

**Pro:** advanced field types — **Date, Time, Range, Rating** (shipped, see
[Advanced Field Types — Batch 1](#advanced-field-types--batch-1-datetimerangerating-shipped-2026-07-20)),
**File Upload (advanced), Country, Address, Password** (shipped, see
[Advanced Field Types — Batch 2](#advanced-field-types--batch-2-started-country-shipped-2026-07-20)
— Address is a preset + an `autocomplete` prop on Input, not a widget;
Password ships with the PRO Create User action), **Signature, Repeater,
HTML, Calculation, Step** (not yet built); Premium presets; Private Storage +
Google Drive/AWS S3 adapters; Google Sheets (OAuth); Advanced Webhook
(custom headers/auth/mapping/conditions/retry, n8n/Zapier/Make presets);
Action Logs + Retry Queue UI; **Validation Pro** (regex, real phone
validation API, country restriction, disposable email, domain rules);
Telegram/WhatsApp/Slack; **Conditional Display Engine**; **Multi-Step
Forms**; Calculator/Quote forms; Analytics/Lead Management; AI Copilot;
HTML Email Template Builder.

Open/unresolved per the spec (flag to product owner, don't guess): whether
Bot Shield presets are Free or Pro (recommendation only: basic protections
Free, advanced presets/logs Pro).

### Atomic element requirements (SSR §4 — authoritative prop names)

**`e-aae-a-form`** — `Atomic_Element_Base` + `Has_Element_Template`, HTML tag
`form`. Props: `form_key` (hidden), `actions_json` (hidden), `behavior`
(`store_email` | `store` | `email`), `msg_success`, `msg_error`,
`msg_invalid`, `spam_honeypot`, `spam_min_seconds`, `captcha_provider`,
`classes`, `attributes`. Allowed children: `e-aae-a-form-field`,
`e-aae-a-form-submit`, `e-heading`, `e-paragraph`, `e-div-block`. Default
children on drop: Name field, Email field, Message field, Submit button.

**`e-aae-a-form-field`** — one element class, behavior branches on a
`field_type` prop (do not make a separate element per field type). Props:
`field_type`, `field_key`, `label`, `placeholder`, `help`, `required`,
`width`, `default_value`, `autocomplete`, `inputmode`, `options`, `rows`,
`min`, `max`, `step`, `consent_text`, `validation_rules` (later). MVP
`field_type` values: `text`, `email`, `textarea`, `tel`, `url`, `number`,
`select`, `radio`, `checkbox`, `acceptance`, `hidden`.

**`e-aae-a-form-submit`** — props: `label`, `loading_label`, `success_label`
(later), `error_label` (later), `icon`, `alignment`. **Locked by default** —
must not become silently unsubmittable via accidental deletion.

### Editor controls (SSR §5)

- **`aae-form-fields`** control — projects real child field elements into a
  list; add/reorder/duplicate/delete; edits label/type/key/required/
  placeholder/width inline; warns on duplicate `field_key`; warns if
  `field_key` is locked because submissions already reference it.
- **`aae-form-actions`** control — opens a modal that reads/writes the
  hidden `actions_json` prop; configures Store/Email/Auto-Reply/Webhook/
  Sheets(later); validates against an action-registry schema; test
  email/webhook buttons call admin REST routes (see below).
- **Preset picker** — reuse the existing
  [preset picker](#to-add-presets-to-a-native-atomic-widget-e-heading-e-button-)
  pattern: search/filter, apply subtree, **regenerate `form_key`** on apply,
  keep current page context.

### Form identity (SSR §6 — simpler than the older PRD's 5-field identity
table; this is the version to implement)

Every form instance has one **`form_key`** (hidden prop on `e-aae-a-form`,
not user-facing by default), tied to `post_id` + `element_id`.

- On create: if `form_key` is empty, generate one.
- On duplicate: regenerate if the key already exists elsewhere.
- On paste/import: regenerate if a collision is detected.
- On Elementor document save: **server-side reconciliation** re-verifies
  uniqueness as a safety net for programmatic imports/edge cases that bypass
  the editor-bridge regeneration path.
- Do not let duplicated forms share submissions unless a future explicit
  "linked/global form" feature supports it.

(The older root-level PRD additionally describes `form_template_id` /
`form_instance_id` / `runtime_instance_id` as separate concepts — treat that
as design *rationale*, not the schema to implement; the SSR doc's single
`form_key` is the authoritative, simpler version actually meant for coding.)

Field-level: changing a `field_key` after submissions exist against it must
**warn** — it creates a new field identity rather than silently rewriting
history.

### Schema sync (SSR §7 — exact steps, runs on Elementor document save)

```
1. Locate e-aae-a-form elements.
2. Walk the form subtree recursively.
3. Read form-level props.
4. Read field child elements.
5. Read submit element.
6. Validate actions_json.
7. Build canonical schema JSON.
8. Hash schema.
9. If hash changed, create new schema version.
10. Mark latest schema active.
```

Schema JSON fields: form key, form version, fields, actions, messages, spam
settings, submit settings, `source = "elements"`, `schema_format`. Every
submission stores its schema version/snapshot so old submissions keep
rendering correctly after the form is edited (including deleted/renamed
fields, which retain their historical label/value).

### Database (SSR §8 — table names adjusted per the packaging table above:
`aaefb_*` → `aae_*`)

**`aae_forms`**: id, form_key, post_id, element_id, status, created_at, updated_at.

**`aae_form_schemas`**: id, form_id, version, schema_hash, schema_json, source, active, created_at.

**`aae_submissions`**: id, form_id, form_key, schema_version, status, source_url, referrer_url, utm_json, ip_hash, user_agent, created_at.

**`aae_submission_values`**: id, submission_id, field_key, field_label, field_type, field_value, created_at.

**`aae_action_jobs`**: id, submission_id, action_type, status, attempts, next_run_at, payload_json, created_at, updated_at.

**`aae_action_logs`**: id, job_id, submission_id, action_type, status, message, request_snapshot, response_snapshot, created_at.

**`aae_attachments`** — later, Pro (file upload).

Dashboard requirements: pagination, search, filter by form/status/date, CSV
export, bulk delete, single-submission drawer (values, source, metadata,
action logs), find-by-email for DSAR/support. No unbounded row loads —
paginate + index, must not timeout or spike memory at scale.

### REST API (SSR §9–10 — namespace adjusted per packaging table:
`aaefb/v1` → `aae/v1`; confirm no collision with an existing namespace
before Milestone 5 by grepping for `register_rest_route` in this plugin)

**Public:**
```
POST /aae/v1/forms/{form_key}/token   — no-store headers, returns a fresh single-use submit token, itself rate-limited
POST /aae/v1/forms/{form_key}/submit  — validates token, runs Bot Shield, runs server validation, saves submission, creates action jobs, returns success
```

**Admin** (cookie auth + nonce + capability checks required):
```
GET  /aae/v1/submissions
GET  /aae/v1/submissions/{id}
POST /aae/v1/actions/test-email
POST /aae/v1/actions/test-webhook
POST /aae/v1/action-jobs/{id}/retry
GET  /aae/v1/form-health/{form_id}
```

### Response codes (SSR §10 — the complete, authoritative table; supersedes
the partial 403/409/429-only list from the older PRD)

| Code | Meaning |
|---|---|
| 200 | success |
| 400 | malformed request |
| 401 | unauthorized admin request |
| 403 | invalid token / security failure |
| 409 | duplicate/replay submission |
| 422 | validation error |
| 429 | rate limit |
| 500 | server error |

Frontend must handle every one of these distinctly.

### Submit runtime requirements (SSR §11)

Initialize forms once (prevent duplicate init with a ready marker —
`data-aae-form-ready="true"`, see packaging table); must work inside hidden
containers; fetch a fresh token on interaction or submit; prevent page
reload; disable submit button while in flight; show loading state; run
frontend validation; submit to REST; show field + global errors; focus
first invalid field; **reveal parent accordion/tab/popup** if the invalid
field is hidden; keep user input after failure; prevent double submit;
respect `prefers-reduced-motion`.

### Submission architecture — exact 10-step pipeline (PRD §3, v1.4)

```
1. Render form with form_key, active schema_version, timestamp, one-time submit token.
2. Visitor submits via REST endpoint ONLY — public admin-ajax is not allowed.
3. Server verifies token, nonce/session context, form identity, schema version, timestamp.
4. Bot Shield checks: honeypot, minimum time, rate limit, duplicate submit, optional CAPTCHA/Turnstile.
5. Server validates all fields against the active schema snapshot.
6. Security/validation failure → log as spam/security, return a safe generic message, create NO clean submission.
7. Validation passes → save submission FIRST.
8. Create async action jobs (email, webhook, Sheets, notification, cloud storage).
9. Return success to visitor AFTER save, NOT after third-party actions complete.
10. Queue processes actions; stores action logs/retry status for admin review.
```

### Bot Shield (PRD §2/§4/§5, v1.4)

| Layer | Purpose | Tier |
|---|---|---|
| Submit token | single-use, prevents duplicate/replayed submit | MVP |
| Nonce / REST permission | verifies request origin + route validity | MVP |
| Honeypot | silently catches automated fillers | MVP |
| Minimum submit time | blocks unrealistically fast submits | MVP |
| Rate limit | by hashed IP + `form_key` | MVP |
| CAPTCHA/Turnstile escalation | challenges suspicious/high-frequency requests | MVP/Pro |
| Email/domain/keyword blocklist | blocks low-quality patterns | Pro |
| Country restriction | allow/block by policy | Pro |
| Real phone validation adapter | validates phone quality/country match | Pro |
| Spam reason log | shows admin why blocked, never stored as a clean lead | — |

Admin defaults: Protection Mode = **Recommended**; Honeypot On; Minimum
Submit Time = **3 seconds**; Rate Limit per Form On; Rate Limit by Hashed IP
On; Duplicate Submit Token On; CAPTCHA Escalation on when connected (shown
only on suspicion or High mode); Spam Log On; generic Block Message; Admin
Alert on Attack Spike = Pro.

**Protection modes:**
- **Low** — nonce/token, honeypot, basic validation. Small low-risk forms.
- **Recommended** (default) — + min time, rate limit, spam log, optional
  CAPTCHA escalation. Default for most contact/lead forms.
- **High** — + stricter rate limits, CAPTCHA/Turnstile challenge,
  domain/keyword blocking, country restrictions. High-traffic/lead-gen forms.

Rate limiting is scoped by **`form_key` + hashed visitor signal**, never a
permanent raw IP, by default. Disabling raw-IP storage must **not** disable
hashed rate limiting — they're independent switches.

### Duplicate-submit prevention (three overlapping layers)

1. Single-use submit token — reused token → 409-style response + spam/security log.
2. Frontend button-state guard — submit button disables immediately on
   click/while sending, before the token round-trip even starts.
3. **Cache-proofing** — fetch a fresh, `no-store` submit token when the user
   interacts with/begins the form, specifically to defeat CDN/cache-plugin
   stale-token 403s (this is the direct fix for the "cache plugins break
   forms" pain point).

### Async action queue (PRD §12 — exact contract)

States: **Pending, Processing, Success, Failed, Retrying, Cancelled.**

Retry schedule: attempt 1 immediate → attempt 2 after **5 minutes** →
attempt 3 after **30 minutes** → attempt 4 after **2 hours** → final failure
marks **Failed** with a manual **Retry** button in admin.

Job record: provider, action type, submission id, payload summary, status,
attempts, last error, next retry time, created/updated time. Store summaries
and redacted payloads, not raw PII, where possible. Redirect must not wait
for queued actions (explicit future carve-out: payment flows requiring
synchronous confirmation).

**Open/unresolved:** Action Scheduler vs. WP Cron vs. custom queue table vs.
hybrid — not decided by the spec; flag before building this milestone.

### Actions after submit (PRD §10, tiers)

| Action | Tier | Priority |
|---|---|---|
| Store in Database | Free | P0 |
| Admin Email (To/CC/BCC/Reply-To/subject/message/smart tags) | Free | P0 |
| Auto Reply | Free | P1 |
| Redirect | Free | P1 |
| Basic Webhook | Free | P1 |
| Advanced Webhook | Pro | P2 |
| Google Sheets | Pro | P2 |
| Telegram/WhatsApp/Slack | Pro | P2 |
| Google Drive/AWS S3 | Pro | P2 |

Auto-reply only fires when a valid email field exists (with a "Include
submitted copy" option). From-domain mismatch (From email ≠ site domain)
must trigger a UI deliverability warning + SMTP-setup suggestion. Google
Sheets requires OAuth + mapping UI + test row + retry on failure;
expired/revoked OAuth token must fail the job safely, prompt reconnect in
the dashboard, and leave the submission saved.

**Smart tags (SSR §22 — exact MVP syntax, resolved this time, not left to
design):**
```
{{field.name}}  {{field.email}}  {{field.phone}}  {{field.message}}
{{site.title}}  {{site.url}}  {{site.admin_email}}
{{page.title}}  {{page.url}}
{{submission.id}}  {{submission.date}}  {{submission.time}}
```
The parser must sanitize/escape based on output context (HTML email body vs.
plain text vs. webhook JSON).

**Action registry (SSR §19)** — implement actions behind a common interface,
not one-off handlers: `id`, `label`, `settings schema`, `validation`,
`run()` method, `log formatter`, `retry support`. Action types: `store`,
`admin_email`, `auto_reply`, `webhook`, `google_sheets` (later), `telegram`
(later), `whatsapp` (later), `redirect`. All actions run through the async
queue **except** immediate UX actions like `redirect`.

### UX requirements

- AJAX/REST submit, no page reload; loading state on submit.
- Inline validation: validate on blur, not while typing unless already
  interacted; validate everything on submit + optional error summary; map
  backend errors back to field-level; hidden conditional fields don't block
  submit by default.
- Focus first invalid field; **reveal parent accordion/tab/popup before
  focusing a field hidden inside one** ("Reveal-Before-Focus" — a named
  differentiator, not just a nice-to-have).
- Never clear user input after a failed submit.
- Slow-network/offline messaging, exact copy from the spec:
  - Slow: "Your connection seems slow. Please wait a moment."
  - Offline: "You appear to be offline. Please reconnect and try again."
  - Timeout: "We could not submit the form. Your information is still here. Please try again."
  - Duplicate: "This form was already submitted."
  - Rate limit: "Too many attempts. Please wait a moment and try again."
- Retry after a network failure uses a **fresh** submit token.
- Native autocomplete/inputmode: `name`, `email`, `tel`, `organization`,
  `street-address`, `address-level1/2`, `postal-code`, `country-name`, `url`
  where appropriate; mobile keyboard matches field type (email/tel/numeric/
  url); editor should warn on conflicting field-type/autocomplete pairs.
- Accessibility (WCAG 2.1 AA-inspired, MVP not later polish): every input
  has an accessible label; required state announced; errors linked via
  accessible descriptions; ARIA live/status for success/error messages;
  full keyboard navigation; focus moves to first invalid field after failed
  submit; usable inside Elementor popups; presets meet contrast requirements.
- i18n/RTL: all strings translatable, consistent text domain, RTL-safe
  layout, WPML/Polylang/TranslatePress compatibility, submissions store
  locale metadata.

### Motion policy

MVP motion scope only: error reveal, success fade/slide, button spinner,
conditional field show/hide transition, preset popup open/close. **No GSAP
dependency for core form submit** — this is repeated emphatically throughout
the spec and is a hard MVP constraint, distinct from the rest of this plugin
where GSAP is already used for animation effects. Respect
`prefers-reduced-motion`; validation clarity must never depend on animation.

**Multi-Step Forms' step-change transition (fade/slide/fade-slide, per-step
selectable) is a pure CSS `transform`/`opacity` transition** — see the
"Step-change animation" entry under
[Multi-Step Forms](#multi-step-forms--shipped-2026-07-19-free-plugin-not-pro-gated-in-code)
above. It briefly used GSAP (2026-07-20, at the user's request) as a
deliberate scoped exception, then was rebuilt CSS-only the same day (also
user-requested, after a residual visual issue) — so the "no GSAP" rule now
holds without exception across the entire Form Builder, including this
transition.

### Milestone order (SSR §33 — authoritative; supersedes the older PRD's
v1.4 sprint reprioritization, which grouped things slightly differently)

Implement and get one milestone reviewed before starting the next; do not
build the whole feature in one pass.

1. **Atomic skeleton** — register `e-aae-a-form`, `-form-field`,
   `-form-submit`; add default children (Name/Email/Message/Submit); render
   basic form; no submit logic yet.
2. **Fields control** — `aae-form-fields` list control; add/reorder/
   duplicate/delete; edit field settings.
3. **Preset popup** — preset registry; popup UI; apply subtree; regenerate
   `form_key` on apply.
4. **Identity and schema sync** — generate `form_key`; regenerate on
   duplicate/paste/import; schema walker; versioning.
5. **REST submit runtime** — frontend submit intercept; token fetch; REST
   submit; UI states (loading/success/error/rate-limited/offline).
6. **Submission DB** — create the six `aae_*` tables; save submission +
   values.
7. **Email action** — admin email; auto reply; smart tags; test email.
8. **Bot Shield** — honeypot; min submit time; token replay prevention;
   rate limit.
9. **Dashboard and health check** — submissions UI; single view; Form
   Health Check.
10. **Basic webhook and action logs** — webhook action; action jobs; logs.

**Note on ordering vs. the older PRD:** the root-level PRD's v1.4 addendum
argues for moving Bot Shield earlier (position 2, right after the skeleton)
because security should precede UI polish. The SSR doc — newer, and written
explicitly as the implementation milestone list for Claude — puts Bot Shield
at position 8, after fields/presets/identity/REST/DB/email are already
built. Follow the **SSR numbering above** since it's the more recent,
implementation-specific source, but build the REST submit endpoint (M5) and
the token/nonce/honeypot checks it depends on together — don't ship an
insecure REST endpoint in M5 and defer all security to M8. At minimum, single-
use tokens + nonce verification belong in M5; rate-limit/honeypot escalation
can wait for M8.

**Pro (separate track, after M10, per Free vs. Pro split above):** advanced
field types, Conditional Display Engine, Multi-Step Forms
(`e-aae-a-form-step`), Calculator/Quote, abandonment tracking/partial save,
Analytics, AI Copilot, HTML Email Template Builder, advanced Bot Shield
(Turnstile/reCAPTCHA v3, disposable-email/keyword/country blocking).

### Advanced Field Types — Batch 1: Date/Time/Range/Rating (shipped 2026-07-20)

First slice of the Pro "advanced field types" list — split into batches by
risk rather than shipped as one large change (Country followed in Batch 2;
Signature/Repeater/HTML/
Calculation/Address/Country remain unbuilt). Two different implementation
shapes, chosen per the same free/Pro registry constraint documented under
[Multi-Step Forms](#multi-step-forms--shipped-2026-07-19-free-plugin-not-pro-gated-in-code):

- **Date, Time** are new `type` enum values on the EXISTING
  `AAE_A_Form_Input` widget, added entirely from Pro
  (`animation-addons-for-elementor-pro/inc/AtomicV4/FormFields/Schema.php`)
  via a filter hook the free widget already exposed and documented for this
  exact purpose (`aae_form/input_types` — its own docblock said "Pro adds
  date/time here" before any Pro code used it). No new widget class, no new
  props, no JS bundle — one `add_filter` call. Registered in
  `inc/AtomicV4/Bootstrap.php` alongside the other AtomicV4 modules.
- **Rating and Range are each a genuinely new element type**
  (`e-aae-a-form-rating`, `e-aae-a-form-range`), so — same reasoning as
  `e-aae-a-form-step`/`-next`/`-prev` — both live in the FREE plugin
  unconditionally, with `is_pro => true` in `class-atomic.php`'s dashboard
  metadata marking them premium-tier (upsell badge only, not a functional
  gate; new optional widgets default to inactive until toggled on in the
  Atomic Widgets dashboard, same as any other non-seeded widget).
  - **Rating** (`inc/AtomicWidgets/Widgets/Form/class-aae-a-form-rating.php`):
    the real control is a plain `<input type="number">` (min 0, max = star
    count, required/error_message all work exactly like any other number
    field to Validator.php and Schema_Walker), progressively enhanced by
    `lib/rating.js` (mirrors `lib/multi-select.js`'s pattern) into a
    clickable star row that mirrors clicks onto the hidden input's value and
    fires `change` — so the rest of the form runtime never needs
    special-case Rating logic. Clicking the currently-set star again clears
    the rating (toggle off). CSS in `form.scss` (`.aae-a-form-rating-*`); no
    framework, no GSAP.
  - **Range** (`class-aae-a-form-range.php`) — originally shipped as a
    THIRD Pro `type` value on the Input widget (like Date/Time), then split
    into its own widget the same day after the user asked to set the
    slider's own colour, independently, from the Style panel. Root problem:
    `accent-color` — the one CSS property that actually recolors a native
    range's track/thumb consistently across browsers — has **no
    equivalent key in Elementor's atomic Style-panel schema at all**
    (confirmed by listing every file in
    `elementor/modules/atomic-widgets/controls/types/` — there is also no
    generic "Color" Content-tab control to fall back to; atomic widgets
    only expose color pickers through `Style_Definition`/`Color_Prop_Type`,
    i.e. the Style tab). Solution: Range gets its own `background` prop on
    its own base style (Style tab → Background Color, same mechanism
    Select's background uses), and `lib/range.js` bridges that to
    `accent-color` by reading the rendered input's own **computed**
    `background-color` at init and copying it onto `input.style.accentColor`
    — a live runtime bridge, not a build-time one (mirrors
    `lib/multi-select.js`'s `applyStyle()`, which does the identical
    computed-style-copy trick for the multi-select trigger). Defaults to
    `#69727D` (the same neutral gray Checkbox's `:checked` state uses), not
    transparent — transparent would make the accent invisible until a
    builder explicitly paints a colour, which reads as broken rather than
    "using the default". Because Range moved to its own widget, Pro's
    `FormFields` module shrank back down to Date/Time only — `Controls.php`
    and `Renderer.php` (which existed only to splice a `step` attribute onto
    the shared Input widget) were deleted entirely; `step` is now a plain
    prop directly on the Range widget itself, no splicing needed.
- `Validator.php` gained `date`/`time` format/range checks inside
  `check_format()` (dispatched by the Input widget's `type` string — works
  regardless of which plugin registered that type) plus one new filter,
  `aae_form/validator/check_format`, for any FUTURE type this switch
  doesn't handle. `rating` and `range` are each their OWN top-level `case`
  in `Validator::validate()`'s main switch instead (they're separate field
  TYPES from `Schema_Walker::FIELD_TYPES`, not Input `type` values, so they
  never reach `check_format()` at all) — `range` reuses the same
  `check_range()` helper the input family's own min/max check uses, and
  never fires a required error (a native range always posts a value).
  `Schema_Walker::FIELD_TYPES` gained `e-aae-a-form-rating` → `rating` and
  `e-aae-a-form-range` → `range` mappings, each with a matching case in
  `build_field()` — Rating's carries `max` into the schema as the star-count
  ceiling, Range's carries `min`/`max` (its `step` is display-only, not a
  submission-security concern, so it isn't in the schema at all).
- New preset: `presets/advanced-fields.json` — Date, Time, Range, Rating,
  Submit, plus the required Field Error / Success / Error message widgets
  (same "every preset needs these three" pattern as `all-fields.json`).
  Deliberately a NEW preset rather than a retrofit of `all-fields.json` (
  which would have required regenerating the derived
  `all-fields-columns.json` too, per the [Form presets](#form-presets)
  note that the columns version is script-generated, not hand-edited).
- **Verified end-to-end** via a WP-CLI fixture (`make-form-advfields-page.php`,
  mirrors `make-form-allfields-page.php`) + Playwright
  (`verify-form-advfields.mjs`, `E:\Local Testing\`): all four field types
  render with correct attributes (native `date`/`time`/`range` inputs, 5
  painted stars for Rating's default max), required-field errors fire
  correctly (range never fires one — a native range input always has a
  value), star clicks mirror to the real input and clear its error, and a
  full REST submit reaches `form-state-success` — proving the whole
  pipeline (Schema_Walker → Validator → Rest.php) accepts all four new
  types.
- **Gotcha — a WP-CLI fixture builder using `Document::save()` alone left
  the page rendering as plain `post_content` (wpautop-wrapped `<p>`/`<br>`,
  no `<form>`/div-block wrappers at all), even though `_elementor_data` and
  the schema-sync DB row were both correct.** Root cause: a freshly
  `wp_insert_post()`-created plain `page` has no `_elementor_edit_mode` meta
  yet, and `Document::save()` doesn't set it — so WordPress never hands the
  page to Elementor's frontend renderer, no matter how correct the saved
  element tree is. This affected the EXISTING `make-form-allfields-page.php`
  fixture too (not something Batch 1 introduced) — it had simply never been
  re-run since being written, so the bug was latent. Fixed in both scripts
  by explicitly `update_post_meta( $post_id, '_elementor_edit_mode',
  'builder' )` BEFORE calling `$document->save()` (mirrors
  `make-host-page.php`, which writes `_elementor_data` directly and always
  set this meta itself — the one fixture-builder pattern in this codebase
  that never hit the bug). Diagnose this class of "children render but the
  container doesn't, and there's no PHP error anywhere" failure by checking
  for a literal `<form`/`<div class="e-div-block"` tag in the raw response
  HTML, not just `data-element_type` attribute counts — Elementor's own
  `render_content` filter still fires per-widget even when the parent
  document isn't being rendered as a document at all, which is what made
  the child widgets appear to "work" while the root silently didn't.
- Bot Shield's minimum-submit-time default (3s) will reject a same-request
  fixture test that fills every field and submits in well under a second —
  this is Bot Shield doing its job, not a bug; any new form-submit
  Playwright test needs a `waitForTimeout` of at least that long before
  clicking Submit (see `verify-form-advfields.mjs`).
- **Bug found + fixed same day (2026-07-20), reported live via screenshot:
  the Rating field's required-error message rendered INLINE next to the
  stars (squeezing Submit onto the same line) instead of on its own line
  below.** Root cause, two independent gaps: (1) `errorAnchor()` in
  `form.js` didn't know about the Rating wrapper — for every other
  enhanced-control field (multi-select, checkbox row, radio group) it walks
  up to the wrapper via `.closest(...)` before inserting the error, but
  Rating's hidden `<input>` fell through to the default case, so
  `insertAdjacentElement('afterend', el)` inserted the error INSIDE
  `.aae-a-form-rating` (next to the visible star row) instead of as a
  sibling of the wrapper — breaking the `:where(.aae-form-field-error) {
  flex-basis: 100% }` row-break, since that rule only forces a line break
  for direct flex children of the `<form>`, not something nested one level
  deeper. Fixed by adding `control.closest('.aae-a-form-rating')` to the
  same lookup chain. (2) Separately, `AAE_A_Form_Rating::define_base_styles()`
  never set `width: 100%` (unlike Input/Select/Textarea, which all do) — so
  the wrapper shrank to its star row's own width, leaving room for Submit to
  tuck in beside it even before the error-anchor fix. Both were required;
  fixing only one still left a layout bug. Verify this class of bug by
  checking computed `getBoundingClientRect()` Y-positions of the field,
  its error, and the next control — not just that the error text exists
  somewhere in the DOM.

### Advanced Field Types — Batch 2 started: Country (shipped 2026-07-20)

**Country** (`e-aae-a-form-country`,
`inc/AtomicWidgets/Widgets/Form/class-aae-a-form-country.php`) — a
single-value sibling of the Select widget: same "value|Label|selected"
options format, same base styles/twig mechanics, but the `options` prop
**defaults to the full built-in ISO 3166-1 list** (239 entries,
`inc/Forms/Countries.php` — translatable names, `aae_form/countries`
filter), values submit as ISO alpha-2 codes, and the `<select>` carries
`autocomplete="country-name"`. No JS bundle (`has_script: false` — native
select). Free plugin + `is_pro => true` badge, same reasoning as
Rating/Range. Priority countries = builder reorders/prunes the pre-filled
options textarea; `|selected` pre-picks one.

- **Why the list is a prop DEFAULT, not render-time PHP context:** atomic
  twigs also render client-side in the editor preview
  (`Has_Template::get_initial_config` ships `twig_templates`), where only
  settings + prop defaults exist — injecting extra context in a `render()`
  override would leave the editor's select empty. Defaults flow through
  both pipelines.
- **Schema/Validator:** `Schema_Walker::FIELD_TYPES` maps to its own
  `country` type whose `build_field()` case falls back to
  `Countries::options_string()` when the prop is untouched — so the schema
  snapshot always carries the full whitelist. `Validator::validate()` adds
  `case 'country':` as a bare fallthrough into `case 'select':` (identical
  required + options-whitelist mechanics, `multiple` absent → false).
- Verified end-to-end (`E:\Local Testing\verify-form-country.mjs` +
  `make-form-country-page.php` + `enable-form-country-widget.php`):
  inventory (240 options incl. placeholder, autocomplete attr, BD label),
  required error with per-field message, **tampered DOM-injected option →
  server 422 whitelist rejection mapped back onto the field**, then valid
  BD → success.

**Address (shipped same day) is a PRESET, not a widget** —
`presets/address.json`: Street / Apartment(optional) / City / State(optional)
/ Postal + the Country widget + Submit + the three message parts. Deliberate
call: an "Address field" has no server-side semantics of its own (it's five
plain text values), so a composite widget would only have re-implemented
Label+Input layout while LOSING per-part styling, reordering, and
conditional-display support. The one thing genuinely missing was native
autofill, so that was added to the shared Input widget instead:

- **New `autocomplete` prop on `e-aae-a-form-input`** (Content → "Autofill",
  `Select_Control`) with a curated token list —
  `AAE_A_Form_Input::autocomplete_options()`: name/given-name/family-name,
  email, tel, url, organization, street-address, address-line1/2,
  address-level2 (City), address-level1 (State), postal-code, country-name,
  off. `''` = browser default → attribute not rendered at all. Enum values
  come from `array_column(autocomplete_options(), 'value')` so control and
  prop can't drift. **Display-only — deliberately NOT in the schema
  snapshot** (same reasoning as Range's `step`: no submission-security
  meaning). Benefits every form, not just Address.
- Verified: `E:\Local Testing\verify-form-address.mjs` +
  `make-form-address-page.php` — all 6 autocomplete tokens land on the
  rendered fields, required errors fire on exactly the 4 required parts
  (line2/state stay optional), full fill + BD → success. Regression
  (allfields/country/regex) re-run green after the Input change.
- **Test-writing note:** the schema stores an input field's SUBTYPE
  (`text`, `email`, …) as its `type`, NOT the widget-family name `input` —
  an assertion expecting `input*` fails against the correct `text*`.

**Password (shipped 2026-07-20) — a SEPARATE widget, not the Input widget's
`password` type.** `e-aae-a-form-password` + `lib/password.js` (reveal-eye
toggle only; min-length/match validation lives in `form.js`'s
`validateFrontend` next to the other rules). The Input widget keeps its own
`password` type for the legacy/simple case; the new widget is the
privacy-correct one, and the split exists **because storage behavior must be
schema-driven**: deriving "never store this" from an Input's mutable `type`
prop would leave already-stored plaintext behind the moment a builder flips
Text→Password. Props: `min_length`, `match_field` (the partner field's
`_cssid`, set on the *confirm* field), `show_toggle`, `store_mode`,
`mismatch_message`. Twig wraps the input in a `.aae-a-form-password` div
that carries the base style, so the eye button sits INSIDE the field box —
which also means `errorAnchor()` in form.js needed the wrapper added to its
`.closest()` chain (same bug class as Rating's, same day).

- **Redaction is central and unconditional.** `store_mode` has NO `plain`
  value by design: `never` (default) records `********`, `hash` stores a
  `wp_hash_password()` digest. `Validator::redact_passwords()` runs in
  `Rest.php` **before** `submission_validated`/storage/the action queue/CSV,
  so no downstream consumer can see a readable password. Note the Validator
  case deliberately does NOT `sanitize_text_field()` the value (it would
  strip legal password characters) — safe because the value is never
  rendered as HTML, only discarded or hashed. Confirm-match uses
  `hash_equals`.
- **Create User action (PRO, `inc/AtomicV4/FormUser/`)** — turns a signup
  submission into a real WP account. Free ships ONE new seam:
  `aae_form/submission_raw`, fired in `Rest.php` immediately BEFORE
  redaction, synchronously (the raw credential must never reach the async
  job queue, which persists its payload). Pro's `Engine` hooks it;
  `Fields.php` maps payload keys onto user fields by **alias**
  (`fname`/`f_name`/`firstname`, `lname`/`l_name`/`lastname`,
  `user_name`/`username`, `email`/`user_email`/`mail`, …). Config is a
  `create_user` block in `actions_json`, edited in the Actions dialog's new
  **Create User** tab, which shows live ✓/! readiness rows naming the exact
  field keys this form has/needs. Safety: role is re-checked against
  `ALLOWED_ROLES` server-side (subscriber/customer only — an edited
  `actions_json` can never mint an admin); an email that already exists is
  SKIPPED, never updated; every failure is caught and logged so a
  submission is never lost.
- **Alias matching is exact-then-suffix, never substring.** Exact normalised
  match first (`email`), then a suffix match so real-world prefixed keys
  (`aae-signup-email`, `billing_email`) resolve — anchored to the END
  specifically so `email_consent` can never be mistaken for the address.
  The same two-pass logic is mirrored in the editor dialog's
  `matchedAlias()`; change one, change both.
- **Gotcha found while testing: `AAE_A_Form_Input` has NO `name` prop** (only
  Select/Country do), yet its twig and `Schema_Walker` both read one. Setting
  `name` on an Input in a preset/fixture is silently dropped at render while
  the schema still records it → the posted key can never match its own
  schema (422 on every submit). Use `_cssid` to control an Input's field key.
- **`presets/signup.json` ("User Registration") is the registration preset** —
  Name / Username (optional) / Email / Password / Confirm, and it ships with
  `create_user` ALREADY ENABLED in its `actions_json` (plus admin email), so
  applying it gives a working registration form with no dialog trip. Its
  field `_cssid`s are deliberately named for the aliases (`name`,
  `user_name`, `email`, `password`, `password_confirm`) — a preset whose keys
  don't match the aliases would apply cleanly and then silently fail to
  create users. Username blank → login derives from the email's local part;
  filled → used verbatim. (`autocomplete` gained a `username` token for it.)
- Verified: `verify-form-password.mjs` (25 checks) + `make-form-signup-page.php`
  — reveal toggle, both frontend blocks with zero network requests, error
  placement below the wrapper, `********` read straight out of
  `aae_submission_values`, the created account's password actually
  authenticating via `wp_check_password`, a duplicate-email re-submit proving
  the existing user is untouched, and BOTH username paths (blank → email
  local part, filled → verbatim).

**GOTCHA — retry-after-failure got 403 `too_fast`, a general form.js bug
this test exposed (fixed 2026-07-20).** The server's minimum-submit-time
check measures from TOKEN ISSUE (`Rest.php`), and form.js's
interaction-prefetch listeners were `{ once: true }` — after the first
submit consumed the token, nothing re-armed them, so a retry's token was
fetched at click time (age ~0s) and Bot Shield blocked an honest retry.
Re-arming the listeners alone is NOT enough: the 422 path auto-focuses the
invalid field (`focusFirstInvalid`) BEFORE the re-arm runs, so a
re-armed `focusin` never fires (focusing an already-focused element is a
no-op). Fix in form.js: on ANY failed submit (non-ok response or catch),
`prefetch()` a fresh token IMMEDIATELY — its age then measures the real
time the visitor spends fixing input. Success path only re-arms the
interaction listeners (covers the success-reset → fill-again flow without
fetching a token nobody may use). Diagnose this class of block via the
spam log: `wp eval` on `wp_aae_action_logs` shows `bot_shield | blocked |
too_fast` with timestamps (the `wp db query` CLI path is broken on this
Local install — no mysql binary in PATH).

### Hide Form After Success (shipped 2026-07-20)

Two props on `e-aae-a-form`, next to the existing "Reset After Success":
`success_hide_form` (Switch) + `success_hide_delay` (Number, seconds; 0 =
hide immediately). Rendered as `data-aae-form-hide-on-success` /
`data-aae-form-hide-delay`; runtime is `scheduleSuccessHide()` in `form.js`,
called next to `scheduleSuccessReset()` on a successful submit.

- **Hiding the `<form>` itself is wrong** — the authored success/error
  message widgets are its CHILDREN, so hiding the form takes the message
  with it and the visitor sees nothing. Instead every DIRECT child EXCEPT
  the status-message containers gets `.aae-form-body-hidden`; the message
  keeps its authored styling and position.
- **The CSS rule needs `.elementor` on the selector** — atomic base styles
  compile as `.elementor .e-<widget>-base { display: … }` (0,2,0), so a bare
  `.aae-form-body-hidden` (0,1,0) loses to them and nothing hides. Same trap
  as `e-aae-a-form-step` (see [Common breakage points](#common-breakage-points)).
- **Hide and Reset are independent and must cooperate**: the reset path calls
  `showFormBody()` so a form with BOTH settings comes back instead of
  resetting into an empty box, and a real Reset/Clear click cancels a pending
  hide timer. Verified as its own case.
- **GOTCHA — `dataset.elementType` does NOT read `data-element_type`.** The
  attribute has an UNDERSCORE, so `dataset` (which maps `data-element-type`,
  hyphen) always returns undefined; `getAttribute('data-element_type')` is
  the only correct read. This silently made every child look like a
  non-message element, so the success message got hidden along with the
  fields — caught only because the test asserted the message stays VISIBLE
  (computed style), not merely that the fields disappeared.
- Verified: `verify-form-success-hide.mjs` + `set-form-success-hide.php` —
  four cases: delayed hide (message still visible), immediate hide (delay 0),
  hide-then-reset-restores, and switch-off leaves the default behaviour
  unchanged.

### Conditional Display Engine (PRO — shipped 2026-07-19)

Lives in `animation-addons-for-elementor-pro/inc/AtomicV4/FormConditions/`
(FlexboxChildHover module pattern). Free ships ONLY neutral plumbing:

- **JS hook registry** — free `form.js` exposes `window.AAEFormHooks`
  (WP-style addAction/addFilter; `aae_form/init` fires per form, late
  registrations replay). Pro runtime
  (`src/modules/atomic-v4/form-conditions.js`, handle
  `aae-pro-form-conditions`, depends on `aae-a-form-js`) binds there.
  Free validation/collect skip anything under `[data-aae-cond-hidden]`.
- **PHP filters (free)** — `aae_form/schema_walker/field` (per-field extras),
  `aae_form/schema_walker/schema` (whole schema + raw tree — used to attach
  ancestor row conditions to inner fields as `conditions_parents`),
  `aae_form/validator/skip_field` (skipped = not validated, not stored).
- **Config delivery** — field/content WIDGETS get `data-aae-cond` spliced
  into their first tag via `elementor/widget/render_content`;
  flexbox/div-block CONTAINERS can't (elements, not widgets) so their
  configs ride `window.AAE_FORM_COND` keyed by `data-id` (collected at
  `before_render`, printed at `wp_footer` prio 5). Runtime enqueued only
  when something actually carries conditions.
- **Rule semantics live in THREE mirrored places** — PHP `Engine.php`,
  runtime `form-conditions.js`, dialog `FormConditionsControl.jsx`
  (free editor-bridge, dormant without pro — Integrations-tab pattern;
  PHP control stub type `aae-form-conditions`). Operators: equals /
  not_equals / empty / not_empty / contains / greater_than / less_than;
  logic all/any; action show/hide. Change one → change all three.
- Editor canvas never hides conditioned elements (styling access); reveal
  animation class `aae-cond-reveal` lives in free `form.scss` behind
  `prefers-reduced-motion`.

### Calculation field — shipped 2026-07-20 (free plugin, `is_pro` badge only)

`e-aae-a-form-calculation`: a read-only field whose value is computed from
OTHER fields by a builder-authored formula (`round(({package} *
{quantity} + {support}) * 1.15, 2)`). Same free-plugin/pro-badge
arrangement as Rating/Range/Country/Password — the element type must be
registered from the free plugin (Elementor's atomic registry has no
outside-registration path), `is_pro => true` is marketing only.

**The security model is the whole point.** The posted value is
DISCARDED: `Validator::validate()`'s `case 'calculation'` recomputes the
total from the other posted values using the formula in the ACTIVE SCHEMA
SNAPSHOT, so editing the hidden input in DevTools changes nothing about
what gets stored or emailed. Verified end-to-end (a submit with the input
rewritten to `1.00` stored the correct `2298.85`).

- **Two mirrored evaluators, and they MUST agree**: `inc/Forms/Formula.php`
  and `Widgets/Form/assets/js/lib/calculation.js`. Hand-written tokenizer +
  shunting-yard, **never `eval()`** (a formula is untrusted input that runs
  server-side on every submit). Supported: numbers, `{field_key}`, `+ - *
  / %`, unary minus, parens, and `round/floor/ceil/abs/min/max`. Anything
  else fails to parse → null → nothing stored (never an error: a builder's
  typo must not block a submission the visitor can't fix). Division by zero
  returns null on BOTH sides (not Infinity). `round()` is half-away-from-zero
  on both — JS routes through a string exponent shift (`roundHalfUp`) because
  `Math.round(-2.5)` is `-2` while PHP's is `-3`, and `1.005` drifts in
  binary.
  - **Parity is regression-tested**: 56 cases (incl. `round(1.005,2)`,
    `round(-2.5)`, `0.1+0.2`, `-10 % 3`, div-by-zero, every syntax error
    shape) run through both implementations and compared —
    `scratchpad/formula-{cases.json,php.php,js.mjs}` pattern. Re-run it
    after ANY change to either evaluator.
- **Empty-form placeholder is a JS-only concern**: an untouched form would
  total 0 and read as a real quote of "$0.00", so `syncCalculations()` holds
  the `empty_text` placeholder until at least one REFERENCED field is
  non-empty. The server has no equivalent case (it only computes at submit
  time). A preset whose first select option is a real value therefore looks
  "already filled" — give such selects a `placeholder` so a genuine empty
  state exists (quote-calculator.json does).
- Value reading (`valuesOf`): unchecked checkbox contributes nothing, a
  checked one contributes ITS OWN value (so `{support}` with value `199`
  works), multi-select sums its picked values, and anything under
  `[data-aae-cond-hidden]` (pro Conditional Display) is skipped — a hidden
  field must not feed a total the visitor can't see.
- Runtime binds ONE delegated `input`/`change` pair on the `<form>` (not
  per-dependency listeners) so it survives fields being added/removed by
  conditional display or a preset apply; `reset` re-syncs on the next tick.
- **Gotcha — Input/Textarea have NO `name` prop** (their submission key IS
  `_cssid`), while Select/Checkbox/Rating/Calculation DO. A formula
  referencing `{quantity}` therefore needs the Input's **ID** set to
  `quantity`, not a `name` setting — a preset that sets `name` on an Input
  silently gets `_cssid` as the real key instead (cost a full e2e debug
  cycle; the panel description for Calculation's Name field says "the key
  this total is saved under" for the same reason).
- **Chained calculations work**: a formula may reference an EARLIER
  calculation field (`{subtotal}` → `{rush_surcharge}` → `{project_total}`).
  Both sides feed each result back into the working value map as they go —
  `Validator.php` writes into `$posted`, `calculation.js` into its `values`
  object — so resolution is strictly DOCUMENT ORDER: a formula can only read
  calculations declared above it, which is also why a circular reference is
  impossible. (Without the feed-back the chain silently produced 0 on the
  server while working in the browser, since the browser reads the previous
  field's rendered DOM value.)
- **Multi-value fields SUM**: `number_of()`/`numberOf()` add up an array, so
  a multi-select's picks total into one number (`{addons}` = 600+900+450).
  **Name normalisation matters here**: a multi-select's DOM name carries the
  PHP array suffix (`addons[]`) but the SCHEMA keys it without one
  (`addons`) — the browser and server would otherwise disagree on the key.
  Both spellings resolve on both sides (`valuesOf()` registers both;
  `Formula::tokenize()` falls back from `{addons[]}` to `addons`). Prefer
  the plain `{addons}` form in presets/docs.
- A multi-select with NOTHING picked leaves its key ABSENT rather than
  reading 0 — otherwise the placeholder check would see a present-but-zero
  value and treat an untouched form as filled.
- Presets (7): `quote-calculator.json` (select-as-price + quantity + priced
  checkbox), `booking-calculator.json` (RANGE slider as quantity, plus a
  second calculation used purely as a slider read-out), `loan-calculator.json`
  (three chained calculations: interest → repayable → monthly),
  `web-project-estimate.json` (the advanced showcase: multi-select summing,
  three priced checkboxes, and a subtotal → percentage-surcharge → total
  chain), plus three INDUSTRY presets:
  `doctor-appointment.json` (3-step multi-step form: patient details →
  appointment → medical info; radio groups, date+time, a NEGATIVE option
  value for a video-call discount wrapped in `max(…, 0)`, multi-file upload,
  consent checkbox, submit inside the last step's nav row),
  `salon-booking.json` (multi-select services + stylist-surcharge select,
  per-guest multiplication, home-visit checkbox, reference-photo upload),
  `hotel-booking.json` (Country widget, check-in/out dates, nights DERIVED
  from those dates via `days_between`, room-type select, rooms RANGE,
  multi-select extras, and a five-calculation chain: nights → room subtotal
  → extras subtotal → taxed total, plus a slider read-out).
  - **Date arithmetic goes through `days_between(a, b)`, never `-`.** Plain
    subtraction still evaluates to 0: `{field}` tokens carry a NUMERIC
    reading and "2026-09-04" has none. `days_between` is the one function
    whose operands are dates — tokens therefore keep a third slot with the
    RAW posted string, and the RPN evaluator runs a parallel `raw` stack so
    date functions can reach it (computed values have no raw form and store
    ''). Hotel's nights field uses it:
    `days_between({check_in}, {check_out})` → 3 for Sep 1 → Sep 4.
    - Returns null (⇒ whole formula uncomputable, nothing stored) when
      either side isn't a real calendar date — including a nonexistent one
      like `2026-02-30`. A BACKWARDS range returns a NEGATIVE number, not
      null; guard with `max(days_between(…), 1)` when a minimum makes sense.
    - Accepts an ISO date with or without a time part (the time is ignored).
    - Both sides count whole days from Y-M-D via the same civil-from-days
      algorithm — deliberately NOT `strtotime`/`Date`, so DST and timezones
      can't shift a night boundary and neither side needs to know the site's
      timezone.
  Fixtures/tests: `verify-industry-presets.mjs` (all three), plus
  `make-form-calc-page.php` +
  `verify-form-calculation.mjs` (tamper case), `make-form-estimate-page.php` +
  `verify-form-estimate.mjs` (multi-select + chain, compares stored values
  against what the page displayed), `read-last-submission.php`,
  `enable-form-calculation-widget.php`.
- **Writing Playwright tests against these long preset forms — two traps
  that both LOOK like product bugs and are not:** (1) a `page.click()` on a
  submit button sitting past the viewport bottom silently no-ops (Playwright
  still reports the click as successful), so dispatch
  `form.requestSubmit()` instead — same code path, no hit-testing; (2) the
  success state is erased ~5s later by Reset After Success, so
  `waitForSelector('.form-state-success')` can miss it entirely — arm a
  MutationObserver latch BEFORE submitting. Also clear Bot Shield's rate
  limiter between submits (`clear-form-rate-limit.php` — transients keyed
  `aae_frl_*`; 5 submits per 5 minutes per visitor+form), or a multi-form
  suite trips it legitimately and it reads as a broken form. Each of these
  cost a full debug cycle chasing a non-existent bug.

### Validation Pro — regex rules (PRO — shipped 2026-07-20)

First slice of Validation Pro. Same free-hooks/pro-engine split as
Conditional Display: free ships two neutral filter points, the regex
engine lives entirely in
`animation-addons-for-elementor-pro/inc/AtomicV4/FormValidation/`
(Schema/Controls/Renderer/Engine/Assets — module registered in
`AtomicV4/Bootstrap.php`).

- **Free plumbing (the only free changes):** PHP filter
  `aae_form/validator/value_error` (Validator.php — runs for input-family +
  textarea AFTER every built-in check passed; return a message string to
  fail) and JS filter `aae_form/validate/value_error` (form.js
  validateFrontend — same contract, only applied when the control's
  built-in checks passed). Both are generic extension points, not
  regex-specific.
- **Props** `aae_regex_pattern` + `aae_regex_message` on
  `e-aae-a-form-input`/`-textarea` (message dep-gated on pattern non-empty);
  panel controls land in the widget's native **Settings** section at filter
  prio 24 — one before Conditional Display (25), so the section reads
  ID → Regex → Conditional Display.
- **Match semantics mirrored in TWO places** — PHP `Engine.php` and runtime
  `src/modules/atomic-v4/form-validation.js` (handle
  `aae-pro-form-validation`, dep `aae-a-form-js`, register-only +
  on-demand enqueue when the Renderer actually splices). Change one →
  change both. Semantics: **partial match** (users anchor with `^ $`),
  pattern accepted bare (`^\d{5}$`) or as `/pattern/flags` (flags
  whitelisted per engine), an **invalid pattern always passes** (never
  blocks a visitor on a builder's typo). Message precedence:
  `regex_message` > field `error_message` > translated default.
- Rule rides the schema snapshot as `regex`/`regex_message` (walker filter
  `aae_form/schema_walker/field`) and the rendered control as
  `data-aae-regex`/`data-aae-regex-message` (first-tag splice, same as
  Conditional Display's Renderer).
- Verified end-to-end: `E:\Local Testing\make-form-regex-page.php` (server:
  schema carries rule, Validator rejects bad value with custom message,
  good value clean) + `verify-form-regex.mjs` (frontend blocks with custom
  message and ZERO network requests; good value → form-state-success).

### Form "Reset After Success" (free — shipped 2026-07-20)

`success_reset_delay` Number prop on `e-aae-a-form` (default **5** seconds,
`0` = keep success state until reload; control in the Content section under
Submission Behavior) → twig `data-aae-form-reset-delay` →
`scheduleSuccessReset()` in form.js: after a successful submit (skipped on
redirect), waits the delay then restores the resting state — success
message/class cleared, field errors cleared, and a multi-step form returns
to its FIRST step (`form.__aaeStepState.current = 0` + `resyncSteps`).
Fields themselves were already cleared by `form.reset()` at success time.
Verified: `E:\Local Testing\verify-form-success-reset.mjs` (multi-step
contact preset — submits on step 2, auto-returns to step 1 with message
cleared).

### Multi-Step Forms — shipped 2026-07-19 (free plugin, not pro-gated in code)

Hard requirements (all live-verified end-to-end on development.local: applied
via a real preset, published, driven on the actual frontend — Next blocked
with 2 field errors on empty required fields, advanced to step 2 once filled,
Previous appeared):

1. **Next only after the current step validates** — advancing to the next
   step MUST run that step's field validation first and block on errors
   (user requirement, non-negotiable). ✅ Confirmed.
2. **A Multi-Step preset must ship with the feature** (user requirement).
   ✅ Two presets: `multi-step-contact.json` (2 steps) and
   `multi-step-lead.json` (3 steps) — see below.
3. Panel copy/UX — the Step widget's Content section labels/describes
   `step_title` clearly (shown in the step-nav progress indicator, not
   inside the step itself).

**Architecture decision (verified via Explore agent before building — see
also `AAE_A_Btn_Pro` precedent, `Widgets/BtnPro/class-aae-a-btn-pro.php`):
Elementor's atomic widget registry (`inc/AtomicWidgets/class-atomic.php`'s
`get_available_widgets()`) is 100% free-plugin territory — a Pro plugin
cannot register a brand-new element TYPE from outside it.** So
`e-aae-a-form-step` is a normal FREE-plugin element
(`inc/AtomicWidgets/Widgets/Form/class-aae-a-form-step.php`, container:
`Atomic_Element_Base` + `Has_Element_Template`, unrestricted children like
the parent form) registered in `class-atomic.php` in all three places new
Form children need: the "always-active internal widgets" list (~line 327,
alongside the other form parts), the Widgets-dashboard metadata (`is_pro:
true` — marketing/upsell badge ONLY, exactly like `aae-a-btn-pro`; the code
itself is NOT license-gated), and the class/file registry (`has_script:
false` — step-nav JS rides inside the existing `aae-a-form-js` bundle, not
a separate script).

**Runtime**: `inc/AtomicWidgets/Widgets/Form/assets/js/lib/multi-step.js`
(new module, mirrors `lib/multi-select.js`'s pattern) — `initSteps(form,
validateStep, onBlocked)` toggles `aae-form-step-active` on exactly one
`[data-aae-form-step="true"]` at a time (same class-toggle-reveal trick as
`form-state-{value}`). Runs in the editor preview too (builders need to
see/style every step) — only the Next-button VALIDATION gate is
frontend-only (`validateStep`/`onBlocked` null in `isEditMode()`).
`form.js` reuses its own private `validateFrontend`/`showFieldError`/
`focusFirstInvalid` for this (no duplicated validation logic) —
`controlsOf()` was generalized to accept ANY root element (not just
`form.elements`) so validation can scope to one step's controls via
`querySelectorAll`.

**Next/Previous are REAL atomic widgets (added 2026-07-19), not
runtime-injected DOM buttons** — `e-aae-a-form-next` /
`e-aae-a-form-prev` (`class-aae-a-form-next.php` / `-prev.php`, leaf
`Atomic_Widget_Base`, same free-plugin reasoning as the Step element
above), rendering `<button data-aae-form-step-nav="next"|"prev">`. This
gives builders full Style-tab control (colors, borders, spacing, hover
state) — something a JS-injected `<div>` never had. `e-aae-a-form-step`
seeds a Prev+Next pair (`e-flexbox.aae-form-step-nav-row`) as its
`define_default_children()`, so a fresh Step drops in ready to navigate;
builders can delete/move/restyle them freely.

- **Detection, not injection, is the default path.** `multi-step.js`'s
  `initSteps` scans each step for its OWN `[data-aae-form-step-nav]`
  widgets (`ownNavButtons()`) and delegates clicks to them via one listener
  on the `<form>` (event delegation — covers both author-placed widgets
  present at initial render AND any fallback button appended later).
  `render()` toggles the `hidden` attribute on whichever Prev/Next widgets
  exist per step (Prev hidden on step 0, Next hidden on the last step) —
  `.aae-a-form-next[hidden], .aae-a-form-prev[hidden] { display: none
  !important }` in `form.scss` is required because the widgets' own atomic
  base style sets `display: flex`, which otherwise beats the UA `[hidden]`
  rule (same specificity class of bug as the entry in [Common breakage
  points](#common-breakage-points) — a `!important` here is the deliberate,
  narrow exception).
- **Fallback only when a step has neither widget.** Gated by the
  **PER-STEP "Auto Step Navigation" switch** (`step_nav_auto` prop on
  `e-aae-a-form-step` — moved here 2026-07-20 from the Form widget, so a
  builder doing fully custom navigation on ONE particular step, e.g. a
  review/confirm step, can turn it off there without affecting the rest of
  the wizard) — read via `data-aae-form-step-nav-auto` on the step element
  itself (see `aae-a-form-step.html.twig`). ON (default): a step missing
  both widgets gets `multi-step.js`'s own injected Prev/Next/`N / M` bar
  (`buildFallbackNav()`, appended at the end of that step's content, tagged
  with the same `data-aae-form-step-nav` attrs so the SAME click-delegation
  path handles it — no separate injected-button code path). OFF: no
  fallback ever for that step — un-advanceable without its own widget,
  which is the point.

**GOTCHA #2 — toggling ANY setting on the form (not just the Auto Step
Navigation switch) made the ENTIRE multi-step form vanish in the editor.**
Root cause, confirmed via a diagnostic that flipped `step_nav_auto`
programmatically and inspected the preview iframe DOM: Elementor's editor
repaints a form's `innerHTML` from scratch on ANY settings change to the
form or its children (not just "Apply Preset") — the `<form>` node itself
is reused (so its `data-aae-form-ready`/`data-aae-steps-bound` dataset
survives), but every STEP node inside gets freshly re-rendered with NO
`aae-form-step-active` class. The original `initSteps()` closed over the
OLD step elements and the "already bound" guard (`aaeStepsBound === true`)
made it skip re-running entirely — so the new step nodes sat there
permanently `display:none` (their own base style), nothing ever reapplied
the active class to any of them, and the whole form looked empty. Fix
(`lib/multi-step.js`, rewritten 2026-07-20): step-navigation state
(current index) now lives on `form.__aaeStepState`, not a closure over
stale DOM references, and a new exported `resyncSteps(form)` always
re-queries fresh step nodes and reapplies active/hidden state — cheap and
idempotent, so it's safe to call unconditionally. `form.js`'s existing
editor-only 1-second polling loop (added for the preset-apply gotcha
below) now calls `resyncSteps()` on EVERY tick for EVERY already-bound
multi-step form, not just unbound ones — this is what actually heals a
form after Elementor repaints its steps out from under it. Verified with
a regression test that flips an intentionally UNRELATED setting
(`behavior`, not `step_nav_auto`) and confirms the form stays visible —
proving the fix isn't specific to one switch, it's general re-render
resilience.
- **Icon**: `Svg_Src_Prop_Type` + `Svg_Control` (`icon` prop) — empty by
  default, Twig falls back to a built-in inline chevron (`→` for Next,
  rendered AFTER the text; `←` for Previous, rendered BEFORE the text).
  Uploading an SVG via the Icon control replaces the chevron. Same
  empty-prop-falls-back-to-glyph pattern as
  `class-aae-a-offcanvas-trigger.php`. Icon sized `1em` in the twig so it
  scales with the button's own Font Size style, no separate icon-size prop.

**Step-change animation (added 2026-07-20, rebuilt CSS-only later the same
day)** — plays when Next/Previous change the active step. Originally used
GSAP as a deliberate, explicit exception to Motion policy's "no GSAP for
core form submit" rule (at the user's direct request, "gsap use koro"),
then was **fully removed and rebuilt as a pure CSS transition** (also
user-requested: "use css animation, remove gsap , it still has issue") —
there is no GSAP dependency anywhere in the Form Builder now, and Motion
policy's "no GSAP" rule holds without exception.

- **Per-step selectable**, not a global form setting: `step_transition`
  prop on `e-aae-a-form-step` (enum `none` / `fade` / `slide` /
  `fade-slide`, default `fade-slide`) — read via
  `data-aae-form-step-transition` on the ARRIVING step (see
  `aae-a-form-step.html.twig`), so a wizard can mix effects per step (e.g.
  a punchier slide on most steps, `none` on a review step for instant
  feedback). Direction (`+1` Next / `-1` Prev) is inferred at runtime, not
  stored — Next always reads as moving forward, Previous as moving back,
  regardless of which step's transition is playing.
- **Implementation**: `animateStepChange()` in `lib/multi-step.js` sets the
  FROM state on the incoming step's inline styles (`transitionDuration`,
  `opacity`, `transform: translateX(...)`), forces a reflow
  (`toStep.offsetHeight`), then flips to the TO state one `requestAnimationFrame`
  later (double-rAF, so the browser commits the FROM state as a real paint
  before starting the transition — a same-frame write would get coalesced
  into a single paint and never animate). Completion is detected via a
  `transitionend` listener scoped to `toStep`'s `transform`/`opacity`
  properties, with a `setTimeout` safety net (`TRANSITION_MS + 120`) in case
  `transitionend` never fires (hidden tab, ancestor `display:none` mid-
  transition, etc.) — the form must never get stuck mid-transition forever.
  `prefersReducedMotion()` and `step_transition === 'none'` both fall back
  to an instant class-toggle swap, calling `onDone()` synchronously.
- **CSS side** (`form.scss`): `transition-property: transform, opacity` is
  scoped to `form.aae-form-step-anim-run .aae-form-step-active` — a class
  added by the runtime only for the animation's duration (removed on
  finish) — specifically so a step's transform/opacity never transitions
  outside an explicit Next/Previous action. This matters because
  `resyncSteps()`'s instant reconciliation (editor polling, GOTCHA #2)
  toggles the SAME `aae-form-step-active` class on unrelated repaints; if
  the transition-property applied unconditionally, every poll-tick
  class-toggle would visibly animate too.
- **Layout containment during the transition**: both the outgoing and
  incoming step are briefly `aae-form-step-active` at once (so they can
  cross-fade/slide against each other), which would otherwise fight the
  form's own `flex-direction: row; flex-wrap: wrap` base style (two
  flex-item steps visible → layout jump/height jump for the transition's
  duration). Fixed with a transition-scoped class pair in `form.scss`:
  `form.aae-form-step-transitioning` (added/removed only for the
  animation's duration) pins the OUTGOING step `position: absolute`
  (`:not(.aae-form-step-incoming)` — the incoming one stays in normal
  flow, so the form keeps the arriving step's real height throughout,
  never collapsing to 0). Both classes are removed in `finish()` (the
  `transitionend`/safety-timer completion handler), at which point
  `resyncSteps()` also runs to reconcile nav-button hidden-state/labels —
  normal flex flow resumes immediately.

**GOTCHA #3 — the outgoing step visibly overlapped/misaligned with the
incoming one during the cross-fade (reported live on a real page,
2026-07-20).** Root cause: the FIRST version of the containment fix above
set `top: 0; left: 0; width: 100%` in plain CSS on the outgoing step —
those percentages/offsets resolve against the form's own BORDER box once
`position: absolute` applies, but the form has `padding: 20px` in its base
style, so the outgoing step's edges landed 20px further out than its
sibling (which stays in normal, padding-respecting flex flow). Confirmed
via Playwright: outgoing step measured `1110px` wide starting at the
form's left edge while the incoming one was `1070px` inset by 20px — a
visible edge mismatch during the overlap window, exactly matching the
"overlap/conflict" report. **Fix**: `animateStepChange()` captures the
outgoing step's REAL `getBoundingClientRect()` a moment before switching
it to `position: absolute` (while it's still in normal flow) and sets
`top`/`left`/`width` inline from that, converted to form-relative
coordinates — pixel-perfect regardless of the form's padding/border/gap,
and immune to future Style-panel edits. `form.scss`'s rule now only sets
`position: absolute` itself, nothing else. Verified with a Playwright
regression across desktop/tablet/mobile: outgoing/incoming widths match
to 0.00px at every sampled frame of the transition; the LEFT position
legitimately drifts during a `slide`/`fade-slide` transition — that's the
animation's own intended motion (the CSS `translateX` offset), not a bug,
and settles back to the exact pre-transition position once the transition
completes (confirmed: `opacity: 1`, `position: relative` — fully reset;
Chromium reports the resting `transform` as the identity matrix
`matrix(1, 0, 0, 1, 0, 0)` rather than the literal string `none` on an
element with a reachable `transition-property: transform` — visually
identical, not a residual transform).

**GSAP removed entirely (2026-07-20, same day, user-requested: "use css
animation, remove gsap , it still has issue")** — after the GOTCHA #3 fix
above, the user asked for GSAP to be dropped in favor of pure CSS despite
the fix being verified. `getGsap()`/`gsap.timeline()`/`gsap.set()` were all
deleted from `lib/multi-step.js`; `class-atomic.php`'s `aae-a-form` entry
no longer declares `'gsap'` in `script_deps`. The rect-capture fix from
GOTCHA #3 (form-relative `top`/`left`/`width` on the outgoing step) was
KEPT as-is — it's independent of which animation engine drives the
transition. Re-verified with the same Playwright regression (desktop/
tablet/mobile): 0.00px width drift, no horizontal overflow, correct
final-state reset, and a real mid-flight opacity/transform sample
confirming the CSS transition actually runs (not an instant swap).
- `resyncSteps()` (the editor re-render healing path, GOTCHA #2 above)
  deliberately NEVER animates — it's the instant, idempotent
  ground-truth reconciliation the polling loop calls every tick, and must
  stay side-effect-free w.r.t. motion or every poll tick would replay a
  transition. Only the explicit `goNext()`/`goPrev()` user-action paths
  call `animateStepChange()`.
- Both new/changed files: `lib/multi-step.js` (rewritten twice — first to
  add GSAP, then to remove it in favor of `transitionend` + inline
  `transform`/`opacity` styles), `class-aae-a-form-step.php`
  (`step_transition` prop + `Select_Control`), `aae-a-form-step.html.twig`
  (data attr), `form.scss` (containment rules + the scoped
  `transition-property` declaration), `class-atomic.php` (no longer
  declares a `gsap` script dependency for `aae-a-form`).
- Both preset JSONs (`multi-step-contact.json`, `multi-step-lead.json`)
  were updated to place explicit `e-aae-a-form-next` / `-prev` widgets in
  every step (in an `aae-form-step-nav-row` flexbox), demonstrating the
  real-widget path rather than relying on the fallback.

**GOTCHA #1 — applying a preset to an ALREADY-initialized form never bound
steps via the MutationObserver.** `initForm`'s one-time `aaeFormReady` guard
means `bindStepsFor` (the shared helper wiring `initSteps`) never re-runs
once a form has already initialized — and empirically, Elementor's
canvas re-render after "Apply Preset" does NOT surface the new step nodes
as an observable `childList` mutation on `document.body` (confirmed via a
diagnostic MutationObserver: 87 nodes added during a preset apply, ZERO of
them contained `[data-aae-form-step]` — the existing form container is
reused/repainted, not replaced as a discrete addable node). Fixed with an
**editor-only 1-second polling fallback** (`isEditMode()` gate) that
re-scans `form.aae-a-form` and calls `bindStepsFor` on any not yet
`aaeStepsBound` — safe because `initSteps`/`bindStepsFor` are fully
idempotent and cheap (early-return under 2 steps). The existing
"late-added `select[multiple]`" MutationObserver branch right above this
one in `form.js` was NOT changed/removed — it may or may not have the same
underlying issue, untested; the step-specific late-MutationObserver branch
is ALSO still present (harmless, just apparently never the one that fires)
alongside the polling fallback that actually does the job.

**Schema**: `Schema_Walker::collect()` threads a `$current_step` accumulator
down the recursion (same technique as the pro Conditional Display engine's
`Engine.php::walk_containers()` ancestor walk) — entering an
`e-aae-a-form-step` sets `$step_scope` to that element's raw id and appends
`{key, title}` to a new `$steps` array; every field built while inside that
scope gets a `'step' => (string) $step` entry. The top-level schema gains a
`'steps'` key (empty array for ordinary single-page forms — no migration
needed, no separate is-multi-step flag). Validator/Rest.php were NOT
changed — final submit still re-validates every field regardless of step
(existing safety-net behavior), the new `step` field is additive metadata
only, not yet consumed server-side (no per-step server gate exists — the
non-negotiable requirement is specifically about the FRONTEND Next-button
gate, confirmed sufficient by re-reading the requirement wording).

### Form presets

`inc/AtomicWidgets/Widgets/Form/presets/`: `contact`, `newsletter`,
`all-fields` (every field type, single column), `all-fields-columns`
(same fields, paired into two-column `e-flexbox` rows with
`e-div-block` columns — desktop `width: calc(50% - 8px)` via the
custom-unit trick, mobile variant `width: 100%`; ids re-keyed
`aae-cols-*` so both all-fields presets can coexist on a page).
`all-fields-columns.json` is GENERATED from `all-fields.json` — script:
scratchpad `make-columns-preset.js` pattern (pairs list + row/column
wrappers). Regenerate rather than hand-editing both.

### Hard "do not" rules (SSR §34, consolidated with the PRD's own list)

- Build the full product in one pass.
- Use public `admin-ajax.php` for submissions.
- Modify unrelated widgets/modules, or refactor the whole plugin, while
  building this feature.
- Depend on Elementor Pro form controls.
- Load assets globally — load form assets only on pages/previews that
  actually have an AAE Form (reuses
  [On-demand asset loading](#on-demand-asset-loading)).
- Add GSAP to MVP submit.
- Run external actions (email/webhook/Sheets) synchronously inside the
  submit request, and never let one of them failing delete or lose an
  already-saved submission.
- Make validation messages removable widgets.
- Store raw IP by default — only with explicit admin opt-in.
- Hide security failures behind a generic "success" in admin logs — log the
  real reason for admins even when the visitor sees a safe generic message.

---

## AAE Popup — global popup system (PRO, AtomicV4, shipped 2026-07)

Site-wide popup system for atomic elements, lives in the PRO plugin:
`animation-addons-for-elementor-pro/inc/AtomicV4/Popup/` +
`inc/AtomicV4/Support/TriggerMap.php` +
`src/modules/atomic-v4/popup{,-editor,-settings-core,-list}.js`. Full
design/data contract:
`animation-addons-for-elementor-pro/docs/atomic-v4/popup-developer.md`.
**Feature-frozen as of 2026-07-16** — consolidation only (tests,
performance, simplification, docs); no new popup features without an
explicit product decision.

Key facts (read the doc before touching):

- Popups are **AAE Builder templates** (this plugin's `wcf-addons-template`
  CPT), template type `aae-popup` ("Popup — Atomic") registered via the
  existing `wcf_builder_template_types` filter. Distinct from the legacy v3
  `popup` type — do not merge them.
- Trigger = "AAE Popup" panel section (`aae_v4_popup_{enabled,action,target}`
  props) on the canonical atomic types; renders NO custom attrs — configs go
  into `window.AAE_V4_POPUP` keyed by `data-interaction-id` via `TriggerMap`
  (atomic widgets are Twig-rendered, custom wrapper attrs are impossible).
  `TriggerMap` is the shared engine a future Click Reveal reuses.
- **Atomic CSS for footer-rendered documents does NOT happen by itself**
  (Elementor Pro's own popup is broken this way — elementor#35397).
  `Popup/Registry.php` fixes it two ways: Tier 1 re-fires
  `do_action('elementor/post/render', $popup_id)` during the host post's
  render pass (mirrors core's Components module; cached via `Cache_Validity`
  root key `aae-v4-popup-refs`); Tier 2 prints inline CSS at `wp_footer` via
  the public `Styles_Renderer` for theme-builder/archive contexts. Any new
  feature that renders an Elementor document outside the main loop needs the
  same treatment.
- wp_footer order: popups print at prio 3, trigger maps at prio 5, scripts 20.
- **One shared settings modal, three entry points:** the premium tabbed
  modal (`popup-settings-core.js`, shared ES module) is the ONLY chrome
  editing UI — opened from the editor's floating button
  (`popup-editor.js`, editor-only seams via `opts`), the popup-list row
  action, and the classic edit screen's metabox launcher button (both via
  `popup-list.js` + `AAE_V4_POPUP_LIST`). The old metabox form is gone.
- **Every popup asset has a tracked SOURCE** (`assets/css/` and
  `assets/build/` are gitignored): modal CSS source is
  `src/modules/atomic-v4/popup-settings-modal.css` (CSS-only webpack
  entry); frontend chrome CSS source is `src/scss/atomic-v4-popup.scss`
  (gulp). After a pull: `npm run build` + `npx gulp buildCss`. A
  hand-authored file directly under `assets/` will be missing on every
  other machine (caused the "modal styles break on another PC" bug).
- Animation: 4 CSS presets (stagger/blur/spring/pop) + none; per-popup
  `anim_duration` (150–900 ms) / `anim_easing` (smooth/spring/ease) ride
  as inline `--dur`/`--ease` vars; runtime close-fallback reads `--dur`.
- Usage tab (list/metabox modal): on-demand host scan
  (`Registry::hosts_of_popup` — SQL LIKE prefilter matching BOTH target
  shapes, query `"id":{"$$type":"number","value":N}` AND string
  `"aae_v4_popup_target":{"$$type":"string","value":"N"}`, verified by
  `parse_post_refs`, revisions excluded) — NOT the volatile render cache.
- Teardown pattern (leak fixes 2026-07-16): timers/listeners armed by an
  open/close cycle must be cancellable by the next cycle —
  `cancelCloseFinish()` in popup.js, `psetCloseTimer` in
  popup-settings-core.js, `disposeEditorHooks()` in popup-editor.js.
- Verified end-to-end via WP-CLI selftest + Playwright; regression suite:
  `E:\Local Testing\run-popup-sweep.mjs` runs all `verify-popup-*.mjs`
  (~19 tests). Suite conventions (learned from a fixture-drift sweep,
  2026-07-16 — keep them when writing new tests): tests SET their own
  fixture state up front (`set-popup-chrome.php`,
  `set-popup-auto-fixtures.php`, `make-host-page.php`) instead of assuming
  it; list-screen navigation uses `edit.php?...&s=<popupId>` (the popup's
  title must contain its ID — pagination breaks bare list URLs as junk
  templates accumulate); every page sets
  `page.setDefaultTimeout(120_000)` (slow editor boots; also rescues the
  `waitForFunction(fn, {timeout})` misuse — options is the 3rd arg, a
  2nd-arg object is silently treated as `arg` leaving the 30s default).
- Fixtures on the dev site: popup 8244 ("AAE Popup Selftest #8244") +
  page 8245; popup 9074; auto popups 8304/8305/8306 + page 8303; popup
  8662 (atomic ✕). Trigger-host pages are GENERATED by
  `make-host-page.php <popupId> <slug> <heading>` (idempotent by slug):
  9161 `aae-popup-scan-host`→9074, 9265 `aae-close-host`→8662. The helper
  wraps the heading in an `e-div-block` (bare root widgets don't render)
  and uses the `html-v3` title envelope (a raw string title renders
  empty); host page titles must never contain the trigger heading text
  (the theme's H1 steals `text=` clicks in Playwright).

## Elementor core reference (Atomic v4 architecture)

A full clone of the official Elementor development repo lives at:

```
e:\Local Sites\app\public\wp-content\plugins\elementor-src-repo\
```

- **What it is:** `github.com/elementor/elementor`, branch `main` — the
  development version, generally ahead of the installed runtime plugin
  `elementor/` (installed is 4.2.0 as of 2026-07-21 — this is when `e-grid`
  shipped in core and became live, see [Adding a new effect](#adding-a-new-effect)'s
  `e-grid` mentions). `git log` / `git pull` it to track upcoming
  architecture. This is the source of truth for **Atomic v4** APIs.
- **Where to look:**
  - PHP atomic core: `modules/atomic-widgets/` — prop types
    (`prop-types/*.php`), the style schema (`styles/style-schema.php`),
    style transformers (`props-resolver/transformers/styles/`), element
    base classes (`elements/base/`), styles manager + base-styles cache
    (`styles/atomic-styles-manager.php`, `styles/atomic-widget-base-styles.php`).
  - Editor (TS/React): `packages/packages/core/*` — `editor-canvas`,
    `editor-editing-panel`, `editor-styles-repository`, `editor-elements-panel`, …
  - Docs: `docs/`, `AGENTS.md` at repo root.
- **When an atomic API question comes up** (valid style keys, prop-type
  shapes, base-style pipeline, why a definition silently fails), read the
  answer HERE first instead of guessing. Example: "why doesn't my
  `define_base_styles()` work" → `styles/style-schema.php` shows there is
  no `flex-grow`/`flex-shrink`/`flex-basis` key, only the `flex` shorthand
  (`Flex_Prop_Type`), and `height` must be a `Size_Prop_Type` — one invalid
  key fails the whole definition silently.
- **Style-key/format quick answer:** the complete verified tables (every
  valid key, its prop type, every enum value, generate() shapes for
  Background/Dimensions/Border_Radius/Flex/gap, and the list of keys that
  DON'T exist) are pre-digested in
  `animation-addons-for-elementor-pro/docs/atomic-v4/atomic-style-schema-reference.md` —
  check there before grepping the schema.
- **Caveat:** the RUNTIME is the installed `elementor/` plugin, which is
  older than this repo. Before using an API found here, confirm it exists
  in the installed version too.
- **Search the editor JS, not just the PHP.** A surprising amount of Elementor's
  behaviour has no PHP seam at all and is owned entirely by the editor client —
  panel visibility, panel layout, canvas state. "No PHP filter exists" is never
  the end of the search; grep `assets/dev/js/editor/` here (readable sources)
  and `plugins/elementor/assets/js/editor.js` (what actually ships) before
  concluding something is impossible or needs an expensive workaround. Real
  case: hiding widgets from the panel looked like a 70-file codemod from the PHP
  side and turned out to be ~20 lines of JS using Elementor's own pattern — see
  the DANGER box under [Animation Settings](#animation-settings-v4-dashboard-vs-the-v3-legacy-surface).
  Check call ORDER too, not just hook existence: a filter that fires after the
  thing you want to influence is as useless as no filter.

---

## Quick reference

### To add a control to an existing effect
1. Schema.php — add const + prop registration
2. Controls.php — add control to the relevant section
3. Render.php — add data-attr in the relevant `build_*_attrs()` method
4. editor-bridge/features.js — add to the feature's `attrMap`
5. Effect JS — read the new data-attr in `read*()`

### To add a new widget type to an existing effect
1. Controls.php — add type to the `in_array()` check in `inject_controls()`
2. Render.php — add type to the `in_array()` check in `inject_into_html()`
3. editor-bridge/features.js — add type to the feature's `widgetTypes`

### To turn auto-replay on/off for an effect
- Set `autoReplaySetting` to the Boolean prop name in `features.js` (e.g.
  `'aae_text_enable_editor'`) to enable, or `null` to disable.

### To build the AAE Form Builder
See [AAE Atomic Form Builder (planned — new widget family)](#aae-atomic-form-builder-planned--new-widget-family).
Follow the **Widgets/** container+children pattern, not the effect recipe —
implement one milestone at a time from the SSR-doc order listed there. Full
spec: `C:\Users\UseR\Documents\atomic-form\AAE_Atomic_Form_Builder_Claude_PRD_SSR_Pack\`.

### Local vs. remote presets — bundled files are a fallback, not the source
Presets come from a **remote server** (`Remote_Client`, `crowdytheme.com` — not
an animation-addons host, which matters when you simulate an outage) **merged**
with whatever local `presets/*.json` are bundled. Merge, not fallback-only: a
local file for a type the remote doesn't know still shows up. The stated
direction of travel is remote-only, with local files deleted over time.

Consequences worth knowing before adding a JSON:

- **A local copy of a preset the remote ALREADY serves would list the design
  twice.** Ids can't collide by construction (`remote-<n>` vs a filename slug),
  so ids can't detect it either. `Cache::drop_shadowed_remote()` removes the
  overlap by slug of the *name* — the only field both halves share — so a
  deliberate local copy must be **named after the remote preset it mirrors**, or
  it is treated as a different design and shown alongside it.
- **On an overlap the LOCAL file wins**, inverting the usual remote-first rule.
  A bundled file ships with the plugin and is version-matched to it — the
  Progress Bar presets reference `e-aae-a-progressbar-{dot,fill,label}`, part
  widgets the remote copies predate. Preferring remote would mean the *applied*
  preset is the older one on every online site, i.e. always. Cost: a genuinely
  improved remote preset stays masked until the local file is deleted — which is
  the intended lifecycle, and needs no code change.
- Only five widgets bundle local presets: Accordion, FlipBox, ImageCompare,
  LoopGrid, StackCards — plus Progress Bar's three
  (`progressbar-{circle,dot,line}.json`), which are local copies of remote
  presets rather than local-only designs.
- `is_dev_environment()` (any `.local` host) bypasses the transient entirely, so
  a dev site hits the network on every panel open — see the "Presets control
  vanishes" row in [Common breakage points](#common-breakage-points).

### Never put a functional hook class in the `classes` prop
Elementor's panel resolves **every** entry in an element's `classes` prop
against the style repository and reports anything unknown as *"Some classes are
missing"* (`useMissingClassesIds`, editor-editing-panel/components/css-classes).
A plain CSS/JS hook class is never a registered style, so it always lands there
— and **the alert's dismiss (✕) button calls `unapplyClasses()` on exactly those
ids**, silently stripping the hook. One click can kill a widget's JS.

So a class that CSS or JS selects on must be rendered by the element's own
twig, never seeded into `classes`:

```twig
{%- set classes = ['aae-progressbar-dot', base_styles.base] | merge( settings.classes | default([]) ) | join(' ') -%}
```

That is only possible for AAE-owned element types, which is a real reason to
give a composite widget's parts their own types instead of reusing native
`e-div-block`/`e-paragraph`. Progress Bar does this (Track/Fill/Label/Dot, all
`is_internal`), and its presets were rebuilt onto those parts — a fresh drop and
every preset now report **zero** missing classes.

**Image Compare is the counter-example and still open**: its parts are native
`e-image`/`e-divider`/`e-paragraph`, whose twigs Elementor owns, so its 7 hook
classes (`aae-a-image-compare-before`, `-thumb`, `-caption-*`, …) can't move and
are all one ✕ away from being unapplied. Fixing it means giving it real part
widgets too.

Measure any widget with `E:\Local Testing\probe-missing-classes.mjs`, which
reproduces Elementor's own calculation against the live providers.

### To auto-apply a preset when a widget is dropped
Add one entry to `AUTO_PRESETS` in
`src/modules/atomic/editor-bridge/auto-preset.js`, then `npm run build`. Pick
the freshness test by asking whether the preset seeds the same child widget
types as `define_default_children()` — same → `defaultMarker`, different →
`defaultChildren`. Full rules, field table and gotchas:
[Auto-preset](#auto-preset--giving-a-widget-a-default-preset-on-drop).

### To add presets to a NATIVE atomic widget (e-heading, e-button, …)
No code needed — drop JSONs in a folder named after the element type:
```
inc/AtomicWidgets/Presets/<element-type>/*.json   ← folder name IS the key
```
- `Atomic\Presets\Controls` (inc/Atomic/Presets/) injects the "Presets"
  section via `elementor/atomic-widgets/controls` for any native type that
  has ≥1 JSON there; `class-atomic.php::get_widget_presets()` scans the
  same folders and keys each preset by the folder name (no
  `detect_primary_widget_type()` detection — native types aren't `e-aae-a-*`).
- JSON format: same two formats as AAE-widget presets (Elementor native
  export with a flex wrapper preferred). Reload the editor — no build.
- **Model shape:** native atomic widgets inside the JSON must keep the
  export shape `{ "elType": "widget", "widgetType": "e-heading", … }`
  (exports produce this automatically). Do NOT hand-convert to
  `"elType": "e-heading"` — atomic containers' `getChildType()` whitelists
  `'widget'`, not the native atomic type names, so the atomic shape makes
  the v1 `addElement` delegate into a widget view and crash
  (`addElement is not a function` → apply silently does nothing).
- AAE's own widgets (`e-aae-a-*`) are deliberately skipped by the injector;
  they place the section themselves in `define_atomic_controls()` and keep
  their JSONs in `Widgets/<Name>/presets/` (see the add-widget-presets skill).
- **Animated presets** (keyframes, ::before/::after layers, descendant
  :hover — things atomic styles can't express): bake the CSS into the
  preset via the AAE **Custom CSS extension** props on the element that
  owns the effect — `aae_custom_css_enable: {$$type:'aae-rj',
  value:{desktop:true}}` + `aae_custom_css_css: {…value:{desktop:'<css>'}}`.
  Use the literal token `selector` in the CSS; the runtime replaces it with
  `[data-interaction-id="<id>"]`, so `selector:hover .child {…}` and
  top-level `@keyframes` both work. To target a child, give it a plain hook
  class in `classes` (e.g. `aae-team-overlay`) — hook classes survive style
  id regeneration. NO .css files, no Preset_Styles entry. References:
  `e-button/shine-pulse.json`, `e-flexbox/team-card.json`.
