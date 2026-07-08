# Loop Grid presets — authoring pattern

This folder holds **design presets** for the Loop Item / Loop Grid atomic
widgets. Each `*.json` file is one reusable design. The preset loader
(`class-atomic.php::get_widget_presets()`) auto-scans every widget's
`presets/` folder, so **dropping a JSON file here is all it takes** — no code
change, no registration.

Presets are exposed to the editor as `window.AAE_WIDGET_PRESETS`, keyed by the
primary atomic widget type inside the model. The Preset Picker control lists
the presets for the selected element and, on pick, inserts the model (IDs
regenerated automatically).

---

## The pattern: how to add a new preset

The whole point is that this is repeatable. To turn any design into a preset:

### 1. Build the design in the editor

Compose it as a **Loop Item** (or a flexbox wrapping the target widget). Use
atomic-native props for everything you can (Style panel). For per-post content
use dynamic tags (Featured Image, Post Title, Post Date, …).

### 2. Export it

Either:

- **Elementor native** — right-click the element → Export, save the `.json`.
  Format is `{ "content": [ <model> ], "title": "...", "type": "..." }`.
- **Plugin format** — `{ "name": "...", "model": { <model> } }`.

Both are accepted by the loader. `content[0]` (native) or `model` (plugin) is
treated as the preset model.

### 3. Drop the JSON in a `presets/` folder

Put it under the widget whose `presets/` dir you want it scanned from — for a
loop card that's this folder (`Widgets/LoopGrid/presets/`). The loader keys the
preset by the first AAE atomic widget found inside the model
(`detect_primary_widget_type`), so a card whose root is `e-aae-a-loop-item`
shows when a Loop Item is selected.

### 4. (Interactions) ship the CSS, not custom_css

Atomic per-element `:hover` variants **cannot reach descendants**
(`.card:hover .image`), and `custom_css` is **Pro-gated** (stripped for
Elementor Pro < 3.35 in `atomic-widget-styles.php::remove_custom_css_from_styles`).

So any parent-hover → child-effect interaction is done with **marker classes +
a shipped stylesheet**:

- Add marker classes to the model elements' `settings.classes.value`
  (e.g. `aae-hover-card` on the item, `aae-hover-img` on the image,
  `aae-hover-tint` on the overlay).
- Put the rules in `assets/css/aae-presets.css` (enqueued on frontend + editor
  preview by `inc/Atomic/Assets.php::enqueue_preset_styles`).

This keeps the interaction portable: the preset JSON carries the marker
classes, the stylesheet carries the behaviour, and both ship with the plugin.

---

## Conventions

| Thing | Convention |
|---|---|
| File name | kebab-case, describes the design (`image-cover-card.json`) |
| `title` | Human label shown in the picker ("Image Cover Card") |
| Marker classes | `aae-hover-<role>` — hook for `aae-presets.css` |
| Atomic style IDs | `e-<id>-<hash>` (regenerated on insert; collisions are harmless) |
| Per-post content | dynamic tags in `settings`, never hard-coded values |

---

## Remote presets (planned)

The JSON files are **self-contained and source-agnostic**, so moving the
catalog to a remote server later is a loader swap, not a format change. The one
place to change is `class-atomic.php::get_widget_presets()`:

- **Today:** it globs local `presets/*.json` per widget.
- **Later:** fetch the same JSON envelopes from the remote endpoint (cache the
  response, fall back to bundled files offline), then feed them through the
  identical `content[0] | model` → `detect_primary_widget_type` → keyed-array
  path. Nothing downstream (the localized `AAE_WIDGET_PRESETS`, the picker
  component) needs to change.

Keep authored presets in this format so they stay drop-in whether served from
disk or from the remote catalog.

---

## Presets in this folder

| File | Design | Primary widget | Interaction |
|---|---|---|---|
| `image-cover-card.json` | Featured-image cover card: image fills the card, bottom gradient, date/author/title over it | `e-aae-a-loop-item` | Hover: image zoom + darken, brand-color tint (`aae-hover-*` → `aae-presets.css`) |
