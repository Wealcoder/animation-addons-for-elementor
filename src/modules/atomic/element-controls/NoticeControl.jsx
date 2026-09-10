/* eslint-env browser */

/**
 * An instruction card in the panel — the generic sibling of ProNoticeControl.
 *
 * PHP: inc/AtomicWidgets/Controls/class-aae-notice-control.php. Static copy
 * arrives in props (title / text / link). A `source` names a dynamic resolver
 * below, which reads the ELEMENT (its settings) and a per-page data map and
 * returns copy that PHP could not know when it built the widget TYPE's
 * controls. A resolver returning null renders nothing — a notice with nothing
 * to say must take no space.
 */

// Classic JSX transform — React must be in scope. See ProNoticeControl.jsx.
// eslint-disable-next-line no-unused-vars
import * as React from "react";
import { useElement } from "@elementor/editor-editing-panel";
import { Box, Button, Stack, Typography } from "@elementor/ui";

const TONES = {
  info: { border: "#3b82f6", bg: "#3b82f614" },
  warning: { border: "#d97706", bg: "#d9770614" },
  success: { border: "#16a34a", bg: "#16a34a14" },
};

/** Read one setting off the selected element, unwrapping the { $$type, value } envelope. */
function settingOf(element, key) {
  try {
    const c = window.elementor?.getContainer?.(element?.id);
    const raw = c?.settings?.get?.(key);
    return raw && typeof raw === "object" && "value" in raw ? raw.value : raw;
  } catch (_e) {
    return undefined;
  }
}

/**
 * Dynamic resolvers, keyed by `source`. Each returns { tone, title, text,
 * linkUrl, linkLabel } or null. The data they read is printed by PHP as
 * window.AAE_LOOP_GRID.notices (see class-atomic.php::localize_loop_grid).
 */
const RESOLVERS = {
  // Loop Grid → Query Filters: which taxonomies this Source can filter by, and
  // which ones exist but are hidden because they are not public / not
  // registered right now.
  "loop-grid-taxonomies": (element) => {
    const data = window.AAE_LOOP_GRID?.notices?.taxonomies;
    if (!data) {
      return null;
    }
    const postType = String(settingOf(element, "post_type") || "post");
    if (postType === "related" || postType === "current_query") {
      return null;
    }
    const forType = data[postType];
    if (!forType) {
      return null;
    }
    const hidden = forType.unregistered || [];
    const nonPublic = forType.nonPublic || [];
    if (!hidden.length && !nonPublic.length && forType.count > 0) {
      return null;
    }
    if (forType.count === 0 && !hidden.length) {
      return {
        tone: "info",
        title: "No taxonomy filters for this source",
        text: "Term filters appear here for every public taxonomy registered for the selected post type. This post type has none.",
      };
    }
    const parts = [];
    if (hidden.length) {
      parts.push(
        `${hidden.join(", ")}: not registered right now (its plugin is off?). Your saved selection is kept and works again when it returns.`
      );
    }
    if (nonPublic.length) {
      parts.push(
        `${nonPublic.join(", ")}: included although not public — attribute taxonomies are added automatically.`
      );
    }
    return { tone: hidden.length ? "warning" : "info", title: "About the taxonomy filters", text: parts.join(" ") };
  },
};

export function NoticeControl(props) {
  const { element } = useElement();
  const { tone = "info", title = "", text = "", linkUrl = "", linkLabel = "", source = "" } = props || {};

  let copy = { tone, title, text, linkUrl, linkLabel };
  if (source && RESOLVERS[source]) {
    const dynamic = RESOLVERS[source](element, props);
    if (!dynamic) {
      return null;
    }
    copy = { ...copy, ...dynamic };
  }

  if (!copy.title && !copy.text) {
    return null;
  }

  const colors = TONES[copy.tone] || TONES.info;

  return (
    <Box
      role="note"
      sx={{
        p: 1.5,
        borderRadius: 1,
        border: `1px solid ${colors.border}55`,
        backgroundColor: colors.bg,
      }}
    >
      <Stack gap={0.75}>
        {copy.title ? (
          <Typography variant="caption" sx={{ fontWeight: 700, color: colors.border }}>
            {copy.title}
          </Typography>
        ) : null}
        {copy.text ? (
          <Typography variant="caption" sx={{ color: "text.secondary", lineHeight: 1.5 }}>
            {copy.text}
          </Typography>
        ) : null}
        {copy.linkUrl && copy.linkLabel ? (
          <Button
            size="small"
            variant="text"
            onClick={() => window.open(copy.linkUrl, "_blank", "noopener")}
            sx={{ alignSelf: "flex-start", px: 0, color: colors.border, fontWeight: 600 }}
          >
            {copy.linkLabel}
          </Button>
        ) : null}
      </Stack>
    </Box>
  );
}
