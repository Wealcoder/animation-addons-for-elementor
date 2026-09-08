import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "../ui/button";
import { Checkbox } from "../ui/checkbox";
import {
  RiLayoutGridLine,
  RiShieldCheckLine,
  RiImageLine,
} from "react-icons/ri";
import { __, sprintf } from "@wordpress/i18n";

/**
 * Shown ONCE per V4 template import, before Required Features.
 *
 * Two things live here, and they are gated differently:
 *
 *  1. The "already uses V4" explanation — only when the site holds Elementor
 *     V4 content (`inUse`). It is not a warning about damage: a second V4
 *     import cannot break the first, because the importer runs the design
 *     system through Elementor's template-library chain in `keep_create`
 *     mode, which creates every class and variable as a NEW entry and
 *     renames a label clash with a `DUP_` prefix (inc/admin/atomic-kit-import.php).
 *     What the user needs to know is the consequence — two demos' classes
 *     coexist rather than merge — and that nothing they built is touched.
 *     Hence the calm indigo (the V4 badge's hue), never DisableAllV3Dialog's
 *     red. No mode picker: for a full template the importer decides the mode
 *     from the same signal, and offering a choice the server then overrides
 *     is a control that lies.
 *
 *  2. The image choice — on EVERY V4 import. A V4 demo's images are usually
 *     linked, not picked from a media library, so they arrive as URLs
 *     pointing at the demo host and keep rendering from there. Ticking this
 *     copies them into the site's own library during the import
 *     (inc/admin/atomic-image-localize.php). Off by default: the import is
 *     then exactly what it was, and faster.
 *
 * @param {boolean}  open              Dialog visibility.
 * @param {Function} setOpen           Visibility setter.
 * @param {Function} onConfirm         Runs only if the user goes ahead.
 * @param {string}   title             The template's title, so the copy names it.
 * @param {boolean}  inUse             The site already holds V4 content.
 * @param {boolean}  localizeImages    Current image choice.
 * @param {Function} setLocalizeImages Image choice setter.
 */
const V4ImportDialog = ({
  open,
  setOpen,
  onConfirm,
  title = "",
  inUse = false,
  localizeImages = false,
  setLocalizeImages = () => {},
}) => {
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      {/*
        DialogContent ships `bg-transparent` — a caller that passes no
        background renders as floating controls over the overlay.
      */}
      <DialogContent
        className="w-[460px] bg-background rounded-2xl overflow-hidden shadow-auth-card"
        data-aae-v4-import-dialog
        data-aae-v4-in-use={inUse ? "1" : "0"}
      >
        <div className="p-6">
          <DialogHeader>
            <div className="flex items-start gap-3.5">
              <span className="shrink-0 h-11 w-11 rounded-full bg-[#EAEAFF] border border-[#D6D6FF] flex items-center justify-center text-[#5453FD]">
                <RiLayoutGridLine size={22} />
              </span>
              <div className="flex flex-col gap-1 pt-0.5">
                <DialogTitle className="text-base font-medium text-start leading-snug">
                  {inUse
                    ? __(
                        "This site already uses Elementor V4",
                        "animation-addons-for-elementor"
                      )
                    : __(
                        "Import an Elementor V4 template",
                        "animation-addons-for-elementor"
                      )}
                </DialogTitle>
                <DialogDescription className="text-sm text-label text-start">
                  {inUse
                    ? title
                      ? sprintf(
                          /* translators: %s: the starter template's title. */
                          __(
                            "The design system of “%s” will be added beside what is already here.",
                            "animation-addons-for-elementor"
                          ),
                          title
                        )
                      : __(
                          "This template's design system will be added beside what is already here.",
                          "animation-addons-for-elementor"
                        )
                    : title
                      ? sprintf(
                          /* translators: %s: the starter template's title. */
                          __(
                            "“%s” is built with Elementor V4 (atomic). Its pages, global classes and variables will be imported.",
                            "animation-addons-for-elementor"
                          ),
                          title
                        )
                      : __(
                          "This template is built with Elementor V4 (atomic). Its pages, global classes and variables will be imported.",
                          "animation-addons-for-elementor"
                        )}
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          {inUse && (
            <div className="mt-5 rounded-xl bg-[#EAEAFF]/60 border border-[#D6D6FF] p-4 text-sm text-[#2E2E8F] space-y-2">
              <p>
                {__(
                  "Its global classes and variables are imported as new entries. Your existing classes, variables and pages are not changed.",
                  "animation-addons-for-elementor"
                )}
              </p>
              <p>
                {__(
                  "If a class name is already taken here, the imported copy is renamed with a “DUP_” prefix so both keep their styling.",
                  "animation-addons-for-elementor"
                )}
              </p>
            </div>
          )}

          <label
            htmlFor="aae-v4-localize-images"
            className="mt-5 flex items-start gap-3 rounded-xl border border-border p-4 cursor-pointer has-[[data-state=checked]]:border-[#5453FD] has-[[data-state=checked]]:bg-[#EAEAFF]/60"
            data-aae-v4-localize-images
          >
            <Checkbox
              id="aae-v4-localize-images"
              className="mt-0.5"
              checked={localizeImages}
              onCheckedChange={(v) => setLocalizeImages(v === true)}
            />
            <span className="flex flex-col gap-1">
              <span className="flex items-center gap-1.5 text-sm font-medium leading-none">
                <RiImageLine size={14} className="text-[#5453FD]" />
                {__(
                  "Copy the images into my Media Library",
                  "animation-addons-for-elementor"
                )}
              </span>
              <span className="text-xs text-text-secondary">
                {__(
                  "The template's images are linked from the demo server and display from there. Tick this to download them into your own library, so you can edit them and the site does not depend on another host. The import takes longer.",
                  "animation-addons-for-elementor"
                )}
              </span>
            </span>
          </label>

          <p className="mt-4 flex items-start gap-2 text-xs text-text-secondary">
            <span className="flex shrink-0 mt-0.5">
              <RiShieldCheckLine size={14} />
            </span>
            {inUse
              ? __(
                  "Nothing is replaced. Unused imported classes can be deleted later from Elementor's Class Manager.",
                  "animation-addons-for-elementor"
                )
              : __(
                  "Elementor V3 widgets, extensions and site features are switched off once the template has landed, except widgets your existing pages use.",
                  "animation-addons-for-elementor"
                )}
          </p>
        </div>

        <div className="flex justify-end gap-2 px-6 py-4 bg-background-secondary border-t">
          <Button
            variant="secondary"
            onClick={() => setOpen(false)}
            data-aae-v4-import-cancel
          >
            {__("Cancel", "animation-addons-for-elementor")}
          </Button>
          <Button
            data-aae-v4-import-confirm
            onClick={() => {
              setOpen(false);
              onConfirm();
            }}
          >
            {__("Continue import", "animation-addons-for-elementor")}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default V4ImportDialog;
