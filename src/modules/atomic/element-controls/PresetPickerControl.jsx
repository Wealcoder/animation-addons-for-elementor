/* eslint-env browser */

/**
 * PresetPickerControl — the "Presets" element-control for AAE atomic widgets.
 *
 * Registered into Elementor's shared controlsRegistry under the type id
 * 'aae-preset-picker' (see ./index.js) and rendered by the editing panel
 * wherever the PHP side places an AAE_A_Preset_Picker_Control.
 *
 * This is an ACTION control, not a prop-bound control: it carries no stored
 * value. A button opens a Dialog showing every preset for the selected
 * element's type as a branded card grid, grouped by category, each with a
 * larger thumbnail (or the placeholder image) and a PRO badge for premium
 * presets. Picking a card REPLACES the selected element in place with the
 * preset's design (settings + styles + children) via the shared
 * applyPresetModel() engine in preset-apply.js (the same engine
 * editor-bridge/auto-preset.js uses for the drag-drop default preset, so
 * both call sites stay in lock-step), then closes the dialog.
 *
 * Presets are fetched on demand (per element type, on mount) from this
 * plugin's own REST proxy — window.AAE_PRESET_CONFIG.restUrl (aae/v1/presets,
 * see inc/Atomic/Presets/Rest.php) — which merges remote (themecrowdy.com)
 * presets with any bundled local JSON server-side.
 *
 * Dialog/DialogTitle/DialogContent are the same @elementor/ui components
 * FormActionsControl.jsx already uses for its own element-control popup —
 * kept consistent with that existing convention rather than introducing a
 * different modal primitive. The accent color (#ff7a00) matches this
 * plugin's own established brand/upgrade color (see e.g.
 * inc/admin/row-actions.php's "Upgrade to Pro" link), not a new palette.
 */

import * as React from "react";
import { useElement } from "@elementor/editor-editing-panel";
import {
  Box,
  Button,
  Chip,
  CircularProgress,
  Dialog,
  DialogContent,
  DialogTitle,
  IconButton,
  Skeleton,
  Stack,
  Typography,
} from "@elementor/ui";
import {
  applyPresetModel,
  invalidatePresetsForType,
  loadPresetsForType,
} from "./preset-apply";

const UPGRADE_URL = "https://animation-addons.com/";
const BRAND = "#ff7a00";
const BRAND_DARK = "#e35f00";

/**
 * The trigger button's loading (disabled) skin.
 *
 * Same hue as the live button so the control stays recognisable, but flat and
 * quiet so an inert button never looks clickable. Both values are translucent
 * because this button lives in the editor panel, which follows the editor's
 * Dark/Light preference — an opaque pale orange would only read correctly on
 * one of the two.
 */
const BRAND_TINT_BG = "rgba(255, 122, 0, 0.16)";
const BRAND_TINT_INK = "rgba(255, 138, 26, 0.85)";

/**
 * The dialog paints on its OWN light surface rather than the editor theme's
 * `background.paper` / `text.*` tokens. Those tokens follow the editor's
 * Dark/Light preference, so on a dark editor the panel came out near-black
 * while the reference design is a white sheet — the popup is a full-bleed
 * gallery of light-background thumbnails, and it has to read the same either
 * way. Every colour the dialog uses is therefore fixed here.
 */
const SHEET = "#ffffff";
const SHEET_BORDER = "#e6e8eb";
const INK = "#17181a";
const INK_MUTED = "#6b7280";
const THUMB_BG = "#f1f2f4";

/**
 * The card grid. `auto-fill` rather than a fixed column count so the same
 * definition gives the ~5 columns of the reference design at the dialog's
 * full width and degrades to 2 on a narrow panel — the Grid xs/sm/md props
 * it replaces could not express a 5-up row (12 columns don't divide by 5).
 */
const CARD_GRID = {
  display: "grid",
  gridTemplateColumns: "repeat(auto-fill, minmax(170px, 1fr))",
  gap: { xs: 2, sm: 3 },
};

