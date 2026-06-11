/* eslint-env browser */

import * as React from "react";
import { useState } from "react";
import { Button } from "@elementor/ui";

import { triggerAnimationReplay } from "../../editor-bridge/settings-bridge";

/**
 * "Play Now" button inside a <ResponsiveSection>.
 *
 * The live bridge already keeps `window.AAE_INTERACTIONS_*[id]` in sync
 * on every settings mutation, so this button doesn't need to re-mirror
 * settings — it just resolves the iframe target and replays.
 */
export function PlayButtonInput({ play_group = "" }) {
  const [played, setPlayed] = useState(false);
  const onClick = (e) => {
    e.preventDefault();
    e.stopPropagation();

    triggerAnimationReplay(play_group);

    setPlayed(true);
    setTimeout(() => setPlayed(false), 600);
  };

  return (
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
  );
}
