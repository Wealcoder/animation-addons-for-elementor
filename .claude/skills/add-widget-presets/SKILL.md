---
name: add-widget-presets
description: Add the "Apply Preset" template-library control to an AAE atomic widget (e.g. button, advanced-heading). Wires a custom element-control into the editing panel that lists bundled preset JSONs for the selected widget type and, on pick, replaces the selected element in place with the preset design — unwrapping a flex/container wrapper, regenerating style ids, sanitizing image-src, and re-selecting the new element. Reuses the shared PresetPickerControl; per-widget work is the PHP control stub, the panel section, and the preset JSON files.
---

# Add a preset library to an atomic widget

This skill encodes the full pipeline for giving an AAE **atomic widget** a
"Presets" picker — a dropdown in the editing panel that lists bundled
design variations and, on pick, swaps the selected element for the
preset's design (settings + styles + children).

The runtime control (`PresetPickerControl.jsx`) is **already shared and
generic** — it keys off the selected element's type and reads a global
preset map. So most of the work is per-widget: a tiny PHP control stub,
one panel section, and the preset JSON files. The heavy JS/PHP plumbing
(scan presets → localize → register control → apply/replace) is done once
and reused.

**Read `CLAUDE.md` at the project root first** for the atomic-widget file
map and conventions. The Advanced Heading widget
(`inc/AtomicWidgets/Widgets/AdvancedHeading/`) is the reference
implementation — copy its shape.

> **NATIVE atomic widgets (e-heading, e-button, e-image, …) are already
> wired — zero code.** We don't own their classes, so the per-widget steps
> below don't apply. Instead:
> - Drop JSONs (same two formats) into
>   `inc/AtomicWidgets/Presets/<element-type>/*.json` — the **folder name
>   is the key** (e.g. `Presets/e-heading/`). Reload the editor; no build.
> - `Atomic\Presets\Controls` (`inc/Atomic/Presets/`) injects the
>   "Presets" section via the `elementor/atomic-widgets/controls` filter
>   for any native type with ≥1 JSON bundled; the scanner keys those
>   presets by folder name (native types aren't `e-aae-a-*`, so
>   `detect_primary_widget_type()` can't detect them).
> - It deliberately skips `e-aae-a-*` types — AAE widgets use the
>   per-widget steps below.
>
> The rest of this skill is the **AAE-widget** path.

---

## How it works (the shipped architecture)

Five cooperating pieces. Items marked **[shared]** already exist — do not
re-create them. Items marked **[per-widget]** are what this skill adds.

```
┌──────────────────────────────────────────────────────────────────────┐
│ 1. PHP control stub  [per-widget]                                     │
│    class-aae-a-preset-picker-control.php                              │
│    Element_Control_Base subclass; get_type() === 'aae-preset-picker'. │
│    Carries NO prop value — it's an action control.                    │
├──────────────────────────────────────────────────────────────────────┤
│ 2. Panel section     [per-widget]                                     │
│    In the widget's define_atomic_controls(): a "Presets" Section      │
│    holding AAE_A_Preset_Picker_Control::make().                       │
├──────────────────────────────────────────────────────────────────────┤
│ 3. Preset JSON       [per-widget]                                     │
│    Widgets/<Name>/presets/*.json — Elementor native exports.          │
├──────────────────────────────────────────────────────────────────────┤
│ 4. Preset scan+localize  [shared]                                     │
│    class-atomic.php::get_widget_presets() scans every widget's        │
│    presets/ dir, keys each by its primary atomic widget type, and     │
│    localizes window.AAE_WIDGET_PRESETS for the editor.                │
├──────────────────────────────────────────────────────────────────────┤
│ 5. Runtime control   [shared]                                         │
│    element-controls/PresetPickerControl.jsx (registered under         │
│    'aae-preset-picker' in element-controls/index.js). Reads the       │
│    global map, lists presets for the selected type, applies on pick.  │
└──────────────────────────────────────────────────────────────────────┘
```

### The apply algorithm (already implemented in PresetPickerControl.jsx)

On picking a preset, the control:

1. Resolves the selected element's **parent container + index** (via the
   V1 container model, not the DOM).
2. **Unwraps**: if the preset root is a container (`e-flexbox`,
   `e-div-block`, `e-grid`, `container`), its `elements` become the
   models to place; otherwise the root itself.
3. For each model: deep-clones, `delete model.id` (Elementor mints fresh
   ids on create — provided ids are ignored), regenerates **local style
   ids** + rewrites `classes` refs (no style-id collisions on repeat
   apply), and **sanitizes image-src** (drops `url` when both `id` and
   `url` are present — Elementor's XOR rule, else save fails with
   `background: invalid_value`).
4. Calls `createElements({ elements: [...] })`. This executes immediately
   and returns `{ createdElements: [...] }`, each carrying the real
   `containerId`.
