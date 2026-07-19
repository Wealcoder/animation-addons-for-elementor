/* eslint-env browser */

/**
 * FormActionsControl — the "Actions After Submit" element-control for the
 * AAE Form container (spec: the aae-form-actions control).
 *
 * Registered under the type id 'aae-form-actions' (see ./index.js) and
 * placed by class-aae-a-form.php. Opens a dialog that reads/writes the
 * form's hidden `actions_json` String prop (the backend Dispatcher parses
 * it at submit time; redirect is read by the submit endpoint itself):
 *
 *   {
 *     "admin_email": { "enabled", "to", "cc", "bcc", "reply_to", "subject", "body" },
 *     "auto_reply":  { "enabled", "subject", "body", "include_copy" },   // visitor acknowledgment
 *     "webhook":     { "enabled", "url" },
 *     "redirect":    { "enabled", "url" }                                 // immediate UX action
 *   }
 *
 * Writes use the documented envelope pattern — a String prop must be saved
 * as { $$type: 'string', value } via $e.run('document/elements/settings').
 * Test buttons call the admin REST routes (aae/v1/actions/test-email /
 * test-webhook) with the wp_rest nonce, same as LinkInput does.
 *
 * The dialog also lists THIS form's smart-tag keys as copyable chips. The
 * key derivation mirrors inc/Forms/Schema_Walker.php exactly (name →
 * _cssid → element id, with checkbox_/radio_ prefixes) so what the user
 * copies is what {{field.<key>}} resolves against at send time.
 */

import * as React from "react";
import { __, sprintf } from "@wordpress/i18n";
import { useElement } from "@elementor/editor-editing-panel";
import { getContainer } from "@elementor/editor-elements";
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Collapse,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  FormControl,
  IconButton,
  InputAdornment,
  InputLabel,
  Menu,
  MenuItem,
  Select,
  Stack,
  Switch,
  Tab,
  Tabs,
  TextField,
  Typography,
} from "@elementor/ui";

const { useState } = React;

const TD = "animation-addons-for-elementor";

/* Animation Addons brand accent (matches the dashboard app). */
const BRAND = "#F6502C";
const BRAND_HOVER = "#E04524";
const BRAND_GRADIENT = "linear-gradient(135deg, #FFA184 0%, #F2754F 100%)";
const BRAND_TINT = "rgba(246, 80, 44, 0.12)";

const DEFAULT_CONFIG = {
  admin_email: { enabled: true, to: "", cc: "", bcc: "", reply_to: "", subject: "", body: "", attach_files: true },
  auto_reply: { enabled: false, subject: "", body: "", include_copy: false },
  webhook: { enabled: false, url: "" },
  redirect: { enabled: false, url: "" },
  // Email-marketing provider blocks (brevo, mailchimp, …) are NOT hardcoded
  // here — readConfig() pulls whatever provider blocks the stored
  // actions_json has, and the editor lists providers + their attributes from
  // the integrations REST route, so a new provider needs no change here.
};

const GENERAL_TAGS = [
  "{{all_fields}}",
  "{{site.title}}",
  "{{site.url}}",
  "{{page.title}}",
  "{{page.url}}",
  "{{submission.id}}",
  "{{submission.date}}",
];

const restRoot = () => window.wpApiSettings?.root || "/wp-json/";
const restNonce = () => window.wpApiSettings?.nonce || "";

