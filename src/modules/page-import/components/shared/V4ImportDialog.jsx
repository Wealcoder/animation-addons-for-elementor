import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "C/components/ui/dialog";
import { Button } from "C/components/ui/button";
import { Checkbox } from "C/components/ui/checkbox";
import { RadioGroup, RadioGroupItem } from "C/components/ui/radio-group";
import {
  RiLayoutGridLine,
  RiShieldCheckLine,
  RiImageLine,
} from "react-icons/ri";
import { PAGE_MODE_KEEP, PAGE_MODE_MATCH } from "C/lib/atomicImport";

/**
 * Shown ONCE per V4 page import, before Required Features.
 *
 * Twin of the dashboard's V4ImportDialog. Two choices live here, gated
 * differently:
 *
 *  - The design-system MODE — only when the site already holds V4 content
 *    (`inUse`). A page is small enough that the user may genuinely want it to
 *    adopt the site's own design rather than bring its own. The default is
 *    "keep" — the page arrives looking exactly like its preview — because a
 *    page whose classes silently resolve to a different site's values arrives
 *    looking wrong with nothing to explain it. With no V4 content on the site
 *    there is nothing to match, so the picker is not offered. The value rides
 *    the URL as `v4mode` and lands in the importer as `aae_page_mode`.
 *
 *  - The IMAGE choice — on every V4 import. Linked (url-only) images keep
 *    displaying from the demo host unless the user asks for them to be
 *    copied into the site's own library (inc/admin/atomic-image-localize.php).
 *    Rides the URL as `v4images`. Off by default.
 *
 * @param {boolean}  open              Dialog visibility.
 * @param {Function} setOpen           Visibility setter.
 * @param {Function} onConfirm         Called with the chosen mode if the user goes ahead.
 * @param {string}   title             The page's title, so the copy names it.
 * @param {boolean}  inUse             The site already holds V4 content.
 * @param {string}   mode              Current mode value.
 * @param {Function} setMode           Mode setter.
 * @param {boolean}  localizeImages    Current image choice.
 * @param {Function} setLocalizeImages Image choice setter.
 */
const V4ImportDialog = ({
  open,
  setOpen,
  onConfirm,
  title = "",
  inUse = false,
  mode = PAGE_MODE_KEEP,
  setMode,
  localizeImages = false,
  setLocalizeImages = () => {},
}) => {
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent
        className="w-[480px] bg-background rounded-2xl overflow-hidden shadow-auth-card"
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
                    ? "This site already uses Elementor V4"
                    : "Import an Elementor V4 page"}
                </DialogTitle>
                <DialogDescription className="text-sm text-label text-start">
                  {inUse
                    ? title
                      ? `Choose how the design of “${title}” should meet what is already here.`
                      : "Choose how this page's design should meet what is already here."
                    : title
                      ? `“${title}” is built with Elementor V4 (atomic). The page and the global classes and variables it uses will be imported.`
                      : "This page is built with Elementor V4 (atomic). The page and the global classes and variables it uses will be imported."}
                </DialogDescription>
              </div>
            </div>
          </DialogHeader>

          {inUse && (
            <RadioGroup
              value={mode}
              onValueChange={setMode}
              className="mt-5 gap-2.5"
              data-aae-v4-page-mode
            >
              <label
                htmlFor="aae-v4-mode-keep"
                className="flex items-start gap-3 rounded-xl border border-[#D6D6FF] bg-[#EAEAFF]/60 p-4 cursor-pointer has-[[data-state=checked]]:border-[#5453FD]"
              >
                <RadioGroupItem
                  value={PAGE_MODE_KEEP}
                  id="aae-v4-mode-keep"
                  className="mt-0.5"
                />
                <span className="flex flex-col gap-1">
                  {/* The wrapping <label> already owns the click; a nested
                      <label> would be invalid HTML and doubles the target. */}
                  <span className="text-sm font-medium leading-none">
                    Keep the page's original design (recommended)
                  </span>
                  <span className="text-xs text-text-secondary">
                    Its classes and variables are added as new entries. A name
                    already taken here is imported with a “DUP_” prefix, so
                    both keep their styling.
                  </span>
                </span>
              </label>
              <label
                htmlFor="aae-v4-mode-match"
                className="flex items-start gap-3 rounded-xl border border-border p-4 cursor-pointer has-[[data-state=checked]]:border-[#5453FD] has-[[data-state=checked]]:bg-[#EAEAFF]/60"
              >
                <RadioGroupItem
                  value={PAGE_MODE_MATCH}
                  id="aae-v4-mode-match"
                  className="mt-0.5"
                />
                <span className="flex flex-col gap-1">
                  <span className="text-sm font-medium leading-none">
                    Match my site's design
                  </span>
                  <span className="text-xs text-text-secondary">
                    Where a class or variable with the same name already
                    exists here, the page uses yours instead of its own.
                  </span>
                </span>
              </label>
            </RadioGroup>
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
                Copy the images into my Media Library
              </span>
              <span className="text-xs text-text-secondary">
                The page's images are linked from the demo server and display
                from there. Tick this to download them into your own library,
                so you can edit them and the page does not depend on another
                host. The import takes longer.
              </span>
            </span>
          </label>

          <p className="mt-4 flex items-start gap-2 text-xs text-text-secondary">
            <span className="flex shrink-0 mt-0.5">
              <RiShieldCheckLine size={14} />
            </span>
            Either way, your existing classes, variables and pages are not
            changed.
          </p>
        </div>

        <div className="flex justify-end gap-2 px-6 py-4 bg-background-secondary border-t">
          <Button
            variant="secondary"
            onClick={() => setOpen(false)}
            data-aae-v4-import-cancel
          >
            Cancel
          </Button>
          <Button
            data-aae-v4-import-confirm
            onClick={() => {
              setOpen(false);
              onConfirm(mode);
            }}
          >
            Continue import
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default V4ImportDialog;
