import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { __, sprintf } from "@wordpress/i18n";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import {
  RiSparklingLine,
  RiFlashlightLine,
  RiCheckLine,
  RiCloseLine,
  RiArrowRightLine,
  RiExternalLinkLine,
  RiEyeLine,
  RiEyeOffLine,
  RiLockLine,
  RiDeleteBinLine,
} from "react-icons/ri";
import { SiOpenai, SiGooglegemini, SiAnthropic } from "react-icons/si";
import { aiApi } from "@/lib/aiApi";

/**
 * The AI provider / API-key form, ported from Pro's "AI & Connections" screen
 * (`AiSettings()` in animation-addons-for-elementor-pro/src/modules/platform/
 * connections/App.jsx) onto this app's own UI kit, so it lives in the
 * Dashboard's own "AI Configuration" tab instead of a separate page. Reads
 * and writes go through the exact same REST routes that screen already uses
 * (`@/lib/aiApi`) - same storage, same encryption, same rate limit; only the
 * form itself is duplicated.
 *
 * This page is only ever reached when `addons_config.ai.available` is true
 * (see config/nav/main-nav.jsx), but the inline notice below covers a direct
 * link/bookmark to `?tab=ai-configuration` on a site where Pro isn't active.
 */
const PROVIDERS = [
  {
    key: "anthropic",
    color: "#D97757",
    Icon: SiAnthropic,
    keyUrl: "https://console.anthropic.com/settings/keys",
  },
  {
    key: "openai",
    color: "#10A37F",
    Icon: SiOpenai,
    keyUrl: "https://platform.openai.com/api-keys",
  },
  {
    key: "gemini",
    color: "#4870FF",
    Icon: SiGooglegemini,
    keyUrl: "https://aistudio.google.com/apikey",
  },
];
const PROVIDER_META = Object.fromEntries(PROVIDERS.map((p) => [p.key, p]));

