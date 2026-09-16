/**
 * Migration — the consent screen and report for the 4.2 storage-name move.
 *
 * Every number on this page is the server's `aaeaddon_migration_state`,
 * shipped as `WCF_ADDONS_ADMIN.addons_config.migration` and refreshed through
 * `aaeaddon_migration_status`. The page invents no wording of its own for a
 * row's outcome: the Details tables print the `items` map verbatim.
 *
 * Reachable ALWAYS — `?tab=migration` and the "Migration" submenu item exist
 * on a fresh site and after the move — so a person restoring an old database
 * months later can find the tool. Only the admin notice is gated on status.
 *
 * States (the server's `status`):
 *   awaiting_consent — an existing database; nothing copied yet. Consent block.
 *   needs_action     — the copy hit an error; nothing switched. Retry.
 *   complete         — done, or a fresh install. Report + backup + log.
 */
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { __, sprintf } from "@wordpress/i18n";
import { Loader2 } from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import { RiExchangeLine } from "react-icons/ri";
import { toast } from "sonner";

const initial = () => window.WCF_ADDONS_ADMIN?.addons_config?.migration || null;

const post = async (action, extra = {}) => {
  const res = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: new URLSearchParams({
      action,
      nonce: WCF_ADDONS_ADMIN.nonce,
      ...extra,
    }),
  });
  return res.json();
};

/**
 * Hand the viewer a file. This is wp-admin, not a sandboxed page, so an
 * anchor with `download` is the whole mechanism.
 */
const saveFile = (filename, text, type) => {
  const blob = new Blob([text], { type });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
};

const fmtDate = (ts) => (ts ? new Date(ts * 1000).toLocaleString() : "");

const STATUS_PILL = {
  waiting: ["bg-[#F3F4F6] text-[#4B5563]", __("Waiting", "animation-addons-for-elementor")],
  done: ["bg-[#E0FAEC] text-[#1A7544]", __("Done", "animation-addons-for-elementor")],
  error: ["bg-[#FDE8E8] text-[#B42318]", __("Error", "animation-addons-for-elementor")],
  kept: ["bg-[#EBF1FF] text-[#335CFF]", __("Kept as is", "animation-addons-for-elementor")],
};

