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
  Grid,
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
        borderRadius: 1.5,
        overflow: "hidden",
        bgcolor: "background.paper",
        border: "1px solid",
        borderColor: "divider",
        boxShadow: "0 1px 2px rgba(16, 24, 40, 0.04)",
        transition: "transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease",
        "&:hover": {
          borderColor: locked ? "divider" : BRAND,
          boxShadow: locked
            ? "0 1px 2px rgba(16, 24, 40, 0.04)"
            : `0 8px 20px rgba(255, 122, 0, 0.18)`,
          transform: locked ? "none" : "translateY(-2px)",
        },
      }}
    >
      <Box
        sx={{
          position: "relative",
          width: "100%",
          pt: "62.5%" /* ~8:5, matches the 300x200 server thumb */,
          bgcolor: "action.hover",
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
      <Box sx={{ px: 1.25, py: 1 }}>
        <Typography
          variant="caption"
          sx={{
            display: "block",
            overflow: "hidden",
            textOverflow: "ellipsis",
            whiteSpace: "nowrap",
            fontWeight: 600,
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
  const proCount = presets ? presets.filter((p) => p.pro).length : 0;

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
        disabled={presets === null}
        endIcon={presets === null ? null : <span aria-hidden="true">{"+"}</span>}
        sx={{
          alignSelf: "flex-end",
          justifyContent: "center",
          px: 1.75,
          py: 0.75,
          textTransform: "none",
          fontWeight: 600,
          color: "#fff",
          background: `linear-gradient(135deg, ${BRAND}, ${BRAND_DARK})`,
          boxShadow: "0 1px 3px rgba(227, 95, 0, 0.35)",
          "&:hover": {
            background: `linear-gradient(135deg, ${BRAND_DARK} 0%, ${BRAND_DARK} 100%)`,
            opacity: 0.92,
            boxShadow: "0 2px 6px rgba(227, 95, 0, 0.45)",
          },
          "&.Mui-disabled": {
            color: "#fff !important",
            background: `linear-gradient(135deg, ${BRAND}, ${BRAND_DARK}) !important`,
          },
        }}
      >
        {presets === null ? "Loading presets…" : "Apply a preset…"}
      </Button>

      <Dialog
        open={open}
        onClose={() => setOpen(false)}
        fullWidth
        maxWidth="md"
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
        PaperProps={{ sx: { borderRadius: 2, overflow: "hidden" } }}
      >
        <DialogTitle
          sx={{
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            background: `linear-gradient(135deg, ${BRAND} 0%, ${BRAND_DARK} 100%)`,
            color: "#fff",
            py: { xs: 1.25, sm: 1.75 },
            px: { xs: 2, sm: 3 },
          }}
        >
          <Stack sx={{ minWidth: 0 }}>
            <Typography
              sx={{
                fontWeight: 700,
                fontSize: { xs: "15px", sm: "17px" },
                lineHeight: 1.3,
              }}
            >
              {"Apply Preset"}
            </Typography>
            <Typography
              sx={{
                fontSize: "12px",
                opacity: 0.85,
                display: { xs: "none", sm: "block" },
              }}
            >
              {(() => {
                if (presets === null) {
                  return "Loading designs…";
                }

                const total = presets.length;
                const designWord = total === 1 ? "design" : "designs";

                if (failed) {
                  return total
                    ? `${total} ${designWord} — some could not be loaded`
                    : "Designs could not be loaded";
                }

                return proCount > 0
                  ? `${total} ${designWord} — ${proCount} premium`
                  : `${total} ${designWord} available`;
              })()}
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
              color: "#fff",
              bgcolor: "rgba(255,255,255,0.15)",
              "&:hover": { bgcolor: "rgba(255,255,255,0.28)" },
            }}
          >
            <Box
              component="span"
              aria-hidden="true"
              sx={{ fontSize: "14px", lineHeight: 1, transform: "translateY(-0.5px)" }}
            >
              {"✕"}
            </Box>
          </IconButton>
        </DialogTitle>
        <DialogContent
          dividers
          sx={{ bgcolor: "background.default", py: 2.5, px: { xs: 1.5, sm: 3 } }}
        >
          {presets === null ? (
            <>
              <Stack
                direction="row"
                alignItems="center"
                gap={1}
                sx={{ mb: 2, color: "text.secondary" }}
              >
                <CircularProgress size={14} thickness={5} sx={{ color: BRAND }} />
                <Typography variant="caption" sx={{ fontWeight: 600 }}>
                  {"Fetching presets…"}
                </Typography>
              </Stack>
              <Grid container spacing={{ xs: 1.5, sm: 2 }}>
                {[0, 1, 2, 3].map((i) => (
                  <Grid item xs={6} sm={4} md={3} key={i}>
                    <Skeleton variant="rounded" height={100} />
                    <Skeleton variant="text" width="70%" />
                  </Grid>
                ))}
              </Grid>
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
                    border: "1px solid",
                    borderColor: "warning.light",
                    bgcolor: "warning.light",
                  }}
                >
                  <Typography variant="caption" sx={{ fontWeight: 600 }}>
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
                        sx={{ fontWeight: 700, letterSpacing: "0.01em" }}
                      >
                        {category}
                      </Typography>
                    </Stack>
                  ) : null}
                  <Grid container spacing={{ xs: 1.5, sm: 2 }}>
                    {items.map((p) => {
                      const locked = !!p.pro && !proActive;

                      return (
                        <Grid item xs={6} sm={4} md={3} key={p.id}>
                          <PresetCard
                            preset={p}
                            placeholderSrc={placeholderSrc}
                            locked={locked}
                            onSelect={handleSelect}
                          />
                        </Grid>
                      );
                    })}
                  </Grid>
                </Box>
              ))}
            </>
          )}
        </DialogContent>
      </Dialog>
    </Stack>
  );
}