import { Button } from "@/components/ui/button";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import {
  RiSparklingLine,
  RiExternalLinkLine,
  RiArrowRightLine,
  RiFileCopyLine,
  RiFlashlightLine,
  RiErrorWarningLine,
  RiBookOpenLine,
} from "react-icons/ri";
import { aiApi } from "@/lib/aiApi";

/**
 * The MCP connect screen, in this app's own UI kit. Reads/writes go through
 * the exact same REST routes Pro's own "AI & Connections" screen uses
 * (`Setup()` in animation-addons-for-elementor-pro/src/modules/platform/
 * connections/App.jsx) — same storage, same tokens, same OAuth server; only
 * the form itself is duplicated, kept deliberately lighter (no manual OAuth
 * client registration, no settings, no activity/tool list — those stay on
 * Pro's full page, one link away).
 *
 * This page is only ever reached when `addons_config.ai.available` is true
 * (see config/nav/main-nav.jsx), but the inline notice below covers a direct
 * link/bookmark to `?tab=ai-configuration` on a site where Pro isn't active.
 *
 * The AI-provider API key form that used to live here moved out — it is not
 * gone, it is the identical card on Pro's full Connections page
 * (`AiSettings()`), which is where provider keys are managed from now.
 */

async function copy(text, label) {
  try {
    await navigator.clipboard.writeText(text);
  } catch (e) {
    const ta = document.createElement("textarea");
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand("copy");
    ta.remove();
  }
  toast.success(label || __("Copied", "animation-addons-for-elementor"));
}

function CopyBox({ value, label }) {
  return (
    <div className="flex items-stretch gap-1.5">
      <input
        readOnly
        value={value}
        onFocus={(e) => e.target.select()}
        className="flex-1 h-9 rounded-lg border border-border bg-background-secondary px-3 font-mono text-[12.5px]"
        aria-label={label}
      />
      <Button
        type="button"
        size="sm"
        variant="secondary"
        className="!h-9 !px-2.5"
        onClick={() => copy(value, label ? `${label} copied` : __("Copied", "animation-addons-for-elementor"))}
      >
        <RiFileCopyLine size={14} />
      </Button>
    </div>
  );
}

// A connected stepper, in this app's own tokens — matches Pro's AI &
// Connections page, which this card mirrors. The numbered circles sit in
// their own grid row, joined by a line, so the steps read as one path to
// follow rather than three unrelated facts.
function StepBadge({ n }) {
  return (
    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#7C5CFC] text-[11px] font-semibold text-white">
      {n}
    </div>
  );
}

function Steps({ steps }) {
  return (
    <div className="grid gap-2.5 sm:grid-cols-3 sm:gap-x-3 sm:gap-y-0">
      <div className="hidden sm:contents">
        {steps.map((_, i) => (
          <div key={i} className="flex items-center pb-2">
            <StepBadge n={i + 1} />
            {i < steps.length - 1 && <div className="ml-2 h-px flex-1 bg-border" />}
          </div>
        ))}
      </div>
      <div className="hidden sm:contents">
        {steps.map((s, i) => (
          <div key={i} className="rounded-lg border border-border bg-background-secondary/60 p-3">
            <p className="text-[12.5px] leading-snug text-text-secondary">{s}</p>
          </div>
        ))}
      </div>
      <div className="grid gap-2.5 sm:hidden">
        {steps.map((s, i) => (
          <div key={i} className="flex gap-2.5 rounded-lg border border-border bg-background-secondary/60 p-3">
            <StepBadge n={i + 1} />
            <p className="text-[12.5px] leading-snug text-text-secondary pt-0.5">{s}</p>
          </div>
        ))}
      </div>
    </div>
  );
}

