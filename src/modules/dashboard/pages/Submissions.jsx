/**
 * AAE Forms — "Form Submissions" dashboard tab (Milestone 9).
 *
 * Lives inside the Animation Addon React dashboard
 * (admin.php?page=wcf_addons_settings&tab=submissions) and reuses its
 * shadcn UI kit so it inherits the dashboard theme. Data comes from the
 * aae/v1/admin/* REST routes (cookie auth + X-WP-Nonce, manage_options),
 * localized as AAE_FORMS_ADMIN on the dashboard bundle.
 */
import { __, sprintf } from "@wordpress/i18n";
import { useCallback, useEffect, useState } from "react";
import { toast } from "sonner";
import {
  Download,
  Eye,
  Inbox,
  Loader2,
  Plug,
  RefreshCw,
  RotateCw,
  Trash2,
  X,
} from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

const PER_PAGE = 20;

const cfg = () => window.AAE_FORMS_ADMIN || { restUrl: "", nonce: "", csvUrl: "" };

/**
 * Join a route + query onto restUrl safely. With plain permalinks the REST
 * base is already a query string (index.php?rest_route=/aae/v1/admin/), so
 * appending "submissions?page=1" verbatim produces a second "?" and WP
 * answers "No route was found" — the params must join with "&" there.
 */
const restJoin = (path) => {
  const [route, query = ""] = String(path).split("?");
  let url = cfg().restUrl + route;
  if (query) {
    url += (url.includes("?") ? "&" : "?") + query;
  }
  return url;
};

const api = async (path, options = {}) => {
  const response = await fetch(restJoin(path), {
    ...options,
    headers: {
      "X-WP-Nonce": cfg().nonce,
      ...(options.body ? { "Content-Type": "application/json" } : {}),
    },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data?.message || `HTTP ${response.status}`);
  }
  return data;
};

/* ------------------------------------------------------------------ */
/* Shared bits                                                         */
/* ------------------------------------------------------------------ */

/**
 * Status → tailwind classes. Explicit color-coding (not shadcn's 3 variants)
 * so "new/success" reads green, "failed" red, "retrying/pending" amber at a
 * glance. Uses the dashboard's own gray/brand tokens where it can.
 */
const STATUS_CLASS = {
  new: "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20",
  success: "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20",
  failed: "bg-red-50 text-red-700 ring-1 ring-red-600/20",
  retrying: "bg-amber-50 text-amber-700 ring-1 ring-amber-600/20",
  pending: "bg-amber-50 text-amber-700 ring-1 ring-amber-600/20",
  processing: "bg-blue-50 text-blue-700 ring-1 ring-blue-600/20",
  cancelled: "bg-gray-100 text-gray-500 ring-1 ring-gray-500/20",
};

const StatusPill = ({ status }) => (
  <span
    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
      STATUS_CLASS[status] || STATUS_CLASS.cancelled
    }`}
  >
    {status}
  </span>
);

const Pager = ({ total, page, setPage }) => {
  const pages = Math.max(1, Math.ceil(total / PER_PAGE));
  return (
    <div className="flex items-center gap-3 text-sm text-text-secondary">
      <span>
        <span className="font-semibold text-text">{total}</span>{" "}
        {__("items", "animation-addons-for-elementor")}
      </span>
      <Button
        variant="outline"
        size="sm"
        className="transition-colors hover:border-brand hover:text-brand disabled:opacity-40"
        disabled={page <= 1}
        onClick={() => setPage(page - 1)}
      >
        {__("Prev", "animation-addons-for-elementor")}
      </Button>
      <span className="tabular-nums">
        {page} / {pages}
      </span>
      <Button
        variant="outline"
        size="sm"
        className="transition-colors hover:border-brand hover:text-brand disabled:opacity-40"
        disabled={page >= pages}
        onClick={() => setPage(page + 1)}
      >
        {__("Next", "animation-addons-for-elementor")}
      </Button>
    </div>
  );
};

const LoadingRows = ({ cols }) => (
  <>
    {[1, 2, 3].map((i) => (
      <TableRow key={i}>
        <TableCell colSpan={cols}>
          <Skeleton className="h-5 w-full" />
        </TableCell>
      </TableRow>
    ))}
  </>
);

/* ------------------------------------------------------------------ */
/* Submissions tab                                                     */
/* ------------------------------------------------------------------ */

/** "1.2 MB" style size for file-field download links. */
const formatBytes = (bytes) => {
  if (!bytes || bytes < 1024) return `${bytes || 0} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