5. `removeElements({ elementIds: [originalId] })` — replace in place.
6. `selectElement(createdElements[0].containerId)` — keep the panel on a
   valid target (otherwise removing the original leaves nothing selected
   and the panel goes blank).

You almost never touch this file. The exception is widening
`CONTAINER_TYPES` or changing apply semantics — see "Extending the shared
control" below.

---

## Before you start

Confirm with the user:

1. **Target widget** — which atomic widget gets the picker? Needs:
   - its directory: `inc/AtomicWidgets/Widgets/<Name>/`
   - its element type, e.g. `e-aae-a-advanced-heading` (from
     `get_element_type()`). This is the **key** presets are grouped by.
2. **Where in the panel** — which tab/section. Convention: a "Presets"
   section at the **top** of the General tab.
3. **The presets themselves** — does the user have exported JSONs ready,
   or are we authoring designs first? Each preset is one Elementor export
   (a flex container wrapping the design is the norm).

If presets aren't ready yet, you can wire the control with zero presets —
it renders nothing (`if (!presets.length) return null`) until JSONs land.

---

## The steps

### Step 1 — Confirm the shared pieces exist

These ship already. Verify before adding per-widget code:

- `src/modules/atomic/element-controls/PresetPickerControl.jsx` exists.
- `element-controls/index.js` registers it:
  `{ type: 'aae-preset-picker', component: PresetPickerControl, layout: 'full' }`.
- `class-atomic.php` has `get_widget_presets()` +
  `detect_primary_widget_type()` and localizes `AAE_WIDGET_PRESETS` in
  `enqueue_atomic_editor_scripts()`.
- `editor-bridge.js` calls `registerAaeElementControls()`.

If any is missing, port it from the current tree — but normally they're
all present (the Advanced Heading rollout added them).

---

### Step 2 — PHP control stub  [per-widget]

**File:** `inc/AtomicWidgets/Widgets/<Name>/class-aae-a-preset-picker-control.php`

Copy verbatim from `references/class-aae-a-preset-picker-control.php`,
changing **only the namespace** to the widget's namespace.

```php
<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\<Name>;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAE_A_Preset_Picker_Control extends Element_Control_Base {
    public function get_type(): string {
        return 'aae-preset-picker';   // MUST match the JS registry key
    }
    public function get_props(): array {
        return [];                    // action control — no stored value
    }
}
```

> The class can be duplicated per widget (each in its own namespace), or
> you can hoist a single shared class into a common namespace and `use` it.
> The reference implementation duplicates per widget — simplest, no
> autoload concerns. `get_type()` must be `'aae-preset-picker'` in every
> copy so they all resolve to the same React component.

---

### Step 3 — Panel section  [per-widget]

**File:** the widget's main class, in `define_atomic_controls()`.

`require_once` the control stub, then add a Presets section. Put it
**first** so it reads as a starting point, and give it a stable id.

```php
protected function define_atomic_controls(): array {
    require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

    return [
        Section::make()
            ->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
            ->set_id( 'aae_presets' )
            ->set_items( [
                AAE_A_Preset_Picker_Control::make()
                    ->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
                    ->set_meta( [ 'layout' => 'custom' ] ),
            ] ),

        // … the widget's existing sections …
    ];
}
```

**Notes:**
- `set_meta( [ 'layout' => 'custom' ] )` tells the panel this item is a
  custom-rendered control, routed to the registered React component.
- The section renders **collapsed by default** in the atomic panel. That
  is expected — the user expands it to reach the picker. If the user wants
  it open by default, that's a panel-section concern, not this control;
  flag it but don't hack the control to force-open.
- To show the picker on **General tab only** (the convention), this
  section being a plain top-level Section already lands in General. Don't
  add it under Style/Interactions tab groups.

---

### Step 4 — Preset JSON files  [per-widget]

**Dir:** `inc/AtomicWidgets/Widgets/<Name>/presets/`

One `.json` per preset. Two accepted formats (the scanner handles both):

**Format B — Elementor native export (preferred).** Export a flex
container wrapping the design from the editor; drop the file in as-is:

```json
{
  "content": [ { "elType": "e-flexbox", "elements": [ /* the design */ ] } ],
  "title": "Gradient CTA",
  "type": "e-flexbox",
  "version": "0.4"
}
```
- `content[0]` is the wrapper used as the preset model; the control
  unwraps it on apply.
- `title` becomes the preset's display name. The dropdown shows it.

**Format A — plugin shape** (hand-authored, no wrapper):

```json
{ "name": "Gradient CTA", "model": { "elType": "e-aae-a-advanced-heading", "elements": [ ... ] } }
```

