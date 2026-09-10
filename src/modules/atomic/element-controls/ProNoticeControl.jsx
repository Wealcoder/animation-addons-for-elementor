/* eslint-env browser */

/**
 * "This field needs Pro" notice, pinned to the top of a locked form widget's panel.
 *
 * Injected by inc/Forms/Pro_Gate.php::inject_pro_notice() and only ever reached
 * by an instance that is ALREADY on the canvas — a preset seeded it, or the page
 * was built while the site was licensed. Those elements stay fully editable on
 * purpose (losing that would cost the customer their work), so without this
 * nothing on screen would say the field will not render for visitors.
 *
 * The copy comes from `elementor.config.v4Promotions`, which Pro_Gate fills for
 * exactly these types — the same source Elementor's own upgrade card reads when
 * the locked panel item is clicked. One place to write the pitch, so the panel
 * card and this notice cannot say different things.
 */

// This build uses the CLASSIC JSX transform (the bundle emits
// React.createElement and declares only `react` as a dependency — no
// react/jsx-runtime), so React has to be in scope for the JSX below even though
// nothing here names it. Every sibling control imports it the same way; they
// escape the lint rule only by referencing React.* somewhere, which is not a
// reason to invent a reference here.
// eslint-disable-next-line no-unused-vars
import * as React from "react";
import { useElement } from "@elementor/editor-editing-panel";
import { Box, Button, Stack, Typography } from "@elementor/ui";

import { PRO_ACCENT as ACCENT, UPGRADE_URL } from "./pro-upsell";

/** Pro_Gate's copy for this element type, read lazily — the config lands after this bundle. */
function promoFor(type) {
  const promotions = window.elementor?.config?.v4Promotions || {};
  const normalized = String(type || "").replace(/[-_]/g, "").toLowerCase();
  const key = Object.keys(promotions).find(
    (k) => k.replace(/[-_]/g, "").toLowerCase() === normalized
  );
  return (key && promotions[key]) || {};
}

export function ProNoticeControl() {
  const { element } = useElement();
  const type = element?.type || element?.model?.get?.("elType");
  const promo = promoFor(type);

  return (
    <Box
      sx={{
        p: 1.5,
        borderRadius: 1,
        border: `1px solid ${ACCENT}55`,
        backgroundColor: `${ACCENT}14`,
      }}
    >
      <Stack gap={1}>
        <Typography variant="caption" sx={{ fontWeight: 700, color: ACCENT }}>
          {promo.title || "Pro feature"}
        </Typography>

        {/* The benefit first. A notice that only states a restriction gives
            nobody a reason to act on it. */}
        {promo.content ? (
          <Typography variant="caption" sx={{ color: "text.secondary", lineHeight: 1.5 }}>
            {promo.content}
          </Typography>
        ) : null}

        {/* Then the consequence, stated plainly — this is the part the builder
            needs and cannot see any other way, because the element looks and
            edits exactly as it did before. */}
        <Typography variant="caption" sx={{ color: "text.secondary", lineHeight: 1.5 }}>
          Your settings are safe and still saving, but visitors will not see this
          field until Pro is activated.
        </Typography>

        <Button
          size="small"
          variant="contained"
          onClick={() => window.open(UPGRADE_URL, "_blank", "noopener")}
          sx={{
            alignSelf: "flex-start",
            backgroundColor: ACCENT,
            "&:hover": { backgroundColor: ACCENT, filter: "brightness(0.92)" },
          }}
        >
          Upgrade to Pro
        </Button>
      </Stack>
    </Box>
  );
}