const ActivePill = ({ active, children }) => (
  <span
    className={
      "inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[12px] font-medium " +
      (active ? "bg-[#E6F9EF] text-[#1A7544]" : "bg-background-secondary text-text-tertiary")
    }
  >
    {active ? <span className="h-1.5 w-1.5 rounded-full bg-[#1A7544]" /> : null}
    {children}
  </span>
);

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

  const [ai, setAi] = useState(boot);
  const connected = PROVIDERS.filter((p) => (ai.keys && ai.keys[p.key]?.set));
  const [editing, setEditing] = useState(
    (connected[0] && connected[0].key) || PROVIDERS[0].key,
  );
  const [keyInput, setKeyInput] = useState("");
  const [showKey, setShowKey] = useState(false);
  const [modelInput, setModelInput] = useState("");
  const [busy, setBusy] = useState(false);
  const [test, setTest] = useState(null);

  useEffect(() => {
    setKeyInput("");
    setShowKey(false);
    setModelInput(
      ai.models && ai.models[editing] !== (ai.defaults && ai.defaults[editing])
        ? ai.models[editing] || ""
        : "",
    );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [editing]);

  if (!boot.available) {
    return <NotAvailable />;
  }

  const keyState = (p) => (ai.keys && ai.keys[p]) || { set: false, hint: "" };
  const meta = PROVIDER_META[editing];

  const applyFreshAi = (fresh) => {
    setAi(fresh);
    if (window.WCF_ADDONS_ADMIN?.addons_config) {
      window.WCF_ADDONS_ADMIN.addons_config.ai = {
        ...window.WCF_ADDONS_ADMIN.addons_config.ai,
        ...fresh,
      };
    }
  };

  const save = async () => {
    setBusy(true);
    try {
      const patch = { model: { [editing]: modelInput } };
      if (keyInput.trim()) {
        patch.keys = { [editing]: keyInput.trim() };
      }
      if (!keyState(editing).set) {
        patch.provider = editing;
      }
      const r = await aiApi.save(patch);
      applyFreshAi(r.ai);
      setKeyInput("");
      toast.success(
        __("Connection saved", "animation-addons-for-elementor"),
      );
    } catch (e) {
      toast.error(e.message);
    } finally {
      setBusy(false);
    }
  };

  const setActive = async (p) => {
    try {
      const r = await aiApi.save({ provider: p });
      applyFreshAi(r.ai);
    } catch (e) {
      toast.error(e.message);
    }
  };

  const removeKey = async (p) => {
    try {
      const r = await aiApi.save({ keys: { [p]: "" } });
      applyFreshAi(r.ai);
      if (editing === p) setKeyInput("");
    } catch (e) {
      toast.error(e.message);
    }
  };

  const run = async () => {
    setTest({ running: true });
    try {
      setTest(await aiApi.test());
    } catch (e) {
      setTest({ ok: false, error: e.message });
    }
  };

  return (
    <div className="border rounded-2xl p-8 shadow-common bg-background max-w-[960px]">
      <div className="flex items-start justify-between gap-3">
        <div className="flex gap-3 min-w-0">
          <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#7C5CFC1A]">
            <RiSparklingLine size={20} color="#7C5CFC" />
          </div>
          <div className="min-w-0">
            <h2 className="text-[18px] font-semibold">
              {__("AI assistant", "animation-addons-for-elementor")}
            </h2>
            <p className="text-sm text-text-secondary mt-1">
              {__(
                "Connect your AI provider to power “Describe it to AI”, field suggestions and sample entries.",
                "animation-addons-for-elementor",
              )}
            </p>
          </div>
        </div>
        <ActivePill active={!!ai.active}>
          {ai.active
            ? sprintf(
                __("Active: %s", "animation-addons-for-elementor"),
                (ai.labels && ai.labels[ai.active]) || ai.active,
              )
            : __("Not configured", "animation-addons-for-elementor")}
        </ActivePill>
      </div>

      <div className="mt-4 flex items-center gap-1.5 text-[12px] text-text-secondary">
        <RiLockLine size={13} />
        {__(
          "Your keys are encrypted on this server and never sent to the browser.",
          "animation-addons-for-elementor",
        )}
      </div>

      <div className="my-6 h-px bg-border" />

      <div className="grid gap-8 md:grid-cols-[220px_1fr]">
        <div>
          <h3 className="text-[15px] font-semibold">
            {__("Connect a provider", "animation-addons-for-elementor")}
          </h3>
          <p className="text-[13px] text-text-secondary mt-1">
            {__(
              "Choose a provider, add your API key, and select a model.",
              "animation-addons-for-elementor",
            )}
          </p>
        </div>

        <div className="grid gap-4 min-w-0">
          <div className="grid gap-2">
            <Label>{__("Provider", "animation-addons-for-elementor")}</Label>
            <Select value={editing} onValueChange={setEditing}>
              <SelectTrigger className="gap-2">
                <SelectValue>
                  <span className="flex items-center gap-2">
                    <meta.Icon size={15} color={meta.color} />
                    {(ai.labels && ai.labels[editing]) || editing}
                  </span>
                </SelectValue>
              </SelectTrigger>
              <SelectContent>
                {PROVIDERS.map((p) => (
                  <SelectItem key={p.key} value={p.key}>
                    <span className="flex items-center gap-2">
                      <p.Icon size={15} color={p.color} />
                      {(ai.labels && ai.labels[p.key]) || p.key}
                    </span>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-2">
            <Label>{__("API key", "animation-addons-for-elementor")}</Label>
            <div className="relative">
              <Input
                type={showKey ? "text" : "password"}
                autoComplete="off"
                className="pr-10"
                placeholder={
                  keyState(editing).set
                    ? keyState(editing).hint
                    : __("Paste your API key", "animation-addons-for-elementor")
                }
                value={keyInput}
                onChange={(e) => setKeyInput(e.target.value)}
              />
              <button
                type="button"
                onClick={() => setShowKey((v) => !v)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hover:text-text"
                aria-label={__("Show or hide the key", "animation-addons-for-elementor")}
              >
                {showKey ? <RiEyeOffLine size={16} /> : <RiEyeLine size={16} />}
              </button>
            </div>
            <p className="text-[12px] text-text-secondary">
              {keyState(editing).set
                ? __(
                    "A key is already saved. Enter a new key to replace it.",
                    "animation-addons-for-elementor",
                  )
                : __(
                    "Nothing saved yet for this provider.",
                    "animation-addons-for-elementor",
                  )}
            </p>
          </div>

          <div className="grid gap-2">
            <Label>{__("Model", "animation-addons-for-elementor")}</Label>
            <Input
              className="font-mono text-[13px]"
              placeholder={(ai.defaults && ai.defaults[editing]) || ""}
              value={modelInput}
              onChange={(e) => setModelInput(e.target.value)}
            />
          </div>

          <div className="flex items-center justify-between gap-3 flex-wrap pt-1">
            <a
              href={meta.keyUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1 text-[12.5px] text-[#7C5CFC] hover:underline"
            >
              {__("Get an API key", "animation-addons-for-elementor")}
              <RiExternalLinkLine size={13} />
            </a>
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                variant="secondary"
                onClick={run}
                disabled={!ai.active}
              >
                <RiFlashlightLine size={14} className="mr-1.5" />
                {__("Test connection", "animation-addons-for-elementor")}
              </Button>
              <Button size="sm" onClick={save} disabled={busy}>
                {busy
                  ? __("Saving…", "animation-addons-for-elementor")
                  : __("Save connection", "animation-addons-for-elementor")}
                <RiArrowRightLine size={14} className="ml-1.5" />
              </Button>
            </div>
          </div>

          {test && !test.running ? (
            <p className="text-[12.5px] flex items-center gap-1.5 -mt-1">
              {test.ok ? (
                <RiCheckLine className="text-[#1A7544] shrink-0" />
              ) : (
                <RiCloseLine className="text-[#E7350F] shrink-0" />
              )}
              <span>
                {test.ok
                  ? sprintf(
                      __("%s answered (%s).", "animation-addons-for-elementor"),
                      test.provider,
                      test.model || "",
                    )
                  : test.error || __("No answer.", "animation-addons-for-elementor")}
              </span>
            </p>
          ) : test && test.running ? (
            <p className="text-[12.5px] text-text-secondary -mt-1">
              {__("Asking…", "animation-addons-for-elementor")}
            </p>
          ) : null}
        </div>
      </div>

      <div className="my-6 h-px bg-border" />

      <div className="flex items-center gap-2">
        <h3 className="text-[15px] font-semibold">
          {__("Connected providers", "animation-addons-for-elementor")}
        </h3>
        <span className="inline-flex items-center justify-center rounded-full bg-[#7C5CFC1A] px-2 py-0.5 text-[12px] font-medium text-[#7C5CFC]">
          {connected.length}
        </span>
      </div>
      <p className="text-[13px] text-text-secondary mt-1">
        {__(
          "Manage the providers connected to this workspace.",
          "animation-addons-for-elementor",
        )}
      </p>

      <div className="mt-4 rounded-xl border border-border divide-y divide-border overflow-hidden">
        {connected.length ? (
          connected.map((p) => (
            <div
              key={p.key}
              className="flex items-center gap-3 px-4 py-3 flex-wrap"
            >
              <p.Icon size={16} color={p.color} className="shrink-0" />
              <span className="font-medium text-[13.5px]">
                {(ai.labels && ai.labels[p.key]) || p.key}
              </span>
              <span className="text-text-tertiary">|</span>
              <span className="text-[12.5px] text-text-secondary truncate">
                {(ai.models && ai.models[p.key]) ||
                  (ai.defaults && ai.defaults[p.key])}
              </span>
              <div className="ml-auto flex items-center gap-4">
                {ai.provider === p.key ? (
                  <ActivePill active>
                    {__("Active", "animation-addons-for-elementor")}
                  </ActivePill>
                ) : (
                  <button
                    type="button"
                    onClick={() => setActive(p.key)}
                    className="text-[12.5px] text-[#7C5CFC] hover:underline"
                  >
                    {__("Set as active", "animation-addons-for-elementor")}
                  </button>
                )}
                <button
                  type="button"
                  onClick={() => setEditing(p.key)}
                  className="text-[12.5px] text-[#7C5CFC] hover:underline"
                >
                  {__("Edit", "animation-addons-for-elementor")}
                </button>
                <button
                  type="button"
                  onClick={() => removeKey(p.key)}
                  className="text-text-secondary hover:text-[#E7350F]"
                  aria-label={__("Remove key", "animation-addons-for-elementor")}
                >
                  <RiDeleteBinLine size={15} />
                </button>
              </div>
            </div>
          ))
        ) : (
          <div className="px-4 py-6 text-center text-[13px] text-text-secondary">
            {__(
              "No providers connected yet — add a key above to get started.",
              "animation-addons-for-elementor",
            )}
          </div>
        )}
      </div>

      <div className="my-6 h-px bg-border" />

      <div className="flex items-center justify-between gap-3 flex-wrap">
        <a
          href="admin.php?page=aaeaddon-connections"
          className="inline-flex items-center gap-1 text-[12.5px] text-text-secondary hover:text-text whitespace-nowrap"
        >
          {__("Manage MCP connections & tokens", "animation-addons-for-elementor")}
          <RiArrowRightLine size={14} />
        </a>
        <span className="inline-flex items-center gap-1.5 text-[12px] text-text-secondary">
          <RiLockLine size={13} />
          {__("Keys stay on your server", "animation-addons-for-elementor")}
        </span>
      </div>
    </div>
  );
};

export default AiConfiguration;