/** Group + sort presets by category for the grouped card grid render. */
function groupByCategory(presets) {
  const groups = new Map();

  presets.forEach((preset) => {
    const category = preset.category || "";
    if (!groups.has(category)) {
      groups.set(category, []);
    }
    groups.get(category).push(preset);
  });

  return Array.from(groups.entries())
    .sort((a, b) => a[0].localeCompare(b[0]))
    .map(([category, items]) => ({ category, items }));
}

/* eslint-disable react/prop-types --
 * Internal-only presentational helpers (not exported, no external callers) —
 * prop-types validation is skipped here the same way the pre-existing
 * `label` prop on the exported PresetPickerControl below is (this file has
 * never used the PropTypes package).
 */
function PresetCard({ preset, placeholderSrc, locked, onSelect }) {
  const src = preset.thumbnail_url || placeholderSrc;

  return (
    <Box
      onClick={() => onSelect(preset)}
      role="button"
      tabIndex={0}
      sx={{
        cursor: "pointer",
        // Borderless — the thumbnail's own tinted panel is the card, so the
        // grid reads as artwork rather than a table of boxed rows.
        "& .aae-preset-thumb": {
          transition: "box-shadow 0.16s ease, transform 0.16s ease",
        },
        "&:hover .aae-preset-thumb": {
          boxShadow: locked ? "none" : "0 6px 18px rgba(16, 24, 40, 0.12)",
          transform: locked ? "none" : "translateY(-2px)",
        },
      }}
    >
      <Box
        className="aae-preset-thumb"
        sx={{
          position: "relative",
          width: "100%",
          pt: "82%" /* the reference card's thumbnail proportion */,
          borderRadius: 1.5,
          overflow: "hidden",
          bgcolor: THUMB_BG,
        }}
      >
        <Box
          component="img"
          src={src}
          alt=""
          sx={{
            position: "absolute",
            inset: 0,
            width: "100%",
            height: "100%",
            objectFit: "cover",
            filter: locked ? "grayscale(0.5) opacity(0.55)" : "none",
          }}
        />
        {locked ? (
          <Box
            sx={{
              position: "absolute",
              inset: 0,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              bgcolor: "rgba(20, 20, 20, 0.15)",
            }}
          >
            <Box
              sx={{
                width: 26,
                height: 26,
                borderRadius: "50%",
                bgcolor: "rgba(255,255,255,0.92)",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                fontSize: "13px",
              }}
            >
              {"🔒"}
            </Box>
          </Box>
        ) : null}
        {preset.pro ? (
          <Chip
            label="PRO"
            size="small"
            sx={{
              position: "absolute",
              top: 6,
              right: 6,
              height: 18,
              fontSize: "9px",
              fontWeight: 700,
              letterSpacing: "0.03em",
              color: "#fff",
              background: `linear-gradient(135deg, ${BRAND}, ${BRAND_DARK})`,
              boxShadow: "0 1px 3px rgba(0,0,0,0.25)",
            }}
          />
        ) : null}
      </Box>
      <Box sx={{ pt: 1.25 }}>
        <Typography
          sx={{
            display: "block",
            overflow: "hidden",
            textOverflow: "ellipsis",
            whiteSpace: "nowrap",
            fontSize: "13px",
            fontWeight: 500,
            lineHeight: 1.4,
            color: INK,
          }}
        >
          {preset.name}
        </Typography>
      </Box>
    </Box>
  );
}
/* eslint-enable react/prop-types */

