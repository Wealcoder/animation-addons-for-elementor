/* eslint-env browser */

/**
 * "This design needs something your site doesn't have yet."
 *
 * A Loop Grid or Loop Filter preset is the first kind in this plugin whose
 * design is not self-contained: a property-directory filter set is wired to a
 * `property` post type and to ACF fields named `price` and `bedrooms`. Apply
 * one on a site with none of that and every control renders, none of them
 * filters, and nothing anywhere says why. This dialog is the "says why".
 *
 * IT NEVER BLOCKS THE APPLY. Every path out of it can still place the design —
 * a builder mocking up a layout they intend to wire up later is a perfectly
 * good reason to apply a preset whose data does not exist yet, and a picker
 * that refused would be overstating the problem (the design renders; it just
 * finds nothing). What the dialog owes the builder is the sentence they would
 * otherwise have to infer from an empty grid.
 *
 * WHO SEES A BUTTON is decided by the SERVER, twice over; this only paints it.
 * `canInstall` mirrors the endpoint's own `manage_options` check — NOT the
 * `edit_posts` that opens the picker — because a contributor who could make
 * this site install a plugin the remote preset server named would be remote
 * code execution by proxy. `installable` mirrors whether anything is listening
 * on `aae/preset/requires/install` at all, i.e. whether Pro is here. An editor
 * on a licensed site, and anyone on an unlicensed one, gets the requirement
 * line and no button — the same shape the starter template's dependency screen
 * already uses for a row it cannot satisfy.
 */

import * as React from "react";
import {
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Stack,
  Typography,
} from "@elementor/ui";
import {
  BRAND,
  BRAND_DARK,
  INK,
  INK_MUTED,
  OK_INK,
  SHEET,
  SHEET_BORDER,
  THUMB_BG,
  WARN_BG,
  WARN_BORDER,
  WARN_INK,
} from "./preset-theme";
import { updateCachedPresetStatus } from "./preset-apply";

/**
 * What each kind is called in front of a builder. Never the wire key: nobody
 * outside this file thinks of a taxonomy as `taxonomies`, and `acf_groups`
 * names the storage rather than the thing.
 */
const KIND_LABEL = {
  plugins: "Plugin",
  post_types: "Post type",
  taxonomies: "Taxonomy",
  acf_groups: "Field group",
};

/**
 * A state and the words that go with it. `inactive` is deliberately its own
 * row rather than folded into "missing": one is a switch and the other is a
 * download, and lumping them together is how "install ACF" appears on a site
 * that already has ACF sitting there switched off.
 */
const STATE_TEXT = {
  ok: "Ready",
  inactive: "Installed, not active",
  missing: "Not on this site",
};

const NOTE_BOX = {
  mt: 1.5,
  px: 1.5,
  py: 1.25,
  borderRadius: 1,
  border: `1px solid ${WARN_BORDER}`,
  bgcolor: WARN_BG,
};

/** Human list — "Property, Price" — for the one-line summary on a card. */
export function requirementNames(status, limit = 3) {
  const names = (status?.items || [])
    .filter((i) => i.status !== "ok")
    .map((i) => i.name);

  if (names.length > limit) {
    return `${names.slice(0, limit).join(", ")} +${names.length - limit}`;
  }

  return names.join(", ");
}

/* eslint-disable react/prop-types -- internal presentational helper, matching
 * the rest of this folder (the PropTypes package is not a dependency here). */
function RequirementRow({ item }) {
  const ok = item.status === "ok";

  return (
    <Stack
      direction="row"
      alignItems="center"
      gap={1.25}
      sx={{
        py: 1,
        px: 1.25,
        borderRadius: 1,
        bgcolor: ok ? "transparent" : THUMB_BG,
      }}
    >
      <Box
        aria-hidden="true"
        sx={{
          flexShrink: 0,
          width: 16,
          textAlign: "center",
          fontSize: "12px",
          lineHeight: 1,
          color: ok ? OK_INK : WARN_INK,
        }}
      >
        {ok ? "✓" : "!"}
      </Box>
      <Stack sx={{ minWidth: 0, flexGrow: 1, gap: 0.25 }}>
        <Typography
          sx={{
            fontSize: "13px",
            fontWeight: 600,
            lineHeight: 1.3,
            color: INK,
            overflow: "hidden",
            textOverflow: "ellipsis",
            whiteSpace: "nowrap",
          }}
        >
          {item.name}
        </Typography>
        <Typography sx={{ fontSize: "11px", lineHeight: 1.3, color: INK_MUTED }}>
          {KIND_LABEL[item.kind] || item.kind}
        </Typography>
      </Stack>
      <Typography
        sx={{
          flexShrink: 0,
          fontSize: "11px",
          fontWeight: 600,
          color: ok ? OK_INK : WARN_INK,
        }}
      >
        {STATE_TEXT[item.status] || item.status}
      </Typography>
    </Stack>
  );
}
/* eslint-enable react/prop-types */

