/* eslint-env browser */

import * as React from "react";
import { useState } from "react";
import { Button, Stack, Typography } from "@elementor/ui";

import { triggerAnimationReplay } from "../../editor-bridge/settings-bridge";
import { getSelectedContainer } from "../../editor-bridge/helpers";

/**
 * Read a scalar from the selected container's settings, unwrapping the
 * common envelope shapes ({$$type,value} plain, or 'aae-rj' responsive →
 * desktop). Used to inspect the chosen preset before previewing.
 */
function readSelectedSetting(key) {
  const container = getSelectedContainer();
  const v = container?.settings?.attributes?.[key];
  if (v && typeof v === "object" && "$$type" in v) {
    const inner = v.value;
    if (inner && typeof inner === "object" && "desktop" in inner) return inner.desktop;
    return inner;
  }
  return v;
}

/**
 * "Play Now" button inside a <ResponsiveSection>.
 *
 * The live bridge already keeps `window.AAE_INTERACTIONS_*[id]` in sync
 * on every settings mutation, so this button doesn't need to re-mirror
 * settings — it just resolves the iframe target and replays.
 *
 * The Image-Hover "Tilt 3D" preset is a true 3D cursor-tracking effect that a
 * click can't reproduce in the editor (and would leave stuck), so for that
 * one preset we show a notice and skip the replay. Every other preset/effect
 * previews normally.
 */
export function PlayButtonInput({ play_group = "" }) {
  const [played, setPlayed] = useState(false);
  const [showNotice, setShowNotice] = useState(false);

  const onClick = (e) => {
    e.preventDefault();
    e.stopPropagation();

    // Image-Hover + Tilt 3D preset → can't preview on click; show notice.
    if (play_group === "aae_ih_" && readSelectedSetting("aae_ih_preset") === "tilt-3d") {
      setShowNotice(true);
      setTimeout(() => setShowNotice(false), 4000);
      return;
    }

    triggerAnimationReplay(play_group);

    setPlayed(true);
    setTimeout(() => setPlayed(false), 600);
  };

  return (
    <Stack direction="column" alignItems="flex-end" spacing={0.75}>
      <Button
        variant="contained"
        size="small"
        onClick={onClick}
        disabled={played}
        sx={{
          background: "#0c977d",
          color: "#fff",
          "&:hover": { background: "#0b8870", color: "#faf1f1" },
          minWidth: 90,
          textTransform: "none",
        }}
      >
        {played ? "Played" : "Play Now"}
      </Button>

      {showNotice && (
        <Stack
          direction="row"
          alignItems="center"
          gap={0.75}
          sx={{
            width: "100%",
            p: 1,
            borderRadius: 1,
            bgcolor: "rgba(245, 166, 35, 0.12)",
            border: "1px solid rgba(245, 166, 35, 0.4)",
          }}
        >
          <span style={{ flexShrink: 0, fontSize: 14, lineHeight: 1 }}>⚠️</span>
          <Typography variant="caption" sx={{ color: "#b6791b", lineHeight: 1.4 }}>
            The <strong>Tilt 3D</strong> effect works in the editor and on the canvas, but it needs the live frontend to preview properly. Test it on the published page.
          </Typography>
        </Stack>
      )}
    </Stack>
  );
}