// Two ways in per app, shown as two tabs — same model as Pro's AI &
// Connections page. "quick" mints a personal token (Claude: a fixed header
// under "No sign in"; ChatGPT: inside the Server URL's path, since its form
// has no header field). "oauth" is the per-person login. Quick is the
// default: it needs no /.well-known/ discovery, so it also works on hosts
// that block that path, where ChatGPT's OAuth cannot (`oauthNeedsRoot`).
// ChatGPT steps match its current UI (Plugins → Add → Add custom MCP server).
const HOSTED_CLIENTS = () => [
  {
    id: "claude",
    name: "Claude (web & Desktop)",
    quick: {
      action: "header",
      showUrl: true,
      steps: [
        __("In Claude: Settings → Connectors → Add custom connector.", "animation-addons-for-elementor"),
        __('Paste the MCP URL above. Authentication: "No sign in".', "animation-addons-for-elementor"),
        __('Under Request headers add one: name "Authorization", value from the button below.', "animation-addons-for-elementor"),
      ],
      button: __("Create token & copy header value", "animation-addons-for-elementor"),
      note: __("Everyone using this connector shares that one token — revoke it anytime under Personal tokens on the full Connections page.", "animation-addons-for-elementor"),
    },
    oauth: {
      showUrl: true,
      steps: [
        __("In Claude: Settings → Connectors → Add custom connector.", "animation-addons-for-elementor"),
        __("Paste the MCP URL above. Leave the client ID and secret empty.", "animation-addons-for-elementor"),
        __("Press Connect — you log in to this site and press Allow.", "animation-addons-for-elementor"),
      ],
    },
  },
  {
    id: "chatgpt",
    name: "ChatGPT",
    oauthNeedsRoot: true,
    quick: {
      action: "url",
      showUrl: false,
      steps: [
        __("In ChatGPT: Plugins → Add → Add custom MCP server.", "animation-addons-for-elementor"),
        __("Press the button below and paste the copied URL into Server URL.", "animation-addons-for-elementor"),
        __('Authentication: "No authentication" (not "OAuth or no authentication"). Tick "I understand", then Create as a plugin.', "animation-addons-for-elementor"),
      ],
      button: __("Create token & copy ready URL", "animation-addons-for-elementor"),
      note: __("The token rides inside this URL, so treat the URL like a password — revoke it anytime under Personal tokens on the full Connections page.", "animation-addons-for-elementor"),
    },
    oauth: {
      showUrl: true,
      steps: [
        __("In ChatGPT: Plugins → Add → Add custom MCP server.", "animation-addons-for-elementor"),
        __('Paste the MCP URL above. Authentication: "OAuth".', "animation-addons-for-elementor"),
        __("Create as a plugin — you're sent here to log in and press Allow.", "animation-addons-for-elementor"),
      ],
    },
  },
];

// These three genuinely edit a local file (or run a CLI command) themselves,
// so a personal token + a ready-made prompt makes sense — config shapes
// verified against each tool's own docs (same ones used on Pro's full page).
const DEV_CLIENTS = (mcpUrl) => [
  {
    id: "claude-code",
    name: "Claude Code",
    steps: [
      __("Copy the prompt below — a real personal token is minted into it.", "animation-addons-for-elementor"),
      __("Paste it into a Claude Code chat, in any project.", "animation-addons-for-elementor"),
      __("It runs the command itself and confirms the connection.", "animation-addons-for-elementor"),
    ],
    configLabel: __("Terminal command", "animation-addons-for-elementor"),
    config: (token) => `claude mcp add --transport http animation-addons "${mcpUrl}" --header "Authorization: Bearer ${token}"`,
    prompt: (token) =>
      `Please connect to my WordPress site's MCP server for me.\n\n` +
      `Run this in the terminal:\n\n` +
      `claude mcp add --transport http animation-addons "${mcpUrl}" --header "Authorization: Bearer ${token}"\n\n` +
      `Then run \`claude mcp list\` to confirm it connected, and tell me what tools you can now see.`,
  },
  {
    id: "cursor",
    name: "Cursor",
    steps: [
      __("Copy the prompt below — a real personal token is minted into it.", "animation-addons-for-elementor"),
      __("Paste it into Cursor's chat (Agent/Composer), in any project.", "animation-addons-for-elementor"),
      __("It writes the file itself — restart Cursor (or reload MCP servers) once it says done.", "animation-addons-for-elementor"),
    ],
    configLabel: __("Config file — .cursor/mcp.json (or ~/.cursor/mcp.json)", "animation-addons-for-elementor"),
    config: (token) => JSON.stringify({ mcpServers: { "animation-addons": { url: mcpUrl, headers: { Authorization: `Bearer ${token}` } } } }, null, 2),
    prompt: (token) =>
      `Please connect to my WordPress site's MCP server for me. Create or edit .cursor/mcp.json in this project ` +
      `(or ~/.cursor/mcp.json if no project is open) and make sure it includes this server, without removing any others already listed:\n\n` +
      `${JSON.stringify({ mcpServers: { "animation-addons": { url: mcpUrl, headers: { Authorization: `Bearer ${token}` } } } }, null, 2)}\n\n` +
      `Save it, then tell me to restart Cursor (or reload MCP servers) so it picks this up.`,
  },
  {
    id: "codex",
    name: "Codex",
    steps: [
      __("Copy the prompt below — a real personal token is minted into it.", "animation-addons-for-elementor"),
      __("Paste it into Codex's chat, in any project.", "animation-addons-for-elementor"),
      __("It edits the file itself — restart Codex once it says done.", "animation-addons-for-elementor"),
    ],
    configLabel: __("Config file — ~/.codex/config.toml", "animation-addons-for-elementor"),
    config: (token) => `[mcp_servers.animation-addons]\nurl = "${mcpUrl}"\nhttp_headers = { "Authorization" = "Bearer ${token}" }`,
    prompt: (token) =>
      `Please connect to my WordPress site's MCP server for me. Open (or create) ~/.codex/config.toml and add this block, ` +
      `without removing any other [mcp_servers.*] tables already there:\n\n` +
      `[mcp_servers.animation-addons]\nurl = "${mcpUrl}"\nhttp_headers = { "Authorization" = "Bearer ${token}" }\n\n` +
      `Save it, then tell me to restart Codex so it picks this up.`,
  },
];