/**
 * Ask the server to set up what this preset needs.
 *
 * THE BROWSER NAMES A PRESET; IT DOES NOT SEND THE LIST. The endpoint re-reads
 * that preset's own requires block server-side, so the only installable things
 * are the ones a preset actually asked for — the same rule the Loop Grid's
 * filter authoriser holds for a visitor's URL, and the reason posting the list
 * would be a far larger surface than it looks.
 */
function installRequirements(elementType, presetId) {
  const config = window.AAE_PRESET_CONFIG || {};

  const body = new FormData();
  body.append("action", config.requiresAction || "aae_preset_requires_install");
  body.append("nonce", config.adminNonce || "");
  body.append("element_type", elementType);
  body.append("preset_id", presetId);

  return fetch(config.ajaxUrl, {
    method: "POST",
    credentials: "same-origin",
    body,
  })
    .then((res) => res.json().catch(() => null))
    .then((data) => {
      if (!data || !data.success) {
        throw new Error(
          data?.data?.message || "That could not be set up. Please try again."
        );
      }
      return data.data;
    });
}

/**
 * A plugin activated during a request is not RUNNING during it, so a field
 * group whose ACF arrived moments ago comes back `deferred` rather than
 * failed. That is not an error and must not be painted as one — but it is also
 * not finished, and a builder told nothing would apply the design onto fields
 * that are still absent.
 */
function hasDeferred(results) {
  return Object.values(results || {}).some((r) => r === "deferred");
}