const callAdminRest = async (path, body) => {
  const response = await fetch(`${restRoot()}aae/v1/actions/${path}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": restNonce(),
    },
    body: JSON.stringify(body),
  });
  const data = await response.json().catch(() => ({}));
  return { ok: response.ok, message: data?.message || `HTTP ${response.status}` };
};

/** GET a dashboard-admin route (aae/v1/admin/*) — used for integrations. */
const getAdminData = async (path) => {
  const response = await fetch(`${restRoot()}aae/v1/admin/${path}`, {
    headers: { "X-WP-Nonce": restNonce() },
  });
  return response.json().catch(() => ({}));
};

/** Unwrap a possibly-enveloped prop value ({$$type,value} → value). */
const unwrap = (raw) =>
  raw && typeof raw === "object" && "value" in raw ? raw.value : raw;

/**
 * Coerce any prop value to a plain string. Atomic props can nest an extra
 * envelope ({ value: { value: 'x' } }) or hand back an object; unwrap once
 * more and only accept a primitive, so a field key/label never renders as
 * "[object Object]".
 */
const asString = (raw) => {
  let value = unwrap(raw);
  if (value && typeof value === "object") {
    value = unwrap(value); // one more level (double-enveloped)
  }
  if (value === null || value === undefined || typeof value === "object") {
    return "";
  }
  return String(value).trim();
};

const readConfig = (container) => {
  const settings = container?.settings?.toJSON?.() || {};
  const behavior = String(unwrap(settings.behavior) || "store_email");

  const config = {
    admin_email: { ...DEFAULT_CONFIG.admin_email, enabled: behavior !== "store" },
    auto_reply: { ...DEFAULT_CONFIG.auto_reply },
    webhook: { ...DEFAULT_CONFIG.webhook },
    redirect: { ...DEFAULT_CONFIG.redirect },
    brevo: { ...DEFAULT_CONFIG.brevo, mapping: {} },
  };

  try {
    const stored = JSON.parse(String(unwrap(settings.actions_json) || "") || "{}");
    // Merge the known blocks (email/webhook/…) onto their defaults, and pull
    // in ANY other stored block verbatim — that's how email-marketing
    // provider blocks (brevo, mailchimp, …) survive without being hardcoded.
    Object.keys(stored).forEach((type) => {
      if (!stored[type] || typeof stored[type] !== "object") {
        return;
      }
      const base = config[type] || { enabled: false, list_id: "", mapping: {} };
      config[type] = { ...base, ...stored[type] };
    });
  } catch (_e) {
    /* corrupt JSON — start from defaults */
  }

  return config;
};

const writeConfig = (elementId, config) => {
  const container = getContainer(elementId);
  const $e = window.$e;
  if (!container || !$e?.run) {
    return false;
  }
  $e.run("document/elements/settings", {
    container,
    settings: {
      actions_json: { $$type: "string", value: JSON.stringify(config) },
    },
    options: { external: true },
  });
  return true;
};

/* ------------------------------------------------------------------ */
/* Field-tag discovery — mirrors Schema_Walker::resolve_key() exactly. */
/* ------------------------------------------------------------------ */

const FIELD_WIDGET_PREFIX = {
  "e-aae-a-form-input": "",
  "e-aae-a-form-textarea": "",
  "e-aae-a-form-select": "",
  "e-aae-a-form-checkbox": "checkbox_",
  "e-aae-a-form-radio": "radio_",
};

const typeOfContainer = (container) => {
  const model = container?.model;
  if (!model?.get) {
    return "";
  }
  const elType = model.get("elType");
  return "widget" === elType ? model.get("widgetType") || "" : elType || "";
};

const stripHtml = (html) => {
  const div = document.createElement("div");
  div.innerHTML = String(html || "");
  return (div.textContent || "").trim();
};

/**
 * A label's `text` prop is an Html_V3 envelope:
 *   { $$type:'html-v3', value:{ content:{ $$type:'string', value:'…' }, children } }
 * Reach the inner string. Falls back through the plain-string / enveloped
 * cases so it also works if the shape is simpler.
 */
const labelText = (raw) => {
  const value = unwrap(raw); // strip html-v3 envelope → { content, children } | string
  if (value && typeof value === "object") {
    return asString(value.content); // content is itself a {$$type,value} string
  }
  return asString(value);
};

/** [{ key, label }] for every field of the form, in DOM order. */
const collectFieldTags = (formContainer) => {
  const found = [];
  const seen = new Set();
  const labels = {}; // input-id (_cssid) → label text

  const walk = (container) => {
    Array.from(container?.children || []).forEach((child) => {
      const type = typeOfContainer(child);
      if ("e-aae-a-form" === type) {
        return; // nested form — its fields never reach this form's schema
      }
      const settings = child.settings?.toJSON?.() || {};

      if ("e-aae-a-form-label" === type) {
        const forId = asString(settings["input-id"]);
        if (forId && !labels[forId]) {
          labels[forId] = stripHtml(labelText(settings.text));
        }
      } else if (type in FIELD_WIDGET_PREFIX) {
        // A field's name/id can arrive raw, enveloped, or (for atomic string
        // props) as { value: { … } } — asString() drills to a primitive so
        // the key never ends up as an object rendered "[object Object]".
        const name = asString(settings.name);
        const cssId = asString(settings._cssid);
        const fallbackId = asString(child.id);
        const key = name || FIELD_WIDGET_PREFIX[type] + (cssId || fallbackId);
        if (key && !seen.has(key)) {
          seen.add(key);
          found.push({ key, cssId });
        }
      }

      walk(child);
    });
  };

  walk(formContainer);
  return found.map(({ key, cssId }) => ({
    key: asString(key),
    label: asString((cssId && labels[cssId]) || ""),
  }));
};

/* ------------------------------------------------------------------ */
/* Inline icons (stroke = currentColor; no icon-package dependency).   */
/* ------------------------------------------------------------------ */

const iconProps = {
  width: 16,
  height: 16,
  viewBox: "0 0 24 24",
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.8,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};

const BoltIcon = () => (
  <svg {...iconProps} width={18} height={18} fill="currentColor" stroke="none">
    <path d="M13 2 3 14h8l-1 8 11-13h-8l0-7z" />
  </svg>
);

const MailIcon = () => (
  <svg {...iconProps}>
    <rect x="3" y="5" width="18" height="14" rx="2" />
    <path d="m3 7 9 6 9-6" />
  </svg>
);

const ReplyIcon = () => (
  <svg {...iconProps}>
    <path d="M9 17 4 12l5-5" />
    <path d="M4 12h11a5 5 0 0 1 0 10h-1" />
  </svg>
);

const LinkIcon = () => (
  <svg {...iconProps}>
    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
  </svg>
);

const RedirectIcon = () => (
  <svg {...iconProps}>
    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
    <path d="M15 3h6v6" />
    <path d="M10 14 21 3" />
  </svg>
);

const BrevoIcon = () => (
  <svg {...iconProps}>
    <path d="M12 2a3 3 0 0 0-3 3v2H7a2 2 0 0 0-2 2v2a5 5 0 0 0 5 5h.5" />
    <path d="M9 7h6a4 4 0 0 1 4 4 5 5 0 0 1-5 5h-1a4 4 0 0 0-4 4" />
    <circle cx="12" cy="11" r="1.5" fill="currentColor" stroke="none" />
  </svg>
);

const BracesIcon = () => (
  <svg {...iconProps}>
    <path d="M7 4a2 2 0 0 0-2 2v3a2 3 0 0 1-2 3 2 3 0 0 1 2 3v3a2 2 0 0 0 2 2" />
    <path d="M17 4a2 2 0 0 1 2 2v3a2 3 0 0 0 2 3 2 3 0 0 0-2 3v3a2 2 0 0 1-2 2" />
  </svg>
);

/* ------------------------------------------------------------------ */

const SectionCard = ({ icon, title, hint, enabled, onToggle, children }) => (
  <Stack
    sx={{
      border: 1,
      borderColor: enabled ? "rgba(246, 80, 44, 0.45)" : "divider",
      borderRadius: 2,
      overflow: "hidden",
      transition: "border-color 0.2s ease",
    }}
  >
    <Stack direction="row" alignItems="center" gap={1.25} sx={{ p: 1.5 }}>
      <Stack
        alignItems="center"
        justifyContent="center"
        sx={{
          width: 32,
          height: 32,
          borderRadius: 1.5,
          flexShrink: 0,
          color: BRAND,
          backgroundColor: BRAND_TINT,
        }}
      >
        {icon}
      </Stack>
      <Stack sx={{ flexGrow: 1, minWidth: 0 }}>
        <Typography variant="subtitle2" sx={{ lineHeight: 1.3 }}>
          {title}
        </Typography>
        <Typography variant="caption" sx={{ color: "text.secondary" }}>
          {hint}
        </Typography>
      </Stack>
      <Switch size="small" checked={enabled} onChange={(e) => onToggle(e.target.checked)} />
    </Stack>
    <Collapse in={enabled}>
      <Divider />
      <Stack gap={1.5} sx={{ p: 1.5, backgroundColor: "action.hover" }}>
        {children}
      </Stack>
    </Collapse>
  </Stack>
);

const Field = ({ label, value, onChange, placeholder, multiline }) => (
  <TextField
    fullWidth
    size="small"
    label={label}
    value={value}
    placeholder={placeholder || ""}
    multiline={!!multiline}
    minRows={multiline ? 3 : undefined}
    onChange={(e) => onChange(e.target.value)}
  />
);

const MenuHeader = ({ children }) => (
  <Typography
    variant="caption"
    sx={{
      px: 2,
      pt: 1,
      pb: 0.25,
      display: "block",
      color: "text.secondary",
      textTransform: "uppercase",
      letterSpacing: "0.05em",
      fontSize: 10,
    }}
  >
    {children}
  </Typography>
);

/**
 * TextField with a {…} adornment button — opens a menu of this form's
 * field tags + the general tags and inserts the pick at the caret.
 */
const TagField = ({ label, value, onChange, placeholder, multiline, fieldTags }) => {
  const inputRef = React.useRef(null);
  const [anchor, setAnchor] = useState(null);

  const insert = (tag) => {
    const input = inputRef.current;
    const start = input?.selectionStart ?? value.length;
    const end = input?.selectionEnd ?? value.length;
    onChange(value.slice(0, start) + tag + value.slice(end));
    setAnchor(null);
    requestAnimationFrame(() => {
      if (!input) {
        return;
      }
      input.focus();
      const caret = start + tag.length;
      input.setSelectionRange(caret, caret);
    });
  };

  const item = (tag, hint) => (
    <MenuItem key={tag} dense onClick={() => insert(tag)}>
      <Stack
        direction="row"
        gap={1.5}
        alignItems="baseline"
        justifyContent="space-between"
        sx={{ width: "100%" }}
      >
        <Typography sx={{ fontFamily: "monospace", fontSize: 12 }}>{tag}</Typography>
        {hint && (
          <Typography variant="caption" sx={{ color: "text.secondary" }}>
            {hint}
          </Typography>
        )}
      </Stack>
    </MenuItem>
  );

  return (
    <>
      <TextField
        fullWidth
        size="small"
        label={label}
        value={value}
        placeholder={placeholder || ""}
        multiline={!!multiline}
        minRows={multiline ? 3 : undefined}
        inputRef={inputRef}
        onChange={(e) => onChange(e.target.value)}
        InputProps={{
          endAdornment: (
            <InputAdornment
              position="end"
              sx={multiline ? { alignSelf: "flex-start", mt: 0.75 } : undefined}
            >
              <IconButton
                size="small"
                title={__("Insert smart tag", TD)}
                onClick={(e) => setAnchor(e.currentTarget)}
                sx={{ color: "text.secondary", "&:hover": { color: BRAND } }}
              >
                <BracesIcon />
              </IconButton>
            </InputAdornment>
          ),
        }}
      />
      <Menu anchorEl={anchor} open={!!anchor} onClose={() => setAnchor(null)}>
        {fieldTags.length > 0 && <MenuHeader>{__("Form fields", TD)}</MenuHeader>}
        {fieldTags.map(({ key, label: fieldLabel }) =>
          item(`{{field.${key}}}`, fieldLabel)
        )}
        <MenuHeader>{__("General", TD)}</MenuHeader>
        {GENERAL_TAGS.map((tag) => item(tag, ""))}
        <Divider sx={{ my: 0.5 }} />
        <Typography
          variant="caption"
          sx={{ px: 2, pb: 0.5, display: "block", color: "text.secondary", maxWidth: 280 }}
        >
          {__("A field's tag comes from its Name attribute — or its ID (select the field → Settings → ID).", TD)}
        </Typography>
      </Menu>
    </>
  );
};

export function FormActionsControl({ label }) {
  const { element } = useElement();
  const [open, setOpen] = useState(false);
  // Drag offset of the dialog paper — kept in a ref (not state) so moving
  // the dialog never re-renders the form inside it.
  const dragPos = React.useRef({ x: 0, y: 0 });

  /** Drag the dialog by its title bar. */
  const startDrag = (event) => {
    if (event.target.closest("button, input, textarea, a")) {
      return;
    }
    const paper = event.currentTarget.closest(".MuiDialog-paper");
    if (!paper) {
      return;
    }
    event.preventDefault(); // no text selection while dragging
    const startX = event.clientX - dragPos.current.x;
    const startY = event.clientY - dragPos.current.y;
    const onMove = (e) => {
      dragPos.current = { x: e.clientX - startX, y: e.clientY - startY };
      paper.style.transform = `translate(${dragPos.current.x}px, ${dragPos.current.y}px)`;
    };
    const onUp = () => {
      window.removeEventListener("pointermove", onMove);
      window.removeEventListener("pointerup", onUp);
    };
    window.addEventListener("pointermove", onMove);
    window.addEventListener("pointerup", onUp);
  };
  const [config, setConfig] = useState(DEFAULT_CONFIG);
  const [fieldTags, setFieldTags] = useState([]);
  const [notice, setNotice] = useState(null); // { severity, text }
  const [busy, setBusy] = useState("");
  const [tab, setTab] = useState("admin_email");
  // Email-marketing integrations state (loaded lazily when dialog opens).
  // Provider-agnostic: `integrations` holds every catalog provider (id,
  // label, pro, connected, attributes); `provider` is the one selected in
  // the Integrations tab dropdown; `lists` are that provider's lists.
  const [integrations, setIntegrations] = useState([]);
  const [provider, setProvider] = useState("");
  const [lists, setLists] = useState([]);
  const [intLoading, setIntLoading] = useState(false);
  const [listsLoading, setListsLoading] = useState(false);

  const loadIntegrations = async () => {
    setIntLoading(true);
    try {
      const data = await getAdminData("integrations");
      const all = data.integrations || [];
      setIntegrations(all);
      // Default the picker to the first provider that is already enabled in
      // this form's config, else the first connected one, else the first.
      const enabled = all.find((i) => config[i.id]?.enabled);
      const connected = all.find((i) => i.pro && i.connected);
      setProvider((enabled || connected || all[0] || {}).id || "");
    } catch (_e) {
      setIntegrations([]);
      setProvider("");
    } finally {
      setIntLoading(false);
    }
  };

  const loadLists = async (providerId) => {
    const info = integrations.find((i) => i.id === providerId);
    if (!info || !info.pro || !info.connected) {
      setLists([]);
      return;
    }
    setListsLoading(true);
    try {
      const data = await getAdminData(`integrations/${providerId}/lists`);
      setLists(data.lists || []);
    } catch (_e) {
      setLists([]);
    } finally {
      setListsLoading(false);
    }
  };

  const openDialog = () => {
    dragPos.current = { x: 0, y: 0 }; // fresh paper each open — start undragged
    const container = getContainer(element.id);
    setConfig(readConfig(container));
    setFieldTags(collectFieldTags(container));
    setNotice(null);
    setTab("admin_email");
    setIntegrations([]);
    setProvider("");
    setLists([]);
    loadIntegrations();
    setOpen(true);
  };

  // Reload the selected provider's lists whenever it changes (or once the
  // integrations list arrives and sets the default provider).
  React.useEffect(() => {
    if (provider) {
      loadLists(provider);
    } else {
      setLists([]);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [provider, integrations]);

  const providerInfo = integrations.find((i) => i.id === provider) || null;
  // The per-provider config block, created lazily so any provider works.
  const providerCfg = config[provider] || { enabled: false, list_id: "", mapping: {} };

  /** Patch a key on the selected provider's config block. */
  const patchProvider = (key, value) =>
    setConfig((current) => ({
      ...current,
      [provider]: { ...(current[provider] || { enabled: false, list_id: "", mapping: {} }), [key]: value },
    }));

  /** Set one provider attribute → form-field mapping (or clear it). */
  const patchProviderMap = (attr, fieldKey) =>
    setConfig((current) => {
      const block = current[provider] || { enabled: false, list_id: "", mapping: {} };
      const mapping = { ...(block.mapping || {}) };
      if (fieldKey) {
        mapping[attr] = fieldKey;
      } else {
        delete mapping[attr];
      }
      return { ...current, [provider]: { ...block, mapping } };
    });

  const patch = (type, key, value) =>
    setConfig((current) => ({
      ...current,
      [type]: { ...current[type], [key]: value },
    }));

  const save = () => {
    if (writeConfig(element.id, config)) {
      setOpen(false);
    } else {
      setNotice({ severity: "error", text: __("Could not save — element not found.", TD) });
    }
  };

  const testEmail = async () => {
    setBusy("email");
    setNotice(null);
    try {
      const result = await callAdminRest("test-email", {
        to: config.admin_email.to || undefined,
      });
      setNotice({ severity: result.ok ? "success" : "error", text: result.message });
    } catch (error) {
      setNotice({ severity: "error", text: String(error.message || error) });
    } finally {
      setBusy("");
    }
  };

  const testWebhook = async () => {
    setBusy("webhook");
    setNotice(null);
    try {
      const result = await callAdminRest("test-webhook", { url: config.webhook.url });
      setNotice({ severity: result.ok ? "success" : "error", text: result.message });
    } catch (error) {
      setNotice({ severity: "error", text: String(error.message || error) });
    } finally {
      setBusy("");
    }
  };

  return (
    <Stack gap={1}>
      <Button variant="outlined" size="small" fullWidth onClick={openDialog}>
        {label || __("Manage Actions", TD)}
      </Button>

      <Dialog
        open={open}
        onClose={() => setOpen(false)}
        maxWidth="sm"
        fullWidth
        // Anchor next to the editing panel (left) instead of centering —
        // keeps the canvas visible while configuring actions.
        sx={{ "& .MuiDialog-container": { justifyContent: "flex-start" } }}
        PaperProps={{ sx: { ml: "310px", maxHeight: "calc(100% - 48px)" } }}
      >
        <DialogTitle
          onPointerDown={startDrag}
          sx={{ pb: 1.5, cursor: "move", userSelect: "none", touchAction: "none" }}
        >
          <Stack direction="row" alignItems="center" gap={1.5}>
            <Stack
              alignItems="center"
              justifyContent="center"
              sx={{
                width: 36,
                height: 36,
                borderRadius: 2,
                flexShrink: 0,
                color: "#fff",
                background: BRAND_GRADIENT,
                boxShadow: "0 2px 8px rgba(246, 80, 44, 0.35)",
              }}
            >
              <BoltIcon />
            </Stack>
            <Stack>
              <Typography variant="subtitle1" sx={{ fontWeight: 600, lineHeight: 1.25 }}>
                {__("Actions After Submit", TD)}
              </Typography>
              <Typography
                variant="caption"
                sx={{
                  color: "text.secondary",
                  textTransform: "uppercase",
                  letterSpacing: "0.06em",
                  fontSize: 10,
                }}
              >
                {__("AAE Form Builder", TD)}
              </Typography>
            </Stack>
          </Stack>
        </DialogTitle>

        <Tabs
          value={tab}
          onChange={(_e, next) => setTab(next)}
          variant="scrollable"
          scrollButtons="auto"
          allowScrollButtonsMobile
          sx={{
            minHeight: 40,
            px: 1,
            "& .MuiTabs-indicator": { backgroundColor: BRAND },
          }}
        >
          {[
            { key: "admin_email", label: __("Email", TD) },
            { key: "auto_reply", label: __("Auto Reply", TD) },
            { key: "integrations", label: __("Integrations", TD) },
            { key: "webhook", label: __("Webhook", TD) },
            { key: "redirect", label: __("Redirect", TD) },
          ].map(({ key, label: tabLabel }) => {
            // The "integrations" tab has no config block of its own — its dot
            // lights when ANY email-marketing provider block is enabled.
            const dotOn =
              "integrations" === key
                ? integrations.some((i) => config[i.id]?.enabled)
                : config[key]?.enabled;
            return (
            <Tab
              key={key}
              value={key}
              label={
                <Stack direction="row" alignItems="center" gap={0.75}>
                  <Box
                    sx={{
                      width: 6,
                      height: 6,
                      borderRadius: "50%",
                      backgroundColor: dotOn ? BRAND : "action.disabled",
                    }}
                  />
                  {tabLabel}
                </Stack>
              }
              sx={{
                minHeight: 40,
                px: 1,
                fontSize: 12,
                textTransform: "none",
                "&.Mui-selected": { color: BRAND },
              }}
            />
            );
          })}
        </Tabs>

        <DialogContent dividers>
          <Stack gap={1.5}>
            {notice && <Alert severity={notice.severity}>{notice.text}</Alert>}

            {/* ---------------- Admin Email ---------------- */}
            {tab === "admin_email" && (
            <SectionCard
              icon={<MailIcon />}
              title={__("Admin Email", TD)}
              hint={__("Notify the site owner about each submission", TD)}
              enabled={config.admin_email.enabled}
              onToggle={(v) => patch("admin_email", "enabled", v)}
            >
              <Field
                label={__("To", TD)}
                value={config.admin_email.to}
                placeholder={__("Site admin email (default)", TD)}
                onChange={(v) => patch("admin_email", "to", v)}
              />
              <Field
                label={__("Cc", TD)}
                value={config.admin_email.cc}
                onChange={(v) => patch("admin_email", "cc", v)}
              />
              <Field
                label={__("Bcc", TD)}
                value={config.admin_email.bcc}
                onChange={(v) => patch("admin_email", "bcc", v)}
              />
              <Field
                label={__("Reply-To", TD)}
                value={config.admin_email.reply_to}
                placeholder={__("Visitor's email field (default)", TD)}
                onChange={(v) => patch("admin_email", "reply_to", v)}
              />
              <TagField
                label={__("Subject", TD)}
                value={config.admin_email.subject}
                placeholder={"New form submission — {{site.title}}"}
                fieldTags={fieldTags}
                onChange={(v) => patch("admin_email", "subject", v)}
              />
              <TagField
                label={__("Body", TD)}
                value={config.admin_email.body}
                placeholder={"{{all_fields}}"}
                multiline
                fieldTags={fieldTags}
                onChange={(v) => patch("admin_email", "body", v)}
              />
              <Stack direction="row" alignItems="center" justifyContent="space-between">
                <Typography variant="caption">
                  {__("Attach uploaded files (up to 20 MB total)", TD)}
                </Typography>
                <Switch
                  size="small"
                  checked={config.admin_email.attach_files !== false}
                  onChange={(e) => patch("admin_email", "attach_files", e.target.checked)}
                />
              </Stack>
              <Button size="small" variant="text" disabled={busy === "email"} onClick={testEmail}>
                {busy === "email" ? __("Sending…", TD) : __("Send Test Email", TD)}
              </Button>
            </SectionCard>
            )}

            {/* ---------------- Auto Reply (visitor acknowledgment) -------- */}
            {tab === "auto_reply" && (
            <SectionCard
              icon={<ReplyIcon />}
              title={__("Auto Reply", TD)}
              hint={__("Send the visitor an instant acknowledgment email", TD)}
              enabled={config.auto_reply.enabled}
              onToggle={(v) => patch("auto_reply", "enabled", v)}
            >
              <Typography variant="caption" sx={{ color: "text.secondary" }}>
                {__("Sent to the first email field of the submission. Skipped when the form has no email field.", TD)}
              </Typography>
              <TagField
                label={__("Subject", TD)}
                value={config.auto_reply.subject}
                placeholder={__("Thanks — we received your message", TD)}
                fieldTags={fieldTags}
                onChange={(v) => patch("auto_reply", "subject", v)}
              />
              <TagField
                label={__("Body", TD)}
                value={config.auto_reply.body}
                placeholder={__("Hi, thanks for reaching out to {{site.title}}…", TD)}
                multiline
                fieldTags={fieldTags}
                onChange={(v) => patch("auto_reply", "body", v)}
              />
              <Stack direction="row" alignItems="center" justifyContent="space-between">
                <Typography variant="caption">
                  {__("Include a copy of the submitted values", TD)}
                </Typography>
                <Switch
                  size="small"
                  checked={config.auto_reply.include_copy}
                  onChange={(e) => patch("auto_reply", "include_copy", e.target.checked)}
                />
              </Stack>
            </SectionCard>
            )}

            {/* ---------------- Integrations (email marketing) ---------- */}
            {tab === "integrations" && (
            <SectionCard
              icon={<BrevoIcon />}
              title={providerInfo ? providerInfo.label : __("Email Marketing", TD)}
              hint={__("Add each submitter as a contact in your list", TD)}
              enabled={!!providerCfg.enabled}
              onToggle={(v) => patchProvider("enabled", v)}
            >
              {/* Provider picker — one tab for all providers (Brevo, …). */}
              <FormControl fullWidth size="small">
                <InputLabel id="aae-int-provider">{__("Service", TD)}</InputLabel>
                <Select
                  labelId="aae-int-provider"
                  label={__("Service", TD)}
                  value={provider}
                  onChange={(e) => setProvider(e.target.value)}
                >
                  {integrations.map((item) => (
                    <MenuItem key={item.id} value={item.id}>
                      {item.label}
                      {!item.pro && ` — ${__("Pro", TD)}`}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>

              {intLoading && (
                <Stack direction="row" alignItems="center" gap={1}>
                  <CircularProgress size={16} />
                  <Typography variant="caption" sx={{ color: "text.secondary" }}>
                    {__("Checking connection…", TD)}
                  </Typography>
                </Stack>
              )}

              {!intLoading && providerInfo && !providerInfo.pro && (
                <Alert severity="info">
                  {/* translators: %s: provider name */}
                  {sprintf(__("%s needs the Pro add-on to sync contacts.", TD), providerInfo.label)}
                </Alert>
              )}

              {!intLoading && providerInfo && providerInfo.pro && !providerInfo.connected && (
                <Alert severity="warning">
                  {__("Not connected. Add the API key in ", TD)}
                  <strong>{__("Form Submissions → Integrations", TD)}</strong>
                  {__(", then reopen this dialog.", TD)}
                </Alert>
              )}

              {!intLoading && providerInfo && providerInfo.pro && providerInfo.connected && (
                <>
                  <Stack direction="row" alignItems="center" gap={0.75} sx={{ color: "success.main" }}>
                    <Box sx={{ width: 8, height: 8, borderRadius: "50%", backgroundColor: "success.main" }} />
                    <Typography variant="caption">
                      {/* translators: %s: provider name */}
                      {sprintf(__("Connected to %s", TD), providerInfo.label)}
                    </Typography>
                  </Stack>

                  <FormControl fullWidth size="small">
                    <InputLabel id="aae-int-list">{__("Contact list", TD)}</InputLabel>
                    <Select
                      labelId="aae-int-list"
                      label={__("Contact list", TD)}
                      value={providerCfg.list_id || ""}
                      disabled={listsLoading}
                      onChange={(e) => patchProvider("list_id", e.target.value)}
                    >
                      <MenuItem value="">
                        <em>{listsLoading ? __("Loading…", TD) : __("Select a list…", TD)}</em>
                      </MenuItem>
                      {lists.map((list) => (
                        <MenuItem key={list.id} value={list.id}>
                          {list.name}
                        </MenuItem>
                      ))}
                    </Select>
                  </FormControl>

                  <Divider textAlign="left" sx={{ "&::before, &::after": { borderColor: "divider" } }}>
                    <Typography variant="caption" sx={{ color: "text.secondary" }}>
                      {__("Field mapping", TD)}
                    </Typography>
                  </Divider>

                  {(providerInfo.attributes || []).map((attr) => (
                    <FormControl key={attr.key} fullWidth size="small">
                      <InputLabel id={`aae-int-map-${attr.key}`}>
                        {attr.required ? `${attr.label} *` : attr.label}
                      </InputLabel>
                      <Select
                        labelId={`aae-int-map-${attr.key}`}
                        label={attr.required ? `${attr.label} *` : attr.label}
                        value={(providerCfg.mapping || {})[attr.key] || ""}
                        onChange={(e) => patchProviderMap(attr.key, e.target.value)}
                      >
                        <MenuItem value="">
                          <em>{__("— None —", TD)}</em>
                        </MenuItem>
                        {fieldTags.map(({ key, label: fieldLabel }) => (
                          <MenuItem key={key} value={key}>
                            {fieldLabel || key}
                          </MenuItem>
                        ))}
                      </Select>
                    </FormControl>
                  ))}

                  <Typography variant="caption" sx={{ color: "text.secondary" }}>
                    {__("Email is required. If left unmapped, the form's first email field is used.", TD)}
                  </Typography>
                </>
              )}
            </SectionCard>
            )}

            {/* ---------------- Webhook ---------------- */}
            {tab === "webhook" && (
            <SectionCard
              icon={<LinkIcon />}
              title={__("Webhook", TD)}
              hint={__("POST every submission as JSON to a URL (n8n / Zapier / Make)", TD)}
              enabled={config.webhook.enabled}
              onToggle={(v) => patch("webhook", "enabled", v)}
            >
              <Field
                label={__("Webhook URL", TD)}
                value={config.webhook.url}
                placeholder={"https://…"}
                onChange={(v) => patch("webhook", "url", v)}
              />
              <Button
                size="small"
                variant="text"
                disabled={busy === "webhook" || !config.webhook.url}
                onClick={testWebhook}
              >
                {busy === "webhook" ? __("Sending…", TD) : __("Send Test Webhook", TD)}
              </Button>
            </SectionCard>
            )}

            {/* ---------------- Redirect ---------------- */}
            {tab === "redirect" && (
            <SectionCard
              icon={<RedirectIcon />}
              title={__("Redirect", TD)}
              hint={__("Send the visitor to a page after a successful submit", TD)}
              enabled={config.redirect.enabled}
              onToggle={(v) => patch("redirect", "enabled", v)}
            >
              <Field
                label={__("Redirect URL", TD)}
                value={config.redirect.url}
                placeholder={"https://…/thank-you"}
                onChange={(v) => patch("redirect", "url", v)}
              />
              <Typography variant="caption" sx={{ color: "text.secondary" }}>
                {__("Runs immediately after the submission is saved — it never waits for email/webhook delivery.", TD)}
              </Typography>
            </SectionCard>
            )}

            {(tab === "admin_email" || tab === "auto_reply") && (
              <Typography variant="caption" sx={{ color: "text.secondary", textAlign: "center" }}>
                {__("Use the { } button on Subject and Body fields to insert this form's smart tags.", TD)}
              </Typography>
            )}
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button size="small" color="secondary" onClick={() => setOpen(false)}>
            {__("Cancel", TD)}
          </Button>
          <Button
            size="small"
            variant="contained"
            onClick={save}
            sx={{
              backgroundColor: BRAND,
              color: "#fff",
              "&:hover": { backgroundColor: BRAND_HOVER },
            }}
          >
            {__("Save Actions", TD)}
          </Button>
        </DialogActions>
      </Dialog>
    </Stack>
  );
}