// A failed mint (most often the 25-per-user token cap) used to surface only
// as a corner toast, so it read as "the copy button does nothing".
function MintError({ err }) {
  if (!err) {
    return null;
  }
  return (
    <p className="text-[12px] text-[#DC2626] mt-1.5" role="alert">
      {err}{" "}
      <a href="admin.php?page=aaeaddon-connections" className="underline">
        {__("Manage personal tokens →", "animation-addons-for-elementor")}
      </a>
    </p>
  );
}

function HostedTab({ c, mcpUrl, blocked }) {
  const [mode, setMode] = useState("quick");
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState("");
  const m = c[mode];
  const mint = async () => {
    setBusy(true);
    setErr("");
    try {
      const r = await aiApi.createToken(`${c.name} ${__("token", "animation-addons-for-elementor")}`, ["read", "write"]);
      if ("url" === c.quick.action) {
        copy(`${mcpUrl}/${r.token}`, __("URL copied — paste it into Server URL", "animation-addons-for-elementor"));
      } else {
        copy(`Bearer ${r.token}`, __("Header value copied — paste it as the Authorization header's value", "animation-addons-for-elementor"));
      }
    } catch (e) {
      setErr(e.message);
      toast.error(e.message);
    } finally {
      setBusy(false);
    }
  };
  const modes = [
    ["quick", __("Quick connect", "animation-addons-for-elementor")],
    ["oauth", __("Sign in with OAuth", "animation-addons-for-elementor")],
  ];
  return (
    <div className="mt-4 rounded-xl bg-background-secondary/60 p-4" data-hosted-mode={mode}>
      <div className="inline-flex rounded-lg border border-border bg-background p-0.5" role="tablist">
        {modes.map(([id, label]) => (
          <button
            key={id}
            type="button"
            role="tab"
            aria-selected={mode === id}
            onClick={() => setMode(id)}
            className={
              "rounded-md px-3 py-1.5 text-[12.5px] font-medium transition-colors " +
              (mode === id ? "bg-[#7C5CFC1A] text-text" : "text-text-secondary hover:text-text")
            }
            data-mode={id}
          >
            {label}
          </button>
        ))}
      </div>
      <p className="text-[12px] text-text-secondary mt-1.5 mb-3">
        {"quick" === mode
          ? __("One paste, no login screen — uses a personal token.", "animation-addons-for-elementor")
          : __("Each person logs in to this site and approves the app.", "animation-addons-for-elementor")}
      </p>
      {"oauth" === mode && c.oauthNeedsRoot && true === blocked ? (
        <p className="text-[12px] text-text-secondary mb-3 flex items-start gap-1.5" data-testid="oauth-blocked">
          <RiErrorWarningLine size={14} className="mt-[1px] shrink-0 text-[#D97706]" />
          <span>{__("This site's host blocks /.well-known/, which ChatGPT needs for OAuth — it will say it couldn't discover OAuth settings. Use Quick connect instead.", "animation-addons-for-elementor")}</span>
        </p>
      ) : null}
      {m.showUrl ? (
        <div className="mb-3">
          <div className="text-[13px] font-medium mb-1.5">{__("MCP server URL", "animation-addons-for-elementor")}</div>
          <CopyBox value={mcpUrl} label={__("MCP URL", "animation-addons-for-elementor")} />
        </div>
      ) : null}
      <Steps steps={m.steps} />
      {"quick" === mode ? (
        <>
          <div className="mt-3 flex flex-wrap items-center gap-2">
            <Button type="button" size="sm" onClick={mint} disabled={busy}>
              <RiFlashlightLine size={14} className="mr-1.5" />
              {busy ? __("Creating…", "animation-addons-for-elementor") : c.quick.button}
            </Button>
          </div>
          <MintError err={err} />
          <p className="text-[12px] text-text-secondary mt-1.5">{c.quick.note}</p>
        </>
      ) : null}
    </div>
  );
}