/* eslint-disable react/prop-types */
export function PresetRequiresDialog({ preset, elementType, onApply, onClose }) {
  const config = window.AAE_PRESET_CONFIG || {};

  const [status, setStatus] = React.useState(null);
  const [busy, setBusy] = React.useState(false);
  const [error, setError] = React.useState("");
  const [deferred, setDeferred] = React.useState(false);

  // Re-seeded per preset: one instance of this component serves every card, so
  // without this the second preset opened would wear the first one's outcome.
  React.useEffect(() => {
    setStatus(preset?.requires_status || null);
    setBusy(false);
    setError("");
    setDeferred(false);
  }, [preset]);

  if (!preset) {
    return null;
  }

  const items = status?.items || [];
  const missing = items.filter((i) => i.status !== "ok").length;
  const canInstall = !!config.canInstall && !!status?.installable && missing > 0;

  const handleInstall = () => {
    setBusy(true);
    setError("");

    installRequirements(elementType, preset.id)
      .then((data) => {
        const fresh = data?.status || null;
        const waiting = hasDeferred(data?.results);

        setStatus(fresh);
        setDeferred(waiting);

        // The picker's list is memoised for the session, so without this the
        // card keeps its "Needs setup" line over a site that now has the thing.
        if (fresh) {
          updateCachedPresetStatus(elementType, preset.id, fresh);
        }

        // Everything resolved, so there is nothing left to say — apply, which
        // is what the button promised. Anything short of that (a plugin that
        // downloaded but would not activate, an ACF group deferred) keeps the
        // dialog open, because the remaining rows are now its whole point.
        const left = (fresh?.items || []).filter((i) => i.status !== "ok").length;
        if (fresh && 0 === left && !waiting) {
          onApply(preset);
        }
      })
      .catch((e) => setError(e.message))
      .finally(() => setBusy(false));
  };

  return (
    <Dialog
      open
      // Not dismissable mid-install: the request writes post types and
      // activates plugins, and closing the dialog would leave that running with
      // nothing left on screen to report where it got to.
      onClose={busy ? undefined : onClose}
      fullWidth
      maxWidth="xs"
      PaperProps={{ sx: { borderRadius: 1.5, bgcolor: SHEET, color: INK } }}
    >
      <DialogTitle sx={{ bgcolor: SHEET, pb: 1.5 }}>
        <Typography
          sx={{ fontWeight: 700, fontSize: "16px", lineHeight: 1.3, color: INK }}
        >
          {"This design needs a few things"}
        </Typography>
        <Typography
          sx={{ mt: 0.5, fontSize: "13px", lineHeight: 1.4, color: INK_MUTED }}
        >
          {`“${preset.name}” filters content that isn’t set up on this site yet.`}
        </Typography>
      </DialogTitle>

      <DialogContent
        dividers
        sx={{ bgcolor: SHEET, borderColor: SHEET_BORDER, py: 1.5 }}
      >
        <Stack gap={0.5}>
          {items.map((item) => (
            <RequirementRow key={`${item.kind}:${item.slug}`} item={item} />
          ))}
        </Stack>

        {/*
          * The sentence that stands in for the button. Two different readers
          * need two different sentences: an administrator on a site with no
          * installer is looking at a licence, and an editor is looking at a
          * wp-admin capability they cannot buy their way out of — telling the
          * second one about Pro would send them to a shop for nothing.
          */}
        {!canInstall && missing > 0 ? (
          <Box sx={NOTE_BOX}>
            <Typography sx={{ fontSize: "12px", lineHeight: 1.5, color: INK }}>
              {config.canInstall
                ? "Setting these up for you is part of Animation Addons Pro. You can still apply the design and wire it up by hand."
                : "Ask an administrator to set these up. You can still apply the design — it will render, but the filters won’t match anything yet."}
            </Typography>
          </Box>
        ) : null}

        {deferred ? (
          <Box sx={NOTE_BOX}>
            <Typography sx={{ fontSize: "12px", lineHeight: 1.5, color: INK }}>
              {
                "A plugin was just installed and isn’t running yet. Reload the editor and open this preset again to finish the rest."
              }
            </Typography>
          </Box>
        ) : null}

        {error ? (
          <Typography
            sx={{ mt: 1.5, fontSize: "12px", lineHeight: 1.5, color: "#b42318" }}
          >
            {error}
          </Typography>
        ) : null}
      </DialogContent>

      <DialogActions sx={{ bgcolor: SHEET, px: 2, py: 1.5, gap: 1 }}>
        <Button
          size="small"
          onClick={onClose}
          disabled={busy}
          sx={{ textTransform: "none", fontSize: "12px", color: INK_MUTED }}
        >
          {"Cancel"}
        </Button>
        <Box sx={{ flexGrow: 1 }} />
        <Button
          size="small"
          variant="outlined"
          onClick={() => onApply(preset)}
          disabled={busy}
          sx={{
            textTransform: "none",
            fontSize: "12px",
            fontWeight: 600,
            color: INK_MUTED,
            borderColor: SHEET_BORDER,
            "&:hover": {
              color: BRAND_DARK,
              borderColor: BRAND_DARK,
              background: "transparent",
            },
          }}
        >
          {missing > 0 ? "Apply anyway" : "Apply"}
        </Button>
        {canInstall ? (
          <Button
            size="small"
            onClick={handleInstall}
            disabled={busy}
            startIcon={
              busy ? (
                <CircularProgress size={11} thickness={5} color="inherit" />
              ) : null
            }
            sx={{
              textTransform: "none",
              fontSize: "12px",
              fontWeight: 700,
              color: "#fff",
              background: `linear-gradient(135deg, ${BRAND}, ${BRAND_DARK})`,
              "&:hover": { background: BRAND_DARK },
              "&.Mui-disabled": {
                color: "rgba(255,255,255,0.75) !important",
                background: BRAND_DARK,
              },
            }}
          >
            {busy ? "Setting up…" : "Set up & apply"}
          </Button>
        ) : null}
      </DialogActions>
    </Dialog>
  );
}
/* eslint-enable react/prop-types */