export function PresetPickerControl({ label }) {
  const { element } = useElement();
  const elementId = element.id;
  const type = element.type || element.model?.get?.("elType");

  const [open, setOpen] = React.useState(false);
  const [presets, setPresets] = React.useState(null); // null = loading
  // The list could not be read in full — a request failure, or a 200 whose
  // `remote_failed` flag says the remote half was unreachable. Kept separate
  // from an empty list because the two must render differently (see below).
  const [failed, setFailed] = React.useState(false);
  const [reloadToken, setReloadToken] = React.useState(0);
  const config = window.AAE_PRESET_CONFIG || {};
  const proActive = !!config.proActive;
  const placeholderSrc = config.placeholderThumb || "";

  React.useEffect(() => {
    let cancelled = false;

    setPresets(null);
    setFailed(false);
    loadPresetsForType(type).then((result) => {
      if (!cancelled) {
        setPresets(Array.isArray(result?.presets) ? result.presets : []);
        setFailed(!!result?.failed);
      }
    });

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [type, reloadToken]);

  const retry = () => {
    invalidatePresetsForType(type);
    setReloadToken((n) => n + 1);
  };

  const applyPreset = (preset) => {
    if (!preset?.model) {
      return;
    }

    // The target type is always the selected element's own type — even when
    // its presets were fetched under an alias (see PRESET_TYPE_ALIASES in
    // preset-apply.js), applyPresetModel rewrites a non-container preset
    // root to `type` so e.g. a slide item stays a slide item rather than
    // becoming the loop-item type its preset was authored against.
    applyPresetModel(preset.model, elementId, type, {
      title: "Preset",
      subtitle: `Applied "${preset.name}"`,
    });
  };

  const handleSelect = (preset) => {
    if (preset.pro && !proActive) {
      window.open(UPGRADE_URL, "_blank", "noopener");
      return;
    }

    applyPreset(preset);
    setOpen(false);
  };

  // Nothing resolved for this type from either remote or local, once loaded —
  // hide the trigger entirely, matching the control's original empty
  // behaviour. This is also the correct end-state once every bundled local
  // .json is eventually removed and a type has no remote presets configured
  // yet either.
  //
  // A FAILED read is deliberately excluded: it is not evidence that the type
  // has no presets, and hiding on it was the reported "preset sometimes
  // disappears" bug — one blip removed the control and, since the failure was
  // also cached, nothing re-fetched it for the rest of the session. Now the
  // trigger stays put and the dialog offers a retry.
  if (presets !== null && !presets.length && !failed) {
    return null;
  }

  const grouped = presets ? groupByCategory(presets) : [];

  // `null` is "the fetch has not settled yet" — distinct from `[]` (settled,
  // nothing for this type) and from `failed`, both of which are actionable and
  // must leave the button live so the dialog can explain / offer a retry.
  const loading = presets === null;

  return (
    <Stack gap={1}>
      <Typography
        variant="caption"
        sx={{ fontWeight: 500, color: "text.secondary" }}
      >
        {label || "Presets"}
      </Typography>
      <Button
        size="small"
        onClick={() => setOpen(true)}
        disabled={loading}
        // The end adornment is never empty — a spinner stands in for the "+"
        // while the fetch is open. It says what the button is doing, and it
        // keeps the icon slot occupied so the two states measure the same
        // (see minHeight below).
        endIcon={
          loading ? (
            <CircularProgress size={11} thickness={5} color="inherit" />
          ) : (
            <span aria-hidden="true">{"+"}</span>
          )
        }
        sx={{
          alignSelf: "flex-end",
          justifyContent: "center",
          alignItems: "center",
          minWidth: 0,
          // FIXED, not content-derived — and `height`, not `minHeight`, which
          // is only a floor. Both the label and the end adornment change when
          // the fetch settles, and the button used to be sized by whatever
          // happened to be inside it: measured 26px while loading and 33px
          // once the "+" glyph arrived, so it visibly grew right where the
          // user is looking. `minHeight` is repeated because MUI's own
          // `size="small"` rule sets one and would otherwise win.
          height: 32,
          minHeight: 32,
          px: 1.25,
          // No vertical padding: with the height pinned, the flex centering
          // below does the work, and padding could only fight it.
          py: 0,
          borderRadius: 1,
          textTransform: "none",
          fontSize: "11px",
          fontWeight: 600,
          lineHeight: 1.5,
          // The default endIcon gutter is sized for a full-height button and
          // reads as a gap at this scale. `lineHeight: 1` keeps the glyph's
          // line box from being the thing that decides the button's height.
          "& .MuiButton-endIcon": {
            ml: 0.5,
            fontSize: "12px",
            lineHeight: 1,
          },
          color: "#fff",
          background: `linear-gradient(135deg, ${BRAND}, ${BRAND_DARK})`,
          boxShadow: "0 1px 3px rgba(227, 95, 0, 0.35)",
          "&:hover": {
            background: `linear-gradient(135deg, ${BRAND_DARK} 0%, ${BRAND_DARK} 100%)`,
            opacity: 0.92,
            boxShadow: "0 2px 6px rgba(227, 95, 0, 0.45)",
          },
          // Loading is an INERT state, so it must not wear the full brand
          // gradient — an un-clickable button painted exactly like a live one
          // is what read wrong here (it also invites a click that does
          // nothing). A soft tint of the same hue keeps the control
          // recognisable while making "not ready yet" obvious.
          //
          // Expressed as a translucent tint rather than a fixed colour on
          // purpose: this button sits in the editor panel, which follows the
          // editor's Dark/Light preference, so a hardcoded pale orange would
          // only work on one of them. (The DIALOG is the opposite case and
          // fixes its colours deliberately — see the SHEET comment above.)
          "&.Mui-disabled": {
            color: `${BRAND_TINT_INK} !important`,
            background: `${BRAND_TINT_BG} !important`,
            boxShadow: "none",
          },
        }}
      >
        {loading ? "Loading presets…" : "Apply a preset…"}
      </Button>

      <Dialog
        open={open}
        onClose={() => setOpen(false)}
        fullWidth
        maxWidth="lg"
        // Full-screen below the "sm" breakpoint (roughly narrow/mobile
        // viewports) so the card grid gets real width to work with instead
        // of being squeezed into a small centered box — CSS-only via sx,
        // matching how the rest of this control already handles responsive
        // sizing (Grid's xs/sm/md props) rather than a JS media-query hook.
        sx={{
          "& .MuiDialog-paper": {
            "@media (max-width: 599.95px)": {
              margin: 0,
              width: "100%",
              maxWidth: "100%",
              height: "100%",
              maxHeight: "100%",
              borderRadius: 0,
            },
          },
        }}
        PaperProps={{
          sx: { borderRadius: 1.5, overflow: "hidden", bgcolor: SHEET, color: INK },
        }}
      >
        <DialogTitle
          sx={{
            display: "flex",
            alignItems: "flex-start",
            justifyContent: "space-between",
            gap: 2,
            bgcolor: SHEET,
            py: { xs: 2, sm: 3 },
            px: { xs: 2, sm: 3 },
          }}
        >
          <Stack sx={{ minWidth: 0, gap: 0.75 }}>
            <Typography
              sx={{
                fontWeight: 700,
                fontSize: { xs: "18px", sm: "22px" },
                lineHeight: 1.2,
                color: INK,
              }}
            >
              {"Preset"}
            </Typography>
            {/*
              * A fixed one-line explanation of what a preset does, per the
              * reference design. The former counts ("12 designs — 3 premium")
              * moved out of the header: they described the fetch, not the
              * feature, and the loading/failure states below already say so
              * where it matters.
              */}
            <Typography
              sx={{
                fontSize: "14px",
                lineHeight: 1.4,
                color: INK_MUTED,
              }}
            >
              {"Apply a style instantly with one simple click."}
            </Typography>
          </Stack>
          <IconButton
            size="small"
            onClick={() => setOpen(false)}
            aria-label="Close"
            sx={{
              flexShrink: 0,
              width: 28,
              height: 28,
              padding: 0,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              lineHeight: 1,
              color: INK_MUTED,
              "&:hover": { color: INK, bgcolor: THUMB_BG },
            }}
          >
            <Box
              component="span"
              aria-hidden="true"
              sx={{ fontSize: "15px", lineHeight: 1, transform: "translateY(-0.5px)" }}
            >
              {"✕"}
            </Box>
          </IconButton>
        </DialogTitle>
        <DialogContent
          dividers
          sx={{
            bgcolor: SHEET,
            // `dividers` draws its rule from the theme's divider token, which
            // is a light hairline on a dark editor — invisible on this sheet.
            borderColor: SHEET_BORDER,
            borderBottom: "none",
            py: 3,
            px: { xs: 2, sm: 3 },
          }}
        >
          {presets === null ? (
            <>
              <Stack
                direction="row"
                alignItems="center"
                gap={1}
                sx={{ mb: 2, color: INK_MUTED }}
              >
                <CircularProgress size={14} thickness={5} sx={{ color: BRAND }} />
                <Typography variant="caption" sx={{ fontWeight: 600 }}>
                  {"Fetching presets…"}
                </Typography>
              </Stack>
              <Box sx={CARD_GRID}>
                {[0, 1, 2, 3, 4, 5, 6, 7, 8, 9].map((i) => (
                  <Box key={i}>
                    <Skeleton variant="rounded" sx={{ pt: "82%", bgcolor: THUMB_BG }} />
                    <Skeleton variant="text" width="70%" sx={{ mt: 0.5, bgcolor: THUMB_BG }} />
                  </Box>
                ))}
              </Box>
            </>
          ) : (
            <>
              {/*
                * The read failed, so this list is incomplete (it may still hold
                * the locally bundled presets). Say so and offer a retry rather
                * than silently presenting a short list as the whole library —
                * and never render nothing at all, which is what made the
                * control look broken.
                */}
              {failed ? (
                <Stack
                  direction="row"
                  alignItems="center"
                  justifyContent="space-between"
                  gap={1.5}
                  sx={{
                    mb: 2.5,
                    px: 1.5,
                    py: 1.25,
                    borderRadius: 1.5,
                    border: "1px solid #f5d9a8",
                    bgcolor: "#fdf4e3",
                  }}
                >
                  <Typography variant="caption" sx={{ fontWeight: 600, color: INK }}>
                    {presets.length
                      ? "Some designs couldn’t be loaded. Check your connection and try again."
                      : "Couldn’t load designs. Check your connection and try again."}
                  </Typography>
                  <Button
                    size="small"
                    onClick={retry}
                    sx={{
                      flexShrink: 0,
                      textTransform: "none",
                      fontWeight: 700,
                      color: BRAND_DARK,
                    }}
                  >
                    {"Try again"}
                  </Button>
                </Stack>
              ) : null}
              {grouped.map(({ category, items }) => (
                <Box key={category || "__uncategorized"} sx={{ mb: 3 }}>
                  {category ? (
                    <Stack direction="row" alignItems="center" gap={1} sx={{ mb: 1.25 }}>
                      <Box
                        sx={{
                          width: 4,
                          height: 14,
                          borderRadius: "2px",
                          background: `linear-gradient(180deg, ${BRAND}, ${BRAND_DARK})`,
                        }}
                      />
                      <Typography
                        variant="subtitle2"
                        sx={{ fontWeight: 700, letterSpacing: "0.01em", color: INK }}
                      >
                        {category}
                      </Typography>
                    </Stack>
                  ) : null}
                  <Box sx={CARD_GRID}>
                    {items.map((p) => {
                      const locked = !!p.pro && !proActive;

                      return (
                        <PresetCard
                          key={p.id}
                          preset={p}
                          placeholderSrc={placeholderSrc}
                          locked={locked}
                          onSelect={handleSelect}
                        />
                      );
                    })}
                  </Box>
                </Box>
              ))}
            </>
          )}
        </DialogContent>
      </Dialog>
    </Stack>
  );
}