function DevTab({ c }) {
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState("");
  const mintAndCopyPrompt = async () => {
    setBusy(true);
    setErr("");
    try {
      const r = await aiApi.createToken(`${c.name} ${__("token", "animation-addons-for-elementor")}`, ["read", "write"]);
      copy(c.prompt(r.token), __("Prompt copied — a real token is inside, ready to paste", "animation-addons-for-elementor"));
    } catch (e) {
      setErr(e.message);
      toast.error(e.message);
    } finally {
      setBusy(false);
    }
  };
  return (
    <div className="mt-4 rounded-xl bg-background-secondary/60 p-4">
      <Steps steps={c.steps} />
      <div className="mt-3 flex flex-wrap items-center gap-2">
        <Button type="button" size="sm" onClick={mintAndCopyPrompt} disabled={busy}>
          <RiFlashlightLine size={14} className="mr-1.5" />
          {busy ? __("Creating…", "animation-addons-for-elementor") : __("Copy prompt (mints a token)", "animation-addons-for-elementor")}
        </Button>
        <Button type="button" size="sm" variant="secondary" onClick={() => copy(c.config("<personal token>"), __("Template copied", "animation-addons-for-elementor"))}>
          <RiFileCopyLine size={13} className="mr-1.5" />
          {__("Copy template only", "animation-addons-for-elementor")}
        </Button>
      </div>
      <MintError err={err} />
      <details className="mt-3 text-[12px]">
        <summary className="cursor-pointer text-text-secondary hover:text-text">{c.configLabel}</summary>
        <pre className="mt-1.5 max-h-[180px] overflow-auto rounded-lg border border-border bg-background p-3 text-[11.5px] leading-relaxed">{c.config("<personal token>")}</pre>
      </details>
    </div>
  );
}

// The "Guide" button in the card header. Point it at the published doc.
const GUIDE_URL = "https://animation-addons.com/docs/getting-started/connect-ai-app/";

const NotAvailable = () => (
  <div className="border rounded-2xl p-6 shadow-common bg-background max-w-[560px]">
    <div className="flex gap-2 items-center">
      <RiSparklingLine size={20} color="#7C5CFC" />
      <p className="font-medium">
        {__("AI Configuration", "animation-addons-for-elementor")}
      </p>
    </div>
    <p className="text-sm text-text-secondary mt-3">
      {__(
        "This needs Animation Addons Pro, active, with the AI & Connections module on.",
        "animation-addons-for-elementor",
      )}
    </p>
  </div>
);

