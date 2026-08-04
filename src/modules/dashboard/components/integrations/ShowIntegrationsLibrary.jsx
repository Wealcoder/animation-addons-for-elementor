import { __ } from "@wordpress/i18n";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion2";
import { Switch } from "@/components/ui/switch";
import { Dot, Settings } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { useState } from "react";
import { toast } from "sonner";
import WidgetCard from "../shared/WidgetCard";
import { deviceMediaMatch } from "@/lib/utils";
import { useLibrary } from "@/hooks/app.hooks";
import { Label } from "../ui/label";
import { Button } from "../ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "../ui/dialog";
import ConditionsField from "../animation-settings/ConditionsField";

/** The visual meaning of "no rules saved": load on every page. */
const DEFAULT_RULES = [{ action: "include", location: "entire" }];

/**
 * @param {boolean} embedded Rendered as an Animation Settings tab, which has
 *                           no IntegrationTopBar — so this mode carries its
 *                           own Save button and drops the section heading the
 *                           tab strip already provides.
 */
const ShowIntegrationsLibrary = ({ embedded = false }) => {
  const {
    allLibrary,
    updateLibrary,
    updateActiveGroupLibrary,
    updateLibraryConditions,
  } = useLibrary();

  // Groups start EXPANDED — the cards (and their gears) are the screen's whole
  // point, and a collapsed row reads as "nothing here". Lazy initializer: the
  // config ships with the page, so the keys exist on first render.
  const [openAccordion, setOpenAccordion] = useState(() =>
    Object.keys(allLibrary?.elements || {})
  );
  const [saving, setSaving] = useState(false);

  // The gear dialog. `slug` doubles as the open flag; `rules` is a draft the
  // dialog owns until Apply — Cancel must never leave half-edited rules in
  // the store.
  const [editing, setEditing] = useState(null); // { group, slug, rules } | null

  /*
   * The location vocabulary ships from Pro's config filter, freshly resolved
   * on every load (a saved blob would freeze the CPT list). Absent means the
   * Pro side is too old to evaluate rules — then no gear renders at all: a
   * rule editor whose rules nothing enforces is worse than none.
   */
  const locations = allLibrary?.locations;

  /**
   * Persist a specific blob — passed in, never read from the hook, because
   * the caller usually just dispatched it and state is still the stale copy.
   * Same endpoint the Integrations top bar has always used.
   */
  const saveLibrary = async (blob) => {
    if (!WCF_ADDONS_ADMIN?.nonce || !WCF_ADDONS_ADMIN?.ajaxurl) return;

    setSaving(true);

    try {
      const response = await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        credentials: "same-origin",
        body: new URLSearchParams({
          action: "save_settings_dashboard_library_ajax",
          fields: JSON.stringify(blob),
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      });

      await response.json();

      toast.success(__("Settings saved", "animation-addons-for-elementor"), {
        position: "top-right",
      });
    } catch (error) {
      toast.error(__("Could not save settings", "animation-addons-for-elementor"), {
        position: "top-right",
        description: String(error?.message || error),
      });
    } finally {
      setSaving(false);
    }
  };

  const openConditions = (group, slug) => {
    const saved = allLibrary?.elements?.[group]?.elements?.[slug]?.conditions;

    setEditing({
      group,
      slug,
      rules:
        Array.isArray(saved) && saved.length
          ? JSON.parse(JSON.stringify(saved))
          : JSON.parse(JSON.stringify(DEFAULT_RULES)),
    });
  };

  /*
   * Apply writes the rules into the store AND saves in the same gesture. A
   * dialog whose Apply only touches local state is a trap: the change looks
   * committed, then silently vanishes with the tab unless a separate Save is
   * remembered.
   */
  const applyConditions = async () => {
    const next = updateLibraryConditions({
      slug: editing.slug,
      conditions: editing.rules,
    });

    setEditing(null);
    await saveLibrary(next);
  };

  return (
    <div className="bg-background-secondary p-2.5 rounded-lg">
      <div>
        <div className="bg-background flex flex-wrap items-center justify-between gap-3 p-5 rounded">
          {!embedded && <h3 className="text-base font-medium">{allLibrary?.title}</h3>}
          {embedded && (
            <>
              <p className="text-sm text-label">
                {__(
                  "Libraries load on every page by default — use the gear on a card to limit where.",
                  "animation-addons-for-elementor"
                )}
              </p>
              <Button disabled={saving} onClick={() => saveLibrary(allLibrary)}>
                {__("Save Settings", "animation-addons-for-elementor")}
              </Button>
            </>
          )}
        </div>

        <Accordion
          type="multiple"
          value={openAccordion}
          onValueChange={(value) => setOpenAccordion(value)}
          className="w-full mt-2 space-y-1.5"
        >
          {Object.keys(allLibrary?.elements)?.map((library) => (
            <AccordionItem key={library} value={library}>
              <div className="p-[2px]">
                <div className="flex items-center bg-background justify-between gap-3 py-3 px-4">
                  <AccordionTrigger className="rounded cursor-pointer w-full">
                    <div className="flex flex-col gap-1">
                      <div className="text-[15px] leading-6 font-medium flex items-center">
                        {allLibrary?.elements[library].title}
                        {allLibrary?.elements[library]?.is_pro ? (
                          <>
                            <Dot
                              className="w-3.5 h-3.5 text-icon-secondary"
                              strokeWidth={2}
                            />
                            <Badge variant="pro">{__("PRO", "animation-addons-for-elementor")}</Badge>
                          </>
                        ) : (
                          ""
                        )}
                      </div>
                    </div>
                  </AccordionTrigger>
                  <div className="flex gap-1 items-center">
                    {Object.keys(allLibrary?.elements[library]?.elements)
                      ?.length ? (
                      ""
                    ) : (
                      <>
                        <Badge
                          variant="pro"
                          className="px-2.5 py-1.5 h-7 bg-[linear-gradient(180deg,#FFA184_0%,#F2754F_100%)] mr-1"
                        >
                          {__("COMING SOON!", "animation-addons-for-elementor")}
                        </Badge>
                      </>
                    )}

                    <div className="flex items-center gap-x-2">
                      <Switch
                        reverse
                        index={library}
                        checked={allLibrary?.elements[library]?.is_active}
                        onCheckedChange={(value) => {
                          value
                            ? setOpenAccordion((prev) => [...prev, library])
                            : setOpenAccordion((prev) =>
                                prev?.filter((el) => el !== library)
                              );
                          updateActiveGroupLibrary({
                            value,
                            slug: library,
                          });
                        }}
                        disabled={
                          !Object.keys(allLibrary?.elements[library]?.elements)
                            ?.length
                        }
                      />
                      <Label htmlFor={library}>{__("Enable All", "animation-addons-for-elementor")}</Label>
                    </div>
                  </div>
                </div>
              </div>
              <AccordionContent>
                {/*
                  grid-cols-1 below md: at phone widths a 2-up grid leaves each
                  card ~180px and the toggle lands ON the title (reported from
                  a 425px screenshot). The md/xl steps match what the filler
                  logic below assumes closely enough — fillers are cosmetic
                  padding for the last row, never content.
                */}
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-1 mt-1 p-[2px]">
                  {Object.keys(allLibrary?.elements[library]?.elements)
                    ?.length ? (
                    <>
                      {Object.keys(
                        allLibrary?.elements[library]?.elements
                      )?.map((content, i) => {
                        const item =
                          allLibrary?.elements[library]?.elements[content];
                        const hasRules =
                          Array.isArray(item?.conditions) &&
                          item.conditions.length > 0;

                        return (
                          <React.Fragment key={`tab_content-${i}`}>
                            {/*
                              The gear rides OUTSIDE WidgetCard — that card is
                              shared with the Widgets and Extensions screens,
                              and teaching it a library-only control would put
                              a dead prop on every other caller.
                            */}
                            <div className="relative group">
                              <WidgetCard
                                widget={item}
                                slug={content}
                                updateActiveItem={updateLibrary}
                                className="rounded p-5"
                                preview={false}
                              />
                              {locations && (
                                <button
                                  type="button"
                                  /*
                                   * Bottom-right corner, revealed on card
                                   * hover (and on keyboard focus — a control
                                   * only a mouse can find fails keyboard
                                   * users). A card with saved rules keeps its
                                   * orange gear ALWAYS visible: the gear is
                                   * the only indicator rules exist, and an
                                   * indicator that hides defeats itself.
                                   */
                                  /*
                                   * p-1.5 + bottom-0, not p-2 + bottom-2.5:
                                   * the card is ~88px tall with the toggle
                                   * vertically centered, and the larger gear
                                   * overlapped the toggle's bottom edge by
                                   * 7px (measured).
                                   */
                                  className={`absolute bottom-0 right-2 inline-flex items-center justify-center rounded-full p-1.5 transition-[color,background-color,opacity] ${
                                    hasRules
                                      ? "opacity-100"
                                      : "opacity-0 group-hover:opacity-100 focus:opacity-100"
                                  } ${
                                    item?.is_active
                                      ? "cursor-pointer bg-[#FAFBFC] text-[var(--600,#525866)] hover:text-[var(--900,#181B25)] hover:bg-[#F1F3F6]"
                                      : "cursor-not-allowed bg-transparent text-[#C9CFD8]"
                                  }`}
                                  disabled={!item?.is_active}
                                  aria-label={`${__(
                                    "Display conditions",
                                    "animation-addons-for-elementor"
                                  )} — ${item?.label || content}`}
                                  title={
                                    item?.is_active
                                      ? __(
                                          "Where this library loads",
                                          "animation-addons-for-elementor"
                                        )
                                      : __(
                                          "Enable the library first",
                                          "animation-addons-for-elementor"
                                        )
                                  }
                                  data-aae-lib-gear={content}
                                  onClick={() => openConditions(library, content)}
                                >
                                  <Settings
                                    className={`w-4 h-4 ${
                                      hasRules ? "text-[#FC6848]" : ""
                                    }`}
                                    aria-hidden="true"
                                  />
                                </button>
                              )}
                            </div>
                          </React.Fragment>
                        );
                      })}
                      {Array.from({
                        length:
                          deviceMediaMatch() -
                          (Object.keys(allLibrary?.elements[library]?.elements)
                            ?.length %
                            deviceMediaMatch() ===
                          0
                            ? deviceMediaMatch()
                            : Object.keys(
                                allLibrary?.elements[library]?.elements
                              )?.length % deviceMediaMatch()),
                      }).map((_, index) => (
                        <WidgetCard
                          key={`tab_content_empty-${index}`}
                          className="rounded"
                        />
                      ))}
                    </>
                  ) : (
                    <div className="col-span-3 px-4 py-[15px] bg-background rounded-lg  box-border">
                      <p className="text-center">{__("Coming soon...", "animation-addons-for-elementor")}</p>
                    </div>
                  )}
                </div>
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>

      {/*
        The conditions dialog. Draft rules live here, not in the store —
        Cancel (or Escape, or the overlay) discards them by dropping the
        whole `editing` object, so nothing half-edited can leak into a save.
      */}
      <Dialog open={!!editing} onOpenChange={(open) => !open && setEditing(null)}>
        {/*
          DialogContent ships bg-transparent and every caller paints its own
          panel — copy ExtensionMissingDialog's recipe or the dialog renders
          as floating controls over the page (it did).
        */}
        <DialogContent
          className="w-[560px] max-w-[90vw] bg-background p-6 gap-3 !rounded-2xl [&>.wcf-dialog-close-button]:right-4 [&>.wcf-dialog-close-button]:top-4"
          closeBtnIconCls="text-[#525866]"
        >
          <DialogHeader>
            <DialogTitle>
              {__("Display Conditions", "animation-addons-for-elementor")}
              {editing ? ` — ${editing.slug}` : ""}
            </DialogTitle>
          </DialogHeader>

          {editing && (
            <>
              <p className="text-sm text-label">
                {__(
                  "By default this library loads on every page. Add rules to limit where — Exclude always wins over Include.",
                  "animation-addons-for-elementor"
                )}
              </p>

              <ConditionsField
                field={{
                  label: "",
                  help: "",
                  locations: locations || {},
                }}
                feature="library"
                name={editing.slug}
                value={editing.rules}
                disabled={saving}
                onChange={(rules) => setEditing((prev) => ({ ...prev, rules }))}
              />
            </>
          )}

          <DialogFooter>
            <Button variant="secondary" onClick={() => setEditing(null)}>
              {__("Cancel", "animation-addons-for-elementor")}
            </Button>
            <Button disabled={saving} onClick={applyConditions}>
              {__("Apply", "animation-addons-for-elementor")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default ShowIntegrationsLibrary;