**Keying:** the scanner calls `detect_primary_widget_type(model)` —
descends a container to the **first `e-aae-a-*` widget** and groups the
preset under that type. So a flex-wrapped advanced-heading preset shows up
when an advanced-heading is selected, not when a bare flexbox is. If your
widget's type prefix isn't `e-aae-a-`, see "Gotchas" below.

**Authoring tips for exports:**
- Build the design in the editor, wrap it in a flexbox, select the
  flexbox, export.
- If a child has an **image background**, the export includes both
  attachment `id` and cached `url`. That's fine — the control's
  `sanitizeImageSrc()` drops the `url` on apply so the save validator
  accepts it. Do **not** hand-strip it from the JSON unless you also want
  it gone visually.
- Element ids in the JSON are placeholders — Elementor regenerates them on
  apply. No need to make them unique by hand.

---

### Step 5 — Build & verify

```bash
npm run build
```

Only needed if you touched JS (you usually don't in this skill — PHP +
JSON only). Preset JSON and PHP changes take effect **without a build**;
just reload the editor. Build only when the shared `PresetPickerControl`
itself changed.

Then verify (see checklist).

---

## Final verification checklist

| Check | Where |
|---|---|
| Section appears | Select the target widget → "Presets" section in the panel (collapsed) |
| Picker renders | Expand "Presets" → "Apply a preset…" select with the bundled names |
| Right presets listed | Only presets keyed to this widget type show; others don't |
| Apply replaces in place | Pick one → the selected element is swapped for the design at the same position |
| Wrapper unwrapped | The flex wrapper is gone; its children sit directly where the element was |
| Base styles travel | Fonts / colors / backgrounds from the preset render in the canvas |
| No style collision | Apply the same preset to two widgets → both keep independent styles |
| Selection persists | After apply, exactly one element is selected; panel is NOT blank |
| Saves on publish | Publish → no "Styles validation failed" / `background: invalid_value` |

**Editor probe** (outer frame console):
```js
window.AAE_WIDGET_PRESETS                       // map: elementType => preset[]
window.elementor.selection.getElements().length // should be 1 after apply
```

---

## Extending the shared control

Touch `PresetPickerControl.jsx` only for cross-cutting changes:

- **New container type to unwrap** → add to `CONTAINER_TYPES`.
- **A new prop type that fails save validation** (like image-src did) →
  add a deep-walk sanitizer mirroring `sanitizeImageSrc()` and call it in
  the apply loop next to the existing sanitize call.
- **Different apply semantics** (e.g. insert-after instead of
  replace-in-place) → change the create/remove pair. The current shape is
  create-children → remove-original → select-first-new.

After any edit here, `npm run build` and re-run the selection +
save checks — both are easy to regress.

## Gotchas

- **Elementor ignores `model.id` on create.** Don't pre-assign element
  ids hoping to select them later — `getContainer(thatId)` won't resolve.
  Capture the real id from `createElements(...).createdElements[i].containerId`.
- **`createElements` runs immediately** and returns `{ createdElements }`.
  It does NOT return a thunk you must call. (`removeElements`,
  `selectElement` are the same family — call directly.)
- **Image-src XOR.** Native exports carry `id` + `url`; the save validator
  rejects both. `sanitizeImageSrc()` handles it on apply — keep it in the
  loop. If you add presets with other media props, check they don't have
  a similar XOR rule.
- **Style ids must be regenerated**, not element ids — `create` does not
  auto-regenerate local style ids (only paste/import/duplicate hooks do).
  `regenerateModelStyleIds()` does this; it also rewrites `settings.classes`.
- **Primary-type detection assumes `e-aae-a-` prefix.** If a widget's type
  doesn't start with `e-aae-a-`, `detect_primary_widget_type()` won't find
  it inside a container and falls back to the container's own type — the
  preset would then key to `e-flexbox` and show on the wrong selection.
  Either name the widget with the `e-aae-a-` prefix, or widen the prefix
  check in `class-atomic.php::detect_primary_widget_type()`.
- **Section collapsed by default** is panel behavior, not a bug.

## Don't

- **Don't DOM-inject the picker** into the panel. Use the registered
  custom control — it's a real `controlsRegistry` entry, survives
  re-renders, and routes through Elementor's panel lifecycle. (An earlier
  DOM-injection approach was removed for being fragile.)
- **Don't add a stored prop** for the picker. It's an action control
  (`get_props()` returns `[]`). A picked preset mutates the tree, it
  doesn't persist a "selected preset" value.
- **Don't hand-edit element ids** in preset JSON to make them unique —
  Elementor regenerates them.
- **Don't strip image `url` from preset JSON** as the fix for save
  failures — the runtime sanitizer is the permanent guard; stripping by
  hand only masks one file and breaks the next export.
- **Don't put the picker under Style/Interactions** — General tab only,
  per convention.
