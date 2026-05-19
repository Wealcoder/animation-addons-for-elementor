/* eslint-env browser */

import * as React from "react";
import { Switch } from "@elementor/ui";

/** Plain Switch input. */
export function SwitchInput({ value, onChange, disabled }) {
  const handleChange = (_, checked) => {
    console.log("Switch changed:", checked);
    onChange(checked);
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
