/* eslint-env browser */

/**
 * SlidesControl — the "Slides" element-control for the AAE Nested Slider.
 *
 * Registered into Elementor's shared controlsRegistry under the type id
 * 'aae-slides' (see ./index.js) and rendered by the editing panel wherever the
 * PHP side places an AAE_A_Slides_Control.
 *
 * This is a CUSTOM accordion list, not Elementor's <Repeater>. The Repeater's
 * row click opens a popover that internally resolves the row's element and
 * needs it to be the selected element — selecting the slide swapped the editing
 * panel over to that slide's settings (unwanted), and NOT selecting it crashed
 * the popover render with React #130. A self-owned accordion sidesteps both:
 * clicking a row expands it inline (showing a rename field) and drives the
 * preview to that slide, without ever touching the editor's selection — so the
 * panel stays on the slider and nothing flickers.
 *
 * The list is a LIVE PROJECTION of the slider's real child elements — one row
 * per <e-aae-a-slide> under the slider's <e-aae-a-slider-track>. There is no
 * stored repeater value; useElementChildren re-reads the element tree on every
 * create/delete/move so the rows always match reality.
 *
 * Interactions:
 *   - Click a row  → expand it (rename field) AND drive the preview slider to it.
 *   - "Add Slide"  → append a new empty slide to the track.
 *   - Duplicate    → clone that slide.
 *   - Remove (×)   → delete that slide (hidden when only one remains).
 *   - Drag a row   → reorder slides (HTML5 native drag).
 */

import * as React from "react";
import {
  createElements,
  duplicateElements,
  getContainer,
  moveElements,
  removeElements,
  updateElementEditorSettings,
  useElementChildren,
  useElementEditorSettings,
} from "@elementor/editor-elements";
import { useElement } from "@elementor/editor-editing-panel";
import {
  Box,
  Collapse,
  IconButton,
  Stack,
  TextField,
  Tooltip,
  Typography,
} from "@elementor/ui";

const TRACK_TYPE = "e-aae-a-slider-track";
const SLIDE_TYPE = "e-aae-a-slide";

/**
 * Build the model for a fresh, EMPTY slide (no heading/image — the user fills
 * it). Matches the PHP define_default_children(), which also generates empty
 * slides.
 *
 * `elements: []` is required, not optional: Elementor's delete command runs
 * deselectRecursive(), which does `model.get('elements').forEach(...)` on every
 * descendant — an undefined `elements` collection throws. An empty array is a
 * valid (iterable) collection, so an empty slide deletes cleanly.
 */
function buildSlideModel(position) {
  return {
    elType: SLIDE_TYPE,
    editor_settings: { title: `Slide ${position}` },
    elements: [],
  };
}

/**
 * Find a descendant container of `parentId` whose elType === type.
 * The slider keeps the track as a direct child; we walk one level using the
 * V1 container model so we don't depend on rendered DOM.
 */
function findChildContainerByType(parentId, type) {
  const elementor = window.elementor;
  const parent = elementor?.getContainer?.(parentId);
  const model = parent?.model;
  const children = model?.get?.("elements");

  if (!children) {
    return null;
  }

  let found = null;
  children.each?.((childModel) => {
    if (found) {
      return;
    }
    if (childModel.get("elType") === type) {
      found = childModel.get("id");
    }
  });

  return found ? elementor?.getContainer?.(found) : null;
}

/**
 * Tell the preview slider to navigate to a slide index. The runtime exposes a
 * hook on the slider DOM node (sliderDiv._aaeGoTo). We also fire the same
 * window CustomEvent the core Tabs widget listens to, as a belt-and-braces
 * fallback for runtimes that wire navigation off the navigator event.
 */
function navigatePreviewToSlide(sliderId, slideId, index) {
  try {
    const previewWin = window.elementor?.$preview?.[0]?.contentWindow || null;

    if (!previewWin) {
      return;
    }

    const sliderNode =
      previewWin.document.querySelector(`[data-id="${sliderId}"]`) ||
      previewWin.document.getElementById(sliderId);

    if (sliderNode && typeof sliderNode._aaeGoTo === "function") {
      sliderNode._aaeGoTo(index);
    }

    // Fallback: broadcast a navigator-style click the runtime can also act on.
    previewWin.dispatchEvent(
      new previewWin.CustomEvent("aae/slider/edit-slide", {
        detail: { sliderId, slideId, index },
      }),
    );
  } catch (_e) {
    /* preview not ready — ignore */
  }
}

