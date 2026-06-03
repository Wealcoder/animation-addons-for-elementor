/* eslint-env browser */

import * as React from "react";
import { Switch } from "@elementor/ui";
import { getSelectedContainer } from "../../editor-bridge/helpers";
import { replayInPreview } from "../../editor-bridge/settings-bridge";
import { applySettingsToDom } from "../../editor-bridge/settings-bridge";

/** Plain Switch input. */
export function SwitchInput({ value, onChange, disabled, play_group = "" }) {
  const handleChange = (_, checked) => {   
    onChange(checked);

    if (play_group) {
      // Simulate play behavior slightly after state update
      setTimeout(() => {
        const container = getSelectedContainer();
        if (!container) return;
        
        let dom_settings = applySettingsToDom(container, play_group);
        
        if (!replayInPreview(dom_settings.target, play_group)) {
          // eslint-disable-next-line no-console
          console.warn("[AAE] Play: animation runtime (aaeAtomicAnimations) not available in preview.");
        }
      }, 150);
    }
  };
  return (
    <Switch
      size="small"
      checked={!!value}
      disabled={disabled}
      onChange={handleChange}
    />
  );
}
