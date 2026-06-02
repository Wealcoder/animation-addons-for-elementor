/* eslint-env browser */

import * as React from "react";
import { useState } from "react";
import { Button } from "@elementor/ui";
import { getSelectedContainer } from "../../editor-bridge/helpers";
import { replayInPreview } from "../../editor-bridge/settings-bridge";
import { applySettingsToDom } from "../../editor-bridge/settings-bridge";
/**
 * "Save Change" button inside a <ResponsiveSection>.
 */
export function SaveButtonInput({ play_group = "" }) {
  const [played, setPlayed] = useState(false);
  const onClick = (e) => {
    e.preventDefault();
    e.stopPropagation();

    const container = getSelectedContainer();
  
    let dom_settings = applySettingsToDom(container, play_group); // Ensure the latest settings are applied to the preview before replaying.
    
    if (!replayInPreview(dom_settings.target, play_group)) {
      // eslint-disable-next-line no-console
      console.warn(
        "[AAE] Play: animation runtime (aaeAtomicAnimations) not available in preview. Is GSAP enqueued?",
      );
      return;
    }

    setPlayed(true);
    
    // Trigger Elementor save
    if (window.$e && window.$e.run) {
      window.$e.run('document/save/default');
    }

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
      {played ? "Saved" : "Save Change"}
    </Button>
  );
}