export function SlidesControl({ label }) {
  const { element } = useElement();
  const sliderId = element.id;

  // One row per real slide under the track.
  const { [SLIDE_TYPE]: slides } = useElementChildren(sliderId, {
    [TRACK_TYPE]: SLIDE_TYPE,
  });

  const rows = (slides || []).map((slide, index) => ({
    id: slide.id,
    title: slide.editorSettings?.title || `Slide ${index + 1}`,
    index,
  }));

  const [expandedId, setExpandedId] = React.useState(null);
  const [dragFrom, setDragFrom] = React.useState(null);
  const [dragOver, setDragOver] = React.useState(null);

  const getTrack = () => findChildContainerByType(sliderId, TRACK_TYPE);

  const handleRowClick = (row) => {
    setExpandedId((cur) => (cur === row.id ? null : row.id));
    navigatePreviewToSlide(sliderId, row.id, row.index);
  };

  const handleAdd = () => {
    const track = getTrack();
    if (!track) {
      return;
    }
    const position = rows.length + 1;
    createElements({
      title: "Slide",
      subtitle: "Slide added",
      elements: [
        {
          container: track,
          model: buildSlideModel(position),
          options: { at: rows.length },
        },
      ],
    });
  };

  const handleDuplicate = (row) => {
    duplicateElements({
      elementIds: [row.id],
      title: "Slide",
      subtitle: "Slide duplicated",
    });
  };

  const handleRemove = (row) => {
    removeElements({
      elementIds: [row.id],
      title: "Slide",
      subtitle: "Slide removed",
    });
    if (expandedId === row.id) {
      setExpandedId(null);
    }
  };

  const handleDrop = (toIndex) => {
    const from = dragFrom;
    setDragFrom(null);
    setDragOver(null);
    if (from == null || from === toIndex) {
      return;
    }
    const track = getTrack();
    const movedId = rows[from]?.id;
    const movedElement = movedId ? getContainer(movedId) : null;
    // Guard against a stale index (a concurrent create/delete between render and
    // drop): only move if the resolved slide is still a child of this track.
    if (track && movedElement && movedElement.parent?.id === track.id) {
      moveElements({
        title: "Slide",
        subtitle: "Slide reordered",
        moves: [
          {
            element: movedElement,
            targetContainer: track,
            options: { at: toIndex },
          },
        ],
      });
    }
  };

  return (
    <Stack gap={1}>
      <Stack
        direction="row"
        alignItems="center"
        justifyContent="space-between"
      >
        <Typography variant="caption" sx={{ fontWeight: 500, color: "text.secondary" }}>
          {label}
        </Typography>
        <Tooltip title="Add Slide">
          <IconButton size="tiny" onClick={handleAdd} aria-label="Add Slide">
            <span style={{ fontSize: 16, lineHeight: 1 }}>+</span>
          </IconButton>
        </Tooltip>
      </Stack>

      <Stack gap={0.5}>
        {rows.map((row) => {
          const isExpanded = expandedId === row.id;
          const isDragOver = dragOver === row.index && dragFrom !== row.index;
          return (
            <Box
              key={row.id}
              draggable
              onDragStart={() => setDragFrom(row.index)}
              onDragOver={(e) => {
                e.preventDefault();
                setDragOver(row.index);
              }}
              onDrop={() => handleDrop(row.index)}
              onDragEnd={() => {
                setDragFrom(null);
                setDragOver(null);
              }}
              sx={{
                border: "1px solid",
                borderColor: isDragOver ? "primary.main" : "divider",
                borderRadius: 1,
                overflow: "hidden",
                bgcolor: "background.default",
              }}
            >
              <Stack
                direction="row"
                alignItems="center"
                gap={0.5}
                onClick={() => handleRowClick(row)}
                sx={{
                  px: 1,
                  py: 0.75,
                  cursor: "pointer",
                  userSelect: "none",
                  "&:hover": { bgcolor: "action.hover" },
                }}
              >
                <Box
                  component="span"
                  sx={{ color: "text.tertiary", cursor: "grab", fontSize: 14, lineHeight: 1 }}
                  aria-hidden
                >
                  ⠿
                </Box>
                <Typography
                  variant="body2"
                  sx={{ flex: 1, fontWeight: isExpanded ? 600 : 400 }}
                >
                  <RowTitle elementId={row.id} fallback={row.title} />
                </Typography>
                <Tooltip title="Duplicate">
                  <IconButton
                    size="tiny"
                    aria-label="Duplicate slide"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDuplicate(row);
                    }}
                  >
                    <span style={{ fontSize: 13, lineHeight: 1 }}>⧉</span>
                  </IconButton>
                </Tooltip>
                {rows.length > 1 && (
                  <Tooltip title="Remove">
                    <IconButton
                      size="tiny"
                      aria-label="Remove slide"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleRemove(row);
                      }}
                    >
                      <span style={{ fontSize: 14, lineHeight: 1 }}>×</span>
                    </IconButton>
                  </Tooltip>
                )}
              </Stack>

              <Collapse in={isExpanded} unmountOnExit>
                <Box sx={{ px: 1.5, py: 1.5, borderTop: "1px solid", borderColor: "divider" }}>
                  <SlideNameField elementId={row.id} />
                </Box>
              </Collapse>
            </Box>
          );
        })}
      </Stack>
    </Stack>
  );
}

/**
 * Row label, read live off the element's own editor_settings instead of off
 * the `useElementChildren` snapshot. That hook only recomputes on element-tree
 * changes, not on an editor_settings rename (which is a bare Backbone
 * model.set with no accompanying document/elements/* command), so typing in
 * the rename field below updated the field itself (which already reads this
 * same live hook) but left the row title stuck. Subscribing here directly
 * sidesteps that dependency entirely.
 */
function RowTitle({ elementId, fallback }) {
  const editorSettings = useElementEditorSettings(elementId);
  return editorSettings?.title || fallback;
}

function SlideNameField({ elementId }) {
  const editorSettings = useElementEditorSettings(elementId);
  const label = editorSettings?.title ?? "";

  return (
    <Stack gap={1}>
      <Typography variant="caption" sx={{ fontWeight: 500, color: "text.secondary" }}>
        {"Slide name"}
      </Typography>
      <TextField
        size="tiny"
        value={label}
        onChange={({ target }) =>
          updateElementEditorSettings({
            elementId,
            settings: { title: target.value },
          })
        }
      />
    </Stack>
  );
}