const Pill = ({ status }) => {
  const [cls, label] = STATUS_PILL[status] || STATUS_PILL.waiting;
  return (
    <span className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ${cls}`}>
      {label}
    </span>
  );
};

const Card = ({ title, children, className = "" }) => (
  <section className={`bg-background rounded-lg p-5 ${className}`}>
    {title && (
      <h3 className="text-[15px] font-medium text-[var(--900,#181B25)] mb-3">{title}</h3>
    )}
    {children}
  </section>
);

/** One category: label · done/total · pill · expandable per-key outcomes. */
const Category = ({ id, cat, found }) => {
  const [open, setOpen] = useState(cat.errors > 0);
  const items = Object.entries(cat.items || {});
  const total = cat.total || found || 0;
  const errors = items.filter(([, v]) => String(v).startsWith("error"));
  const rest = items.filter(([, v]) => !String(v).startsWith("error"));
  return (
    <div className="border border-[#E5E7EB] rounded-lg p-4" data-aae-migration-category={id}>
      <div className="flex flex-wrap items-center gap-3">
        <span className="font-medium text-[var(--900,#181B25)]">{cat.label}</span>
        <span className="text-sm text-text-secondary tabular-nums">
          {cat.status === "waiting"
            ? sprintf(
                /* translators: %d: number of settings found */
                __("%d found", "animation-addons-for-elementor"),
                total,
              )
            : sprintf(
                /* translators: 1: copied, 2: total, 3: skipped */
                __("%1$d of %2$d copied · %3$d absent", "animation-addons-for-elementor"),
                cat.done,
                cat.total,
                cat.skipped,
              )}
        </span>
        <Pill status={cat.status} />
        {items.length > 0 && (
          <button
            type="button"
            className="ml-auto text-sm text-brand underline decoration-dotted"
            onClick={() => setOpen((o) => !o)}
          >
            {open
              ? __("Hide details", "animation-addons-for-elementor")
              : __("Details", "animation-addons-for-elementor")}
          </button>
        )}
      </div>
      {open && items.length > 0 && (
        <table className="mt-3 w-full text-sm">
          <tbody>
            {[...errors, ...rest].map(([key, outcome]) => (
              <tr key={key} className="border-t border-[#F3F4F6]">
                <td className="py-1 pr-4 font-mono text-xs text-text-secondary">{key}</td>
                <td
                  className={`py-1 text-xs ${
                    String(outcome).startsWith("error") ? "text-[#B42318]" : ""
                  }`}
                >
                  {outcome}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
};

const Migration = () => {
  const [m, setM] = useState(initial);
  const [busy, setBusy] = useState("");
  const [agreed, setAgreed] = useState(false);
  const fileInput = useRef(null);

  const refresh = useCallback(async () => {
    const r = await post("aaeaddon_migration_status");
    if (r?.success) setM(r.data);
    return r;
  }, []);

  useEffect(() => {
    // The page payload carries no log or counts (it rides every dashboard
    // load); ask for the full picture once the page is actually open.
    refresh();
  }, [refresh]);

  if (!m) {
    return (
      <Card>
        <p className="text-sm text-text-secondary">
          {__("Loading…", "animation-addons-for-elementor")}
        </p>
      </Card>
    );
  }

  const status = m.status;
  const found = m.found || {};
  const foundTotal = Object.values(found).reduce((a, b) => a + b, 0);
  const cats = m.categories || {};
  const anyErrors = Object.values(cats).some((c) => c.errors > 0);

  const start = async () => {
    setBusy("start");
    try {
      const r = await post("aaeaddon_migration_start");
      if (r?.success) {
        setM(r.data);
        toast.success(__("Done. Your settings are on the new storage names.", "animation-addons-for-elementor"));
      } else {
        if (r?.data?.migration) setM(r.data.migration);
        toast.error(r?.data?.message || __("Something went wrong.", "animation-addons-for-elementor"));
      }
    } finally {
      setBusy("");
    }
  };

  const exportBackup = async () => {
    setBusy("export");
    try {
      const r = await post("aaeaddon_migration_export");
      if (r?.success) {
        saveFile(r.data.filename, JSON.stringify(r.data.backup, null, 2), "application/json");
        refresh();
      } else {
        toast.error(__("Could not build the backup.", "animation-addons-for-elementor"));
      }
    } finally {
      setBusy("");
    }
  };

  const report = async () => {
    setBusy("report");
    try {
      const r = await post("aaeaddon_migration_report");
      if (r?.success) saveFile(r.data.filename, r.data.report, "text/plain");
    } finally {
      setBusy("");
    }
  };

  const importBackup = async (file) => {
    if (!file) return;
    setBusy("import");
    try {
      const text = await file.text();
      const r = await post("aaeaddon_migration_import", { payload: text });
      if (r?.success) {
        setM(r.data.migration);
        toast.success(
          sprintf(
            /* translators: 1: restored count, 2: ignored count */
            __("Restored %1$d setting(s); %2$d ignored.", "animation-addons-for-elementor"),
            r.data.restored,
            r.data.ignored,
          ),
        );
      } else {
        toast.error(r?.data?.message || __("That file could not be restored.", "animation-addons-for-elementor"));
      }
    } finally {
      setBusy("");
      if (fileInput.current) fileInput.current.value = "";
    }
  };

  /* ---- banner ---------------------------------------------------------- */
  let banner;
  if (status === "awaiting_consent") {
    banner = {
      tone: "bg-[#FFF8EB] border-[#FFE3A3]",
      text: __(
        "Animation Addons 4.2 uses new storage names. Your site is already working on them — this page copies your existing data across, once, when you choose. Nothing is changed or deleted.",
        "animation-addons-for-elementor",
      ),
    };
  } else if (status === "needs_action") {
    banner = {
      tone: "bg-[#FDE8E8] border-[#F5B5B5]",
      text: __(
        "One step needs you: some rows could not be copied. Nothing was switched — your site keeps running on the old names. Press Retry; if it repeats, download the report and send it to support.",
        "animation-addons-for-elementor",
      ),
    };
  } else {
    banner = {
      tone: "bg-[#E0FAEC] border-[#B7EBCB]",
      text:
        m.site === "fresh"
          ? __(
              "Nothing to migrate: this site was installed on the new storage names.",
              "animation-addons-for-elementor",
            )
          : sprintf(
              /* translators: %s: date */
              __(
                "Done on %s. Your data is on the new storage names; the old copy is kept in step with it, untouched, so an older version of the plugin would still find everything.",
                "animation-addons-for-elementor",
              ),
              fmtDate(m.finished_at),
            ),
    };
  }

  return (
    <div className="flex flex-col gap-5" data-aae-migration-status={status}>
      <div className="flex items-center gap-2">
        <RiExchangeLine size={20} className="text-[var(--900,#181B25)]" />
        <h2 className="text-[18px] font-medium text-[var(--900,#181B25)]">
          {__("Storage Migration", "animation-addons-for-elementor")}
        </h2>
      </div>

      <div className={`rounded-lg border p-4 text-sm ${banner.tone}`} data-aae-migration-banner>
        {banner.text}
      </div>

      {/* ---- consent ---------------------------------------------------- */}
      {status === "awaiting_consent" && (
        <Card title={__("Before you start", "animation-addons-for-elementor")}>
          <ol className="list-decimal pl-5 text-sm space-y-2 text-text-secondary">
            <li>
              <strong className="text-[var(--900,#181B25)]">
                {__("What this does.", "animation-addons-for-elementor")}
              </strong>{" "}
              {sprintf(
                /* translators: 1: settings count, 2: pro settings count, 3: menu settings count */
                __(
                  "Copies your Animation Addons settings to the storage names WordPress.org requires: %1$d settings, %2$d Pro settings, %3$d menu settings. Your current data is not changed or deleted — the copy is written beside it.",
                  "animation-addons-for-elementor",
                ),
                found.options || 0,
                found.options_pro || 0,
                found.prefixes || 0,
              )}
            </li>
            <li>
              <strong className="text-[var(--900,#181B25)]">
                {__("How long.", "animation-addons-for-elementor")}
              </strong>{" "}
              {__("A moment — it is one request. Post data, templates and caches are not touched.", "animation-addons-for-elementor")}
            </li>
            <li>
              <strong className="text-[var(--900,#181B25)]">
                {__("What you should do first.", "animation-addons-for-elementor")}
              </strong>{" "}
              {__("Take a backup of your database (your host's backup tool or a plugin), or download the settings backup below.", "animation-addons-for-elementor")}{" "}
              {m.pro?.installed &&
                (m.pro.ok
                  ? sprintf(
                      /* translators: %s: Pro version */
                      __("Animation Addons Pro %s is installed and up to date. ✓", "animation-addons-for-elementor"),
                      m.pro.version,
                    )
                  : sprintf(
                      /* translators: 1: installed Pro version, 2: required Pro version */
                      __("Animation Addons Pro %1$s is installed; update it to %2$s or newer when you can — it keeps working either way.", "animation-addons-for-elementor"),
                      m.pro.version,
                      m.pro.min,
                    ))}
            </li>
            <li>
              <strong className="text-[var(--900,#181B25)]">
                {__("Afterwards.", "animation-addons-for-elementor")}
              </strong>{" "}
              {__("Nothing changes for you. The old data stays as a live copy, never deleted, so downgrading the plugin restores the previous behaviour at any time.", "animation-addons-for-elementor")}
            </li>
          </ol>
          <label className="mt-4 flex items-center gap-2 text-sm cursor-pointer">
            <Checkbox
              checked={agreed}
              onCheckedChange={(v) => setAgreed(v === true)}
              data-aae-migration-consent
            />
            {__("I have a backup and want to start the migration", "animation-addons-for-elementor")}
          </label>
          <div className="mt-4 flex flex-wrap gap-2">
            <Button disabled={!agreed || !!busy} onClick={start} data-aae-migration-start>
              {busy === "start" && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {__("Start migration", "animation-addons-for-elementor")}
            </Button>
            <Button variant="outline" disabled={!!busy} onClick={exportBackup}>
              {__("Download backup (JSON)", "animation-addons-for-elementor")}
            </Button>
          </div>
        </Card>
      )}

      {/* ---- categories ------------------------------------------------- */}
      <Card title={__("What moves, and what stays", "animation-addons-for-elementor")}>
        <div className="flex flex-col gap-3">
          {Object.entries(cats).map(([id, cat]) => (
            <Category key={id} id={id} cat={cat} found={found[id]} />
          ))}
          <div className="border border-[#E5E7EB] rounded-lg p-4">
            <div className="flex flex-wrap items-center gap-3">
              <span className="font-medium text-[var(--900,#181B25)]">
                {__("Post data, templates, caches, scheduled tasks, form tables", "animation-addons-for-elementor")}
              </span>
              <Pill status="kept" />
            </div>
            <p className="mt-2 text-sm text-text-secondary">
              {sprintf(
                /* translators: 1: post meta keys, 2: term meta keys, 3: user meta keys, 4: transients, 5: cron hooks, 6: tables */
                __(
                  "%1$d post fields, %2$d category fields, %3$d user fields, %4$d caches, %5$d scheduled tasks and %6$d form tables keep their names. They are not part of the WordPress.org rule, and several are what your existing templates and the Pro add-on sort by.",
                  "animation-addons-for-elementor",
                ),
                m.kept?.postmeta || 0,
                m.kept?.termmeta || 0,
                m.kept?.usermeta || 0,
                m.kept?.transients || 0,
                m.kept?.cron || 0,
                m.kept?.tables || 0,
              )}
            </p>
          </div>
        </div>
      </Card>

      {/* ---- actions ---------------------------------------------------- */}
      <Card title={__("Actions", "animation-addons-for-elementor")}>
        <div className="flex flex-wrap gap-2">
          {status === "needs_action" && (
            <Button disabled={!!busy} onClick={start} data-aae-migration-retry>
              {busy === "start" && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {__("Retry failed", "animation-addons-for-elementor")}
            </Button>
          )}
          {m.pro?.installed && !m.pro.ok && (
            <Button variant="outline" asChild>
              <a href={m.plugins_url}>{__("Update Animation Addons Pro", "animation-addons-for-elementor")}</a>
            </Button>
          )}
          <Button variant="outline" disabled={!!busy} onClick={exportBackup} data-aae-migration-export>
            {busy === "export" && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {__("Download backup (JSON)", "animation-addons-for-elementor")}
          </Button>
          <Button variant="outline" disabled={!!busy} onClick={() => fileInput.current?.click()}>
            {busy === "import" && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {__("Restore a backup…", "animation-addons-for-elementor")}
          </Button>
          <input
            ref={fileInput}
            type="file"
            accept="application/json,.json"
            className="hidden"
            data-aae-migration-import
            onChange={(e) => importBackup(e.target.files?.[0])}
          />
          <Button variant="outline" disabled={!!busy} onClick={report} data-aae-migration-report>
            {__("Download report", "animation-addons-for-elementor")}
          </Button>
        </div>
        {m.pro?.installed && !m.pro.ok && (
          <p className="mt-3 text-xs text-text-secondary">
            {sprintf(
              /* translators: 1: installed Pro version, 2: required Pro version */
              __("Pro %1$s still reads the old names through the compatibility layer; update to %2$s or newer for the best result. It never blocks anything here.", "animation-addons-for-elementor"),
              m.pro.version,
              m.pro.min,
            )}
          </p>
        )}
        {m.backup?.exported_at && (
          <p className="mt-3 text-xs text-text-secondary">
            {sprintf(
              /* translators: %s: date */
              __("Last backup downloaded %s.", "animation-addons-for-elementor"),
              fmtDate(m.backup.exported_at),
            )}
          </p>
        )}
      </Card>

      {/* ---- instructions ----------------------------------------------- */}
      <Card title={__("How this works", "animation-addons-for-elementor")}>
        <ul className="list-disc pl-5 text-sm space-y-1 text-text-secondary">
          <li>{__("What is happening: settings are copied to new names, never moved. The old data is kept and stays in step with the new copy.", "animation-addons-for-elementor")}</li>
          <li>{__("What you should do: nothing is required. Start the migration when you have a backup; update Animation Addons Pro if the row above says so.", "animation-addons-for-elementor")}</li>
          <li>{__("If something goes wrong: press Retry, download the report, or restore a backup. Downgrading the plugin restores the previous behaviour at any time, because the old data is still there.", "animation-addons-for-elementor")}</li>
        </ul>
      </Card>

      {/* ---- log -------------------------------------------------------- */}
      {Array.isArray(m.log) && m.log.length > 0 && (
        <Card title={__("Log", "animation-addons-for-elementor")}>
          <ul className="text-xs font-mono space-y-1 text-text-secondary" data-aae-migration-log>
            {m.log
              .slice()
              .reverse()
              .map(([ts, line], i) => (
                <li key={i}>
                  <span className="text-text-tertiary">{fmtDate(ts)}</span> {line}
                </li>
              ))}
          </ul>
        </Card>
      )}
      {anyErrors && null}
    </div>
  );
};

export default Migration;