/** yyyy-mm-dd for "today minus N days" in the browser's local time. */
const daysAgo = (n) => {
  const d = new Date();
  d.setDate(d.getDate() - n);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const DATE_PRESETS = [
  { key: "all", label: __("All time", "animation-addons-for-elementor"), from: () => "", to: () => "" },
  { key: "today", label: __("Today", "animation-addons-for-elementor"), from: () => daysAgo(0), to: () => daysAgo(0) },
  { key: "7d", label: __("Last 7 days", "animation-addons-for-elementor"), from: () => daysAgo(6), to: () => daysAgo(0) },
  { key: "30d", label: __("Last 30 days", "animation-addons-for-elementor"), from: () => daysAgo(29), to: () => daysAgo(0) },
  { key: "custom", label: __("Custom range…", "animation-addons-for-elementor"), from: null, to: null },
];

const EMPTY_FILTERS = { form_key: "", from: "", to: "", s: "" };

const SubmissionsTab = ({ presetFormKey }) => {
  const [filters, setFilters] = useState({ ...EMPTY_FILTERS, form_key: presetFormKey || "" });
  const [datePreset, setDatePreset] = useState("all");
  const [search, setSearch] = useState(""); // debounced into filters.s
  const [page, setPage] = useState(1);
  const [data, setData] = useState({ rows: [], total: 0, forms: [] });
  const [loading, setLoading] = useState(true);
  const [selected, setSelected] = useState([]);
  const [detailId, setDetailId] = useState(null);

  // Filters apply live — a select/date change refreshes immediately, the
  // search box debounces while typing. No "Filter" button to remember.
  const patchFilters = (patch) => {
    setPage(1);
    setFilters((current) => ({ ...current, ...patch }));
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      setPage(1);
      setFilters((current) => (current.s === search ? current : { ...current, s: search }));
    }, 400);
    return () => clearTimeout(timer);
  }, [search]);

  // "View" jump from the Form Health tab.
  useEffect(() => {
    if (presetFormKey) {
      patchFilters({ form_key: presetFormKey });
    }
  }, [presetFormKey]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const query = new URLSearchParams({ page, ...filters });
      setData(await api(`submissions?${query}`));
      setSelected([]);
    } catch (error) {
      toast.error(error.message);
    } finally {
      setLoading(false);
    }
  }, [page, filters]);

  useEffect(() => {
    load();
  }, [load]);

  const hasFilters = Object.values(filters).some((v) => v !== "");

  const clearFilters = () => {
    setDatePreset("all");
    setSearch("");
    setPage(1);
    setFilters(EMPTY_FILTERS);
  };

  const applyDatePreset = (key) => {
    setDatePreset(key);
    const preset = DATE_PRESETS.find((p) => p.key === key);
    if (preset && preset.from) {
      patchFilters({ from: preset.from(), to: preset.to() });
    } else if (key === "all") {
      patchFilters({ from: "", to: "" });
    }
    // "custom" keeps the current values — the date inputs take over.
  };

  const bulkDelete = async () => {
    if (!selected.length) return;
    if (!window.confirm(__("Delete selected submissions?", "animation-addons-for-elementor"))) return;
    try {
      const result = await api("submissions/delete", {
        method: "POST",
        body: JSON.stringify({ ids: selected }),
      });
      toast.success(`${result.deleted} ${__("submission(s) deleted", "animation-addons-for-elementor")}`);
      load();
    } catch (error) {
      toast.error(error.message);
    }
  };

  const csvExport = () => {
    const query = new URLSearchParams(
      Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ""))
    );
    window.open(`${cfg().csvUrl}&${query}`, "_blank");
  };

  const toggle = (id) =>
    setSelected((current) =>
      current.includes(id) ? current.filter((x) => x !== id) : [...current, id]
    );

  return (
    <div className="space-y-4">
      {/* Filter bar — everything applies live */}
      <div className="flex flex-wrap items-center gap-2">
        <Select
          value={filters.form_key || "all"}
          onValueChange={(value) =>
            patchFilters({ form_key: value === "all" ? "" : value })
          }
        >
          <SelectTrigger className="w-[240px]">
            <SelectValue placeholder={__("All forms", "animation-addons-for-elementor")} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">
              {__("All forms", "animation-addons-for-elementor")}
            </SelectItem>
            {data.forms.map((form) => (
              <SelectItem key={form.key} value={form.key}>
                <span className="flex items-center gap-2">
                  <span className="truncate max-w-[150px]">{form.label}</span>
                  <span className="text-xs text-muted-foreground">({form.count})</span>
                </span>
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Select value={datePreset} onValueChange={applyDatePreset}>
          <SelectTrigger className="w-[150px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {DATE_PRESETS.map((preset) => (
              <SelectItem key={preset.key} value={preset.key}>
                {preset.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {datePreset === "custom" && (
          <>
            <Input
              type="date"
              className="w-[150px]"
              value={filters.from}
              onChange={(e) => patchFilters({ from: e.target.value })}
            />
            <Input
              type="date"
              className="w-[150px]"
              value={filters.to}
              onChange={(e) => patchFilters({ to: e.target.value })}
            />
          </>
        )}

        <Input
          type="search"
          className="w-[230px]"
          placeholder={__("Search values (e.g. an email)…", "animation-addons-for-elementor")}
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />

        {hasFilters && (
          <Button
            variant="ghost"
            size="sm"
            className="gap-1 text-text-secondary hover:text-brand"
            onClick={clearFilters}
          >
            <X className="h-3.5 w-3.5" />
            {__("Clear", "animation-addons-for-elementor")}
          </Button>
        )}

        <div className="ml-auto flex gap-2">
          <Button
            variant="outline"
            className="transition-colors hover:border-brand hover:text-brand"
            onClick={csvExport}
          >
            <Download className="w-4 h-4 mr-2" />
            {__("Export CSV", "animation-addons-for-elementor")}
          </Button>
          <Button
            variant="destructive"
            className="transition-opacity disabled:opacity-40"
            disabled={!selected.length}
            onClick={bulkDelete}
          >
            <Trash2 className="w-4 h-4 mr-2" />
            {__("Delete", "animation-addons-for-elementor")} ({selected.length})
          </Button>
        </div>
      </div>

      {selected.length > 0 && (
        <div className="flex items-center gap-2 rounded-lg bg-brand/[0.06] px-3 py-2 text-sm text-text-secondary ring-1 ring-brand/20">
          <span className="font-medium text-text">{selected.length}</span>
          {__("selected", "animation-addons-for-elementor")}
          <button
            type="button"
            className="ml-auto text-xs underline underline-offset-2 hover:text-brand"
            onClick={() => setSelected([])}
          >
            {__("Clear selection", "animation-addons-for-elementor")}
          </button>
        </div>
      )}

      {/* List */}
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="w-8">
              <input
                type="checkbox"
                className="cursor-pointer accent-brand"
                checked={data.rows.length > 0 && selected.length === data.rows.length}
                onChange={(e) =>
                  setSelected(e.target.checked ? data.rows.map((r) => r.id) : [])
                }
              />
            </TableHead>
            <TableHead>ID</TableHead>
            <TableHead>{__("Preview", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Form", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Status", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Date", "animation-addons-for-elementor")}</TableHead>
            <TableHead className="w-16" />
          </TableRow>
        </TableHeader>
        <TableBody>
          {loading && <LoadingRows cols={7} />}
          {!loading && !data.rows.length && (
            <TableRow className="hover:bg-transparent">
              <TableCell colSpan={7} className="py-12">
                <div className="flex flex-col items-center gap-2 text-center">
                  <span className="flex h-12 w-12 items-center justify-center rounded-full bg-background-secondary text-text-secondary">
                    <Inbox className="h-6 w-6" />
                  </span>
                  <p className="text-sm text-text-secondary">
                    {hasFilters
                      ? __("No submissions match these filters.", "animation-addons-for-elementor")
                      : __("No leads yet — submissions appear here the moment a form is submitted.", "animation-addons-for-elementor")}
                  </p>
                  {hasFilters && (
                    <button
                      type="button"
                      className="text-xs text-brand underline underline-offset-2"
                      onClick={clearFilters}
                    >
                      {__("Clear filters", "animation-addons-for-elementor")}
                    </button>
                  )}
                </div>
              </TableCell>
            </TableRow>
          )}
          {!loading &&
            data.rows.map((row) => (
              <TableRow
                key={row.id}
                data-selected={selected.includes(row.id)}
                className="group cursor-pointer transition-colors hover:bg-background-secondary data-[selected=true]:bg-brand/[0.06]"
                onClick={() => setDetailId(row.id)}
              >
                <TableCell onClick={(e) => e.stopPropagation()}>
                  <input
                    type="checkbox"
                    className="cursor-pointer accent-brand"
                    checked={selected.includes(row.id)}
                    onChange={() => toggle(row.id)}
                  />
                </TableCell>
                <TableCell className="font-medium text-text-secondary">#{row.id}</TableCell>
                <TableCell className="max-w-[320px] truncate font-medium group-hover:text-brand">
                  {row.preview || "—"}
                </TableCell>
                <TableCell>
                  <code className="rounded bg-background-secondary px-1.5 py-0.5 text-xs text-text-secondary">
                    {row.form_key}
                  </code>
                </TableCell>
                <TableCell>
                  <StatusPill status={row.status} />
                </TableCell>
                <TableCell className="whitespace-nowrap text-text-secondary">
                  {row.created_at}
                </TableCell>
                <TableCell onClick={(e) => e.stopPropagation()}>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="opacity-0 transition-opacity group-hover:opacity-100 hover:text-brand"
                    onClick={() => setDetailId(row.id)}
                  >
                    <Eye className="h-4 w-4" />
                  </Button>
                </TableCell>
              </TableRow>
            ))}
        </TableBody>
      </Table>

      <div className="flex justify-end">
        <Pager total={data.total} page={page} setPage={setPage} />
      </div>

      <DetailSheet id={detailId} onClose={() => setDetailId(null)} />
    </div>
  );
};

/* ------------------------------------------------------------------ */
/* Single submission drawer                                            */
/* ------------------------------------------------------------------ */

const DetailSheet = ({ id, onClose }) => {
  const [detail, setDetail] = useState(null);

  useEffect(() => {
    if (!id) {
      setDetail(null);
      return;
    }
    api(`submissions/${id}`)
      .then(setDetail)
      .catch((error) => toast.error(error.message));
  }, [id]);

  const meta = detail?.submission || {};

  return (
    <Sheet open={!!id} onOpenChange={(open) => !open && onClose()}>
      <SheetContent className="w-[480px] sm:max-w-[480px] overflow-y-auto">
        <SheetHeader>
          <SheetTitle>
            {__("Submission", "animation-addons-for-elementor")} #{id}
          </SheetTitle>
        </SheetHeader>

        {!detail && <Skeleton className="h-40 w-full mt-6" />}

        {detail && (
          <div className="mt-6 space-y-6 text-sm">
            <div className="space-y-2">
              {detail.values.map((value) => (
                <div key={value.key} className="grid grid-cols-3 gap-2 border-b pb-2">
                  <span className="font-medium text-muted-foreground">{value.label}</span>
                  <span className="col-span-2 whitespace-pre-wrap break-words">
                    {value.files?.length ? (
                      <span className="flex flex-col gap-1">
                        {value.files.map((file) => (
                          <a
                            key={file.id}
                            href={file.url}
                            className="text-brand underline underline-offset-2 break-all"
                          >
                            {file.name}
                            {file.size ? ` (${formatBytes(file.size)})` : ""}
                          </a>
                        ))}
                      </span>
                    ) : (
                      value.value || "—"
                    )}
                  </span>
                </div>
              ))}
            </div>

            <div>
              <h4 className="font-semibold mb-2">
                {__("Metadata", "animation-addons-for-elementor")}
              </h4>
              <div className="space-y-1 text-muted-foreground">
                <p>{__("Form", "animation-addons-for-elementor")}: <code>{meta.form_key}</code> (v{meta.schema_version})</p>
                <p>{__("Date", "animation-addons-for-elementor")}: {meta.created_at}</p>
                {meta.source_url && (
                  <p className="break-all">{__("Source", "animation-addons-for-elementor")}: {meta.source_url}</p>
                )}
                {meta.referrer_url && (
                  <p className="break-all">{__("Referrer", "animation-addons-for-elementor")}: {meta.referrer_url}</p>
                )}
                {meta.utm_json && <p className="break-all">UTM: {meta.utm_json}</p>}
                {meta.user_agent && <p className="break-all">UA: {meta.user_agent}</p>}
              </div>
            </div>

            <div>
              <h4 className="font-semibold mb-2">
                {__("Action Logs", "animation-addons-for-elementor")}
              </h4>
              {!detail.logs.length && (
                <p className="text-muted-foreground">
                  {__("No actions ran for this submission.", "animation-addons-for-elementor")}
                </p>
              )}
              <div className="space-y-2">
                {detail.logs.map((log, index) => (
                  <div key={index} className="border rounded-md p-2">
                    <div className="flex items-center justify-between">
                      <span className="font-medium">{log.action_type}</span>
                      <StatusPill status={log.status} />
                    </div>
                    <p className="text-muted-foreground mt-1">{log.message}</p>
                    <p className="text-xs text-muted-foreground mt-1">{log.created_at}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}
      </SheetContent>
    </Sheet>
  );
};

/* ------------------------------------------------------------------ */
/* Spam log tab                                                        */
/* ------------------------------------------------------------------ */

const SpamTab = () => {
  const [page, setPage] = useState(1);
  const [data, setData] = useState({ rows: [], total: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    api(`spam-log?page=${page}`)
      .then(setData)
      .catch((error) => toast.error(error.message))
      .finally(() => setLoading(false));
  }, [page]);

  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        {__(
          "Every blocked submit attempt, with the real reason. Blocked attempts never create submissions.",
          "animation-addons-for-elementor"
        )}
      </p>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{__("Date", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Reason", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Form", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Visitor hash", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("User agent", "animation-addons-for-elementor")}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {loading && <LoadingRows cols={5} />}
          {!loading && !data.rows.length && (
            <TableRow>
              <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                {__("No blocked attempts logged.", "animation-addons-for-elementor")}
              </TableCell>
            </TableRow>
          )}
          {!loading &&
            data.rows.map((row) => (
              <TableRow key={row.id}>
                <TableCell className="whitespace-nowrap">{row.created_at}</TableCell>
                <TableCell>
                  <span className="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20">
                    {row.reason}
                  </span>
                </TableCell>
                <TableCell>
                  <code className="text-xs">{row.form_key}</code>
                </TableCell>
                <TableCell className="text-muted-foreground">{row.ip_hash}…</TableCell>
                <TableCell className="text-muted-foreground max-w-[280px] truncate">
                  {row.user_agent}
                </TableCell>
              </TableRow>
            ))}
        </TableBody>
      </Table>
      <div className="flex justify-end">
        <Pager total={data.total} page={page} setPage={setPage} />
      </div>
    </div>
  );
};

/* ------------------------------------------------------------------ */
/* Action jobs tab                                                     */
/* ------------------------------------------------------------------ */

const JobsTab = () => {
  const [page, setPage] = useState(1);
  const [data, setData] = useState({ rows: [], total: 0 });
  const [loading, setLoading] = useState(true);
  const [retrying, setRetrying] = useState(0);
  // Id of the row that just changed via retry — flashes green so the user
  // can SEE which row updated (a corner toast alone is easy to miss).
  const [flashId, setFlashId] = useState(0);

  const load = useCallback(() => {
    setLoading(true);
    return api(`jobs?page=${page}`)
      .then(setData)
      .catch((error) => toast.error(error.message))
      .finally(() => setLoading(false));
  }, [page]);

  useEffect(() => {
    load();
  }, [load]);

  const retry = async (id) => {
    setRetrying(id);
    setFlashId(0);
    try {
      const result = await api(`jobs/${id}/retry`, { method: "POST" });
      toast.success(result.message);
      await load();
      // Highlight the just-retried row for a moment after the reload.
      setFlashId(id);
      setTimeout(() => setFlashId(0), 2500);
    } catch (error) {
      toast.error(error.message);
    } finally {
      setRetrying(0);
    }
  };

  const failedCount = data.rows.filter((r) => r.status === "failed").length;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-text-secondary">
          {failedCount > 0
            ? sprintf(
                /* translators: %d: number of failed jobs */
                __(
                  "%d failed job(s) can be retried. A retried job re-runs immediately.",
                  "animation-addons-for-elementor"
                ),
                failedCount
              )
            : __(
                "Delivery jobs for email, webhook and other actions.",
                "animation-addons-for-elementor"
              )}
        </p>
        <Button
          variant="outline"
          size="sm"
          disabled={loading}
          onClick={load}
          className="group"
        >
          <RefreshCw
            className={`w-4 h-4 mr-1 transition-transform group-hover:rotate-180 ${
              loading ? "animate-spin" : ""
            }`}
          />
          {__("Refresh", "animation-addons-for-elementor")}
        </Button>
      </div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{__("Job", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Submission", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Action", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Status", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Attempts", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Next run", "animation-addons-for-elementor")}</TableHead>
            <TableHead>{__("Updated", "animation-addons-for-elementor")}</TableHead>
            <TableHead className="w-24" />
          </TableRow>
        </TableHeader>
        <TableBody>
          {loading && <LoadingRows cols={8} />}
          {!loading && !data.rows.length && (
            <TableRow>
              <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                {__("No action jobs yet.", "animation-addons-for-elementor")}
              </TableCell>
            </TableRow>
          )}
          {!loading &&
            data.rows.map((row) => (
              <TableRow
                key={row.id}
                className={
                  flashId === row.id
                    ? "bg-emerald-50 transition-colors duration-500"
                    : "transition-colors duration-500"
                }
              >
                <TableCell>#{row.id}</TableCell>
                <TableCell>{row.submission_id > 0 ? `#${row.submission_id}` : "—"}</TableCell>
                <TableCell>{row.action_type}</TableCell>
                <TableCell>
                  <StatusPill status={row.status} />
                </TableCell>
                <TableCell>{row.attempts}</TableCell>
                <TableCell className="whitespace-nowrap">{row.next_run_at || "—"}</TableCell>
                <TableCell className="whitespace-nowrap">{row.updated_at}</TableCell>
                <TableCell>
                  {row.status === "failed" && (
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={retrying === row.id}
                      onClick={() => retry(row.id)}
                    >
                      {retrying === row.id ? (
                        <Loader2 className="w-4 h-4 animate-spin" />
                      ) : (
                        <RotateCw className="w-4 h-4 mr-1" />
                      )}
                      {__("Retry", "animation-addons-for-elementor")}
                    </Button>
                  )}
                </TableCell>
              </TableRow>
            ))}
        </TableBody>
      </Table>
      <div className="flex justify-end">
        <Pager total={data.total} page={page} setPage={setPage} />
      </div>
    </div>
  );
};

/* ------------------------------------------------------------------ */
/* Form health tab                                                     */
/* ------------------------------------------------------------------ */

const ISSUE_LABELS = {
  no_active_schema: "No active schema",
  page_missing: "Page missing/trashed",
  failed_jobs: "Failed jobs",
};

const HealthTab = ({ onViewSubmissions }) => {
  const [forms, setForms] = useState(null);
  const [server, setServer] = useState(null);

  useEffect(() => {
    api("health")
      .then((data) => {
        setForms(data.forms);
        setServer(data.server || null);
      })
      .catch((error) => toast.error(error.message));
  }, []);

  return (
    <>
    {server?.uploads_protection === "nginx_config_needed" && (
      <div className="mb-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
        <p className="font-medium">
          {__("Nginx detected — protect the uploads folder", "animation-addons-for-elementor")}
        </p>
        <p className="mt-1">
          {__(
            "Form file uploads live under wp-content/uploads/aae-forms/. The bundled .htaccess blocks direct downloads on Apache, but nginx ignores .htaccess — add this to your server config (then reload nginx):",
            "animation-addons-for-elementor"
          )}
        </p>
        <pre className="mt-2 overflow-x-auto rounded bg-amber-100 p-2 text-xs dark:bg-amber-900">
          {server.nginx_snippet}
        </pre>
      </div>
    )}
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>{__("Form", "animation-addons-for-elementor")}</TableHead>
          <TableHead>{__("Page", "animation-addons-for-elementor")}</TableHead>
          <TableHead>{__("Schema", "animation-addons-for-elementor")}</TableHead>
          <TableHead>{__("Submissions", "animation-addons-for-elementor")}</TableHead>
          <TableHead>{__("Last submission", "animation-addons-for-elementor")}</TableHead>
          <TableHead>{__("Health", "animation-addons-for-elementor")}</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {!forms && <LoadingRows cols={6} />}
        {forms && !forms.length && (
          <TableRow>
            <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
              {__(
                "No forms saved yet. Save a page containing an AAE Form in the editor.",
                "animation-addons-for-elementor"
              )}
            </TableCell>
          </TableRow>
        )}
        {forms &&
          forms.map((form) => (
            <TableRow key={form.form_key}>
              <TableCell>
                <code className="text-xs">{form.form_key}</code>
              </TableCell>
              <TableCell>
                {form.edit_url ? (
                  <a
                    href={form.edit_url}
                    className="underline underline-offset-2"
                    target="_blank"
                    rel="noreferrer"
                  >
                    {form.post_title}
                  </a>
                ) : (
                  "—"
                )}
              </TableCell>
              <TableCell>{form.schema_version ? `v${form.schema_version}` : "—"}</TableCell>
              <TableCell>
                {form.submissions > 0 ? (
                  <button
                    type="button"
                    className="underline underline-offset-2 hover:text-foreground"
                    title={__("View this form's submissions", "animation-addons-for-elementor")}
                    onClick={() => onViewSubmissions(form.form_key)}
                  >
                    {form.submissions}
                  </button>
                ) : (
                  form.submissions
                )}
              </TableCell>
              <TableCell className="whitespace-nowrap">{form.last_at || "—"}</TableCell>
              <TableCell>
                {form.issues.length ? (
                  <div className="flex flex-wrap gap-1">
                    {form.issues.map((issue) => (
                      <span
                        key={issue}
                        className="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20"
                      >
                        {ISSUE_LABELS[issue] || issue}
                      </span>
                    ))}
                  </div>
                ) : (
                  <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20">
                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                    {__("Healthy", "animation-addons-for-elementor")}
                  </span>
                )}
              </TableCell>
            </TableRow>
          ))}
      </TableBody>
    </Table>
    </>
  );
};

/* ------------------------------------------------------------------ */
/* Integrations tab — email-marketing connections (Brevo, …)           */
/* ------------------------------------------------------------------ */

/**
 * One connect card per catalog provider. The API key is a GLOBAL setting
 * (one per provider, site-wide) — NOT per form; per-form list + mapping
 * live in the editor's form Actions dialog. Free ships this UI + key save;
 * the real validate/list/sync is Pro, so a provider with `pro:false` shows
 * a "Requires Pro" badge and saving reports the key unverified.
 */
const IntegrationCard = ({ item, onChanged }) => {
  const [value, setValue] = useState("");
  const [saving, setSaving] = useState(false);
  const [editing, setEditing] = useState(!item.connected);

  const connected = item.connected;

  const save = async (apiKey) => {
    setSaving(true);
    try {
      const result = await api(`integrations/${item.id}/key`, {
        method: "POST",
        body: JSON.stringify({ api_key: apiKey }),
      });
      toast.success(result.message);
      setValue("");
      setEditing(false);
      onChanged();
    } catch (error) {
      toast.error(error.message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="rounded-xl border bg-background p-5 transition-shadow hover:shadow-widget-card">
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#FFA184] to-[#F2754F] text-white shadow-[0_2px_8px_rgba(246,80,44,0.35)]">
            <Plug className="h-5 w-5" />
          </span>
          <div>
            <div className="flex items-center gap-2">
              <h3 className="text-base font-semibold">{item.label}</h3>
              {!item.pro && (
                <span className="inline-flex items-center rounded-full bg-[linear-gradient(180deg,#FFA184_0%,#F2754F_100%)] px-2 py-0.5 text-[11px] font-semibold text-white">
                  {__("PRO", "animation-addons-for-elementor")}
                </span>
              )}
            </div>
            <p className="mt-0.5 text-sm text-text-secondary">
              {connected ? (
                <span className="inline-flex items-center gap-1 text-emerald-600">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                  {item.pro
                    ? __("Connected", "animation-addons-for-elementor")
                    : __("Key saved — verify with Pro", "animation-addons-for-elementor")}
                </span>
              ) : (
                __("Not connected", "animation-addons-for-elementor")
              )}
            </p>
          </div>
        </div>
      </div>

      {!item.pro && (
        <p className="mt-3 rounded-lg bg-background-secondary px-3 py-2 text-xs text-text-secondary">
          {__(
            "Add the Pro add-on to verify the key, pick a list and sync subscribers automatically.",
            "animation-addons-for-elementor"
          )}
        </p>
      )}

      <div className="mt-4">
        {connected && !editing ? (
          <div className="flex items-center gap-2">
            <code className="flex-1 rounded-lg border bg-background-secondary px-3 py-2 text-sm tracking-wide text-text-secondary">
              {item.key_mask}
            </code>
            <Button variant="outline" size="sm" onClick={() => setEditing(true)}>
              {__("Change", "animation-addons-for-elementor")}
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={saving}
              className="text-red-600 hover:border-red-300 hover:text-red-700"
              onClick={() => save("")}
            >
              {__("Disconnect", "animation-addons-for-elementor")}
            </Button>
          </div>
        ) : (
          <div className="flex items-center gap-2">
            <Input
              type="password"
              autoComplete="off"
              placeholder={sprintf(
                /* translators: %s: provider name */
                __("Paste your %s API key", "animation-addons-for-elementor"),
                item.label
              )}
              value={value}
              onChange={(e) => setValue(e.target.value)}
              className="flex-1"
            />
            <Button
              size="sm"
              disabled={saving || !value.trim()}
              className="bg-brand text-white hover:bg-brand-secondary"
              onClick={() => save(value.trim())}
            >
              {saving ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                __("Connect", "animation-addons-for-elementor")
              )}
            </Button>
            {connected && (
              <Button variant="ghost" size="sm" onClick={() => setEditing(false)}>
                {__("Cancel", "animation-addons-for-elementor")}
              </Button>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

const IntegrationsTab = () => {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    api("integrations")
      .then((data) => setItems(data.integrations || []))
      .catch((error) => toast.error(error.message))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div className="space-y-4">
      <p className="text-sm text-text-secondary">
        {__(
          "Connect an email-marketing service once here; pick the list and map fields per form in the form's Actions dialog. Changing a key updates every form instantly.",
          "animation-addons-for-elementor"
        )}
      </p>
      {loading ? (
        <div className="grid gap-3 md:grid-cols-2">
          <Skeleton className="h-40 w-full rounded-xl" />
          <Skeleton className="h-40 w-full rounded-xl" />
        </div>
      ) : (
        <div className="grid gap-3 md:grid-cols-2">
          {items.map((item) => (
            <IntegrationCard key={item.id} item={item} onChanged={load} />
          ))}
        </div>
      )}
    </div>
  );
};

/* ------------------------------------------------------------------ */
/* Page                                                                */
/* ------------------------------------------------------------------ */

const Submissions = () => {
  const [tab, setTab] = useState("submissions");
  const [presetFormKey, setPresetFormKey] = useState("");

  // Form Health → click a submissions count → that form's leads.
  const viewSubmissions = (formKey) => {
    setPresetFormKey(formKey);
    setTab("submissions");
  };

  const tabClass =
    "relative transition-colors data-[state=active]:text-brand data-[state=active]:shadow-tab-trigger data-[state=inactive]:text-text-secondary data-[state=inactive]:hover:text-text";

  return (
    <div className="min-h-screen px-8 py-6 border rounded-2xl bg-background">
      <div className="pb-6 border-b flex items-center justify-between">
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#FFA184] to-[#F2754F] text-white shadow-[0_2px_8px_rgba(246,80,44,0.35)]">
            <Inbox className="h-5 w-5" />
          </span>
          <div>
            <h2 className="text-2xl font-semibold">
              {__("Form Submissions", "animation-addons-for-elementor")}
            </h2>
            <p className="text-sm text-text-secondary mt-0.5">
              {__(
                "Leads collected by AAE Forms — with spam log, action queue and per-form health.",
                "animation-addons-for-elementor"
              )}
            </p>
          </div>
        </div>
        <Button
          variant="ghost"
          size="sm"
          className="group hover:text-brand"
          title={__("Reload", "animation-addons-for-elementor")}
          onClick={() => window.location.reload()}
        >
          <RefreshCw className="w-4 h-4 transition-transform duration-500 group-hover:rotate-180" />
        </Button>
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-6">
        <TabsList>
          <TabsTrigger value="submissions" className={tabClass}>
            {__("Submissions", "animation-addons-for-elementor")}
          </TabsTrigger>
          <TabsTrigger value="spam" className={tabClass}>
            {__("Spam Log", "animation-addons-for-elementor")}
          </TabsTrigger>
          <TabsTrigger value="jobs" className={tabClass}>
            {__("Action Jobs", "animation-addons-for-elementor")}
          </TabsTrigger>
          <TabsTrigger value="health" className={tabClass}>
            {__("Form Health", "animation-addons-for-elementor")}
          </TabsTrigger>
          <TabsTrigger value="integrations" className={tabClass}>
            {__("Integrations", "animation-addons-for-elementor")}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="submissions" className="mt-4">
          <SubmissionsTab presetFormKey={presetFormKey} />
        </TabsContent>
        <TabsContent value="spam" className="mt-4">
          <SpamTab />
        </TabsContent>
        <TabsContent value="jobs" className="mt-4">
          <JobsTab />
        </TabsContent>
        <TabsContent value="health" className="mt-4">
          <HealthTab onViewSubmissions={viewSubmissions} />
        </TabsContent>
        <TabsContent value="integrations" className="mt-4">
          <IntegrationsTab />
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default Submissions;
