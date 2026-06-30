/* eslint-env browser */

/**
 * LoopTemplateControl — the "Loop Item Template" element-control for the AAE
 * Loop Grid widget.
 *
 * Registered into Elementor's shared controlsRegistry under the type id
 * 'aae-loop-template' (see ./index.js) and rendered by the editing panel
 * wherever the PHP side places an AAE_A_Loop_Template_Control.
 *
 * Responsibilities (Pro Loop Builder replica, edit-in-place):
 *   - No template yet  → "Create Template" button: ajax-creates an aae-loop-item
 *     document, writes its id into the widget's `template_id` prop, then switches
 *     the editor to that document IN PLACE (no page reload).
 *   - Template chosen  → "Edit Template" (switch in place) + "Detach".
 *
 * In-place switching uses the legacy command $e.run('editor/documents/switch',
 * { id, selector }) — the same mechanism Pro relies on. Switching back is done
 * by the editor JS bridge (a "Back" affordance), or by re-running switch with
 * the page document id.
 */

import * as React from "react";
import { updateElementSettings } from "@elementor/editor-elements";
import { useElement } from "@elementor/editor-editing-panel";
import { Stack, Typography, Button, Select, MenuItem } from "@elementor/ui";

function getConfig() {
  return window.AAE_LOOP_GRID || {};
}

/** Read the widget's current template_id prop value (unwrapped). */
function readTemplateId(element) {
  const settings = element?.model?.get?.("settings");
  const raw = settings?.get?.("loop_template_id") ?? settings?.loop_template_id;
  const val = raw && typeof raw === "object" ? raw.value : raw;
  const num = parseInt(val, 10);
  return Number.isFinite(num) && num > 0 ? num : 0;
}

/**
 * Edit the loop-item template in place. The editor bridge (atomic-editor bundle)
 * owns the preview DOM, so it manages the attach target + the switch; we just
 * delegate. Falls back to a direct switch if the bridge isn't present.
 */
function editTemplateInPlace(id) {
  const tplId = parseInt(id, 10);
  if (window.AAELoopGrid?.editTemplate) {
    window.AAELoopGrid.editTemplate(tplId);
    return;
  }
  if (window.$e?.run) {
    window.$e.run("editor/documents/switch", {
      id: tplId,
      selector: ".elementor-" + tplId,
      shouldNavigateToDefaultRoute: false,
      setAsInitial: false,
    });
  }
}

export function LoopTemplateControl({ label }) {
  const { element } = useElement();
  const elementId = element.id;
  const [templateId, setTemplateId] = React.useState(() => readTemplateId(element));
  const [templates, setTemplates] = React.useState([]);
  const [loadingTemplates, setLoadingTemplates] = React.useState(false);
  const [busy, setBusy] = React.useState(false);

  // Fetch the list of available loop templates.
  const fetchTemplates = async () => {
    const cfg = getConfig();
    if (!cfg.ajaxUrl || !cfg.createNonce) {
      return;
    }
    setLoadingTemplates(true);
    try {
      const body = new FormData();
      body.append("action", "aae_get_loop_templates");
      body.append("nonce", cfg.createNonce);

      const res = await fetch(cfg.ajaxUrl, {
        method: "POST",
        body,
        credentials: "same-origin",
      });
      const json = await res.json();
      if (json && json.success && Array.isArray(json.data)) {
        setTemplates(json.data);
      }
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error("[aae-loop-grid] fetch templates error", e);
    } finally {
      setLoadingTemplates(false);
    }
  };

  // Keep local state and template list in sync.
  React.useEffect(() => {
    fetchTemplates();
  }, []);

  // Listen to Backbone model changes reactively so panel updates instantly.
  React.useEffect(() => {
    const model = element?.model;
    if (!model) {
      return;
    }
    const handleChange = () => {
      setTemplateId(readTemplateId(element));
    };
    model.on("change", handleChange);
    
    const settings = model.get("settings");
    if (settings && typeof settings.on === "function") {
      settings.on("change", handleChange);
    }

    setTemplateId(readTemplateId(element));

    return () => {
      model.off("change", handleChange);
      if (settings && typeof settings.off === "function") {
        settings.off("change", handleChange);
      }
    };
  }, [element]);

  const writeTemplateId = (id) => {
    updateElementSettings({
      id: elementId,
      props: { loop_template_id: { $$type: "number", value: parseInt(id, 10) } },
      withHistory: true,
    });
    setTemplateId(parseInt(id, 10));
  };

  const createTemplate = async () => {
    const cfg = getConfig();
    if (!cfg.ajaxUrl || !cfg.createNonce) {
      // eslint-disable-next-line no-console
      console.warn("[aae-loop-grid] missing ajax config");
      return;
    }
    setBusy(true);
    try {
      const body = new FormData();
      body.append("action", "aae_create_loop_item");
      body.append("nonce", cfg.createNonce);
      body.append("title", "Loop Item");

      const res = await fetch(cfg.ajaxUrl, {
        method: "POST",
        body,
        credentials: "same-origin",
      });
      const json = await res.json();
      if (json && json.success && json.data && json.data.id) {
        writeTemplateId(json.data.id);
        // Reload list so the new template shows up in select options.
        fetchTemplates();
        // Give the settings command a tick to commit before switching.
        setTimeout(() => editTemplateInPlace(json.data.id), 50);
      } else {
        // eslint-disable-next-line no-console
        console.error("[aae-loop-grid] create failed", json);
      }
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error("[aae-loop-grid] create error", e);
    } finally {
      setBusy(false);
    }
  };

  const editTemplate = () => {
    if (templateId) {
      editTemplateInPlace(templateId);
    }
  };

  const detachTemplate = () => {
    writeTemplateId(0);
  };

  const currentTemplate = templates.find((t) => t.id === templateId);
  const templateTitle = currentTemplate ? currentTemplate.title : `Template #${templateId}`;

  return (
    <Stack gap={1}>
      <Typography variant="caption" sx={{ fontWeight: 500, color: "text.secondary" }}>
        {label || "Loop Item Template"}
      </Typography>

      {templateId ? (
        <Stack gap={1}>
          <Typography variant="caption" sx={{ color: "text.secondary", fontWeight: 600 }}>
            {templateTitle}
          </Typography>
          <Button size="small" variant="contained" onClick={editTemplate}>
            {"Edit Template"}
          </Button>
          <Button size="small" variant="outlined" color="secondary" onClick={detachTemplate}>
            {"Detach"}
          </Button>
        </Stack>
      ) : (
        <Stack gap={1}>
          <Select
            size="tiny"
            displayEmpty
            value=""
            disabled={loadingTemplates || busy}
            onChange={(e) => {
              const val = e.target.value;
              if (val) {
                writeTemplateId(val);
              }
            }}
          >
            <MenuItem value="" disabled>
              {loadingTemplates ? "Loading templates…" : "Choose a template…"}
            </MenuItem>
            {templates.map((t) => (
              <MenuItem key={t.id} value={t.id}>
                {t.title}
              </MenuItem>
            ))}
          </Select>
          <Button size="small" variant="contained" disabled={busy} onClick={createTemplate}>
            {busy ? "Creating…" : "Create Template"}
          </Button>
        </Stack>
      )}
    </Stack>
  );
}