const AiConfiguration = () => {
  const boot = window.WCF_ADDONS_ADMIN?.addons_config?.ai || {
    available: false,
  };

  const [status, setStatus] = useState(null);
  const [open, setOpen] = useState("claude");

  useEffect(() => {
    if (!boot.available) return;
    aiApi
      .overview()
      .then((r) => setStatus(r.status))
      .catch((e) => toast.error(e.message));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (!boot.available) {
    return <NotAvailable />;
  }

  const mcpUrl = status?.mcp_url || "";
  const hosted = HOSTED_CLIENTS();
  const dev = DEV_CLIENTS(mcpUrl);

  return (
    <div className="border rounded-2xl p-8 shadow-common bg-background max-w-[960px]">
      <div className="flex items-start justify-between gap-3">
        <div className="flex gap-3 min-w-0">
          <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#7C5CFC1A]">
            <RiSparklingLine size={20} color="#7C5CFC" />
          </div>
          <div className="min-w-0">
            <h2 className="text-[18px] font-semibold">
              {__("Connect an AI app", "animation-addons-for-elementor")}
            </h2>
            <p className="text-sm text-text-secondary mt-1">
              {__(
                "One URL for Claude, ChatGPT or a coding agent to work on this site. The login happens on this site, in your browser, and you choose what the app may do.",
                "animation-addons-for-elementor",
              )}
            </p>
          </div>
        </div>
        <a
          href={GUIDE_URL}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-[13px] font-medium text-text hover:border-[#7C5CFC] hover:text-[#7C5CFC] transition-colors"
        >
          <RiBookOpenLine size={15} />
          {__("Guide", "animation-addons-for-elementor")}
        </a>
      </div>

      <div className="my-6 h-px bg-border" />

      {!status ? (
        <p className="text-sm text-text-secondary">{__("Loading…", "animation-addons-for-elementor")}</p>
      ) : (
        <>
          <div className="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-text-secondary">{__("Hosted chat apps", "animation-addons-for-elementor")}</div>
          <div className="grid gap-2 sm:grid-cols-3">
            {hosted.map((c) => (
              <button
                key={c.id}
                type="button"
                onClick={() => setOpen(c.id)}
                className={
                  "rounded-xl border px-3 py-2.5 text-left text-[12.5px] font-medium transition-colors " +
                  (open === c.id ? "border-[#7C5CFC] bg-[#7C5CFC1A] text-text" : "border-border hover:bg-background-secondary text-text-secondary")
                }
              >
                {c.name}
              </button>
            ))}
          </div>
          <div className="mb-1.5 mt-3 text-[11px] font-semibold uppercase tracking-wide text-text-secondary">{__("Developer tools", "animation-addons-for-elementor")}</div>
          <div className="grid gap-2 sm:grid-cols-3">
            {dev.map((c) => (
              <button
                key={c.id}
                type="button"
                onClick={() => setOpen(c.id)}
                className={
                  "rounded-xl border px-3 py-2.5 text-left text-[12.5px] font-medium transition-colors " +
                  (open === c.id ? "border-[#7C5CFC] bg-[#7C5CFC1A] text-text" : "border-border hover:bg-background-secondary text-text-secondary")
                }
              >
                {c.name}
              </button>
            ))}
          </div>

          {hosted.filter((c) => c.id === open).map((c) => <HostedTab key={c.id} c={c} mcpUrl={mcpUrl} blocked={status.discovery_blocked} />)}
          {dev.filter((c) => c.id === open).map((c) => <DevTab key={c.id} c={c} />)}
        </>
      )}

      <div className="my-6 h-px bg-border" />

      <div className="flex items-center justify-between gap-3 flex-wrap">
        <a
          href="admin.php?page=aaeaddon-connections"
          className="inline-flex items-center gap-1 text-[12.5px] text-text-secondary hover:text-text whitespace-nowrap"
        >
          {__("Need the full settings, personal tokens or your AI provider key? Manage them here", "animation-addons-for-elementor")}
          <RiArrowRightLine size={14} />
        </a>
        <a
          href="https://modelcontextprotocol.io/"
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-1 text-[12px] text-text-secondary hover:text-text"
        >
          {__("What is MCP?", "animation-addons-for-elementor")}
          <RiExternalLinkLine size={13} />
        </a>
      </div>
    </div>
  );
};

export default AiConfiguration;
